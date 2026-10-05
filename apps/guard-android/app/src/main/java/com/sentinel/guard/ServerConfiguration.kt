package com.sentinel.guard

import java.net.URI
import java.net.URLDecoder

object ServerConfiguration {
    fun origin(value: String, allowPrivateHttp: Boolean = false): String {
        val trimmed=value.trim()
        val candidate=if(trimmed.startsWith("sentinel://connect?",true)) {
            val query=URI(trimmed).rawQuery.orEmpty().split('&').mapNotNull { part ->
                val pieces=part.split('=',limit=2)
                if(pieces.size==2) URLDecoder.decode(pieces[0],"UTF-8") to URLDecoder.decode(pieces[1],"UTF-8") else null
            }.toMap()
            query["server"] ?: throw IllegalArgumentException("This company setup code is incomplete.")
        } else trimmed
        val uri=URI(candidate.trim().trimEnd('/'))
        val secure=uri.scheme=="https"
        val privateTest=allowPrivateHttp&&uri.scheme=="http"&&isPrivateHost(uri.host.orEmpty())
        require((secure||privateTest)&&!uri.host.isNullOrBlank()&&uri.userInfo==null&&uri.query==null&&uri.fragment==null&&(uri.path.isNullOrEmpty()||uri.path=="/")) { "The company connection must be a valid HTTPS address." }
        return "${uri.scheme}://${uri.rawAuthority}"
    }

    fun signupUrl(origin: String, configuredUrl: String, allowPrivateHttp: Boolean = false): String {
        val selected=configuredUrl.trim().ifBlank { origin.trimEnd('/')+"/admin/index.html?signup=FREE" }
        val uri=URI(selected)
        val secure=uri.scheme=="https"
        val privateTest=allowPrivateHttp&&uri.scheme=="http"&&isPrivateHost(uri.host.orEmpty())
        require((secure||privateTest)&&!uri.host.isNullOrBlank()&&uri.userInfo==null) { "Organisation signup is not configured for this app." }
        return selected
    }

    private fun isPrivateHost(host: String): Boolean {
        if(host.equals("localhost",true)||host=="127.0.0.1") return true
        val parts=host.split('.').mapNotNull{it.toIntOrNull()}
        if(parts.size!=4||parts.any{it !in 0..255}) return false
        return parts[0]==10||parts[0]==192&&parts[1]==168||parts[0]==172&&parts[1] in 16..31
    }
}
