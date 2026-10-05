package com.sentinel.guard
import org.junit.Assert.assertEquals
import org.junit.Test
class RetryPolicyTest {
    @Test fun transientFailuresRetainTheSameCommand() { for (status in listOf(0,408,429,500,503)) assertEquals(RetryPolicy.Decision.RETRY,RetryPolicy.decide(status)) }
    @Test fun authenticationAndConflictsNeedExplicitHandling() { assertEquals(RetryPolicy.Decision.SIGN_IN,RetryPolicy.decide(401)); for(status in listOf(403,404,409,422)) assertEquals(RetryPolicy.Decision.REVIEW,RetryPolicy.decide(status)) }
    @Test fun onlyAcknowledgedRequestsLeaveTheQueue() { for(status in listOf(200,201,204)) assertEquals(RetryPolicy.Decision.ACKNOWLEDGE,RetryPolicy.decide(status)) }
}
