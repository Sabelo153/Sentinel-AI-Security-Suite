// Requires Playwright and Microsoft Edge. Uses a local fixture HTTP server.
const fs=require('fs'),path=require('path'),http=require('http'),assert=require('node:assert/strict'),{chromium}=require('playwright');
(async()=>{
  const root=path.resolve(__dirname,'../../public');let posts=0,failOnce=true;const accepted=new Map(),errors=[];
  const template={form_id:1,site_id:1,form_code:'GATE',title:'Gate offline check',revision:1,display_language:'en',fields_json:JSON.stringify([{key:'gate',label:'Gate secure',type:'PASS_FAIL',required:true,photo_required:true,followup_on_fail:true}])};
  const server=http.createServer(async(req,res)=>{
    const url=new URL(req.url,'http://localhost');
    if(url.pathname.startsWith('/api/v1/')){
      res.setHeader('Content-Type','application/json');res.setHeader('Cache-Control','no-store');let data;
      if(url.pathname.endsWith('/auth/me'))data={user_id:req.headers.authorization==='Bearer other-user'?'3':'2'};
      else if(url.pathname.endsWith('/auth/tenant'))data={company_id:101};
      else if(url.pathname==='/api/v1/inspection-forms')data=[template];
      else if(url.pathname.endsWith('/submit')){let raw='';for await(const chunk of req)raw+=chunk;const body=JSON.parse(raw);posts++;if(!accepted.has(body.operation_uuid))accepted.set(body.operation_uuid,{id:'42',received:true});if(failOnce){failOnce=false;res.statusCode=503;return res.end(JSON.stringify({message:'Receipt connection interrupted'}));}data=accepted.get(body.operation_uuid);}
      else data={};return res.end(JSON.stringify({data}));
    }
    const file=path.resolve(root,'.'+url.pathname);if(!file.startsWith(root+path.sep)){res.statusCode=404;return res.end();}
    try{res.setHeader('Content-Type',file.endsWith('.html')?'text/html':file.endsWith('.js')?'text/javascript':file.endsWith('.css')?'text/css':file.endsWith('.webmanifest')?'application/manifest+json':'application/octet-stream');res.end(fs.readFileSync(file));}catch{res.statusCode=404;res.end();}
  });
  await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));const origin='http://127.0.0.1:'+server.address().port,browser=await chromium.launch({headless:true,channel:'msedge'});
  try {
    const context=await browser.newContext(),page=await context.newPage();page.on('pageerror',e=>errors.push(e.message));
    await page.addInitScript(()=>{if(!sessionStorage.getItem('sentinel_admin_token'))sessionStorage.setItem('sentinel_admin_token','fixture-token');});
    await page.goto(origin+'/admin/offline-inspections.html');await page.getByRole('status').filter({hasText:'Offline page ready'}).waitFor();
    await page.locator('#passphrase').fill('offline-test-passphrase');await page.getByRole('button',{name:'Unlock or create my vault'}).click();await page.locator('#workspace').waitFor({state:'visible'});
    await page.locator('#site').fill('1');await page.getByRole('button',{name:'Download current site forms for offline use'}).click();await page.locator('summary').filter({hasText:'Gate offline check'}).waitFor();
    await context.setOffline(true);await page.reload();await page.locator('#passphrase').fill('wrong-passphrase');await page.getByRole('button',{name:'Unlock or create my vault'}).click();await page.getByRole('status').filter({hasText:'incorrect'}).waitFor();
    await page.locator('#passphrase').fill('offline-test-passphrase');await page.getByRole('button',{name:'Unlock or create my vault'}).click();await page.locator('summary').filter({hasText:'Gate offline check'}).click();await page.getByLabel('Gate secure',{exact:true}).selectOption('FAIL');
    await page.locator('input[type=file]').setInputFiles({name:'gate.png',mimeType:'image/png',buffer:Buffer.from(await page.evaluate(()=>{const c=document.createElement('canvas');c.width=32;c.height=24;const x=c.getContext('2d');x.fillStyle='#176b77';x.fillRect(0,0,32,24);return c.toDataURL('image/png').split(',')[1];}),'base64')});
    await page.getByRole('button',{name:'Save inspection offline'}).click();await page.locator('#queue').getByText(/QUEUED/).waitFor({timeout:10000}).catch(async()=>{throw Error(await page.locator('body').innerText());});assert.equal(posts,0);
    await page.reload();await page.locator('#passphrase').fill('offline-test-passphrase');await page.getByRole('button',{name:'Unlock or create my vault'}).click();await page.locator('#queue').getByText(/QUEUED/).waitFor();
    const raw=await page.evaluate(async()=>{const db=await new Promise(resolve=>{const r=indexedDB.open('sentinel-inspection-vault',1);r.onsuccess=()=>resolve(r.result);});return new Promise(resolve=>{const r=db.transaction('accounts').objectStore('accounts').get('101:2');r.onsuccess=()=>resolve(new TextDecoder().decode(r.result.cipher));});});assert.equal(raw.includes('Gate offline check'),false);assert.equal(raw.includes('photo_base64'),false);
    await context.setOffline(false);await page.getByRole('status').filter({hasText:'Pending inspections retained'}).waitFor();assert.equal(accepted.size,1);await page.locator('#queue').getByText(/QUEUED/).waitFor();
    await page.getByRole('button',{name:'Sync now',exact:true}).click();await page.locator('#receipts').getByText(/server submission 42/).waitFor();await page.locator('#queue').getByText('No pending inspections.',{exact:true}).waitFor();assert.equal(accepted.size,1);assert.equal(posts,2);
    const concurrent=await page.evaluate(async()=>{
      const a=new SentinelInspectionVault({company_id:101,user_id:2}),b=new SentinelInspectionVault({company_id:101,user_id:2});await a.unlock('offline-test-passphrase');await b.unlock('offline-test-passphrase');
      await Promise.all([a.enqueue({form_id:1,site_id:1,title:'First',revision:1},{operation_uuid:crypto.randomUUID(),answers:{a:{value:'PASS'}}}),b.enqueue({form_id:1,site_id:1,title:'Second',revision:1},{operation_uuid:crypto.randomUUID(),answers:{a:{value:'FAIL'}}})]);
      const other=new SentinelInspectionVault({company_id:202,user_id:2});await other.unlock('different-passphrase');return {sameAccount:(await a.snapshot()).queue.length,otherAccount:(await other.snapshot()).queue.length};
    });assert.deepEqual(concurrent,{sameAccount:2,otherAccount:0});
    await page.evaluate(()=>sessionStorage.setItem('sentinel_admin_token','other-user'));await page.getByRole('status').filter({hasText:'login session changed'}).waitFor();await page.locator('#passphrase').fill('offline-test-passphrase');await page.getByRole('button',{name:'Unlock or create my vault'}).click();await page.getByRole('status').filter({hasText:'login changed'}).waitFor();
    const cached=await page.evaluate(async()=>{const names=await caches.keys();const urls=[];for(const n of names)for(const r of await(await caches.open(n)).keys())urls.push(r.url);return urls;});assert.equal(cached.some(u=>u.includes('/api/')),false);assert.deepEqual(errors,[]);
    console.log('PASS: offline shell reload, encrypted passphrase vault, cached required-photo form, durable pending capture, interrupted receipt retry without duplicate acceptance, atomic concurrent saves, account isolation and login-change lock.');
  }finally{await browser.close();server.closeAllConnections();await new Promise(resolve=>server.close(resolve));}
})().catch(e=>{console.error(e);process.exitCode=1;});
