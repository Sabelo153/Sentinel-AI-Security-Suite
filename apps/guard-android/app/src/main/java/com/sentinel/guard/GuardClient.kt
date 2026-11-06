package com.sentinel.guard

import android.content.Context
import android.database.sqlite.SQLiteDatabase
import android.database.sqlite.SQLiteOpenHelper
import org.json.JSONObject
import java.net.URI
import java.net.HttpURLConnection
import java.util.UUID
import java.security.MessageDigest

class ApiFailure(val status: Int, message: String) : Exception(message)
data class GuardSession(val base: String, val token: String, val user: String) {
    val account: String get() = MessageDigest.getInstance("SHA-256").digest("$base|$user".toByteArray()).joinToString("") { "%02x".format(it) }
    fun json() = JSONObject().put("base", base).put("token", token).put("user", user).toString()
}
class GuardClient(context: Context) {
    private val appContext = context.applicationContext
    private val secure = SecureStore(context)
    private val queue = object : SQLiteOpenHelper(context, "guard_queue.db", null, 3) {
        private fun patrols(db:SQLiteDatabase){db.execSQL("CREATE TABLE IF NOT EXISTS local_patrols(account TEXT NOT NULL,operation_uuid TEXT PRIMARY KEY,payload TEXT NOT NULL,server_id TEXT,state TEXT NOT NULL)")}
        override fun onCreate(db: SQLiteDatabase) { db.execSQL("CREATE TABLE queue (sequence INTEGER PRIMARY KEY AUTOINCREMENT, account TEXT NOT NULL, uuid TEXT NOT NULL UNIQUE, payload TEXT NOT NULL, blocked INTEGER NOT NULL DEFAULT 0, error_status INTEGER NOT NULL DEFAULT 0)");patrols(db) }
        override fun onUpgrade(db: SQLiteDatabase, oldVersion: Int, newVersion: Int) { if(oldVersion<2)db.execSQL("ALTER TABLE queue ADD COLUMN error_status INTEGER NOT NULL DEFAULT 0");if(oldVersion<3)patrols(db) }
    }
    fun configuredServer(): String? {
        if(BuildConfig.DEBUG&&BuildConfig.SENTINEL_SERVER_URL.isNotBlank()) return ServerConfiguration.origin(BuildConfig.SENTINEL_SERVER_URL,true).also { secure.put("server_origin",it) }
        val saved=secure.get("server_origin")
        if(!saved.isNullOrBlank()) return saved
        if(BuildConfig.SENTINEL_SERVER_URL.isBlank()) return null
        return ServerConfiguration.origin(BuildConfig.SENTINEL_SERVER_URL,BuildConfig.DEBUG).also { secure.put("server_origin",it) }
    }
    fun configureServer(value: String): String = ServerConfiguration.origin(value,BuildConfig.DEBUG).also { secure.put("server_origin",it) }
    fun signupUrl(): String? {
        if(BuildConfig.SENTINEL_SIGNUP_URL.isNotBlank()) return ServerConfiguration.signupUrl("",BuildConfig.SENTINEL_SIGNUP_URL,BuildConfig.DEBUG)
        return configuredServer()?.let { ServerConfiguration.signupUrl(it,"",BuildConfig.DEBUG) }
    }
    fun sponsorPlacement():JSONObject {val s=session()?:throw ApiFailure(401,"Sign in required");return request(s.base,s.token,"/auth/sponsor",readTimeout=3000).getJSONObject("data")}
    fun adminUrl(): String? = configuredServer()?.plus("/admin/index.html")
    fun distributionUrl(): String? = configuredServer()?.plus("/downloads")
    fun session(): GuardSession? = secure.get("session")?.let { val s = JSONObject(it); GuardSession(s.getString("base"), s.getString("token"), s.getString("user")) }
    fun signOut() { secure.remove("session") }
    fun logout():String {val current=session()?:return "Signed out";return try{request(current.base,current.token,"/auth/logout",JSONObject());"Signed out and server session revoked."}catch(_:Exception){"Signed out on this phone. Server revocation could not be confirmed; contact support if the phone is compromised."}finally{signOut()}}
    fun installationId(): String = secure.get("installation_id") ?: UUID.randomUUID().toString().also { secure.put("installation_id", it) }
    fun enrollManagedDevice(code: String) {
        val s = session() ?: throw ApiFailure(401, "Sign in before enrollment")
        val data = request(s.base,s.token,"/guard/managed/enroll",JSONObject().put("installation_id",installationId()).put("enrollment_code",code)).getJSONObject("data")
        secure.put("mdm_connection",JSONObject().put("base",s.base).put("token",data.getString("device_token")).toString())
        refreshManagedPolicy()
    }
    fun refreshManagedPolicy() {
        val connection = secure.get("mdm_connection")?.let(::JSONObject) ?: return
        val controller = ManagedDeviceController(appContext)
        val data = request(connection.getString("base"),"","/managed-device/policy",JSONObject().put("installation_id",installationId()).put("device_token",connection.getString("token")).put("reported_revision",controller.revision()).put("reported_state",controller.state())).getJSONObject("data")
        controller.cache(data)
    }
    fun login(identifier: String, password: String, code: String = ""): JSONObject {
        val origin=configuredServer() ?: throw ApiFailure(503,"This app has not been connected to your organisation yet.")
        val response = try { request(origin, "", "/auth/login", JSONObject().put("identifier", identifier).put("password", password).put("code",code).put("session_label","Android "+installationId().take(8))) }
        catch (error:ApiFailure) {
            if(error.status==401)throw ApiFailure(401,"Check your login and enter a fresh authenticator code if MFA is enabled.")
            throw error
        }
        val token = response.getJSONObject("data").getString("token")
        val workspace = try { request(origin, token, "/guard/workspace").getJSONObject("data") }
        catch (error: ApiFailure) {
            if (error.status == 403) throw ApiFailure(403, appContext.getString(R.string.guard_access_required))
            throw error
        }
        val session = GuardSession(origin, token, workspace.getString("user_id"))
        secure.put("session", session.json()); secure.put("workspace_${session.account}", workspace.toString())
        return workspace
    }
    fun cached(): JSONObject? = session()?.let { s -> secure.get("workspace_${s.account}")?.let(::JSONObject) }
    fun refresh(): JSONObject {
        val s = session() ?: throw ApiFailure(401, "Sign in required")
        val data = request(s.base, s.token, "/guard/workspace").getJSONObject("data")
        require(data.getString("user_id") == s.user) { "Account changed; sign in again" }
        secure.put("workspace_${s.account}", data.toString())
        try { uploadPhoneHealth() } catch(_:Exception) { /* Health never blocks duty synchronization. */ }
        return data
    }
    fun patrolDetails(id:String):JSONObject {
        val current=session()?:throw ApiFailure(401,"Sign in again.")
        return request(current.base,current.token,"/guard/patrols/"+id).getJSONObject("data")
    }
    fun patrolSchedules(before:String?=null):JSONObject {
        val current=session()?:throw ApiFailure(401,"Sign in again.")
        return request(current.base,current.token,"/guard/patrol-schedules"+(before?.let{"?before_id="+it}?:"")).getJSONObject("data")
    }
    fun help(topic: String, question: String): String {
        val s = session() ?: throw ApiFailure(401, "Sign in required")
        return request(s.base, s.token, "/guard/help", JSONObject().put("topic", topic).put("question", question), 130000)
            .getJSONObject("data").getString("answer")
    }
    fun enqueue(action: String, data: JSONObject): String {
        val s = session() ?: throw ApiFailure(401, "Sign in required")
        synchronized(syncLock) {
            require(pending(s) < 500) { "Offline queue is full. Synchronize before recording more events." }
            if(action=="start_patrol"){
                val patrols=patrolsForDisplay(cached()?.optJSONArray("patrols")?:org.json.JSONArray())
                require((0 until patrols.length()).none{patrols.getJSONObject(it).optString("status")=="IN_PROGRESS"}){"Complete the active patrol before starting another."}
            }
            val uuid = UUID.randomUUID().toString()
            val payload = JSONObject().put("operation_uuid", uuid).put("action", action).put("data", data)
            val db=queue.writableDatabase;db.beginTransaction()
            try{
                db.execSQL("INSERT INTO queue (account,uuid,payload) VALUES (?,?,?)", arrayOf(s.account, uuid, secure.encrypt(payload.toString())))
                if(action=="start_patrol")db.execSQL("INSERT INTO local_patrols(account,operation_uuid,payload,state) VALUES(?,?,?,'IN_PROGRESS')",arrayOf(s.account,uuid,secure.encrypt(data.toString())))
                if(action=="finish_patrol"){
                    val id=data.getString("patrol_session_id")
                    if(id.startsWith("local:"))db.execSQL("UPDATE local_patrols SET state='COMPLETED_PENDING' WHERE account=? AND operation_uuid=?",arrayOf(s.account,id.removePrefix("local:")))
                    else {
                        db.execSQL("UPDATE local_patrols SET state='COMPLETED_PENDING' WHERE account=? AND server_id=?",arrayOf(s.account,id))
                        val saved=cached()?.optJSONArray("patrols")?:org.json.JSONArray()
                        val existing=(0 until saved.length()).map{saved.getJSONObject(it)}.firstOrNull{it.optString("patrol_session_id")==id}
                        if(existing!=null)db.execSQL("INSERT OR IGNORE INTO local_patrols(account,operation_uuid,payload,server_id,state) VALUES(?,?,?,?,'COMPLETED_PENDING')",arrayOf(s.account,"remote:$id",secure.encrypt(existing.toString()),id))
                    }
                }
                db.setTransactionSuccessful()
            }finally{db.endTransaction()}
            SyncJob.requestSync(appContext)
            return uuid
        }
    }
    fun pending(s: GuardSession? = session()): Int {
        if (s == null) return 0
        queue.readableDatabase.rawQuery("SELECT COUNT(*) FROM queue WHERE account=?", arrayOf(s.account)).use { it.moveToFirst(); return it.getInt(0) }
    }
    fun patrolsForDisplay(server:org.json.JSONArray):org.json.JSONArray = synchronized(syncLock){
        val s=session()?:return@synchronized server
        val locals=mutableListOf<JSONObject>();val serverIds=mutableSetOf<String>()
        queue.readableDatabase.rawQuery("SELECT operation_uuid,payload,server_id,state FROM local_patrols WHERE account=? ORDER BY rowid DESC LIMIT 100",arrayOf(s.account)).use{c->while(c.moveToNext()){
            val data=JSONObject(secure.decrypt(c.getString(1)));val remote=c.getString(2);val state=c.getString(3)
            if(remote!=null&&state=="SYNCED"&&(0 until server.length()).any{server.getJSONObject(it).optString("patrol_session_id")==remote&&server.getJSONObject(it).optString("status")=="COMPLETED"})continue
            if(remote!=null)serverIds.add(remote)
            locals.add(JSONObject(data.toString()).put("patrol_session_id","local:"+c.getString(0)).put("status",if(state=="SYNCED")"COMPLETED" else state).put("local_only",true).put("server_id",remote).put("ended_at",if(state=="IN_PROGRESS")JSONObject.NULL else "Queued or synchronized"))
        }}
        val result=org.json.JSONArray();locals.forEach{result.put(it)}
        val finishing=mutableSetOf<String>();queue.readableDatabase.rawQuery("SELECT payload FROM queue WHERE account=?",arrayOf(s.account)).use{c->while(c.moveToNext()){val p=JSONObject(secure.decrypt(c.getString(0)));if(p.optString("action")=="finish_patrol")finishing.add(p.getJSONObject("data").getString("patrol_session_id"))}}
        for(i in 0 until server.length()){val p=JSONObject(server.getJSONObject(i).toString());val id=p.optString("patrol_session_id");if(!serverIds.contains(id)){if(finishing.contains(id))p.put("status","COMPLETED_PENDING");result.put(p)}}
        result
    }
    fun offlinePatrolEvents(id:String):List<JSONObject> = synchronized(syncLock){
        val s=session()?:return@synchronized emptyList();val events=mutableListOf<JSONObject>()
        queue.readableDatabase.rawQuery("SELECT payload FROM queue WHERE account=? ORDER BY sequence",arrayOf(s.account)).use{c->while(c.moveToNext()){val p=JSONObject(secure.decrypt(c.getString(0)));val d=p.getJSONObject("data");if(d.optString("patrol_session_id")==id)events.add(JSONObject(d.toString()).put("action",p.getString("action")))}}
        events
    }
    private fun resolvedCommand(payload:JSONObject,s:GuardSession):JSONObject{
        val copy=JSONObject(payload.toString());val data=copy.getJSONObject("data");val id=data.optString("patrol_session_id")
        if(id.startsWith("local:")){
            val uuid=id.removePrefix("local:")
            queue.readableDatabase.rawQuery("SELECT server_id FROM local_patrols WHERE account=? AND operation_uuid=?",arrayOf(s.account,uuid)).use{c->
                if(!c.moveToFirst()||c.isNull(0))throw ApiFailure(409,"Offline patrol is waiting for its start receipt. Ask your supervisor to review the queue.")
                val resolved=OfflinePatrolReference.resolve(id,c.getString(0))?:throw ApiFailure(409,"Offline patrol start receipt is invalid. Ask your supervisor to review it.")
                data.put("patrol_session_id",resolved)
            }
        }
        return copy
    }
    fun queueSummary(): List<JSONObject> = synchronized(syncLock) {
        val s=session()?:return@synchronized emptyList()
        val result=mutableListOf<JSONObject>()
        queue.readableDatabase.rawQuery("SELECT uuid,payload,blocked,error_status FROM queue WHERE account=? ORDER BY sequence",arrayOf(s.account)).use { cursor ->
            while(cursor.moveToNext())result.add(JSONObject().put("uuid",cursor.getString(0)).put("action",JSONObject(secure.decrypt(cursor.getString(1))).getString("action")).put("blocked",cursor.getInt(2)!=0).put("error_status",cursor.getInt(3)))
        }
        result
    }
    fun retryBlocked(reason:String) = synchronized(syncLock) {
        require(reason.trim().length in 10..500){"Enter a supervisor reference/reason between 10 and 500 characters."}
        val s=session()?:throw ApiFailure(401,"Sign in required")
        val history=org.json.JSONArray(secure.get("queue_recovery_${s.account}")?:"[]")
        history.put(JSONObject().put("at",System.currentTimeMillis()).put("reason",reason.trim()).put("operations",org.json.JSONArray(queueSummary().filter{it.getBoolean("blocked")}.map{it.getString("uuid")})))
        while(history.length()>50)history.remove(0)
        secure.put("queue_recovery_${s.account}",history.toString())
        queue.writableDatabase.execSQL("UPDATE queue SET blocked=0 WHERE account=? AND blocked=1",arrayOf(s.account))
    }
    fun controlRoom(): JSONObject {val s=session()?:throw ApiFailure(401,"Sign in required");return request(s.base,s.token,"/guard/control-room").getJSONObject("data")}
    fun siteAssurance(): JSONObject {
        val s=session()?:throw ApiFailure(401,"Sign in required")
        return try { val data=request(s.base,s.token,"/guard/site-assurance").getJSONObject("data");data.put("cached_at",java.time.Instant.now().toString());secure.put("site_assurance_${s.account}",data.toString());data.put("cached",false) }
        catch(error:ApiFailure) { if(error.status!=0)throw error;secure.get("site_assurance_${s.account}")?.let{JSONObject(it).put("cached",true)}?:throw error }
    }
    fun acknowledgeInstructions(id:String) {val s=session()?:throw ApiFailure(401,"Sign in required");request(s.base,s.token,"/guard/instructions/$id/acknowledge",JSONObject())}
    fun sendHandover(data:JSONObject) {val s=session()?:throw ApiFailure(401,"Sign in required");request(s.base,s.token,"/guard/handovers",data)}
    fun acceptHandover(id:String,notes:String) {val s=session()?:throw ApiFailure(401,"Sign in required");request(s.base,s.token,"/guard/handovers/$id/accept",JSONObject().put("receiver_notes",notes))}
    fun responseAction(id:String,action:String,revision:Int) {require(action in listOf("accept","arrive"));val s=session()?:throw ApiFailure(401,"Sign in required");request(s.base,s.token,"/guard/dispatch/$id/$action",JSONObject().put("expected_revision",revision))}
    fun checkin(id:String,code:String) {val s=session()?:throw ApiFailure(401,"Sign in required");request(s.base,s.token,"/guard/checkins/$id",JSONObject().put("code",code))}
    fun uploadPhoneHealth() {
        val s=session()?:return
        val battery=appContext.getSystemService(android.os.BatteryManager::class.java).getIntProperty(android.os.BatteryManager.BATTERY_PROPERTY_CAPACITY)
        if(battery !in 0..100)return
        request(s.base,s.token,"/guard/phone-health",JSONObject().put("installation_id",installationId()).put("battery_percent",battery).put("version_code",BuildConfig.VERSION_CODE))
    }
    fun uploadDiagnostics() {
        val s=session()?:throw ApiFailure(401,"Sign in required")
        val rows=queueSummary()
        val connectivity=appContext.getSystemService(android.net.ConnectivityManager::class.java)
        val capabilities=connectivity.getNetworkCapabilities(connectivity.activeNetwork)
        val state=if(capabilities?.hasCapability(android.net.NetworkCapabilities.NET_CAPABILITY_VALIDATED)==true)"VALIDATED" else "UNVERIFIED"
        request(s.base,s.token,"/guard/diagnostics",JSONObject().put("installation_id",installationId()).put("app_version",BuildConfig.VERSION_NAME).put("pending_count",rows.size).put("blocked_count",rows.count{it.getBoolean("blocked")}).put("network_state",state))
    }
    fun sync(): String = synchronized(syncLock) {
        val s = session() ?: return@synchronized "Sign in required"
        val connectivity=appContext.getSystemService(android.net.ConnectivityManager::class.java)
        if(connectivity.activeNetwork==null)return@synchronized "Saved offline. Patrols and scans will upload when connected; keep this app installed and sign in again if the session expires."
        queue.readableDatabase.rawQuery("SELECT sequence,payload,blocked FROM queue WHERE account=? ORDER BY sequence", arrayOf(s.account)).use { cursor ->
            while (cursor.moveToNext()) {
                if (session()?.account != s.account) return@synchronized "Synchronization stopped after sign out."
                if (cursor.getInt(2) != 0) return@synchronized "A queued event needs supervisor review. Later events are retained."
                val sequence = cursor.getLong(0)
                try {
                    val original=JSONObject(secure.decrypt(cursor.getString(1)))
                    val command=resolvedCommand(original,s)
                    val result=request(s.base,s.token,"/guard/commands",command).getJSONObject("data")
                    val db=queue.writableDatabase;db.beginTransaction()
                    try{
                        if(original.getString("action")=="start_patrol")db.execSQL("UPDATE local_patrols SET server_id=? WHERE account=? AND operation_uuid=?",arrayOf(result.getString("patrol_session_id"),s.account,original.getString("operation_uuid")))
                        if(original.getString("action")=="finish_patrol"){
                            val reference=original.getJSONObject("data").getString("patrol_session_id")
                            if(reference.startsWith("local:"))db.execSQL("UPDATE local_patrols SET state='SYNCED' WHERE account=? AND operation_uuid=?",arrayOf(s.account,reference.removePrefix("local:")))
                            else db.execSQL("UPDATE local_patrols SET state='SYNCED' WHERE account=? AND server_id=?",arrayOf(s.account,reference))
                        }
                        db.delete("queue","sequence=? AND account=?",arrayOf(sequence.toString(),s.account))
                        db.execSQL("DELETE FROM local_patrols WHERE account=? AND state='SYNCED' AND operation_uuid NOT IN(SELECT operation_uuid FROM local_patrols WHERE account=? AND state='SYNCED' ORDER BY rowid DESC LIMIT 100)",arrayOf(s.account,s.account))
                        db.setTransactionSuccessful()
                    }finally{db.endTransaction()}
                } catch (error: ApiFailure) {
                    when (RetryPolicy.decide(error.status)) {
                        RetryPolicy.Decision.SIGN_IN -> { signOut(); return@synchronized "Session expired. Sign in to resume retained events." }
                        RetryPolicy.Decision.REVIEW -> { queue.writableDatabase.execSQL("UPDATE queue SET blocked=1,error_status=? WHERE sequence=? AND account=?", arrayOf<Any>(error.status,sequence, s.account)); return@synchronized "Event needs review: ${error.message}" }
                        else -> return@synchronized "Connection unavailable. Events remain encrypted on this device."
                    }
                }
            }
        }
        "All queued events synchronized."
    }
    private fun request(base: String, token: String, path: String, body: JSONObject? = null, readTimeout: Int = 20000): JSONObject {
        val connection = URI(base + "/api/v1" + path).toURL().openConnection() as HttpURLConnection
        connection.connectTimeout = 15000; connection.readTimeout = readTimeout; connection.instanceFollowRedirects = false
        connection.setRequestProperty("Accept", "application/json")
        if (token.isNotEmpty()) connection.setRequestProperty("Authorization", "Bearer $token")
        try {
            if (body != null) { connection.requestMethod = "POST"; connection.doOutput = true; connection.setRequestProperty("Content-Type", "application/json"); connection.outputStream.use { it.write(body.toString().toByteArray(Charsets.UTF_8)) } }
            val status = connection.responseCode
            val stream = if (status in 200..299) connection.inputStream else connection.errorStream
            val text = stream?.bufferedReader()?.use { it.readText() } ?: "{}"
            val json = try { JSONObject(text) } catch (_: Exception) { throw ApiFailure(if (status in 200..299) 503 else status, "Invalid server response") }
            if (status !in 200..299) throw ApiFailure(status, json.optString("message", "Request could not be completed"))
            return json
        } catch (error: ApiFailure) { throw error }
        catch (_: java.io.IOException) { throw ApiFailure(0, "Connection unavailable") }
        finally { connection.disconnect() }
    }
    companion object { private val syncLock = Any() }
}
