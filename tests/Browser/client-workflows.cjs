// Run with Playwright installed and Microsoft Edge available:
// node tests/Browser/client-workflows.cjs
const fs=require('fs'),path=require('path'),assert=require('node:assert/strict'),{chromium}=require('playwright');
(async()=>{
  const browser=await chromium.launch({headless:true,channel:'msedge'});
  try {
    const page=await browser.newPage({viewport:{width:1440,height:1000}}),errors=[],posts=[];
    page.on('pageerror',e=>errors.push(e.message));
    const modules=['/site-workspace/sites','/site-access','/inspection-forms','/client-requests','/client/requests','/availability','/my-availability','/report-reviews','/client/report-reviews','/quotations','/client/quotations'];
    const permissions=['site_workspace.view','site_access.view','site_access.manage','inspection_forms.view','inspection_forms.manage','client_requests.view','client_requests.manage','client_requests.client','availability.view','availability.manage','availability.self','report_reviews.view','report_reviews.client','quotations.view','quotations.manage','quotations.client'];
    const template={form_id:1,form_code:'GATE',title:'Gate safety check',revision:1,fields_json:JSON.stringify([{key:'gate',label:'Gate secure',type:'PASS_FAIL',required:true,photo_required:true,followup_on_fail:true}])};
    const quote={quotation_id:1,client_id:1,proposed_site_name:'New site',title:'Monthly guarding',currency:'ZAR',subtotal_minor:10000,tax_minor:1500,total_minor:11500,tax_basis_points:1500,valid_until:'2027-01-01',state:'SHARED',revision:2,shared_hash:'quote-content-hash',terms:'Monthly service terms',lines:[{description:'Guard service',quantity:1,unit_minor:10000,amount_minor:10000}]};
    let quoteState='SHARED';
    await page.route('https://sentinel.test/**',async route=>{
      const url=new URL(route.request().url()),p=url.pathname.slice(7);
      if(url.pathname.startsWith('/api/v1/')){
        if(p==='/auth/login')return route.fulfill({json:{data:{token:'fixture-token'}}});
        if(p==='/auth/access')return route.fulfill({json:{data:{permissions,read_paths:modules.map(p=>'/api/v1'+p)}}});
        if(['POST','PATCH'].includes(route.request().method())){posts.push({path:p,body:route.request().postDataJSON()});return route.fulfill({json:{data:{id:'10',received:true}}});}
        const data={
          '/site-workspace/sites':[{site_id:1,site_name:'Assigned site',client_id:1}],
          '/site-workspace':{site:{site_id:1},instructions:{title:'Site procedures',instructions:'Keep the gate secured.',emergency_contacts:'Control room'},open_incidents:[],faults:[]},
          '/site-access':[{grant_id:1,user_id:2,site_id:1}],
          '/inspection-forms':[template],'/inspection-form-history':{submissions:[],followups:[]},
          '/client/care-sites':[{site_id:1,site_name:'Client site'}],
          '/client-requests':[{request_id:1,title:'Gate issue',state:'OPEN',revision:3}],
          '/client/requests':[{request_id:1,title:'Gate issue',state:'RESOLVED',revision:4}],
          '/client-requests/1/history':[{audience:'INTERNAL',message:'Private investigation'}],
          '/client/requests/1/history':[{audience:'CLIENT',message:'Gate repaired.'}],
          '/availability':[], '/my-availability':[], '/report-reviews':[],
          '/client/report-reviews':[{report_id:1,site_id:1,title:'Patrol summary',report_text:'All checkpoints visited.',published_at:'2026-11-03 06:00:00',report_hash:'exact-report-hash',my_acknowledgement:null}],
          '/quotations':[{...quote,state:quoteState}], '/client/quotations':[{...quote,state:quoteState}],
          '/quotations/1':{...quote,state:quoteState},'/client/quotations/1':{...quote,state:quoteState},
          '/site-onboardings':[{onboarding_id:1,site_id:4,state:'PREPARING',tasks:[{task_id:1,task_name:'Test checkpoint',revision:1,completed_at:null}]}]
        };
        return route.fulfill({json:{data:data[p]??[]}});
      }
      const publicRoot=path.resolve(__dirname,'../../public'),file=path.resolve(publicRoot,'.'+url.pathname);
      if(!file.startsWith(publicRoot+path.sep))return route.fulfill({status:404});
      try{return route.fulfill({body:fs.readFileSync(file),contentType:file.endsWith('.html')?'text/html':file.endsWith('.js')?'text/javascript':file.endsWith('.css')?'text/css':'application/octet-stream'});}catch{return route.fulfill({status:404});}
    });
    await page.goto('https://sentinel.test/admin/index.html');await page.locator('#identifier').fill('test');await page.locator('#password').fill('test-password');await page.getByRole('button',{name:'Sign in',exact:true}).click();
    const go=async name=>{await page.locator('#navSearch').fill(name);await page.locator('#nav').getByRole('button',{name,exact:true}).click();await page.locator('#content [role=status]').filter({hasText:'Ready.'}).waitFor();};
    const site=async()=>{await page.getByLabel('Site',{exact:true}).fill('1');await page.getByRole('button',{name:'Load site',exact:true}).click();await page.locator('#content [role=status]').filter({hasText:'Loaded.'}).waitFor();};
    const open=async title=>{const d=page.locator('details').filter({has:page.locator('summary').filter({hasText:title})}).first();await d.locator('summary').click();return d;};
    const submit=async(d,name)=>{const response=page.waitForResponse(r=>r.request().method()==='POST'&&r.url().includes('/api/v1/'));await d.getByRole('button',{name,exact:true}).click();await response;};
    await go('Assigned site workspace');await page.getByRole('button',{name:'Assigned site',exact:true}).click();await page.getByText('Keep the gate secured.',{exact:true}).waitFor();
    await go('Site access assignments');let d=await open('Set role site restriction');await d.getByLabel('Role Id').fill('2');await d.getByLabel('Restricted',{exact:true}).selectOption('Restricted to assigned sites');await submit(d,'Set role site restriction');assert.equal(posts.at(-1).body.restricted,true);
    await go('Custom inspection forms');await site();d=await open('Publish inspection form');await d.getByLabel('Form Code').fill('GATE');await d.getByLabel('Title',{exact:true}).fill('Night gate');await d.getByLabel('Key',{exact:true}).fill('gate');await d.getByLabel('Label',{exact:true}).fill('Secure gate');await d.getByLabel('Photo Required',{exact:true}).check();await d.getByLabel('Followup On Fail',{exact:true}).check();await submit(d,'Publish inspection form');assert.equal(posts.at(-1).body.fields[0].photo_required,true);assert.equal(posts.at(-1).body.fields[0].followup_on_fail,true);
    d=await open('Gate safety check');await d.getByLabel('Gate secure',{exact:true}).selectOption('FAIL');await d.locator('input[type=file]').setInputFiles({name:'gate.png',mimeType:'image/png',buffer:Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aR1cAAAAASUVORK5CYII=','base64')});await submit(d,'Submit inspection');assert.equal(posts.at(-1).body.answers.gate.value,'FAIL');assert.ok(posts.at(-1).body.operation_uuid);assert.ok(posts.at(-1).body.answers.gate.photo_base64);
    await go('Client requests and complaints');await site();d=await open('Reply request #1');await d.getByLabel('Message').fill('Private note');await d.getByLabel('Audience').selectOption('INTERNAL');await submit(d,'Reply request #1');assert.equal(posts.at(-1).body.audience,'INTERNAL');assert.equal(posts.at(-1).body.expected_revision,3);
    await go('My requests and complaints');await site();d=await open('Confirm request #1');assert.equal(await d.getByLabel('Audience').count(),0);await d.getByLabel('Message').fill('Confirmed fixed');await submit(d,'Confirm request #1');assert.equal(posts.at(-1).path,'/client/requests/1/confirm');assert.equal(posts.at(-1).body.expected_revision,4);
    await go('My leave and availability');d=await open('Request leave or unavailability');assert.equal(await d.getByLabel('Employee Id').count(),0);await d.getByLabel('Starts At').fill('2027-01-01 08:00:00');await d.getByLabel('Ends At').fill('2027-01-01 18:00:00');await d.getByLabel('Reason').fill('Annual leave');await submit(d,'Request leave or unavailability');assert.equal('employee_id'in posts.at(-1).body,false);
    await go('Read and acknowledge reports');await page.getByRole('button',{name:'I have read report #1',exact:true}).click();await page.getByRole('button',{name:'I have read report #1',exact:true}).waitFor({state:'visible'});assert.ok(posts.some(p=>p.path.endsWith('/acknowledge')&&p.body.expected_hash==='exact-report-hash'));
    await go('My service quotations');d=await open('Accept quotation #1');await d.getByLabel('Notes').fill('We accept these terms.');await submit(d,'Accept quotation #1');assert.equal(posts.at(-1).body.expected_hash,'quote-content-hash');
    quoteState='ACCEPTED';await go('Quotations and mobilisation');d=await open('Create contract and site from quotation #1');for(const [k,v]of Object.entries({'Branch Id':'1','Contract Number':'CON-123','Start Date':'2027-01-01','End Date':'2027-12-31','Required Guards':'2','Address':'Client location'}))await d.getByLabel(k,{exact:true}).fill(v);await submit(d,'Create contract and site from quotation #1');assert.equal(posts.at(-1).path,'/quotations/1/convert');d=await open('Complete check #1: Test checkpoint');await d.getByLabel('Evidence Reference').fill('Commissioning test 123');await submit(d,'Complete check #1: Test checkpoint');assert.equal(posts.at(-1).body.expected_revision,1);
    assert.deepEqual(errors,[]);console.log('PASS: assigned-site workspace, scope roles, typed inspection builder/photo submission, internal/client actions, self leave, exact report acknowledgement, quotation acceptance/conversion and mobilisation evidence.');
  } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
