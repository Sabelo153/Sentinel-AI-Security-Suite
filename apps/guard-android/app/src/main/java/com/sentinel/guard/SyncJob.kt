package com.sentinel.guard
import android.app.job.JobParameters
import android.app.job.JobService
import android.app.job.JobInfo
import android.app.job.JobScheduler
import android.content.ComponentName
import android.content.Context

class SyncJob : JobService() {
    companion object {
        fun requestSync(context:Context){
            val job=JobInfo.Builder(7102,ComponentName(context,SyncJob::class.java)).setRequiredNetworkType(JobInfo.NETWORK_TYPE_ANY).setPersisted(true).setMinimumLatency(1000).setBackoffCriteria(30000,JobInfo.BACKOFF_POLICY_EXPONENTIAL).build()
            context.getSystemService(JobScheduler::class.java).schedule(job)
        }
    }
    override fun onStartJob(params: JobParameters): Boolean {
        Thread {
            var retry = false
            val client = GuardClient(applicationContext)
            try { client.refreshManagedPolicy(); ManagedDeviceController(applicationContext).apply(); client.refreshManagedPolicy() } catch (_: Exception) { retry = true }
            try { client.sync(); retry = retry || (client.session() != null && client.pending() > 0) } catch (_: Exception) { retry = true }
            jobFinished(params, retry)
        }.start()
        return true
    }
    override fun onStopJob(params: JobParameters): Boolean = true
}
