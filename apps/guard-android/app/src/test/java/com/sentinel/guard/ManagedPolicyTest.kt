package com.sentinel.guard
import org.junit.Assert.*
import org.junit.Test
class ManagedPolicyTest {
    @Test fun guardAlwaysRemainsAllowed() { assertEquals(listOf(ManagedPolicy.GUARD,"com.example.radio"),ManagedPolicy.packages(listOf("com.example.radio","com.example.radio"))) }
    @Test(expected=IllegalArgumentException::class) fun settingsCannotBeAllowed() { ManagedPolicy.packages(listOf("com.android.settings")) }
    @Test(expected=IllegalArgumentException::class) fun malformedPackagesRejected() { ManagedPolicy.packages(listOf("../settings")) }
    @Test fun recoveryPersistsUntilNewRevision() { assertFalse(ManagedPolicy.needsApply(4,4));assertFalse(ManagedPolicy.needsApply(3,4));assertTrue(ManagedPolicy.needsApply(5,4)) }
}
