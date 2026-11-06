package com.sentinel.guard

import android.app.Activity
import android.app.ActivityOptions
import android.app.admin.DevicePolicyManager
import android.content.ComponentName
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.os.Build
import android.os.UserManager
import org.json.JSONObject
import java.security.MessageDigest

class ManagedDeviceController(private val context: Context) {
    private val secure = SecureStore(context)
    private val manager = context.getSystemService(DevicePolicyManager::class.java)
    private val admin = ComponentName(context, SentinelDeviceAdmin::class.java)
    fun isOwner(): Boolean = manager.isDeviceOwnerApp(context.packageName)
    fun state(): String = secure.get("mdm_state") ?: "UNKNOWN"
    fun revision(): Int = secure.get("mdm_applied_revision")?.toIntOrNull() ?: 0
    fun cache(response: JSONObject) { secure.put("mdm_policy", response.toString()) }
    fun cached(): JSONObject? = secure.get("mdm_policy")?.let(::JSONObject)

    /** Must run on the foreground Activity; never silently substitutes ordinary screen pinning. */
    fun apply(activity: Activity? = null): String {
        val response = cached() ?: return "UNKNOWN"
        val revision = response.getInt("revision")
        if (!isOwner()) return saveState("NOT_DEVICE_OWNER")
        if (!ManagedPolicy.needsApply(revision, secure.get("mdm_recovery_revision")?.toIntOrNull() ?: -1)) return saveState("RECOVERY_RELEASED")
        val policy = response.getJSONObject("policy")
        if (!policy.getBoolean("enabled")) {
            release(activity)
            secure.put("mdm_applied_revision", revision.toString())
            return saveState("RELEASED")
        }
        val input = policy.getJSONArray("allowed_packages")
        val packages = ManagedPolicy.packages((0 until input.length()).map { input.getString(it) })
        // Validate all fields before changing device policy.
        val blockStatus = policy.getBoolean("block_status_bar")
        val blockInstall = policy.getBoolean("block_install")
        manager.setLockTaskPackages(admin, packages.toTypedArray())
        val statusApplied = manager.setStatusBarDisabled(admin, blockStatus)
        if (Build.VERSION.SDK_INT >= 28) {
            manager.setLockTaskFeatures(admin, DevicePolicyManager.LOCK_TASK_FEATURE_HOME or DevicePolicyManager.LOCK_TASK_FEATURE_GLOBAL_ACTIONS or
                (if (blockStatus) 0 else DevicePolicyManager.LOCK_TASK_FEATURE_NOTIFICATIONS))
        }
        val home = IntentFilter(Intent.ACTION_MAIN).apply { addCategory(Intent.CATEGORY_HOME); addCategory(Intent.CATEGORY_DEFAULT) }
        manager.addPersistentPreferredActivity(admin, home, ComponentName(context, MainActivity::class.java))
        if (blockInstall) manager.addUserRestriction(admin, UserManager.DISALLOW_INSTALL_APPS)
        else manager.clearUserRestriction(admin, UserManager.DISALLOW_INSTALL_APPS)
        manager.addUserRestriction(admin, UserManager.DISALLOW_CREATE_WINDOWS)
        if(activity!=null)activity.startLockTask()
        secure.put("mdm_applied_revision", revision.toString())
        val locked=context.getSystemService(android.app.ActivityManager::class.java).lockTaskModeState==android.app.ActivityManager.LOCK_TASK_MODE_LOCKED
        val installed=packages.all{try{context.packageManager.getPackageInfo(it,0);true}catch(_:android.content.pm.PackageManager.NameNotFoundException){false}}
        return saveState(if (statusApplied && locked && installed) "APPLIED" else "PARTIAL")
    }

    fun allowedApps(): List<String> {
        val policy = cached()?.optJSONObject("policy") ?: return emptyList()
        val packages = policy.optJSONArray("allowed_packages") ?: return emptyList()
        return ManagedPolicy.packages((0 until packages.length()).map { packages.getString(it) }).filter { it != context.packageName }
    }

    fun launch(activity: Activity, name: String) {
        require(name in allowedApps()) { "App is not approved" }
        val intent = context.packageManager.getLaunchIntentForPackage(name) ?: error("Approved app is not installed")
        if(cached()?.getJSONObject("policy")?.getBoolean("enabled")==false || state()=="RECOVERY_RELEASED") { activity.startActivity(intent);return }
        if (isOwner() && Build.VERSION.SDK_INT >= 28) activity.startActivity(intent, ActivityOptions.makeBasic().setLockTaskEnabled(true).toBundle())
        else error("Launching other kiosk apps requires a fully managed Android 9+ device")
    }

    fun recover(activity: Activity, code: String) {
        val now = System.currentTimeMillis()
        require(now >= (secure.get("mdm_recovery_locked_until")?.toLongOrNull() ?: 0)) { "Recovery is temporarily locked. Contact your administrator." }
        val policy = cached() ?: error("No managed policy stored")
        val actual = MessageDigest.getInstance("SHA-256").digest((policy.getString("recovery_salt") + ":" + code.trim()).toByteArray()).joinToString("") { "%02x".format(it) }
        if (!MessageDigest.isEqual(actual.toByteArray(), policy.getString("recovery_hash").toByteArray())) {
            val attempts = (secure.get("mdm_recovery_attempts")?.toIntOrNull() ?: 0) + 1
            secure.put("mdm_recovery_attempts", attempts.toString())
            if (attempts % 5 == 0) secure.put("mdm_recovery_locked_until", (now + 15 * 60 * 1000).toString())
            error("Recovery code is invalid")
        }
        release(activity)
        secure.put("mdm_recovery_revision", policy.getInt("revision").toString())
        secure.put("mdm_recovery_attempts", "0")
        saveState("RECOVERY_RELEASED")
    }

    private fun release(activity: Activity?) {
        if (!isOwner()) return
        activity?.stopLockTask()
        manager.setStatusBarDisabled(admin, false)
        manager.clearUserRestriction(admin, UserManager.DISALLOW_INSTALL_APPS)
        manager.clearUserRestriction(admin, UserManager.DISALLOW_CREATE_WINDOWS)
        manager.clearPackagePersistentPreferredActivities(admin, context.packageName)
        manager.setLockTaskPackages(admin, emptyArray())
    }
    private fun saveState(state: String): String { secure.put("mdm_state", state); return state }
}
