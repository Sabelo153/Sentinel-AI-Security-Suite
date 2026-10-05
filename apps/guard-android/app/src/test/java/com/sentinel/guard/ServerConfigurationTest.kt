package com.sentinel.guard

import java.net.URLEncoder
import org.junit.Assert.assertEquals
import org.junit.Assert.assertThrows
import org.junit.Test

class ServerConfigurationTest {
    @Test fun acceptsBakedAddressAndCompanySetupCode() {
        assertEquals("https://security.example.com",ServerConfiguration.origin("https://security.example.com/"))
        val encoded=URLEncoder.encode("https://security.example.com",Charsets.UTF_8)
        assertEquals("https://security.example.com",ServerConfiguration.origin("sentinel://connect?server=$encoded"))
    }
    @Test fun rejectsInsecureOrCredentialBearingAddresses() {
        for(value in listOf("http://security.example.com","https://user:pass@security.example.com","https://security.example.com/path")) assertThrows(IllegalArgumentException::class.java){ServerConfiguration.origin(value)}
    }
    @Test fun debugModeAcceptsOnlyPrivateHttpAddresses() {
        assertEquals("http://192.168.1.187",ServerConfiguration.origin("http://192.168.1.187",true))
        assertEquals("http://10.0.0.4",ServerConfiguration.origin("http://10.0.0.4",true))
        assertThrows(IllegalArgumentException::class.java){ServerConfiguration.origin("http://example.com",true)}
        assertThrows(IllegalArgumentException::class.java){ServerConfiguration.origin("http://192.168.1.187/path",true)}
    }
    @Test fun buildsSameOriginSignupWhenNoDedicatedUrlExists() {
        assertEquals("https://security.example.com/admin/index.html?signup=FREE",ServerConfiguration.signupUrl("https://security.example.com",""))
        assertEquals("https://join.example.com/start",ServerConfiguration.signupUrl("","https://join.example.com/start"))
        assertEquals("http://192.168.1.187/admin/index.html?signup=FREE",ServerConfiguration.signupUrl("http://192.168.1.187","",true))
    }
}
