(() => {
  'use strict';

  const STORAGE_KEY = 'sentinel_billing_market';
  const markets = Object.freeze({
    ZA: {label: 'South Africa', locale: 'en-ZA', currency: 'ZAR', essential: 49, professional: 89, tax: 'VAT will be shown before payment if applicable.'},
    US: {label: 'United States', locale: 'en-US', currency: 'USD', essential: 5, professional: 8.5, tax: 'Applicable sales tax will be shown before payment.'},
    GB: {label: 'United Kingdom', locale: 'en-GB', currency: 'GBP', essential: 4, professional: 7, tax: 'Applicable VAT will be shown before payment.'},
    EU: {label: 'European Union', locale: 'en-IE', currency: 'EUR', essential: 4.5, professional: 8, tax: 'Applicable VAT will be shown before payment.'},
    AU: {label: 'Australia', locale: 'en-AU', currency: 'AUD', essential: 7.5, professional: 13, tax: 'Applicable GST will be shown before payment.'},
    OTHER: {label: 'Other countries', locale: 'en-US', currency: 'USD', essential: 5, professional: 8.5, tax: 'Local taxes will be shown before payment where applicable.'}
  });

  const read = () => {
    try {
      const saved = window.localStorage.getItem(STORAGE_KEY);
      return Object.hasOwn(markets, saved) ? saved : 'ZA';
    } catch {
      return 'ZA';
    }
  };

  const write = marketCode => {
    const code = Object.hasOwn(markets, marketCode) ? marketCode : 'ZA';
    try { window.localStorage.setItem(STORAGE_KEY, code); } catch {}
    return code;
  };

  const money = (amount, marketCode = read()) => {
    const market = markets[marketCode] || markets.ZA;
    return new Intl.NumberFormat(market.locale, {
      style: 'currency', currency: market.currency,
      minimumFractionDigits: Number.isInteger(amount) ? 0 : 2,
      maximumFractionDigits: 2
    }).format(amount);
  };

  const planPrice = (plan, marketCode = read()) => {
    const market = markets[marketCode] || markets.ZA;
    const key = String(plan || '').toLowerCase();
    return Object.hasOwn(market, key) ? money(market[key], marketCode) : '';
  };

  const planDescription = (plan, marketCode = read()) => {
    const price = planPrice(plan, marketCode);
    if (plan === 'ESSENTIAL') return `${price} per active user/month. Up to 30 users and 10 sites, adding workforce administration, attendance, shifts and operational reports. Includes a 14-day trial.`;
    if (plan === 'PROFESSIONAL') return `${price} per active user/month. Up to 100 users and 50 sites, adding patrols, devices, GPS, geofences and supervisor workflows. Includes a 14-day trial.`;
    return '';
  };

  const apply = (marketCode = read()) => {
    const code = write(marketCode);
    const market = markets[code];
    document.documentElement.dataset.market = code;
    document.querySelectorAll('[data-market-select]').forEach(select => { select.value = code; });
    document.querySelectorAll('[data-plan-price]').forEach(element => {
      const value = document.createElement('span');
      value.textContent = planPrice(element.dataset.planPrice, code);
      const interval = document.createElement('small');
      interval.textContent = ` ${market.currency} / active user / month`;
      element.replaceChildren(value, interval);
    });
    document.querySelectorAll('[data-market-note]').forEach(element => {
      element.textContent = `Prices are shown for ${market.label} in ${market.currency}. ${market.tax} Paid billing is not yet enabled; no charge begins without a separate checkout and acceptance.`;
    });
    document.dispatchEvent(new CustomEvent('sentinel:market-change', {detail: {code, market}}));
    return code;
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-market-select]').forEach(select => {
      select.addEventListener('change', () => apply(select.value));
    });
    apply();
  });

  window.SentinelMarkets = Object.freeze({markets, read, apply, planPrice, planDescription});
})();
