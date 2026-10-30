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
    private val queue = object : SQLiteOpenHelper(context, "guard_queue.db", null, 2) {
        override fun onCreate(db: SQLiteDatabase) { db.execSQL("CREATE TABLE queue (sequence INTEGER PRIMARY KEY AUTOINCREMENT, account TEXT NOT NULL, uuid TEXT NOT NULL UNIQUE, payload TEXT NOT NULL, blocked INTEGER NOT NULL DEFAULT 0, error_status INTEGER NOT NULL DEFAULT 0)") }
        override fun onUpgrade(db: SQLiteDatabase, oldVersion: Int, newVersion: Int) { if(oldVersion<2)db.execSQL("ALTER TABLE queue ADD COLUMN error_status INTEGER NOT NULL DEFAULT 0") }
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
        secure.put("workspace_${s.account}", data.toString()); return data
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
            val uuid = UUID.randomUUID().toString()
            val payload = JSONObject().put("operation_uuid", uuid).put("action", action).put("data", data)
            queue.writableDatabase.execSQL("INSERT INTO queue (account,uuid,payload) VALUES (?,?,?)", arrayOf(s.account, uuid, secure.encrypt(payload.toString())))
            return uuid
        }
    }
    fun pending(s: GuardSession? = session()): Int {
        if (s == null) return 0
        queue.readableDatabase.rawQuery("SELECT COUNT(*) FROM queue WHERE account=?", arrayOf(s.account)).use { it.moveToFirst(); return it.getInt(0) }
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
        queue.readableDatabase.rawQuery("SELECT sequence,payload,blocked FROM queue WHERE account=? ORDER BY sequence", arrayOf(s.account)).use { cursor ->
            while (cursor.moveToNext()) {
                if (session()?.account != s.account) return@synchronized "Synchronization stopped after sign out."
                if (cursor.getInt(2) != 0) return@synchronized "A queued event needs supervisor review. Later events are retained."
                val sequence = cursor.getLong(0)
                try {
                    request(s.base, s.token, "/guard/commands", JSONObject(secure.decrypt(cursor.getString(1)))).getJSONObject("data")
                    queue.writableDatabase.delete("queue", "sequence=? AND account=?", arrayOf(sequence.toString(), s.account))
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
