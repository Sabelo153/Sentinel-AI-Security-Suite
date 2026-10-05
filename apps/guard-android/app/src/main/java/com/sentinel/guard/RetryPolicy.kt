package com.sentinel.guard

object RetryPolicy {
    enum class Decision { ACKNOWLEDGE, RETRY, SIGN_IN, REVIEW }
    fun decide(status: Int): Decision = when {
        status in 200..299 -> Decision.ACKNOWLEDGE
        status == 401 -> Decision.SIGN_IN
        status == 0 || status == 408 || status == 429 || status >= 500 -> Decision.RETRY
        else -> Decision.REVIEW
    }
}
