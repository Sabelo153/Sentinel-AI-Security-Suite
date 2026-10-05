(() => {
  'use strict';
  const $ = id => document.getElementById(id);
  const FORM_IDLE_MS = 5 * 60 * 1000;
  let formIdleTimer;
  const descriptions = {
    FREE: 'Up to 10 users and 3 sites. Unlimited Occurrence Book, incidents, visitors, vehicles/access, inspections, alerts and in-app notifications.',
    ESSENTIAL: () => window.SentinelMarkets.planDescription('ESSENTIAL'),
    PROFESSIONAL: () => window.SentinelMarkets.planDescription('PROFESSIONAL'),
    ENTERPRISE: 'All Sentinel modules, integration controls and rollout support. Includes a 14-day trial.'
  };
  const setMode = signup => {
    $('loginForm').classList.toggle('hidden', signup);
    $('loginTitle').textContent = signup ? 'Start with Sentinel' : 'Admin access';
    $('signupForm').classList.toggle('hidden', !signup);
    $('loginMessage').textContent = '';
    $('signupMessage').textContent = '';
  };
  const describe = () => {
    const description = descriptions[$('signupPlan').value] || '';
    $('planDescription').textContent = typeof description === 'function' ? description() : description;
  };
  const resetFormIdleTimer = () => {
    window.clearTimeout(formIdleTimer);
    formIdleTimer = window.setTimeout(() => {
      const hadPassword = Boolean($('password').value || $('signupPassword').value);
      $('password').value = '';
      $('signupPassword').value = '';
      if (hadPassword && !$('loginView').classList.contains('hidden')) {
        const target = $('signupForm').classList.contains('hidden') ? $('loginMessage') : $('signupMessage');
        target.textContent = 'For your security, the password field was cleared after 5 minutes without activity.';
      }
    }, FORM_IDLE_MS);
  };
  ['input', 'change', 'keydown', 'pointerdown'].forEach(eventName => $('loginView').addEventListener(eventName, resetFormIdleTimer, {passive: true}));
  resetFormIdleTimer();
  $('openSignup').onclick = () => { setMode(true); describe(); };
  $('cancelSignup').onclick = () => setMode(false);
  $('signupPlan').onchange = describe;
  document.addEventListener('sentinel:market-change', describe);

  const requestedPlan = new URLSearchParams(window.location.search).get('signup');
  if (requestedPlan && Object.hasOwn(descriptions, requestedPlan.toUpperCase())) {
    $('signupPlan').value = requestedPlan.toUpperCase();
    setMode(true);
    describe();
  } else if (window.location.hash === '#signup') {
    setMode(true);
    describe();
  }

  $('signupForm').onsubmit = async event => {
    event.preventDefault();
    const button = event.submitter;
    button.disabled = true;
    $('signupMessage').textContent = 'Creating your organisation…';
    const payload = {
      plan_code: $('signupPlan').value, billing_market: window.SentinelMarkets.read(),
      company_name: $('signupCompany').value.trim(), site_name: $('signupSite').value.trim(),
      first_name: $('signupFirst').value.trim(), last_name: $('signupLast').value.trim(),
      email: $('signupEmail').value.trim(), phone: $('signupPhone').value.trim(), password: $('signupPassword').value
    };
    try {
      const response = await fetch('/api/v1/auth/signup', {method:'POST',headers:{Accept:'application/json','Content-Type':'application/json'},body:JSON.stringify(payload)});
      const result = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(result.message || 'Account creation failed.');
      $('identifier').value = payload.email;
      $('signupPassword').value = '';
      setMode(false);
      $('loginMessage').textContent = `${result.data.plan_code} account created. Sign in to continue.`;
    } catch (error) {
      $('signupMessage').textContent = error.message;
    } finally {
      button.disabled = false;
    }
  };
})();

