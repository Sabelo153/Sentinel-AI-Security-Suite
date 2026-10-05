package com.sentinel.guard

import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

class GuardHelpGuidesTest {
    @Test fun guardGuidesCoverDailyWorkAndRemainUnique() {
        val topics=GuardHelpGuides.all.map{it.topic}
        assertEquals(topics.size,topics.toSet().size)
        assertTrue(topics.containsAll(listOf("shift_duties","patrols_checkpoints","report_writing","incidents","handover","emergency_escalation")))
        assertTrue(GuardHelpGuides.all.all{it.title.isNotBlank()&&it.summary.length>=60})
    }
}
