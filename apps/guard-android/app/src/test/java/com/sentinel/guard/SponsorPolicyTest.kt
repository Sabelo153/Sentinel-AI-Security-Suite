package com.sentinel.guard
import org.junit.Assert.*
import org.junit.Test
class SponsorPolicyTest {
    @Test fun onlyExplicitFreeCanShowAds(){assertTrue(SponsorPolicy.canShow(true,"FREE"));for(plan in listOf("ESSENTIAL","PROFESSIONAL","ENTERPRISE","UNKNOWN","",null)){assertFalse(SponsorPolicy.canShow(true,plan))};assertFalse(SponsorPolicy.canShow(false,"FREE"))}
    @Test fun destinationsRejectExecutableAndCredentialUrls(){for(url in listOf("javascript:alert(1)","https://user:password@example.com/","//example.com/","http://example.com/","/\\example.com")){assertNull(SponsorPolicy.destination("https://company.example",url))}}
    @Test fun companyRelativeAndHttpsSponsorsAreAllowed(){assertEquals("https://company.example/admin/index.html#company-workspace",SponsorPolicy.destination("https://company.example","/admin/index.html#company-workspace"));assertEquals("https://sponsor.example/",SponsorPolicy.destination("https://company.example","https://sponsor.example/"))}
}
