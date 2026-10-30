/* Uses the manager workspace's authenticated transport; no additional token storage. */
window.SentinelSupport = {
  build({node, request, table, content, navigate}) {
    let generation = 0;
    return {
      cancel() { generation++; },
      async render() {
        const current = ++generation;
        const root = node('section', '', 'section');
        const status = node('p', 'Loading support data…'); status.setAttribute('role', 'status');
        const banner = node('div', '', 'state');
        banner.append(node('strong', 'Active emergency? Use your site radio or phone escalation procedure.'), node('p', 'This workspace diagnoses technical problems. It does not dispatch assistance or prove a guard is safe.'));
        const controls = node('div', '', 'toolbar');
        const refresh = node('button', 'Refresh status', 'primary'); refresh.onclick = () => this.render();
        const download = node('button', 'Download sanitised support bundle', 'secondary');
        download.onclick = async () => {
          download.disabled = true;
          try {
            const {payload} = await request('/support/bundle');
            const url = URL.createObjectURL(new Blob([JSON.stringify(payload.data, null, 2)], {type:'application/json'}));
            const a = node('a'); a.href = url; a.download = 'sentinel-support.json'; a.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
            status.textContent = 'Sanitised bundle downloaded. Export recorded in the audit trail.';
          } catch (e) { status.textContent = e.message; }
          finally { download.disabled = false; }
        };
        controls.append(refresh, download);
        root.append(banner, controls, status); content.replaceChildren(root);
        try {
          const [{payload}, access] = await Promise.all([request('/support/overview'), request('/auth/access')]);
          if (current !== generation) return;
          const report = payload.data;
          status.textContent = `Snapshot: ${new Date(report.generated_at).toLocaleString()} • ${report.status}. Refresh to check again.`;
          const health = node('section'); health.append(node('h2', 'Server diagnostics'), node('p', 'Checking…')); root.append(health);
          request('/support/health').then(({payload}) => {
            if (current !== generation) return;
            health.replaceChildren(node('h2', 'Server diagnostics'), table(Object.entries(payload.data.checks).map(([component, check]) => ({component, status:check.status}))));
          }).catch(e => { if(current === generation) health.replaceChildren(node('h2', 'Server diagnostics'), node('p', e.message)); });
          const names = {devices:'Device last contact', sessions:'Active device sessions and affected sites', sync:'Server sync receipts', mobile_diagnostics:'Last uploaded phone diagnostics', timeline:'Recent support actions'};
          for (const [name, section] of Object.entries(report.sections)) {
            const card = node('section', '', 'section'); card.append(node('h2', names[name]));
            if (section.status !== 'ready') card.append(node('p', 'Data unavailable. Check server diagnostics; do not interpret missing data as a healthy site.'));
            else card.append(table(section.rows));
            if (section.truncated) card.append(node('p', 'Results are limited. Use the relevant operational module to investigate further.'));
            root.append(card);
          }
          const guidance = node('section', '', 'section'); guidance.append(node('h2', 'Recovery checklist'));
          const steps = node('ol');
          for (const text of ['Contact the affected guard/site by radio or phone; confirm immediate safety and arrange operational cover.', 'Check last-contact timestamps. STALE means older than 30 minutes; UNKNOWN means no reliable timestamp.', 'For sync trouble: keep the phone charged, confirm its connection and account, then use Synchronize and refresh. Preserve the queue and note the error/request ID.', 'Do not uninstall the app, clear its storage, or delete retry receipts. Unsynced encrypted records may be lost.', 'Record radio/phone reports in the occurrence book using the actual event time and clearly identify the controller and reporting source.', 'For lost devices, disable the affected account below. This affects all its devices; arrange a replacement account through your administrator.']) steps.append(node('li', text));
          guidance.append(steps);
          for (const [label, view] of [['Supervisor attention','supervisor'],['Occurrence book / fallback report','occurrence-entries'],['Incident records','incidents']]) {
            const b = node('button', label, 'secondary'); b.onclick = () => navigate(view); guidance.append(b);
          }
          report.limits.forEach(text => guidance.append(node('p', text))); root.append(guidance);
          if (access.payload.data.permissions.includes('support.manage')) {
            const form = node('form', '', 'form-stack'); form.append(node('h2', 'Disable a compromised account'), node('p', 'Blocks company access on subsequent requests across all devices for this user. It does not wipe a phone or delete queued records.'));
            const user = node('input'); user.required = true; user.pattern = '[1-9][0-9]{0,18}'; user.inputMode = 'numeric';
            const reason = node('textarea'); reason.required = true; reason.minLength = 10; reason.maxLength = 500;
            const confirm = node('input'); confirm.required = true;
            for (const [label, input] of [['Affected user ID',user],['Reason (recorded in audit history)',reason],['Type the same user ID to confirm',confirm]]) { const l = node('label', label); l.append(input); form.append(l); }
            const submit = node('button', 'Disable account', 'primary'); submit.type = 'submit';
            const outcome = node('p'); outcome.setAttribute('role','status'); form.append(submit, outcome);
            form.onsubmit = async e => {
              e.preventDefault(); submit.disabled = true;
              try { const {payload} = await request(`/support/accounts/${encodeURIComponent(user.value.trim())}/disable`, {method:'POST',body:JSON.stringify({reason:reason.value.trim(),confirm_user_id:confirm.value.trim()})}); outcome.textContent = payload.data.detail; form.reset(); }
              catch(e) { outcome.textContent = e.message; }
              finally { submit.disabled = false; }
            };
            root.append(form);
          }
        } catch (e) { if(current === generation) status.textContent = `Support data unavailable: ${e.message}. Use phone/radio fallback.`; }
      }
    };
  }
};
