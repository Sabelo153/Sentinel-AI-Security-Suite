(() => {
  'use strict';
  const choice = (...values) => values;
  const schemas = {
    'supervisor-attention': {title:'Supervisor attention',path:'/supervisor/attention',readOnly:true,key:'type',fields:{}},
    devices: {title:'Devices',key:'device_id',edit:true,fields:{device_name:'text',device_type:'text',platform:'text',device_identifier:'text',device_status:choice('REGISTERED','ACTIVE','SUSPENDED','LOST','RETIRED')}},
    'occurrence-entries': {title:'Occurrence Book', key:'occurrence_entry_id', fields:{site_id:'ref:sites',employee_id:'ref:employees?',shift_assignment_id:'ref:shift-assignments?',entry_type:'text',occurred_at:'datetime',entry_text:'long',handover_required:'boolean',handover_reference:'text?'}},
    visitors: {title:'Visitors',key:'visitor_id',edit:true,fields:{first_name:'text',last_name:'text',contact_phone:'text?',contact_email:'email?',external_reference:'text?',visitor_status:choice('ACTIVE','BLOCKED','ARCHIVED')}},
    vehicles: {title:'Vehicles',key:'vehicle_id',edit:true,fields:{registration_number:'text',make:'text?',model:'text?',color:'text?',vehicle_status:choice('ACTIVE','BLOCKED','ARCHIVED')}},
    'visitor-visits': {title:'Visits',key:'visitor_visit_id',fields:{visitor_id:'ref:visitors',site_id:'ref:sites',host_employee_id:'ref:employees?',purpose:'long',planned_arrival_at:'datetime?',planned_departure_at:'datetime?'}},
    'access-events': {title:'Access events',key:'access_event_id',fields:{site_id:'ref:sites',vehicle_id:'ref:vehicles?',visitor_visit_id:'ref:visitor-visits?',employee_id:'ref:employees?',event_type:'text',direction:choice('ENTRY','EXIT'),authorization_status:choice('PENDING','AUTHORIZED','DENIED'),denied_reason:'long?',occurred_at:'datetime'}},
    inspections: {title:'Inspections',key:'inspection_id',edit:true,fields:{site_id:'ref:sites',inspector_employee_id:'ref:employees?',inspector_user_id:'ref:users?',inspection_type:'text',scheduled_at:'datetime?',overall_outcome:choice('','PASS','FAIL','OBSERVATION','NA'),findings_summary:'long?',corrective_action_summary:'long?',follow_up_at:'datetime?',notes:'long?'}},
    alerts: {title:'Alerts',key:'alert_id',fields:{alert_type:'text',severity:choice('LOW','MEDIUM','HIGH','CRITICAL'),message:'long',occurred_at:'datetime'}},
    notifications: {title:'My notifications',key:'notification_id',fields:{recipient_user_id:'ref:users',event_type:'text',severity:choice('LOW','MEDIUM','HIGH','CRITICAL'),title:'text',message:'long'}},
    incidents: {title:'Incidents',key:'incident_id',edit:true,fields:{site_id:'ref:sites',employee_id:'ref:employees?',incident_type:'text',incident_category:'text',severity:choice('LOW','MEDIUM','HIGH','CRITICAL'),occurred_at:'datetime',description:'long',actions_taken:'long?',resolution:'long?'}},
  };
  const label = key => key.replace(/_id$/,'').replaceAll('_',' ').replace(/\b\w/g,c=>c.toUpperCase());
  const utcNow = () => new Date().toISOString().slice(0,19).replace('T',' ');
  function build({node,request,table,content,notice,loadingView}) {
    let generation = 0;
    function button(text,action) {const b=node('button',text,'secondary');b.type='button';b.onclick=action;return b;}
    function emptyState(schema,create) {
      const panel=node('section','','module-empty');
      panel.append(node('span','Ready when you are','module-empty-kicker'),node('h2','No '+schema.title.toLowerCase()+' yet'),node('p','This workspace is new. Add the first record to begin, or return later when your team is ready.'));
      if(!schema.readOnly){const start=button('Create first record',create);start.className='primary';panel.append(start);}
      return panel;
    }
    function dialog(title) {
      const overlay=node('div','','modal'),card=node('section','','modal-card'),head=node('div','','modal-head');
      overlay.setAttribute('role','dialog');overlay.setAttribute('aria-modal','true');overlay.setAttribute('aria-label',title);
      const close=()=>overlay.remove();head.append(node('h2',title),button('Close',close));card.append(head);overlay.append(card);document.body.append(overlay);
      overlay.addEventListener('keydown',event=>{if(event.key==='Escape')close();});
      return {overlay,card,close};
    }
    async function form(title,fields,path,method='POST',initial={},done=()=>{}) {
      const {card,close}=dialog(title),f=node('form','','form-stack'),message=node('p','','message'),controls={};
      message.setAttribute('role','alert');card.append(f,message);
      for (const [key,spec] of Object.entries(fields)) {
        const optional=typeof spec==='string'&&spec.endsWith('?'),type=optional?spec.slice(0,-1):spec;
        const wrap=node('label',label(key)+(type==='datetime'?' (UTC)':''));let input;
        if(Array.isArray(type)||String(type).startsWith('ref:')) {
          input=node('select');input.append(node('option','Choose…'));input.firstChild.value='';
          if(Array.isArray(type))for(const value of type){const option=node('option',value?label(value.toLowerCase()):'Not set');option.value=value;input.append(option);}
          else {
            try {const {payload}=await request('/'+type.slice(4));for(const row of payload.data||[]){const idKey=schemas[type.slice(4)]?.key||Object.keys(row).find(k=>k.endsWith('_id')&&k!=='company_id');const name=row.site_name||row.username||row.employee_number||row.registration_number||row.entry_reference||(row.first_name?row.first_name+' '+row.last_name:null)||row.shift_name||row.purpose||String(row[idKey]);const option=node('option',name);option.value=row[idKey];input.append(option);}}
            catch(error){if(optional){input.firstChild.textContent='Not available on this plan';input.disabled=true;}else message.textContent='Could not load '+label(key)+': '+error.message;}
          }
        } else if(type==='long') input=node('textarea');
        else {input=node('input');input.type=type==='boolean'?'checkbox':type==='datetime'?'datetime-local':type==='email'?'email':type==='number'?'number':'text';if(type==='datetime')input.step='1';}
        if(type==='boolean')input.checked=Boolean(Number(initial[key]||0));
        else input.value=initial[key]??(type==='datetime'&&!optional?utcNow().replace(' ','T'):'');
        if(type==='datetime'&&input.value)input.value=input.value.replace(' ','T');
        input.required=!optional&&type!=='boolean'&&!(Array.isArray(type)&&type.includes(''));
        if(type==='number'){input.min='0';input.step='1';}
        controls[key]={input,type,optional};wrap.append(input);f.append(wrap);
      }
      const save=node('button','Save','primary');save.type='submit';f.append(save);
      f.onsubmit=async event=>{
        event.preventDefault();save.disabled=true;message.textContent='';const body={};if(initial.expected_revision!==undefined)body.expected_revision=initial.expected_revision;
        for(const [key,{input,type,optional}] of Object.entries(controls)) {
          if(type==='boolean')body[key]=input.checked;
          else if(input.value===''){if(method==='PATCH')body[key]=null;else if(!optional&&!(Array.isArray(type)&&type.includes('')))body[key]='';}
          else if(type==='datetime')body[key]=input.value.replace('T',' ')+(input.value.length===16?':00':'');
          else if(type==='number')body[key]=Number(input.value);
          else body[key]=input.value;
        }
        if(path==='/access-events'&&!body.vehicle_id&&!body.visitor_visit_id&&!body.employee_id){message.textContent='Choose a vehicle, visitor visit, or employee before saving this access event.';save.disabled=false;return;}
        try{await request(path,{method,body:JSON.stringify(body)});close();await done();}catch(error){message.textContent=error.message;}finally{save.disabled=false;}
      };
      f.querySelector('input,select,textarea')?.focus();
    }
    async function history(row) {
      const {card}=dialog('Occurrence revision history');
      try{const {payload}=await request(`/occurrence-entries/${row.occurrence_entry_id}/revisions`);card.append(table(payload.data||[]));}catch(error){card.append(node('p',error.message));}
    }
    async function checklist(row,reload) {
      const {card}=dialog('Inspection checklist');const area=node('div');card.append(area);
      const fields={checklist_code:'text',checklist_label:'text',item_result:choice('','PASS','FAIL','NA','OBSERVATION'),finding:'long?',corrective_action:'long?',item_status:choice('OPEN','IN_PROGRESS','RESOLVED','NOT_REQUIRED'),due_at:'datetime?',resolved_at:'datetime?'};
      const base=`/inspections/${row.inspection_id}/items`;
      const load=async()=>{try{const {payload}=await request(base);area.replaceChildren(table(payload.data||[]));if(['DRAFT','IN_PROGRESS'].includes(row.status)){area.prepend(button('Add checklist item',()=>form('Checklist item',fields,base,'POST',{},load)));for(const item of payload.data||[])area.append(button('Edit '+item.checklist_label,()=>form('Edit checklist item',fields,base+'/'+item.inspection_item_id,'PATCH',item,load)));}}catch(error){area.replaceChildren(node('p',error.message));}};
      await load();
    }
    async function render(view) {
      const current=++generation,schema=schemas[view];if(!schema)return;
      content.replaceChildren(loadingView());
      try {
        const endpoint=schema.path||('/'+view);const {payload}=await request(endpoint);if(current!==generation)return;
        const rows=(view==='supervisor-attention'?payload.data?.findings:payload.data)||[],toolbar=node('div','','toolbar'),body=node('div');
        const reload=()=>render(view);
        const create=()=>form(schema.title,schema.fields,'/'+view,'POST',{},reload);
        toolbar.append(button('Refresh',reload));if(!schema.readOnly)toolbar.append(button(view==='notifications'?'Send in-app notification':'Create '+schema.title.toLowerCase(),create));
        for(const row of rows) {
          const card=node('article','','section'),actions=node('div','','toolbar');
          const title=row.type||row.entry_reference||row.incident_number||row.title||row.registration_number||(row.first_name?row.first_name+' '+row.last_name:null)||row.inspection_type||row.alert_type||row.purpose||row.event_type;
          card.append(node('h2',title||schema.title));
          const details=node('dl');for(const [key,value] of Object.entries(row)){if(value===null||key.endsWith('_id')||['company_id','payload_json'].includes(key))continue;details.append(node('dt',label(key)),node('dd',value));}card.append(details);
          const path='/'+view+'/'+row[schema.key];
          if(schema.edit&&!(view==='inspections'&&!['DRAFT','IN_PROGRESS'].includes(row.status)))actions.append(button('Edit',()=>form('Edit '+schema.title.toLowerCase(),schema.fields,path,'PATCH',row,reload)));
          if(view==='occurrence-entries'){
            actions.append(button('History',()=>history(row)),button('Revise',async()=>{try{const {payload}=await request(path+'/revisions');await form('Revise entry',{entry_text:'long',revision_reason:'long'},path+'/revisions','POST',{entry_text:row.entry_text,expected_revision:(payload.data||[]).length},reload);}catch(error){notice(error.message);}}));
          }
          if(view==='visitor-visits'){
            const next={PRE_REGISTERED:['AUTHORIZED','DENIED','CANCELLED'],AUTHORIZED:['CHECKED_IN','DENIED','CANCELLED'],CHECKED_IN:['CHECKED_OUT']}[row.visit_status];
            if(next)actions.append(button('Change visit status',()=>form('Visit status',{status:next,occurred_at:'datetime'},path+'/status','POST',{},reload)));
          }
          if(view==='alerts'){
            const next={OPEN:['ACKNOWLEDGED','DISMISSED'],ACKNOWLEDGED:['RESOLVED','DISMISSED']}[row.status];
            if(next)actions.append(button('Change alert status',()=>form('Alert status',{status:next},path+'/status','POST',{},reload)));
          }
          if(view==='notifications'&&!row.read_at)actions.append(button('Mark read',async()=>{try{await request(path+'/read',{method:'POST',body:'{}'});reload();}catch(error){notice(error.message);}}));
          if(view==='inspections'){
            actions.append(button('Checklist',()=>checklist(row,reload)));
            const next={DRAFT:['IN_PROGRESS','CANCELLED'],IN_PROGRESS:['COMPLETED','CANCELLED']}[row.status];
            if(next)actions.append(button('Change status',()=>form('Inspection status',{status:next,occurred_at:'datetime'},path+'/status','POST',{},reload)));
            if(row.status==='COMPLETED')actions.append(button('Review inspection',()=>form('Confirm inspection review',{},path+'/review','POST',{},reload)));
          }
          card.append(actions);body.append(card);
        }
        if(!rows.length)body.append(emptyState(schema,create));
        content.replaceChildren(toolbar,body);
      } catch(error){if(current===generation)content.replaceChildren(node('p',error.message),button('Retry',()=>render(view)));}
    }
    return render;
  }
  window.SentinelOperations={modules:Object.entries(schemas).map(([key,s])=>[key,s.title,'/'+key]),has:view=>Boolean(schemas[view]),build};
})();
