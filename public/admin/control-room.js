(() => {
  'use strict';
  const modules=[['dispatch-board','Control-room dispatch','/control-room/dispatch'],['lone-worker-plans','Lone-worker protection','/control-room/lone-workers'],['roster-coverage','Rostering and relief','/control-room/coverage'],['gate-register','Visitor and vehicle gate register','/control-room/gate'],['contract-billing','Contract rates and invoice drafts','/control-room/invoices'],['phone-health','Device health','/control-room/device-health'],['readiness','Operational readiness','/control-room/readiness'],['my-response','My response and check-ins','/guard/control-room']];
  const title=k=>k.replaceAll('_',' ').replace(/\b\w/g,c=>c.toUpperCase());
  function build({node,request,table,content}) {
    let generation=0;
    async function render(view) {
      const turn=++generation,root=node('section','','section'),status=node('p','Loading…');status.setAttribute('role','status');root.append(status);content.replaceChildren(root);
      try {
        const access=(await request('/auth/access')).payload.data.permissions;if(turn!==generation)return;const can=p=>access.includes(p);
        const get=async path=>(await request(path)).payload.data;
        const show=(label,rows)=>root.append(node('h2',label),table(rows));
        const form=(label,path,fields,initial={})=>{
          const detail=node('details','','section'),f=node('form','','form-stack'),inputs={},message=node('p');detail.append(node('summary',label),f,message);root.append(detail);
          for(const[key,type]of Object.entries(fields)){const l=node('label',title(key));let input;
            if(Array.isArray(type)){input=node('select');for(const v of type){const option=node('option',v);option.value=v;input.append(option);}}
            else {input=node(type==='long'?'textarea':'input');if(type!=='long')input.type=type==='utc'?'text':type==='number'?'number':type==='password'?'password':type==='date'?'date':'text';if(type==='utc')input.placeholder='UTC YYYY-MM-DD HH:MM:SS';if(type==='number'){input.min='1';input.step='1';}}
            input.required=!key.endsWith('_optional');input.autocomplete='off';input.setAttribute('aria-label',title(key));if(initial[key]!==undefined)input.value=initial[key];l.append(input);f.append(l);inputs[key]=input;
          }
          const save=node('button',label,'primary');f.append(save);f.onsubmit=async e=>{e.preventDefault();save.disabled=true;try{const body={};for(const[key,input]of Object.entries(inputs))body[key.replace(/_optional$/,'')]=input.value;const target=typeof path==='function'?path(body):path;const data=(await request(target,{method:'POST',body:JSON.stringify(body)})).payload.data;if(turn===generation){message.replaceChildren(node('pre',JSON.stringify(data,null,2)));status.textContent='Saved. Refresh for the current snapshot.';}}catch(e){if(turn===generation)message.textContent=e.message;}finally{save.disabled=false;}};
        };
        const action=(label,path,body={})=>{const b=node('button',label,'secondary');root.append(b);b.onclick=async()=>{b.disabled=true;try{await request(path,{method:'POST',body:JSON.stringify(body)});if(turn===generation)await render(view);}catch(e){if(turn===generation)status.textContent=e.message;b.disabled=false;}};};
        root.append(node('p','Times are UTC. Use Refresh for current server state. Emergency response still requires your company phone/radio procedure.'));
        if(view==='dispatch-board'){
          const rows=await get('/control-room/dispatch');if(turn!==generation)return;show('Incident response assignments',rows);
          if(can('dispatch.manage')){form('Assign responder','/control-room/dispatch',{incident_id:'number',responder_user_id:'number',response_due_at:'utc'});for(const row of rows){const base=`/control-room/dispatch/${row.dispatch_id}`;if(row.state==='ASSIGNED')action(`Record acceptance #${row.dispatch_id}`,base+'/accept',{expected_revision:row.revision});if(row.state==='ACCEPTED')action(`Record arrival #${row.dispatch_id}`,base+'/arrive',{expected_revision:row.revision});if(row.state==='ARRIVED')form(`Close response #${row.dispatch_id}`,body=>{body.expected_revision=row.revision;return base+'/close';},{resolution:'long'});if(!['CLOSED','CANCELLED'].includes(row.state))form(`Cancel response #${row.dispatch_id}`,body=>{body.expected_revision=row.revision;return base+'/cancel';},{resolution:'long'});}}
        } else if(view==='lone-worker-plans'){
          root.append(node('p','Arrange a check-in interval and share the one-time duress code privately with the guard. A missed check-in needs the scheduled control-room worker and an operator response.'));
          const rows=await get('/control-room/lone-workers');if(turn!==generation)return;show('Check-in plans',rows);if(can('lone_worker.manage')){form('Create check-in plan','/control-room/lone-workers',{shift_assignment_id:'number',user_id:'number',interval_minutes:'number'});for(const row of rows)if(!row.closed_at)form(`End plan #${row.plan_id}`,`/control-room/lone-workers/${row.plan_id}/close`,{reason:'long'});}
        } else if(view==='roster-coverage'){
          root.append(node('p','Create shifts under Shift assignments. Coverage measures the minimum scheduled headcount throughout each required window; it is not proof a guard is physically present. Relief is allowed before attendance begins.'));
          const rows=await get('/control-room/coverage');if(turn!==generation)return;show('Post coverage requirements',rows);show('Relief history',await get('/control-room/relief-history'));
          if(can('rostering.manage')){form('Require post coverage','/control-room/coverage',{post_id:'number',starts_at:'utc',ends_at:'utc',required_guards:'number'});form('Assign relief guard',body=>{const id=body.shift_assignment_id;delete body.shift_assignment_id;return `/control-room/shifts/${id}/relief`;},{shift_assignment_id:'number',expected_employee_id:'number',replacement_employee_id:'number',reason:'long'});for(const row of rows)action(`Cancel coverage #${row.coverage_id}`,`/control-room/coverage/${row.coverage_id}/cancel`);}
        } else if(view==='gate-register'){
          root.append(node('p','Record people, deliveries and vehicle movements. The Android app can capture encrypted gate records offline for an assigned shift. Offline captures do not verify authorisation or blocked-person lists; follow the gate procedure.'));
          const rows=await get('/control-room/gate');if(turn!==generation)return;show('Gate movements',rows);const captureUuid=crypto.randomUUID();if(can('gate_register.manage'))form('Record gate movement',body=>{body.operation_uuid=captureUuid;return '/control-room/gate';},{site_id:'number',visitor_name:'text',vehicle_registration_optional:'text',purpose:'long',direction:['ENTRY','EXIT'],authorization:['PENDING','AUTHORIZED','DENIED'],occurred_at:'utc'});
        } else if(view==='contract-billing'){
          root.append(node('p','Amounts are integer currency minor units (ZAR 12500 = R125.00). Rates are immutable and apply from their effective date. Drafts use current approved elapsed attendance and round each line to the nearest minor unit. Tax, breaks and overtime require review.'));
          show('Site contract rates',await get('/control-room/rates'));const rows=await get('/control-room/invoices');if(turn!==generation)return;show('Invoice drafts',rows);
          if(can('billing.manage')){form('Add effective rate','/control-room/rates',{contract_id:'number',site_id:'number',hourly_minor:'number',currency:'text',effective_from:'date'},{currency:'ZAR'});form('Generate invoice draft','/control-room/invoices',{contract_id:'number',site_id:'number',date_from:'date',date_to:'date'});}
          for(const row of rows){if(can('billing.manage')&&row.state==='DRAFT')form('Void draft #'+row.invoice_id,'/control-room/invoices/'+row.invoice_id+'/void',{reason:'long'});const b=node('button',`Review draft #${row.invoice_id}`,'secondary'),detail=node('div');root.append(b,detail);b.onclick=async()=>{try{const d=await get(`/control-room/invoices/${row.invoice_id}`);if(turn!==generation)return;detail.replaceChildren(node('p',d.attendance_changed?'Attendance has changed. This draft needs review.':d.note),table(d.lines));}catch(e){status.textContent=e.message;}};}
        } else if(view==='phone-health'){
          const data=await get('/control-room/device-health');if(turn!==generation)return;root.append(node('p',`Minimum app version code: ${data.minimum_version_code}. ${data.note}`));show('Battery, app version and contact',data.phones);show('Managed-policy acknowledgements',data.managed);
        } else if(view==='readiness'){
          const data=await get('/control-room/readiness');if(turn!==generation)return;root.append(node('p',data.note));show('Scheduled workers',data.workers.map(w=>({worker:w.worker,state:w.state,last_contact:w.last_run?.finished_at||'Unknown'})));show('Backup evidence',[{state:data.backup.state,...data.backup.evidence}]);root.append(node('p',data.backup.note),node('p',`Escalation channel: ${data.escalation_delivery}`));show('Unacknowledged operational signals',data.unresolved_signals);
          if(can('readiness.manage')){form('Record backup and restore evidence','/control-room/backup-checks',{backup_reference:'text',backed_up_at:'utc',restored_at_optional:'utc'});for(const row of data.unresolved_signals)action(`Acknowledge ${row.signal_type} #${row.signal_id}`,`/control-room/signals/${row.signal_id}/acknowledge`);}
        } else if(view==='my-response'){
          const data=await get('/guard/control-room');if(turn!==generation)return;show('My response assignments',data.dispatch);show('My check-in plans',data.checkins);for(const row of data.dispatch){const next=row.state==='ASSIGNED'?'accept':row.state==='ACCEPTED'?'arrive':null;if(next)action(`Record ${next} #${row.dispatch_id}`,`/guard/dispatch/${row.dispatch_id}/${next}`,{expected_revision:row.revision});}for(const row of data.checkins)form(`Submit check-in #${row.plan_id}`,`/guard/checkins/${row.plan_id}`,{code:'password'},{code:'OK'});root.append(node('p','Check-ins and response acknowledgements require a live server connection. If it fails, use your radio or phone.'));
        }
        if(turn===generation)status.textContent='Snapshot loaded. Refresh for current status.';
      } catch(e) {if(turn===generation)status.textContent=e.message;}
    }
    return {render,cancel:()=>generation++};
  }
  window.SentinelControlRoom={modules,has:view=>modules.some(([id])=>id===view),build};
})();
