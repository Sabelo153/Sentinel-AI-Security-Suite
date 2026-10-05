/* Patrol administration uses the same protected API contracts as the guard app. */
window.SentinelPatrols = (() => {
  'use strict';
  const views = ['patrol-routes','patrol-sessions','patrol-schedules','supervisor'];
  function build({node,request,table,content,notice,loadingView,openEditor,navigate}) {
    let generation=0;
    const action=(label,handler)=>{const b=node('button',label,'secondary');b.type='button';b.onclick=handler;return b};
    const when=value=>value?new Date(value.replace(' ','T')+'Z').toLocaleString():'Not recorded';
    const section=title=>{const el=node('section','','section');el.append(node('h2',title));return el};
    const error=e=>notice(e.message);
    function guide() {
      const el=section('Set up a patrol');
      el.append(node('p','Create a site and guard employee, give the employee an active GUARD position assignment, assign a shift at the site, then create a route and its ordered checkpoints. Schedule each expected patrol start window. The guard signs in to the app and synchronizes to receive their work.'));
      const links=node('div','','toolbar');
      [['sites','1. Sites'],['employees','2. Employees'],['positions','3. Guard positions'],['assignments','4. Employee assignments'],['shifts','5. Shifts'],['patrol-routes','6. Routes & checkpoints'],['patrol-schedules','7. Schedule'],['patrol-sessions','Patrol history'],['supervisor','Supervisor attention']].forEach(([id,label])=>links.append(action(label,()=>navigate(id))));
      el.append(links);return el;
    }
    async function checkpoints(route) {
      const id=++generation;content.replaceChildren(loadingView());
      try {
        const {payload}=await request(`/patrol-routes/${route.patrol_route_id}/checkpoints`);if(id!==generation)return;
        const el=section(`${route.route_name}: checkpoints`),bar=node('div','','toolbar');
        bar.append(action('Back to routes',()=>render('patrol-routes')),action('Add checkpoint',()=>openEditor('checkpoints','checkpoint',{},`/patrol-routes/${route.patrol_route_id}/checkpoints`,()=>checkpoints(route))));
        el.append(bar,node('p','Order checkpoints starting at 1. Set the expected interval from the previous checkpoint (or patrol start for the first). A missing visit beyond that interval appears in Supervisor attention. Print a QR code containing the exact QR value, or enter the NFC tag UID.'));
        const rows=payload.data||[];
        if(!rows.length)el.append(node('p','No checkpoints yet. Add the first stop before sending this route to guards.','empty'));
        rows.forEach(row=>{const card=section(`${row.checkpoint_order}. ${row.checkpoint_name}`);card.append(table([row]),action('Edit checkpoint',()=>openEditor('checkpoints','checkpoint',row,`/patrol-checkpoints/${row.patrol_checkpoint_id}`,()=>checkpoints(route))));el.append(card)});
        content.replaceChildren(el);
      }catch(e){error(e)}
    }
    async function details(session) {
      const id=++generation;content.replaceChildren(loadingView());
      try {
        const [events,checkpoints]=await Promise.all([request(`/patrol-sessions/${session.patrol_session_id}/events`),request(`/patrol-routes/${session.patrol_route_id}/checkpoints`)]);if(id!==generation)return;
        const el=section(`Patrol #${session.patrol_session_id}: ${session.route_name}`);
        el.append(action('Back to history',()=>render('patrol-sessions')),table([session]),node('h3','Checkpoint progress'));
        const eventRows=events.payload.data||[];
        el.append(table((checkpoints.payload.data||[]).map(c=>{
          const all=eventRows.filter(e=>String(e.patrol_checkpoint_id)===String(c.patrol_checkpoint_id));
          return {order:c.checkpoint_order,checkpoint:c.checkpoint_name,status:all.some(e=>e.event_type==='VISITED')?'VISITED':all.length?all[all.length-1].event_type:'NO VISIT RECORDED',events:all.length};
        })),node('h3','Recorded events'),table(eventRows));
        el.append(node('p','Checkpoint progress uses the current route definition. Event history is the recorded evidence. A completed session does not by itself prove every checkpoint was visited.'));
        if(session.status==='IN_PROGRESS') {
          const reason=node('textarea');reason.placeholder='Explain why the patrol is being ended';reason.setAttribute('aria-label','Reason for ending patrol');el.append(reason);
          ['ABORTED','CANCELLED'].forEach(status=>el.append(action(status==='ABORTED'?'Abort patrol':'Cancel patrol',async()=>{
            if(!reason.value.trim()){notice('Enter a reason first.');reason.focus();return}
            if(!confirm(`End this patrol as ${status.toLowerCase()}? The record will remain in history.`))return;
            try{await request(`/patrol-sessions/${session.patrol_session_id}`,{method:'PATCH',body:JSON.stringify({status,ended_at:new Date().toISOString().slice(0,19).replace('T',' '),execution_notes:reason.value.trim()})});render('patrol-sessions')}catch(e){error(e)}
          })));
        }
        content.replaceChildren(el);
      }catch(e){error(e)}
    }
    function filteredList(rows,renderRow,statuses) {
      const container=node('div'),bar=node('div','','toolbar'),search=node('input'),select=node('select'),list=node('div');
      search.placeholder='Search route, guard, shift or record';search.setAttribute('aria-label','Search records');
      ['ALL',...statuses].forEach(status=>{const o=node('option',status.replaceAll('_',' '));o.value=status;select.append(o)});select.setAttribute('aria-label','Filter status');
      const show=()=>{const matching=rows.filter(r=>(select.value==='ALL'||r.status===select.value)&&JSON.stringify(r).toLowerCase().includes(search.value.toLowerCase()));list.replaceChildren(...matching.map(renderRow));if(!matching.length)list.append(node('p','No records match these filters.','empty'))};
      search.oninput=show;select.onchange=show;bar.append(search,select);container.append(bar,list);show();return container;
    }
    async function render(view,before=null) {
      const id=++generation;content.replaceChildren(loadingView());
      try {
        const path=view==='supervisor'?'/supervisor/attention':`/${view}`+(before?`?before_id=${encodeURIComponent(before)}`:'');
        const {payload}=await request(path);if(id!==generation)return;
        const root=node('div'),bar=node('div','','toolbar');bar.append(action('Refresh',()=>render(view)));
        root.append(guide(),bar);
        if(view==='patrol-routes') {
          bar.append(action('Create route',()=>openEditor(view,'route',{},'/patrol-routes',()=>render(view))));
          root.append(filteredList(payload.data||[],row=>{const card=section(row.route_name);card.append(node('p',`${row.status} · Site ${row.site_name||row.site_id} · Expected duration ${row.expected_duration_minutes||'not set'} minutes`),action('Checkpoints',()=>checkpoints(row)),action('Edit route',()=>openEditor(view,'route',row,`/patrol-routes/${row.patrol_route_id}`,()=>render(view))));return card},['ACTIVE','INACTIVE','ARCHIVED']));
        } else if(view==='patrol-sessions') {
          root.append(node('p','Every recorded patrol remains here, including completed, in-progress, aborted and cancelled sessions. A patrol that never started appears under Patrol schedule as missed. Reports provides CSV exports. Times below use your device timezone.'));
          root.append(filteredList(payload.data||[],row=>{const card=section(`${row.route_name} · ${row.status.replaceAll('_',' ')}`);card.append(node('p',`#${row.patrol_session_id} · ${row.first_name||''} ${row.last_name||''} · ${row.site_name||''}`),node('p',`Started ${when(row.started_at)} · Ended ${when(row.ended_at)}`),action('View checkpoints and events',()=>details(row)));return card},['IN_PROGRESS','COMPLETED','ABORTED','CANCELLED']));
        } else if(view==='patrol-schedules') {
          bar.append(action('Schedule patrol',()=>openEditor(view,'patrol start window',{},'/patrol-schedules',()=>render(view))));
          root.append(node('p','Each window expects one patrol to start on the selected route and shift. No start received by the end of the window is MISSED. Offline patrols may change this result after synchronization; contact the guard before escalating. Windows must fit within the shift and cannot overlap for the same route.'));
          const data=payload.data||{};
          root.append(filteredList(data.items||[],row=>{const card=section(`${row.route_name} · ${row.status.replaceAll('_',' ')}`);card.append(node('p',`${row.shift_name} · Guard employee #${row.employee_id}`),node('p',`${when(row.starts_at)} to ${when(row.ends_at)}`));if(row.patrol_session_id)card.append(node('p',`Recorded patrol #${row.patrol_session_id}`),action('Open patrol history',()=>navigate('patrol-sessions')));if(row.status==='UPCOMING')card.append(action('Cancel scheduled patrol',async()=>{if(!confirm('Cancel this upcoming patrol window?'))return;try{await request(`/patrol-schedules/${row.patrol_schedule_id}/cancel`,{method:'POST',body:'{}'});render(view)}catch(e){error(e)}}));return card},['UPCOMING','DUE','MISSED','IN_PROGRESS','COMPLETED','ABORTED','CANCELLED']));
          if(data.next_before_id)root.append(action('Older schedules',()=>render(view,data.next_before_id)));
        } else {
          const data=payload.data||{};
          root.append(node('h2','Needs supervisor attention'),node('p',`Evaluated ${when(data.evaluated_at)}. Refresh to re-evaluate. These are missing reports or telemetry, not proof of misconduct.`),table(data.findings||[]),action('Review missed and due patrols',()=>navigate('patrol-schedules')));
          if(data.truncated)root.append(node('p','Only part of the attention data could be evaluated. Review individual shifts and patrols.'));
        }
        content.replaceChildren(root);
      }catch(e){if(id===generation){content.replaceChildren(action('Retry',()=>render(view)));error(e)}}
    }
    render.cancel=()=>{generation++};
    return render;
  }
  return {has:view=>views.includes(view),build};
})();
