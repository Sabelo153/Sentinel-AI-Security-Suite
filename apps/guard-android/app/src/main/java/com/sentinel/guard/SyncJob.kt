package com.sentinel.guard
import android.app.job.JobParameters
import android.app.job.JobService

class SyncJob : JobService() {
    override fun onStartJob(params: JobParameters): Boolean {
        Thread {
            var retry = false
            try { val client = GuardClient(applicationContext); client.sync(); retry = client.session() != null && client.pending() > 0 } catch (_: Exception) { retry = true }
            jobFinished(params, retry)
        }.start()
        return true
    }
    override fun onStopJob(params: JobParameters): Boolean = true
}
