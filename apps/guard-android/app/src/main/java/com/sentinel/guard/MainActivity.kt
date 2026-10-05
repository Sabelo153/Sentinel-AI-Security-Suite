package com.sentinel.guard

import android.Manifest
import android.app.Activity
import android.app.AlertDialog
import android.app.job.JobInfo
import android.app.job.JobScheduler
import android.content.ComponentName
import android.content.Intent
import android.content.res.ColorStateList
import android.content.pm.PackageManager
import android.graphics.Color
import android.graphics.Bitmap
import android.graphics.Typeface
import android.graphics.drawable.GradientDrawable
import android.graphics.drawable.RippleDrawable
import android.location.LocationManager
import android.location.Location
import android.location.LocationListener
import android.nfc.NfcAdapter
import android.os.Build
import android.os.Bundle
import android.os.CancellationSignal
import android.os.Handler
import android.os.Looper
import android.text.InputType
import android.net.Uri
import android.provider.MediaStore
import android.view.View
import android.view.Gravity
import android.view.WindowManager
import android.view.animation.DecelerateInterpolator
import android.widget.*
import com.google.mlkit.vision.codescanner.GmsBarcodeScanning
import com.google.mlkit.vision.barcode.common.Barcode
import com.google.mlkit.vision.barcode.BarcodeScanning
import com.google.mlkit.vision.barcode.BarcodeScannerOptions
import com.google.mlkit.vision.common.InputImage
import org.json.JSONArray
import org.json.JSONObject
import java.time.Instant
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter
import java.util.concurrent.Executors

