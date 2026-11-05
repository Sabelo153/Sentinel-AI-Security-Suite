'use strict';
const CACHE='sentinel-offline-inspections-v1';
const FILES=['/admin/offline-inspections.html','/admin/offline-inspection-vault.js','/admin/offline-inspections.js','/admin/admin.css?v=6','/admin/workspace.css?v=1','/admin/inspection-offline.webmanifest'];
self.addEventListener('install',event=>event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(FILES))));
self.addEventListener('activate',event=>event.waitUntil((async()=>{for(const key of await caches.keys())if(key.startsWith('sentinel-offline-inspections-')&&key!==CACHE)await caches.delete(key);await self.clients.claim();})()));
self.addEventListener('fetch',event=>{const url=new URL(event.request.url);if(event.request.method!=='GET'||url.origin!==self.location.origin||!FILES.includes(url.pathname+url.search))return;event.respondWith((async()=>{try{const response=await fetch(event.request);if(response.ok){const cache=await caches.open(CACHE);await cache.put(event.request,response.clone());}return response;}catch{const cached=await caches.match(event.request);return cached||Response.error();}})());});
