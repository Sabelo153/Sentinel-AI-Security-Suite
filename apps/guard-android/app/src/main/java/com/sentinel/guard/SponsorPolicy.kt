package com.sentinel.guard
import java.net.URI
object SponsorPolicy {
    fun canShow(enabled:Boolean,plan:String?):Boolean=enabled&&plan=="FREE"
    fun destination(base:String,value:String):String?=try{
        val relative=value.startsWith("/")&&!value.startsWith("//")&&!value.contains('\\')&&!value.any{it.code<=32}
        val uri=if(relative)URI(base).resolve(value)else URI(value)
        if(uri.userInfo!=null||uri.host.isNullOrBlank()||(!relative&&uri.scheme!="https")||(relative&&uri.scheme !in listOf("https","http")))null else uri.toString()
    }catch(_:Exception){null}
}
