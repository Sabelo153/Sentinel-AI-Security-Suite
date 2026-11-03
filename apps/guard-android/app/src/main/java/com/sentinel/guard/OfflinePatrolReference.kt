package com.sentinel.guard

/** IDs received from the server are required before dependent operations upload. */
object OfflinePatrolReference {
    fun resolve(reference:String,mappedServerId:String?):String? {
        val id=if(reference.startsWith("local:"))mappedServerId else reference
        return id?.takeIf{Regex("[1-9][0-9]{0,18}").matches(it)}
    }
}
