package com.sentinel.guard
import org.junit.Assert.*
import org.junit.Test
class OfflinePatrolReferenceTest {
    @Test fun waitsForStartAcknowledgement(){assertNull(OfflinePatrolReference.resolve("local:operation",null))}
    @Test fun reusesStableAcknowledgedId(){assertEquals("42",OfflinePatrolReference.resolve("local:operation","42"));assertEquals("42",OfflinePatrolReference.resolve("local:operation","42"))}
    @Test fun doesNotRewriteServerReferences(){assertEquals("17",OfflinePatrolReference.resolve("17",null))}
    @Test fun rejectsInvalidReceiptIds(){for(id in listOf("0","-1","1.0","local:other","12 OR 1=1","999999999999999999999999"))assertNull(OfflinePatrolReference.resolve("local:operation",id))}
}
