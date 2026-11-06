(() => {
  'use strict';
  const modules = [
    ['managed-android','Managed Android phones','/managed-android'],
    ['compliance','Compliance reminders','/field/compliance'],
    ['equipment-register','Equipment register','/field/equipment'],
    ['equipment-handovers','Equipment handovers','/field/handovers'],
    ['escalation-rules','Escalation rules','/field/escalation-rules'],
    ['escalation-events','Escalation events','/field/escalations'],
    ['client-sharing','Client report sharing','/field/portal-access'],
    ['client-portal','My client reports','/client-portal'],
    ['account-security','Account security','/account/security'],
    ['support-security','Support access and sessions','/support/sessions'],
    ['payroll-export','Approved payroll hours','/field/payroll'],
    ['delegated-support','Temporary support view','/support/delegated-overview'],
  ];
  const pretty = value => value.replaceAll('_',' ').replace(/\b\w/g,c=>c.toUpperCase());
  function build({node,request,table,content}) {
    let generation=0;
    function form(root,title,path,fields,done,initial={}) {
      const panel=node('details','','section');panel.append(node('summary',title));
      const f=node('form','','form-stack'),message=node('p');message.setAttribute('role','status');const inputs={};
      for(const [key,type] of Object.entries(fields)) {
        const label=node('label',pretty(key));let input=node(type==='long'||type==='packages'?'textarea':'input');
        if(type==='boolean'){input.type='checkbox';input.checked=initial[key]??true;}else if(type==='number'){input.type='number';input.min='1';input.step='1';}else if(type==='password') {input.type='password';input.autocomplete='off';}else if(type==='date')input.type='date';
        if(type!=='boolean'){input.required=true;if(initial[key]!==undefined)input.value=type==='packages'?initial[key].join('\n'):initial[key];}inputs[key]={input,type};label.append(input);f.append(label);
      }
      const save=node('button',title,'primary');save.type='submit';f.append(save);panel.append(f,message);root.append(panel);
      f.onsubmit=async e=>{e.preventDefault();save.disabled=true;try{const body={};for(const[key,{input,type}]of Object.entries(inputs))body[key]=type==='boolean'?input.checked:type==='number'&&!key.endsWith('_id')?Number(input.value):type==='number'?input.value:type==='packages'?input.value.split(/[\s,]+/).filter(Boolean):input.value;
        const actual=typeof path==='function'?path(body):path;
        const {payload}=await request(actual,{method:'POST',body:JSON.stringify(body)});
        // Codes/secrets are shown only in this response, never persisted by this UI.
        const output=node('pre',JSON.stringify(payload.data,null,2));message.replaceChildren(output);f.reset();await done?.(payload.data);
      }catch(e){message.textContent=e.message;}finally{save.disabled=false;}};
      return {inputs,panel};
    }
    async function render(view) {
      const turn=++generation;const root=node('section','','section'),status=node('p','Loading…');status.setAttribute('role','status');root.append(status);content.replaceChildren(root);
      try{
        const access=(await request('/auth/access')).payload.data.permissions; if(turn!==generation)return;
        const can=permission=>access.includes(permission);
        const list=async(path,title,project=rows=>rows)=>{const {payload}=await request(path);if(turn!==generation)return[];root.append(node('h2',title),table(project(payload.data)));return payload.data;};
        const action=(label,path,body={})=>{const b=node('button',label,'secondary');b.onclick=async()=>{b.disabled=true;try{await request(path,{method:'POST',body:JSON.stringify(body)});if(turn===generation)render(view);}catch(e){status.textContent=e.message;b.disabled=false;}};root.append(b);};
        if(view==='managed-android'){
          root.append(node('p','Fully managed company Android phones only. A saved policy is not proof it was applied. Keep the offline recovery code before enabling kiosk mode. App package names must be exact; install apps before restricting installation.'));
          const parsePolicy=row=>{try{return JSON.parse(row.policy_json);}catch{return {};}};const rows=await list('/managed-android','Enrolled devices and policy acknowledgements',rows=>rows.map(row=>{const{policy_json,...safe}=row;const policy=parsePolicy(row);return {...safe,kiosk_enabled:policy.enabled??'unknown',approved_apps:(policy.allowed_packages||[]).join(', '),notification_dropdown_blocked:policy.block_status_bar??'unknown',app_installation_blocked:policy.block_install??'unknown'};}));
          if(can('managed_android.manage')){
            form(root,'Create enrollment','/managed-android',{device_name:'text'},()=>{});
            for(const row of rows){const box=node('section','','section');box.append(node('h3',`${row.device_name} · ID ${row.managed_device_id} · revision ${row.revision}`));root.append(box);
              form(box,'Save policy',body=>{body.expected_revision=Number(row.revision);return `/managed-android/${row.managed_device_id}/policy`;},{enabled:'boolean',allowed_packages:'packages',block_status_bar:'boolean',block_install:'boolean'},()=>{status.textContent='Policy saved. Refresh to see the new revision and phone acknowledgement.';},{enabled:false,allowed_packages:['com.sentinel.guard'],block_status_bar:true,block_install:true,...parsePolicy(row)});
              form(box,'Replace enrollment after confirmed release',`/managed-android/${row.managed_device_id}/replace-enrollment`,{confirm_device_id:'text'});
              action(`Release ${row.device_name} on next contact`,`/managed-android/${row.managed_device_id}/policy`,{expected_revision:Number(row.revision),enabled:false,allowed_packages:['com.sentinel.guard'],block_status_bar:false,block_install:false});
            }
          }
        }else if(view==='compliance'){
          root.append(node('p','Record evidence reviews and renewal dates. Verification records a human review; it does not query PSiRA automatically. Due reminders are evaluated by the scheduled escalation worker.'));
          const rows=await list('/field/compliance','Compliance requirements');if(can('compliance.manage')){form(root,'Add requirement','/field/compliance',{employee_id:'number',requirement_name:'text',reference_number:'text',due_on:'date'});for(const row of rows){action(`Record evidence review: ${row.requirement_name}`,`/field/compliance/${row.compliance_id}/verify`);form(root,`Renew ${row.requirement_name}`,`/field/compliance/${row.compliance_id}/renew`,{due_on:'date',reference_number:'text'});}}
        }else if(view==='equipment-register'){
          await list('/field/equipment','Equipment');if(can('equipment.manage'))form(root,'Register equipment','/field/equipment',{asset_code:'text',equipment_name:'text'});
        }else if(view==='equipment-handovers'){
          const rows=await list('/field/handovers','Issue and return history');if(can('equipment.manage')){form(root,'Issue equipment','/field/handovers',{equipment_id:'number',employee_id:'number',site_id:'number',issue_condition:'long'});for(const row of rows)if(!row.returned_at)form(root,`Return handover ${row.handover_id}`,`/field/handovers/${row.handover_id}/return`,{return_condition:'long'});}
        }else if(view==='escalation-rules'){
          root.append(node('p','Recipients need notifications.view plus escalation viewing/acknowledgement permissions. These rules send in-app notifications. The server worker must run regularly. SMS, email and WhatsApp delivery require a configured provider; they are not sent by this release.'));
          const rows=await list('/field/escalation-rules','Rules');if(can('escalations.manage')){form(root,'Create rule','/field/escalation-rules',{recipient_user_id:'number',backup_user_id:'number',escalate_minutes:'number'});for(const row of rows)if(Number(row.active))action(`Disable rule ${row.rule_id}`,`/field/escalation-rules/${row.rule_id}/disable`);}
        }else if(view==='escalation-events'){
          const rows=await list('/field/escalations','Operational attention and escalation');if(can('escalations.manage'))for(const row of rows)if(!row.acknowledged_at)action(`Acknowledge ${row.event_type} #${row.escalation_id}`,`/field/escalations/${row.escalation_id}/acknowledge`);
        }else if(view==='client-sharing'){
          root.append(node('p','Publish reviewed, client-appropriate summaries. Clients see only published reports for their mapped client IDs. Give client accounts client_portal.read, without company administration permissions.'));
          const rows=await list('/field/portal-access','Client account mappings');if(can('client_portal.manage')){form(root,'Grant client access','/field/portal-access',{user_id:'number',client_id:'number'});for(const row of rows)action(`Revoke user ${row.user_id} / client ${row.client_id}`,'/field/portal-access/revoke',{user_id:String(row.user_id),client_id:String(row.client_id)});let publisher;form(root,'Generate site report draft','/field/report-draft',{site_id:'number',date_from:'date',date_to:'date'},draft=>{for(const key of ['site_id','title','report_text'])publisher.inputs[key].input.value=draft[key];publisher.panel.open=true;});publisher=form(root,'Publish approved site report','/field/publish',{site_id:'number',title:'text',report_text:'long'});}
        }else if(view==='client-portal'){
          const {payload}=await request('/client-portal');for(const row of payload.data){const card=node('article','','section');card.append(node('h2',row.title),node('p',`Site ${row.site_id} · ${row.published_at} UTC`));const body=node('p',row.report_text);body.style.whiteSpace='pre-wrap';card.append(body);root.append(card);}if(!payload.data.length)root.append(node('p','No approved reports have been shared with this account.'));
        }else if(view==='account-security'){
          const data=(await request('/account/security')).payload.data;root.append(node('h2',`Authenticator MFA: ${data.mfa.enabled?'enabled':'disabled'}`),node('p','Keep your company account recovery procedure available before enabling MFA. Use a fresh code for each action.'));
          if(!data.mfa.enabled){form(root,'Begin authenticator setup','/account/mfa/setup',{current_password:'password'});form(root,'Confirm setup','/account/mfa/confirm',{code:'password'});}else form(root,'Disable MFA','/account/mfa/disable',{current_password:'password',code:'password'});
          root.append(node('h2','Your login sessions'),table(data.sessions));for(const row of data.sessions)if(!row.revoked_at)action(`Revoke session ${row.session_id}`,`/account/sessions/${row.session_id}/revoke`);
        }else if(view==='support-security'){
          const sessions=await list('/support/sessions','Company login sessions');for(const row of sessions)if(!row.revoked_at)action(`Revoke user ${row.user_id} session ${row.session_id}`,`/support/sessions/${row.session_id}/revoke`);
          const grants=await list('/support/grants','Temporary support grants');root.append(node('p','Use a separate same-company support account with MFA and support.delegated_view only. Grants do not remove unrelated permissions already assigned to that account.'));
          form(root,'Grant temporary diagnostic access','/support/grants',{user_id:'number',hours:'number',reason:'long'});for(const row of grants)if(!row.revoked_at)action(`Revoke grant ${row.grant_id}`,`/support/grants/${row.grant_id}/revoke`);
        }else if(view==='payroll-export'){
          root.append(node('p','Approve completed attendance after checking corrections. Later changes invalidate that approval. Exports contain worked hours; payroll software must apply breaks, overtime, wages and statutory rules.'));
          if(can('payroll_export.manage'))form(root,'Approve attendance',body=>{const id=body.attendance_id;delete body.attendance_id;return `/field/attendance/${id}/approve`;},{attendance_id:'number'});
          const f=node('form','','toolbar'),from=node('input'),to=node('input');from.type=to.type='date';from.required=to.required=true;from.setAttribute('aria-label','From date');to.setAttribute('aria-label','To date');const run=node('button','Preview approved hours','primary'),download=node('button','Download CSV','secondary');download.type='button';f.append(from,to,run,download);root.append(f);const result=node('div');root.append(result);const query=()=>new URLSearchParams({date_from:from.value,date_to:to.value});
          f.onsubmit=async e=>{e.preventDefault();try{const{payload}=await request(`/field/payroll?${query()}`);result.replaceChildren(node('p',`Stale approvals excluded: ${payload.data.stale_approvals_excluded}`),table(payload.data.rows));}catch(e){status.textContent=e.message;}};
          download.onclick=async()=>{if(!f.reportValidity())return;try{const{response}=await request(`/field/payroll/csv?${query()}`);const url=URL.createObjectURL(await response.blob());const a=node('a');a.href=url;a.download='sentinel-approved-hours.csv';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);status.textContent=`CSV downloaded. Stale approvals excluded: ${response.headers.get('X-Stale-Approvals-Excluded')||0}`;}catch(e){status.textContent=e.message;}};
        }else if(view==='delegated-support'){
          const data=(await request('/support/delegated-overview')).payload.data;root.append(node('p',`Temporary diagnostic snapshot: ${data.generated_at}`));for(const[name,section]of Object.entries(data.sections))root.append(node('h2',pretty(name)),node('p',section.status),table(section.rows));
        }
        if(turn===generation)status.textContent='Snapshot loaded. Refresh this page after saving changes to see the latest records.';
      }catch(e){if(turn===generation)status.textContent=e.message;}
    }
    return {render,cancel:()=>{generation++;}};
  }
  window.SentinelRelease={modules,has:view=>modules.some(([id])=>id===view),build};
})();