class MainActivity : Activity() {
    private companion object { const val QR_CAMERA_REQUEST = 7102 }
    private lateinit var client: GuardClient
    private lateinit var layout: LinearLayout
    private lateinit var notice: TextView
    private lateinit var progress: ProgressBar
    private var passwordVisibility: CheckBox? = null
    private var workspace: JSONObject? = null
    private var nfcSession: String? = null
    private var locationCancellation: CancellationSignal? = null
    private var legacyLocationListener: LocationListener? = null
    private var qrCallback: ((String) -> Unit)? = null
    private val worker = Executors.newSingleThreadExecutor()
    private val navy = Color.rgb(6,35,66)
    private val cyan = Color.rgb(18,204,229)
    private val ink = Color.rgb(16,36,62)
    private val muted = Color.rgb(91,107,128)
    private fun dp(value:Int)=Math.round(value*resources.displayMetrics.density)
    private fun surface(color:Int,radius:Int=14,strokeColor:Int?=null)=GradientDrawable().apply { setColor(color);cornerRadius=dp(radius).toFloat();strokeColor?.let{setStroke(dp(1),it)} }
    private fun ripple(content:GradientDrawable)=RippleDrawable(ColorStateList.valueOf(Color.argb(32,6,35,66)),content,null)
    private fun now(): String = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss").withZone(ZoneOffset.UTC).format(Instant.now())
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        if(!BuildConfig.DEBUG) window.addFlags(WindowManager.LayoutParams.FLAG_SECURE)
        client = GuardClient(applicationContext)
        try { workspace = client.cached(); if(client.session() == null) loginScreen() else { home(); synchronize() } }
        catch (_: Exception) { loginScreen(); notice.text = getString(R.string.secure_storage_unavailable) }
        val job = JobInfo.Builder(7101,ComponentName(this,SyncJob::class.java)).setRequiredNetworkType(JobInfo.NETWORK_TYPE_ANY).setPeriodic(15*60*1000L).setPersisted(true).build()
        getSystemService(JobScheduler::class.java).schedule(job)
    }
    private fun screen(title: String) {
        passwordVisibility?.isChecked=false
        passwordVisibility=null
        val scroll=ScrollView(this).apply { alpha=0f;translationY=16f*resources.displayMetrics.density;setBackgroundColor(Color.rgb(246,249,251));isFillViewport=true };layout=LinearLayout(this).apply { orientation=LinearLayout.VERTICAL;setPadding(dp(22),dp(34),dp(22),dp(36));gravity=Gravity.CENTER_HORIZONTAL };scroll.addView(layout);setContentView(scroll)
        layout.addView(ImageView(this).apply { setImageResource(R.drawable.sentinel_logo);contentDescription=getString(R.string.sentinel_logo);adjustViewBounds=true;layoutParams=LinearLayout.LayoutParams(dp(58),dp(58)).apply{bottomMargin=dp(18);gravity=Gravity.START} })
        layout.addView(TextView(this).apply { text=title;textSize=27f;setTextColor(navy);setTypeface(Typeface.DEFAULT,Typeface.BOLD);setPadding(0,0,0,dp(12));gravity=Gravity.START;layoutParams=LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT,LinearLayout.LayoutParams.WRAP_CONTENT) })
        notice=TextView(this).apply { accessibilityLiveRegion=View.ACCESSIBILITY_LIVE_REGION_POLITE;setTextColor(muted);textSize=14f;setLineSpacing(0f,1.18f);setPadding(0,0,0,dp(12));layoutParams=LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT,LinearLayout.LayoutParams.WRAP_CONTENT) };layout.addView(notice)
        progress=ProgressBar(this,null,android.R.attr.progressBarStyleHorizontal).apply { isIndeterminate=true;indeterminateTintList=ColorStateList.valueOf(cyan);visibility=View.GONE;contentDescription=getString(R.string.working) };layout.addView(progress,LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT,dp(4)).apply{bottomMargin=dp(14)})
        scroll.animate().alpha(1f).translationY(0f).setDuration(280).setInterpolator(DecelerateInterpolator()).start()
    }
    private fun text(value: String) { layout.addView(TextView(this).apply { text=value;textSize=16f;setTextColor(ink);setLineSpacing(0f,1.16f);setPadding(dp(2),dp(18),dp(2),dp(8));layoutParams=LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT,LinearLayout.LayoutParams.WRAP_CONTENT) }) }
    private fun button(value: String, action: ()->Unit) { layout.addView(Button(this).apply { text=value;isAllCaps=false;textSize=14f;setTextColor(navy);stateListAnimator=null;background=ripple(surface(Color.WHITE,12,Color.rgb(207,220,229)));setPadding(dp(14),0,dp(14),0);minHeight=dp(50);setOnClickListener { animate().scaleX(.98f).scaleY(.98f).setDuration(70).withEndAction{animate().scaleX(1f).scaleY(1f).setDuration(120).start();action()}.start() };layoutParams=LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT,dp(52)).apply{topMargin=dp(6)} }) }
    private fun primaryButton(value:String,action:()->Unit){layout.addView(Button(this).apply{text=value;isAllCaps=false;textSize=15f;setTypeface(Typeface.DEFAULT,Typeface.BOLD);setTextColor(navy);stateListAnimator=null;background=ripple(surface(cyan,12));minHeight=dp(52);setOnClickListener{action()};layoutParams=LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT,dp(52)).apply{topMargin=dp(8)}})}
    private fun field(hintValue: String, password: Boolean=false): EditText = EditText(this).apply { hint=hintValue;setHintTextColor(Color.rgb(116,132,151));setTextColor(ink);textSize=16f;isSingleLine=true;isSaveEnabled=false;background=surface(Color.WHITE,12,Color.rgb(189,207,219));setPadding(dp(14),0,dp(14),0);inputType=if(password) InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_PASSWORD else InputType.TYPE_CLASS_TEXT;this@MainActivity.layout.addView(this,LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT,dp(54)).apply{bottomMargin=dp(8)}) }
    private fun loginScreen() {
        screen(getString(R.string.welcome_to_sentinel))
        val connected=client.configuredServer()!=null
        if(connected) {
            text(getString(R.string.login_intro))
            notice.text=getString(R.string.organisation_connected)
            val identifier=field(getString(R.string.username_or_email)).apply { setAutofillHints(View.AUTOFILL_HINT_USERNAME);inputType=InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_EMAIL_ADDRESS }
            val password=field(getString(R.string.password),true).apply { setAutofillHints(View.AUTOFILL_HINT_PASSWORD) }
            val reveal=CheckBox(this).apply {
                text=getString(R.string.show_password);setTextColor(ink);minHeight=dp(48)
                setOnCheckedChangeListener { _,visible ->
                    val cursor=password.selectionStart.coerceAtLeast(0)
                    password.transformationMethod=if(visible) android.text.method.HideReturnsTransformationMethod.getInstance() else android.text.method.PasswordTransformationMethod.getInstance()
                    password.setSelection(cursor.coerceAtMost(password.length()))
                }
            }
            passwordVisibility=reveal
            layout.addView(reveal,LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT,LinearLayout.LayoutParams.WRAP_CONTENT))
            val loginLayout=layout
            val loginNotice=notice
            val loginProgress=progress
            var signingIn=false
            primaryButton(getString(R.string.sign_in)) {
                if(signingIn)return@primaryButton
                val name=identifier.text.toString().trim();val secret=password.text.toString()
                if(name.isBlank()||secret.isBlank()){notice.text=getString(R.string.enter_login_details);return@primaryButton}
                signingIn=true
                identifier.isEnabled=false;password.isEnabled=false;reveal.isChecked=false;reveal.isEnabled=false
                loginNotice.text=getString(R.string.signing_in);loginProgress.visibility=View.VISIBLE
                worker.execute {
                    try {
                        val result=client.login(name,secret)
                        runOnUiThread { password.text.clear();if(!isDestroyed&&layout===loginLayout){workspace=result;home();synchronize()} }
                    } catch(error:Exception) {
                        runOnUiThread { if(!isDestroyed&&layout===loginLayout){loginNotice.text=error.message?:getString(R.string.operation_failed);password.requestFocus()} }
                    } finally {
                        runOnUiThread { signingIn=false;identifier.isEnabled=true;password.isEnabled=true;reveal.isEnabled=true;loginProgress.visibility=View.GONE }
                    }
                }
            }
            button(getString(R.string.forgot_login_details)) { AlertDialog.Builder(this).setTitle(R.string.need_login_help).setMessage(R.string.guard_account_help).setPositiveButton(R.string.close,null).show() }
            button(getString(R.string.open_owner_dashboard)) { client.adminUrl()?.let { startActivity(Intent(Intent.ACTION_VIEW,Uri.parse(it))) } }
        } else {
            text(getString(R.string.first_run_intro))
            notice.text=getString(R.string.not_connected)
            button(getString(R.string.join_as_guard),::guardJoinScreen)
        }
        button(getString(R.string.create_organisation),::openSignup)
        button(getString(R.string.new_to_sentinel),::newUserScreen)
        button(getString(R.string.explore_demo),::demoScreen)
    }
    private fun guardJoinScreen() {
        screen(getString(R.string.join_as_guard))
        notice.text=getString(R.string.guard_join_explanation)
        button(getString(R.string.scan_company_setup)) { scanCompanySetup() }
        button(getString(R.string.guard_account_help_button)) { AlertDialog.Builder(this).setTitle(R.string.guard_account_title).setMessage(R.string.guard_account_help).setPositiveButton(R.string.close,null).show() }
        button(getString(R.string.back_to_sign_in),::loginScreen)
    }
    private fun demoScreen() {
        screen(getString(R.string.demo_title))
        notice.text=getString(R.string.demo_notice)
        text(getString(R.string.demo_shift))
        text(getString(R.string.demo_shift_detail))
        text(getString(R.string.demo_patrol))
        text(getString(R.string.demo_patrol_detail))
        text(getString(R.string.demo_report))
        text(getString(R.string.demo_report_detail))
        button(getString(R.string.view_offline_guides)) {
            val titles=GuardHelpGuides.all.map{it.title}.toTypedArray()
            AlertDialog.Builder(this).setTitle(R.string.quick_guides).setItems(titles){_,index->val guide=GuardHelpGuides.all[index];AlertDialog.Builder(this).setTitle(guide.title).setMessage(guide.summary).setPositiveButton(R.string.close,null).show()}.show()
        }
        button(getString(R.string.back_to_sign_in),::loginScreen)
    }
    private fun scanCompanySetup() {
        scanQr { value ->
            try { client.configureServer(value);loginScreen();notice.text=getString(R.string.setup_complete) }
            catch(error:Exception){notice.text=error.message?:getString(R.string.invalid_setup_code)}
        }
    }
    private fun newUserScreen() {
        screen(getString(R.string.new_user_title))
        text(getString(R.string.owner_heading));text(getString(R.string.owner_explanation));button(getString(R.string.create_organisation),::openSignup)
        text(getString(R.string.guard_heading));text(getString(R.string.guard_join_explanation));button(getString(R.string.join_as_guard),::guardJoinScreen)
        text(getString(R.string.preview_heading));text(getString(R.string.preview_explanation));button(getString(R.string.explore_demo),::demoScreen)
        text(getString(R.string.private_deployment_heading));button(getString(R.string.administrator_setup)) { administratorSetup() }
        button(getString(R.string.back_to_sign_in),::loginScreen)
    }
    private fun openSignup() {
        val url=try{client.signupUrl()}catch(_:Exception){null}
        if(url==null){signupUnavailableScreen();return}
        startActivity(Intent(Intent.ACTION_VIEW,Uri.parse(url)))
    }
    private fun signupUnavailableScreen() {
        screen(getString(R.string.create_organisation))
        notice.text=getString(R.string.signup_not_configured)
        text(getString(R.string.signup_not_configured_detail))
        button(getString(R.string.explore_demo),::demoScreen)
        button(getString(R.string.back_to_sign_in),::loginScreen)
    }
    private fun administratorSetup() {
        val input=EditText(this).apply { hint=getString(R.string.https_server_address);inputType=InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_URI;isSaveEnabled=false }
        AlertDialog.Builder(this).setTitle(R.string.administrator_setup).setMessage(R.string.admin_setup_explanation).setView(input)
            .setPositiveButton(R.string.connect) { _,_->try{client.configureServer(input.text.toString());loginScreen();notice.text=getString(R.string.setup_complete)}catch(error:Exception){notice.text=error.message?:getString(R.string.invalid_setup_code)} }
            .setNegativeButton(R.string.cancel,null).show()
    }
    private fun background(action: ()->Unit) {
        notice.text=getString(R.string.working)
        progress.visibility=View.VISIBLE
        worker.execute { try { action() } catch(error:Exception) { runOnUiThread { if(error is ApiFailure && error.status==401){client.signOut();loginScreen()};notice.text=error.message?:getString(R.string.operation_failed) } } finally { runOnUiThread { if(::progress.isInitialized)progress.visibility=View.GONE } } }
    }
    private fun synchronize() = background {
        val result=client.sync()
        if(client.session()!=null)try { workspace=client.refresh() } catch(_:ApiFailure) { /* Cached workspace stays available offline. */ }
        runOnUiThread { if(client.session()==null)loginScreen() else home();notice.text=result }
    }
    private fun enqueue(action:String,data:JSONObject) {
        background {
            client.enqueue(action,data)
            val result=client.sync()
            if(client.session()!=null)try{workspace=client.refresh()}catch(_:ApiFailure){}
            runOnUiThread{if(client.session()==null)loginScreen()else home();notice.text=result}
        }
    }
    private fun rows(name:String):List<JSONObject> {
        val array=workspace?.optJSONArray(name)?:JSONArray();return(0 until array.length()).map{array.getJSONObject(it)}
    }
    private fun home() {
        screen(getString(R.string.guard_workspace))
        notice.text=resources.getQuantityString(R.plurals.pending_events,client.pending(),client.pending())
        button(getString(R.string.synchronize_refresh),::synchronize)
        button(getString(R.string.help_guide),::helpScreen)
        button(getString(R.string.capture_gps),::captureLocation)
        button("My patrol schedule") { scheduleScreen() }
        button("Patrol history",::historyScreen)
        button("Open manager dashboard") { client.adminUrl()?.let{startActivity(Intent(Intent.ACTION_VIEW,android.net.Uri.parse(it)))} }
        text(getString(R.string.shifts))
        val openShifts=rows("shifts").filter{it.optString("assignment_status") in listOf("ASSIGNED","CONFIRMED","IN_PROGRESS")}
        if(openShifts.isEmpty())text("No assigned shifts yet. Your manager must assign a shift at your site, then you can synchronize here. You can sign in successfully before work has been assigned.")
        for(shift in openShifts) {
            text("${shift.optString("shift_name")} · ${shift.optString("shift_start_at")}")
            if(rows("attendance").none{it.optString("shift_assignment_id")==shift.optString("shift_assignment_id")})button(getString(R.string.clock_in)) { enqueue("clock_in",JSONObject().put("shift_assignment_id",shift.get("shift_assignment_id")).put("occurred_at",now())) }
        }
        for(attendance in rows("attendance").filter{it.isNull("clock_out_at")})button(getString(R.string.clock_out,attendance.optString("shift_date"))) { enqueue("clock_out",JSONObject().put("attendance_id",attendance.get("attendance_id")).put("occurred_at",now())) }
        text(getString(R.string.patrols))
        for(patrol in rows("patrols").filter{it.optString("status")=="IN_PROGRESS"}) {
            val id=patrol.getString("patrol_session_id")
            text(getString(R.string.active_patrol,patrol.optString("started_at")))
            button("Checkpoint progress") { patrolDetailsScreen(id) }
            button(getString(R.string.scan_checkpoint_qr)) {
                scanQr { checkpoint(id,"QR",it) }
            }
            button(getString(R.string.read_checkpoint_nfc)) { armNfc(id) }
            button(getString(R.string.complete_patrol)) { AlertDialog.Builder(this).setMessage(R.string.confirm_patrol_complete).setNegativeButton(R.string.cancel,null).setPositiveButton(R.string.complete) { _,_->enqueue("finish_patrol",JSONObject().put("patrol_session_id",id).put("occurred_at",now())) }.show() }
        }
        val assignments=rows("assignments")
        if(assignments.isEmpty())text("No active guard position assignment. Ask your manager to assign an active GUARD position to your employee record.")
        if(rows("routes").isEmpty())text("No patrol routes assigned to your shift sites. Your manager creates a route and checkpoints under Patrol routes on the website.")
        for(shift in openShifts)for(route in rows("routes").filter{it.optString("site_id")==shift.optString("site_id")}) {
            button(getString(R.string.start_route,route.optString("route_name"),shift.optString("shift_name"))) {
                if(assignments.isEmpty()){notice.text=getString(R.string.no_active_assignment);return@button}
                val labels=assignments.map{getString(R.string.assignment_label,it.optString("position_name"),it.optString("employee_assignment_id"))}.toTypedArray()
                AlertDialog.Builder(this).setTitle(R.string.choose_assignment).setItems(labels){_,index->enqueue("start_patrol",JSONObject().put("employee_assignment_id",assignments[index].get("employee_assignment_id")).put("patrol_route_id",route.get("patrol_route_id")).put("shift_assignment_id",shift.get("shift_assignment_id")).put("started_at",now()))}.show()
            }
        }
        text(getString(R.string.offline_events_notice))
        button(getString(R.string.sign_out)) { client.signOut();nfcSession=null;NfcAdapter.getDefaultAdapter(this)?.disableReaderMode(this);loginScreen() }
    }
    private fun historyScreen() {
        screen("My patrol history")
        button("Back to workspace",::home)
        text("Recent 100 patrols stored on this device after synchronization. Full company history and exports are available to authorized managers on the website.")
        if(rows("patrols").isEmpty())text("No patrols have been recorded for your account yet.")
        for(patrol in rows("patrols")) {
            text("#${patrol.optString("patrol_session_id")} · ${patrol.optString("status").replace('_',' ')}\nStarted ${patrol.optString("started_at")} UTC\nEnded ${if(patrol.isNull("ended_at")) "Not ended" else patrol.optString("ended_at")+" UTC"}")
            button("View checkpoint evidence") { patrolDetailsScreen(patrol.getString("patrol_session_id")) }
        }
    }
    private fun patrolDetailsScreen(id:String) {
        background {
            val result=client.patrolDetails(id)
            runOnUiThread {
                screen("Patrol #$id")
                button("Back to workspace",::home)
                val session=result.getJSONObject("session")
                text("${session.optString("route_name")} · ${session.optString("status").replace('_',' ')}")
                val events=result.optJSONArray("events")?:JSONArray()
                val checkpoints=result.optJSONArray("checkpoints")?:JSONArray()
                if(checkpoints.length()==0)text("No checkpoints configured for this route. Ask your manager to review it.")
                for(i in 0 until checkpoints.length()) {
                    val point=checkpoints.getJSONObject(i)
                    val matches=(0 until events.length()).map{events.getJSONObject(it)}.filter{it.optString("patrol_checkpoint_id")==point.optString("patrol_checkpoint_id")}
                    val status=if(matches.any{it.optString("event_type")=="VISITED"})"Visited" else matches.lastOrNull()?.optString("event_type")?:"No visit recorded"
                    text("${point.optString("checkpoint_order")}. ${point.optString("checkpoint_name")} · $status")
                }
                text("Progress uses the current route setup. Recorded events below preserve the visit history.")
                for(i in 0 until events.length()) {val event=events.getJSONObject(i);text("Checkpoint ${event.optString("patrol_checkpoint_id")} · ${event.optString("event_type")} · ${event.optString("recorded_at")} UTC")}
            }
        }
    }
    private fun scheduleScreen(before:String?=null) {
        background {
            val result=client.patrolSchedules(before)
            runOnUiThread {
                screen("My patrol schedule")
                button("Back to workspace",::home)
                text("Start the route during its window from your workspace. A missed window means no matching start has reached the server; synchronize offline events before contacting your manager. Times shown are UTC.")
                val items=result.optJSONArray("items")?:JSONArray()
                if(items.length()==0)text("No patrol windows scheduled yet. Ask your manager to use Patrol schedule on the website.")
                for(i in 0 until items.length()) {val item=items.getJSONObject(i);text("${item.optString("route_name")} · ${item.optString("status").replace('_',' ')}\n${item.optString("starts_at")} to ${item.optString("ends_at")} UTC")}
                if(!result.isNull("next_before_id"))button("Older schedules"){scheduleScreen(result.getString("next_before_id"))}
            }
        }
    }
    private fun helpScreen() {
        screen(getString(R.string.help_guide))
        notice.text=getString(R.string.help_privacy)
        button(getString(R.string.back_to_workspace),::home)
        text(getString(R.string.quick_guides))
        GuardHelpGuides.all.forEach { guide ->
            button(guide.title) { AlertDialog.Builder(this).setTitle(guide.title).setMessage(guide.summary).setPositiveButton(R.string.close,null).show() }
        }
        text(getString(R.string.ask_sentinel_guide))
        val topic=Spinner(this).apply {
            adapter=ArrayAdapter(this@MainActivity,android.R.layout.simple_spinner_dropdown_item,GuardHelpGuides.all.map{it.title})
            layout.addView(this)
        }
        val question=field(getString(R.string.general_help_question)).apply { isSingleLine=false;inputType=InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_FLAG_MULTI_LINE;minLines=3;maxLines=6;layoutParams.height=LinearLayout.LayoutParams.WRAP_CONTENT }
        val helpLayout=layout
        val helpNotice=notice
        val helpProgress=progress
        var asking=false
        button(getString(R.string.ask_for_guidance)) {
            if(asking)return@button
            val value=question.text.toString().trim()
            if(value.length<3){notice.text=getString(R.string.enter_help_question);return@button}
            val guide=GuardHelpGuides.all[topic.selectedItemPosition]
            asking=true
            helpNotice.text=getString(R.string.working);helpProgress.visibility=View.VISIBLE
            worker.execute {
                try {
                    val answer=client.help(guide.topic,value)
                    runOnUiThread { if(!isDestroyed&&layout===helpLayout) { AlertDialog.Builder(this).setTitle(R.string.sentinel_guide_answer).setMessage(answer).setPositiveButton(R.string.close,null).show();helpNotice.text=getString(R.string.help_privacy) } }
                } catch(error:Exception) {
                    runOnUiThread { if(!isDestroyed&&layout===helpLayout)helpNotice.text=error.message?:getString(R.string.operation_failed) }
                } finally {
                    runOnUiThread { asking=false;helpProgress.visibility=View.GONE }
                }
            }
        }
    }
    private fun checkpoint(id:String,method:String,code:String) { enqueue("checkpoint",JSONObject().put("patrol_session_id",id).put("method",method).put("code",code).put("recorded_at",now())) }
    private fun armNfc(id:String) {
        val adapter=NfcAdapter.getDefaultAdapter(this)
        if(adapter==null||!adapter.isEnabled){notice.text=getString(R.string.nfc_unavailable);return}
        nfcSession=id;notice.text=getString(R.string.nfc_hold)
        adapter.enableReaderMode(this,{tag->
            val session=nfcSession;nfcSession=null
            if(session!=null)runOnUiThread { adapter.disableReaderMode(this);checkpoint(session,"NFC",tag.id.joinToString(""){"%02x".format(it)}) }
        },NfcAdapter.FLAG_READER_NFC_A or NfcAdapter.FLAG_READER_NFC_B or NfcAdapter.FLAG_READER_NFC_F or NfcAdapter.FLAG_READER_NFC_V or NfcAdapter.FLAG_READER_SKIP_NDEF_CHECK,null)
    }
    private fun recordLocation(location: Location?) {
        if(location==null){notice.text=getString(R.string.no_gps_fix);return}
        @Suppress("DEPRECATION") val mock=location.isFromMockProvider
        if(mock){notice.text=getString(R.string.mock_location);return}
        enqueue("location",JSONObject().put("latitude",location.latitude).put("longitude",location.longitude).put("accuracy",location.accuracy.toDouble()).put("captured_at",DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss").withZone(ZoneOffset.UTC).format(Instant.ofEpochMilli(location.time))))
    }

    private fun scanQr(onScanned:(String)->Unit) {
        qrCallback=onScanned
        try {
            GmsBarcodeScanning.getClient(this).startScan()
                .addOnSuccessListener { barcode -> finishQrScan(barcode.rawValue) }
                .addOnFailureListener { openCameraQrCapture() }
        } catch (_: Exception) {
            openCameraQrCapture()
        }
    }

    private fun openCameraQrCapture() {
        val capture=Intent(MediaStore.ACTION_IMAGE_CAPTURE)
        notice.text=getString(R.string.qr_camera_capture)
        try { startActivityForResult(capture,QR_CAMERA_REQUEST) }
        catch (_: Exception) { qrCallback=null;notice.text=getString(R.string.qr_unavailable) }
    }

    private fun finishQrScan(rawValue:String?) {
        val value=rawValue?.trim().orEmpty()
        if(value.isEmpty()) { qrCallback=null;notice.text=getString(R.string.qr_not_found);return }
        val callback=qrCallback
        qrCallback=null
        callback?.invoke(value)
    }

    @Suppress("DEPRECATION")
    private fun cameraResultBitmap(data:Intent?):Bitmap? = if(Build.VERSION.SDK_INT>=33)
        data?.getParcelableExtra("data",Bitmap::class.java)
    else data?.extras?.get("data") as? Bitmap

    @Deprecated("Camera capture fallback for devices without Google Play services")
    override fun onActivityResult(requestCode:Int,resultCode:Int,data:Intent?) {
        super.onActivityResult(requestCode,resultCode,data)
        if(requestCode!=QR_CAMERA_REQUEST)return
        if(resultCode!=RESULT_OK) { qrCallback=null;notice.text=getString(R.string.qr_scan_cancelled);return }
        val bitmap=cameraResultBitmap(data)
        if(bitmap==null) { qrCallback=null;notice.text=getString(R.string.qr_not_found);return }
        notice.text=getString(R.string.qr_reading_photo)
        val options=BarcodeScannerOptions.Builder().setBarcodeFormats(Barcode.FORMAT_QR_CODE).build()
        val scanner=BarcodeScanning.getClient(options)
        scanner.process(InputImage.fromBitmap(bitmap,0))
            .addOnSuccessListener { codes -> finishQrScan(codes.firstOrNull()?.rawValue) }
            .addOnFailureListener { qrCallback=null;notice.text=getString(R.string.qr_not_found) }
            .addOnCompleteListener { scanner.close() }
    }
    private fun captureLocation() {
        if(checkSelfPermission(Manifest.permission.ACCESS_FINE_LOCATION)!=PackageManager.PERMISSION_GRANTED){requestPermissions(arrayOf(Manifest.permission.ACCESS_FINE_LOCATION,Manifest.permission.ACCESS_COARSE_LOCATION),41);return}
        val manager=getSystemService(LocationManager::class.java)
        if(!manager.isProviderEnabled(LocationManager.GPS_PROVIDER)){notice.text=getString(R.string.enable_gps);return}
        locationCancellation?.cancel();val signal=CancellationSignal();locationCancellation=signal;notice.text=getString(R.string.waiting_gps)
        if(Build.VERSION.SDK_INT>=Build.VERSION_CODES.R) manager.getCurrentLocation(LocationManager.GPS_PROVIDER,signal,mainExecutor){location->
            if(locationCancellation===signal){locationCancellation=null;recordLocation(location)}
        } else {
            legacyLocationListener?.let { manager.removeUpdates(it) }
            val listener=object:LocationListener{
                override fun onLocationChanged(location:Location){ manager.removeUpdates(this);if(legacyLocationListener===this){legacyLocationListener=null;recordLocation(location)} }
            }
            legacyLocationListener=listener
            @Suppress("DEPRECATION") manager.requestSingleUpdate(LocationManager.GPS_PROVIDER,listener,Looper.getMainLooper())
        }
        Handler(Looper.getMainLooper()).postDelayed({if(locationCancellation===signal){signal.cancel();locationCancellation=null};legacyLocationListener?.let{manager.removeUpdates(it);legacyLocationListener=null}},30000)
    }
    override fun onRequestPermissionsResult(requestCode:Int,permissions:Array<out String>,grantResults:IntArray){super.onRequestPermissionsResult(requestCode,permissions,grantResults);if(requestCode==41&&grantResults.firstOrNull()==PackageManager.PERMISSION_GRANTED)captureLocation()else notice.text=getString(R.string.location_permission_denied)}
    override fun onPause(){super.onPause();passwordVisibility?.isChecked=false;nfcSession=null;NfcAdapter.getDefaultAdapter(this)?.disableReaderMode(this);locationCancellation?.cancel()}
    override fun onDestroy(){super.onDestroy();worker.shutdown()}
}
