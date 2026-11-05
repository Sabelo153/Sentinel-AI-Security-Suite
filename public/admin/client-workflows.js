(() => {
  'use strict';
  const modules = [
    ['site-workspace','Assigned site workspace','/site-workspace/sites'],
    ['site-access','Site access assignments','/site-access'],
    ['inspection-forms','Custom inspection forms','/inspection-forms'],
    ['client-care','Client requests and complaints','/client-requests'],
    ['my-client-care','My requests and complaints','/client/requests'],
    ['availability','Leave and availability','/availability'],
    ['my-availability','My leave and availability','/my-availability'],
    ['report-reviews','Client report acknowledgements','/report-reviews'],
    ['my-report-reviews','Read and acknowledge reports','/client/report-reviews'],
    ['quotations','Quotations and mobilisation','/quotations'],
    ['my-quotations','My service quotations','/client/quotations']
  ];
  const label = s => s.replaceAll('_',' ').replace(/\b\w/g,c=>c.toUpperCase());
  function build({node,request,table,content}) {
    let generation=0;
    async function render(view) {
      const turn=++generation,root=node('section','','section'),status=node('p','Loading…');
      status.setAttribute('role','status');root.append(status);content.replaceChildren(root);
      const active=()=>turn===generation;
      try {
        const get=async path=>(await request(path)).payload.data;
        const permissions=(await get('/auth/access')).permissions;if(!active())return;
        const can=p=>permissions.includes(p),show=(title,rows)=>root.append(node('h2',title),table(rows));
        const note=text=>root.append(node('p',text));
        const post=async(path,body={},method='POST')=>(await request(path,{method,body:JSON.stringify(body)})).payload.data;
        function form(title,fields,submit,initial={}) {
          const d=node('details','','section'),f=node('form','','form-stack'),message=node('p'),inputs={};
          d.append(node('summary',title),f,message);root.append(d);
          for(const [key,type] of Object.entries(fields)) {
            const l=node('label',label(key));let input;
            if(Array.isArray(type)){input=node('select');for(const v of type){const o=node('option',v);o.value=v;input.append(o);}}
            else {input=node(type==='long'?'textarea':'input');if(type!=='long')input.type=type==='utc'?'text':type;if(type==='utc')input.placeholder='UTC YYYY-MM-DD HH:MM:SS';if(type==='number'){input.step='1';input.min='0';}}
            input.required=true;input.setAttribute('aria-label',label(key));if(initial[key]!==undefined)input.value=initial[key];l.append(input);f.append(l);inputs[key]=input;
          }
          const save=node('button',title,'primary');f.append(save);
          f.onsubmit=async e=>{e.preventDefault();save.disabled=true;try {const body=Object.fromEntries(Object.entries(inputs).map(([k,v])=>[k,v.value]));await submit(body);if(active()){status.textContent='Saved. Refresh to load current records.';message.textContent='Saved.';}}catch(e){if(active())message.textContent=e.message;}finally{save.disabled=false;}};
          return {d,f,inputs,save,message};
        }
        function action(title,path,body={}) {const b=node('button',title,'secondary');root.append(b);b.onclick=async()=>{b.disabled=true;try{await post(path,body);if(active())await render(view);}catch(e){if(active())status.textContent=e.message;b.disabled=false;}};}
        function sitePicker(load,client=false) {
          const f=node('form','','form-stack'),l=node('label','Site'),input=node('input'),b=node('button','Load site','primary');input.type='number';input.min='1';input.required=true;input.setAttribute('aria-label','Site');l.append(input);f.append(l,b);root.append(f);
          if(client)get('/client/care-sites').then(rows=>{if(active())show('My client sites',rows);}).catch(e=>{if(active())status.textContent=e.message;});
          f.onsubmit=async e=>{e.preventDefault();b.disabled=true;try {await load(input.value);if(active()){f.remove();status.textContent='Loaded.';}}catch(e){if(active())status.textContent=e.message;}finally{b.disabled=false;}};
        }
        const refresh=node('button','Refresh','secondary');refresh.onclick=()=>render(view);root.append(refresh);
        note('Changes require a connection. Dates and times on these pages are UTC. Refresh after saving to use the latest revision.');
        if(view==='site-workspace') {
          const sites=await get('/site-workspace/sites');show('Accessible sites',sites);
          for(const site of sites){const b=node('button',site.site_name,'secondary');root.append(b);b.onclick=async()=>{try{const data=await get('/site-workspace?site_id='+site.site_id);if(active()){root.append(node('h2',site.site_name));if(data.instructions){root.append(node('h3',data.instructions.title),node('pre',data.instructions.instructions),node('pre',data.instructions.emergency_contacts));}show('Open incident references',data.open_incidents);show('Open faults',data.faults);}}catch(e){if(active())status.textContent=e.message;}};}
          note('Restricted accounts can use only assigned sites and scope-aware workflows. Company-wide pages and exports are blocked for the entire account.');
        } else if(view==='site-access') {
          const rows=await get('/site-access');show('Site grants',rows);
          if(can('site_access.manage')) {
            form('Assign site',{user_id:'number',site_id:'number'},b=>post('/site-access',b));
            form('Set role site restriction',{role_id:'number',restricted:['Restricted to assigned sites','Unrestricted']},b=>post('/site-access/role',{role_id:b.role_id,restricted:b.restricted==='Restricted to assigned sites'}));
            for(const row of rows)action(`Revoke grant #${row.grant_id}`,`/site-access/${row.grant_id}/revoke`);
          }
          note('Use a dedicated supervisor or contractor role. Grant its normal workflow permissions, enable its restriction, then assign sites. With no assigned sites it has no site access. The restriction also applies when the user has other roles.');
        } else if(view==='inspection-forms') {
          const offline=node('a','Open passphrase-protected offline inspections');offline.href='offline-inspections.html';root.append(offline);note('Prepare the offline page and download forms while connected before leaving coverage.');
          sitePicker(async site=>{
            const forms=await get('/inspection-forms?site_id='+site);show('Latest form revisions',forms.map(({fields_json,...r})=>r));
            if(can('inspection_forms.manage')) {
              const editor=form('Publish inspection form',{form_code:'text',title:'text',expected_revision:'number'},b=>post('/inspection-forms',{...b,site_id:site,fields:fieldRows.map(r=>({key:r.key.value,label:r.label.value,type:r.type.value,required:r.required.checked,photo_required:r.photo_required.checked,followup_on_fail:r.followup_on_fail.checked}))}),{expected_revision:0});
              const rows=node('div'),add=node('button','Add inspection field','secondary'),fieldRows=[];add.type='button';editor.f.insertBefore(rows,editor.save);editor.f.insertBefore(add,editor.save);
              function addField(){const box=node('fieldset'),r={};box.append(node('legend',`Field ${fieldRows.length+1}`));for(const k of ['key','label','type','required','photo_required','followup_on_fail']){const l=node('label',label(k));let input;if(k==='type'){input=node('select');for(const t of ['PASS_FAIL','CHECK','TEXT','NUMBER']){const o=node('option',t);o.value=t;input.append(o);}}else{input=node('input');input.type=['required','photo_required','followup_on_fail'].includes(k)?'checkbox':'text';input.required=input.type==='text';}input.setAttribute('aria-label',label(k));if(k==='required')input.checked=true;l.append(input);box.append(l);r[k]=input;}const remove=node('button','Remove field');remove.type='button';remove.onclick=()=>{fieldRows.splice(fieldRows.indexOf(r),1);box.remove();};box.append(remove);fieldRows.push(r);rows.append(box);}
              add.onclick=addField;addField();note('Use stable lowercase field keys. Expected revision is 0 for a new form code; use its latest revision to publish a replacement. Existing submissions retain their original form.');
            }
            for(const template of forms) {
              const fields=JSON.parse(template.fields_json),d=node('details','','section'),f=node('form','','form-stack'),message=node('p'),answers={};
              d.append(node('summary',`${template.title} · revision ${template.revision}`),f,message);root.append(d);
              for(const field of fields) {
                const l=node('label',field.label);let input;
                if(['CHECK','PASS_FAIL'].includes(field.type)){input=node('select');for(const [value,text]of [['','Choose…'],...(field.type==='CHECK'?[['true','Yes'],['false','No']]:[['PASS','Pass'],['FAIL','Fail']])]){const o=node('option',text);o.value=value;input.append(o);}}
                else {input=node(field.type==='TEXT'?'textarea':'input');if(field.type==='NUMBER'){input.type='number';input.step='any';}}
                input.required=field.required||field.photo_required;input.setAttribute('aria-label',field.label);l.append(input);f.append(l);let photo;
                if(field.photo_required){const pl=node('label',field.label+' · required photo (JPEG/PNG, 128 KiB maximum)'),p=node('input');p.type='file';p.accept='image/jpeg,image/png';p.required=true;pl.append(p);f.append(pl);photo=p;}
                answers[field.key]={field,input,photo};
              }
              const save=node('button','Submit inspection','primary');f.append(save);const uuid=crypto.randomUUID();let pending;
              f.onsubmit=async e=>{e.preventDefault();save.disabled=true;try{if(!pending){const values={};for(const [key,{field,input,photo}]of Object.entries(answers)){if(input.value===''&&!field.required&&!photo)continue;const answer={value:field.type==='NUMBER'?Number(input.value):field.type==='CHECK'?input.value==='true':input.value};if(photo){const file=photo.files[0];if(!file||file.size>131072)throw Error('Choose a photo no larger than 128 KiB.');const bytes=new Uint8Array(await file.arrayBuffer());let raw='';for(const byte of bytes)raw+=String.fromCharCode(byte);answer.photo_base64=btoa(raw);}values[key]=answer;}pending={operation_uuid:uuid,answers:values};}const receipt=await post(`/inspection-forms/${template.form_id}/submit`,pending);if(active()){message.textContent=`Saved submission #${receipt.id}. Refresh to view follow-ups.`;for(const input of f.elements)input.disabled=true;}}catch(e){if(active())message.textContent=e.message+' Retry sends the same inspection; refresh to start a different one.';save.disabled=false;}};
            }
            const history=await get('/inspection-form-history?site_id='+site);show('Submission history',history.submissions.map(({photos,...r})=>r));show('Failed-check follow-ups',history.followups);
            for(const s of history.submissions)for(const photo of s.photos){const b=node('button',`View photo · submission ${s.submission_id} · ${photo.field_key}`,'secondary');root.append(b);b.onclick=async()=>{try{const {response}=await request('/inspection-photos/'+photo.photo_id);const url=URL.createObjectURL(await response.blob());if(active()){const img=node('img');img.alt=photo.field_key;img.style.maxWidth='100%';img.onload=()=>URL.revokeObjectURL(url);img.src=url;root.append(img);}else URL.revokeObjectURL(url);}catch(e){if(active())status.textContent=e.message;}};}
            if(can('inspection_forms.manage'))for(const task of history.followups)if(task.state==='OPEN')form(`Resolve follow-up #${task.followup_id}`,{resolution:'long'},b=>post(`/inspection-followups/${task.followup_id}/resolve`,b));
          });
        } else if(['client-care','my-client-care'].includes(view)) {
          const client=view==='my-client-care',base=client?'/client/requests':'/client-requests';
          sitePicker(async site=>{
            const rows=await get(base+'?site_id='+site);show('Requests and response deadlines',rows);
            if(client||can('client_requests.manage'))form('Create request or complaint',{request_type:['REQUEST','COMPLAINT'],title:'text',description:'long'},b=>post(base,{...b,site_id:site}));
            for(const row of rows){show(`#${row.request_id} · ${row.title}`,await get(`${base}/${row.request_id}/history`));if(row.state==='CLOSED')continue;
              if(client||can('client_requests.manage')) {
                const actions=client?(row.state==='RESOLVED'?['reply','confirm','reopen']:['reply']):(row.state==='OPEN'?['begin','reply','resolve']:row.state==='IN_PROGRESS'?['reply','resolve']:['reply']);
                for(const actionName of actions)form(`${label(actionName)} request #${row.request_id}`,client||actionName==='resolve'?{message:'long'}:{message:'long',audience:['CLIENT','INTERNAL']},b=>post(`${base}/${row.request_id}/${actionName}`,{...b,expected_revision:row.revision}));
              }
            }
            note('The first public company response is tracked against a 24-hour deadline. Internal notes remain private. Only a mapped client can confirm resolution and close a request.');
          },client);
        } else if(['availability','my-availability'].includes(view)) {
          const self=view==='my-availability',base=self?'/my-availability':'/availability',rows=await get(base);show('Availability requests',rows);
          if(self||can('availability.manage')){form('Request leave or unavailability',{...(self?{}:{employee_id:'number'}),request_type:['LEAVE','UNAVAILABLE'],starts_at:'utc',ends_at:'utc',reason:'long'},b=>post(base,b));for(const row of rows)for(const a of row.state==='REQUESTED'?(self?['cancel']:['approve','reject','cancel']):row.state==='APPROVED'?['cancel']:[])form(`${label(a)} absence #${row.availability_id}`,{notes:'long'},b=>post(`${base}/${row.availability_id}/${a}`,{...b,expected_revision:row.revision}));}
          note('Arrange relief or cancel overlapping shifts before approving absence. Approved periods block new or changed active shifts and relief assignments.');
        } else if(view==='report-reviews') {
          sitePicker(async site=>show('Explicit client acknowledgements',await get('/report-reviews?site_id='+site)));note('Acknowledgement records identify the exact report content. Delivery and email acceptance do not mean that a client has read a report.');
        } else if(view==='my-report-reviews') {
          for(const report of await get('/client/report-reviews')){root.append(node('h2',report.title),node('p',`Site ${report.site_id} · ${report.published_at} UTC`),node('pre',report.report_text));if(report.my_acknowledgement)note('Acknowledged '+report.my_acknowledgement.acknowledged_at+' UTC');else action(`I have read report #${report.report_id}`,`/client/report-reviews/${report.report_id}/acknowledge`,{expected_hash:report.report_hash});if(can('client_requests.client'))form(`Ask about report #${report.report_id}`,{title:'text',description:'long'},b=>post('/client/requests',{...b,site_id:report.site_id,related_report_id:report.report_id,request_type:'REPORT_QUESTION'}));}
        } else if(['quotations','my-quotations'].includes(view)) {
          const client=view==='my-quotations',base=client?'/client/quotations':'/quotations',rows=await get(base);show('Service quotations',rows);
          function quoteEditor(title,initial={},id=null){const lines=[],editor=form(title,{client_id:'number',proposed_site_name:'text',title:'text',currency:['ZAR','USD','EUR','GBP','NAD','BWP'],tax_basis_points:'number',valid_until:'date',terms:'long'},b=>post(id?`/quotations/${id}`:'/quotations',{...b,...(id?{expected_revision:initial.revision}:{}),lines:lines.map(r=>({description:r.description.value,quantity:Number(r.quantity.value),unit_minor:Number(r.unit_minor.value)}))},id?'PATCH':'POST'),initial);const lineBox=node('div'),add=node('button','Add monthly service line','secondary');add.type='button';editor.f.insertBefore(lineBox,editor.save);editor.f.insertBefore(add,editor.save);function addLine(value={}){const box=node('fieldset'),r={};box.append(node('legend','Monthly service line'));for(const k of ['description','quantity','unit_minor']){const l=node('label',label(k)),i=node('input');i.type=k==='description'?'text':'number';i.required=true;if(i.type==='number'){i.min=k==='quantity'?'1':'0';i.step='1';}i.value=value[k]??(k==='quantity'?1:'');l.append(i);box.append(l);r[k]=i;}const remove=node('button','Remove line');remove.type='button';remove.onclick=()=>{lines.splice(lines.indexOf(r),1);box.remove();};box.append(remove);lineBox.append(box);lines.push(r);}add.onclick=()=>addLine();for(const line of initial.lines??[{}])addLine(line);}
          if(!client&&can('quotations.manage')){note('Monthly services only. Enter unit prices in minor units: 10000 means R100.00 in ZAR. Tax uses basis points: 1500 means 15%.');quoteEditor('Create quotation',{currency:'ZAR',tax_basis_points:0});}
          for(const row of rows){const q=await get(`${base}/${row.quotation_id}`);root.append(node('h2',q.title),node('p',`${q.currency} ${(q.total_minor/100).toFixed(2)} per month · ${q.state}`),table(q.lines.map(l=>({description:l.description,quantity:l.quantity,unit_price:q.currency+' '+(l.unit_minor/100).toFixed(2),line_total:q.currency+' '+(l.amount_minor/100).toFixed(2)}))),node('p',`Subtotal ${q.currency} ${(q.subtotal_minor/100).toFixed(2)} · tax ${q.currency} ${(q.tax_minor/100).toFixed(2)}`),node('pre',q.terms),node('p',`Valid through ${q.valid_until} UTC`));
            if(client&&q.state==='SHARED')for(const decision of ['accept','decline'])form(`${label(decision)} quotation #${q.quotation_id}`,{notes:'long'},b=>post(`/client/quotations/${q.quotation_id}/${decision}`,{...b,expected_hash:q.shared_hash}));
            if(!client&&can('quotations.manage')){if(q.state==='DRAFT'){quoteEditor(`Edit quotation #${q.quotation_id}`,q,q.quotation_id);action(`Share quotation #${q.quotation_id} to client portal`,`/quotations/${q.quotation_id}/share`,{expected_revision:q.revision});}if(q.state==='ACCEPTED')form(`Create contract and site from quotation #${q.quotation_id}`,{branch_id:'number',contract_number:'text',start_date:'date',end_date:'date',required_guards:'number',address:'long'},b=>post(`/quotations/${q.quotation_id}/convert`,b));}
          }
          if(!client){const onboardings=await get('/site-onboardings');for(const o of onboardings){root.append(node('h2',`Mobilisation #${o.onboarding_id} · site ${o.site_id} · ${o.state}`));show('Readiness checks',o.tasks);if(o.state==='PREPARING'&&can('quotations.manage')){for(const t of o.tasks)if(!t.completed_at)form(`Complete check #${t.task_id}: ${t.task_name}`,{evidence_reference:'long'},b=>post(`/mobilisation-tasks/${t.task_id}/complete`,{...b,expected_revision:t.revision}));if(o.tasks.every(t=>t.completed_at))action(`Activate contract and site #${o.site_id}`,`/site-onboardings/${o.onboarding_id}/activate`);}}note('Accepted quotations create draft contracts and onboarding sites. Complete every mobilisation check with evidence before activating service. Portal sharing does not send quotation email.');}
        }
        if(active())status.textContent='Ready.';
      } catch(e){if(active())status.textContent=e.message;}
    }
    return {render,cancel:()=>generation++};
  }
  window.SentinelClientWorkflows={modules,has:view=>modules.some(([id])=>id===view),build};
})();
