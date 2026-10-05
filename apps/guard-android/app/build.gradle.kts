plugins { id("com.android.application") }
val sentinelServerUrl=providers.gradleProperty("SENTINEL_SERVER_URL").orElse(providers.environmentVariable("SENTINEL_SERVER_URL")).orElse("").get()
val sentinelSignupUrl=providers.gradleProperty("SENTINEL_SIGNUP_URL").orElse(providers.environmentVariable("SENTINEL_SIGNUP_URL")).orElse("").get()
val releaseStoreFile=providers.gradleProperty("SENTINEL_RELEASE_STORE_FILE").orElse(providers.environmentVariable("SENTINEL_RELEASE_STORE_FILE")).orElse("").get()
val releaseStorePassword=providers.gradleProperty("SENTINEL_RELEASE_STORE_PASSWORD").orElse(providers.environmentVariable("SENTINEL_RELEASE_STORE_PASSWORD")).orElse("").get()
val releaseKeyAlias=providers.gradleProperty("SENTINEL_RELEASE_KEY_ALIAS").orElse(providers.environmentVariable("SENTINEL_RELEASE_KEY_ALIAS")).orElse("").get()
val releaseKeyPassword=providers.gradleProperty("SENTINEL_RELEASE_KEY_PASSWORD").orElse(providers.environmentVariable("SENTINEL_RELEASE_KEY_PASSWORD")).orElse("").get()
val releaseRequested=gradle.startParameter.taskNames.any { it.contains("release", ignoreCase = true) }
fun buildString(value:String)="\""+value.replace("\\","\\\\").replace("\"","\\\"")+"\""

if (releaseRequested) {
    require(sentinelServerUrl.startsWith("https://")) { "Release builds require an HTTPS SENTINEL_SERVER_URL." }
    require(sentinelSignupUrl.startsWith("https://")) { "Release builds require an HTTPS SENTINEL_SIGNUP_URL." }
    require(listOf(releaseStoreFile,releaseStorePassword,releaseKeyAlias,releaseKeyPassword).all { it.isNotBlank() }) {
        "Release builds require a signing keystore. See release-signing.properties.example."
    }
}
android {
    namespace = "com.sentinel.guard"
    compileSdk = 37
    defaultConfig {
        applicationId = "com.sentinel.guard"; minSdk = 26; targetSdk = 37; versionCode = 14; versionName = "0.6.4"
        buildConfigField("String","SENTINEL_SERVER_URL",buildString(sentinelServerUrl))
        buildConfigField("String","SENTINEL_SIGNUP_URL",buildString(sentinelSignupUrl))
        manifestPlaceholders["allowCleartextTraffic"] = "false"
    }
    signingConfigs {
        create("sentinelRelease") {
            if (releaseStoreFile.isNotBlank()) storeFile = file(releaseStoreFile)
            storePassword = releaseStorePassword
            keyAlias = releaseKeyAlias
            keyPassword = releaseKeyPassword
        }
    }
    buildTypes {
        getByName("debug") { manifestPlaceholders["allowCleartextTraffic"] = "true" }
        getByName("release") {
            manifestPlaceholders["allowCleartextTraffic"] = "false"
            signingConfig = signingConfigs.getByName("sentinelRelease")
        }
    }
    buildFeatures { buildConfig = true }
    compileOptions { sourceCompatibility = JavaVersion.VERSION_17; targetCompatibility = JavaVersion.VERSION_17 }
    lint { abortOnError = true }
}
dependencies {
    implementation("com.google.android.gms:play-services-code-scanner:16.1.0")
    // Bundled QR model keeps checkpoint scanning available without Google Play services or network access.
    implementation("com.google.mlkit:barcode-scanning:17.3.0")
    testImplementation("junit:junit:4.13.2")
}
