package com.sentinel.guard

/** Pure policy validation shared by enforcement and unit tests. */
object ManagedPolicy {
    const val GUARD = "com.sentinel.guard"
    fun packages(values: List<String>): List<String> {
        require(values.size <= 31) { "Too many allowed apps" }
        val reserved = setOf("com.android.settings", "com.android.packageinstaller", "com.google.android.packageinstaller", "com.android.permissioncontroller")
        require(values.all { it.length <= 180 && it.matches(Regex("[A-Za-z][A-Za-z0-9_]*(\\.[A-Za-z][A-Za-z0-9_]*)+")) && it !in reserved }) { "Unsafe app package in policy" }
        return (listOf(GUARD) + values).distinct()
    }
    fun needsApply(revision: Int, recoveryRevision: Int): Boolean = revision > recoveryRevision
}
