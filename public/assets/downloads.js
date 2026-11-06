(() => {
  'use strict';
  fetch('/downloads/guard-release.json',{cache:'no-store'}).then(async response=>{
    if(!response.ok)throw Error('No signed company release has been published here yet. Ask your administrator for the approved APK.');
    const release=await response.json();
    if(release.apk!=='sentinel-guard.apk'||!Number.isSafeInteger(release.versionCode)||release.versionCode<1||typeof release.versionName!=='string'||!/^[a-f0-9]{64}$/i.test(release.sha256)||!/^[a-f0-9]{64}$/i.test(release.signingCertificateSha256))throw Error('Release metadata is invalid. Ask your administrator to verify the distribution package.');
    document.getElementById('status').textContent='A company release is available.';
    const link=document.getElementById('apk');link.href='/downloads/sentinel-guard.apk';link.hidden=false;
    document.getElementById('version').textContent=`Version ${release.versionName} (build ${release.versionCode})`;
    document.getElementById('checksum').textContent=`SHA-256: ${release.sha256}`;
    document.getElementById('certificate').textContent=`Signing certificate SHA-256: ${release.signingCertificateSha256}`;
  }).catch(error=>{document.getElementById('status').textContent=error.message;});
})();
