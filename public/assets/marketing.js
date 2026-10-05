(() => {
  'use strict';
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.documentElement.classList.add('motion-ready');

  const progress = document.createElement('div');
  progress.className = 'page-progress';
  progress.setAttribute('aria-hidden', 'true');
  document.body.prepend(progress);
  requestAnimationFrame(() => document.body.classList.add('page-ready'));

  if (!reduceMotion && 'IntersectionObserver' in window) {
    const revealItems = [...document.querySelectorAll(
      '.section-head, .feature-card, .platform-card, .plan-card, .step, .trust-item, .pathway-shell, .tour-shell, .content-page > *, .legal-page > *'
    )];
    revealItems.forEach((item, index) => {
      item.classList.add('reveal-ready');
      item.style.setProperty('--reveal-delay', `${Math.min(index % 4, 3) * 55}ms`);
    });
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    revealItems.forEach(item => observer.observe(item));
  }

  window.addEventListener('pageshow', () => {
    document.body.classList.remove('page-leaving');
    document.body.classList.add('page-ready');
  });

  document.addEventListener('click', event => {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (link.target === '_blank' || link.hasAttribute('download')) return;
    const destination = new URL(link.href, location.href);
    if (destination.origin !== location.origin || !['http:', 'https:'].includes(destination.protocol)) return;
    if (destination.pathname === location.pathname && destination.search === location.search && destination.hash) return;
    event.preventDefault();
    document.body.classList.add('page-leaving');
    window.setTimeout(() => location.assign(destination.href), reduceMotion ? 0 : 180);
  });
  const nav = document.querySelector('.nav-wrap');
  const toggle = document.querySelector('.menu-toggle');
  if (nav && toggle) {
    const setMenuOpen = open => {
      nav.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', String(open));
    };

    toggle.addEventListener('click', () => {
      setMenuOpen(!nav.classList.contains('open'));
    });

    nav.querySelectorAll('.main-nav a, .nav-actions a').forEach(link => {
      link.addEventListener('click', () => setMenuOpen(false));
    });

    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && nav.classList.contains('open')) {
        setMenuOpen(false);
        toggle.focus();
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 980) setMenuOpen(false);
    });
  }

  document.querySelectorAll('.faq-item button').forEach(button => {
    button.addEventListener('click', () => {
      const item = button.closest('.faq-item');
      const open = item.classList.toggle('open');
      button.setAttribute('aria-expanded', String(open));
      button.querySelector('span:last-child').textContent = open ? '−' : '+';
    });
  });

  const activateTabs = (buttonSelector, panelSelector, buttonAttribute, panelAttribute) => {
    const buttons = [...document.querySelectorAll(buttonSelector)];
    const panels = [...document.querySelectorAll(panelSelector)];
    buttons.forEach((button, index) => {
      const activate = () => {
        const value = button.dataset[buttonAttribute];
        buttons.forEach(item => item.setAttribute('aria-selected', String(item === button)));
        panels.forEach(panel => {
          const active = panel.dataset[panelAttribute] === value;
          panel.classList.toggle('active', active);
          if (active) {
            panel.classList.remove('panel-entering');
            requestAnimationFrame(() => panel.classList.add('panel-entering'));
          }
        });
      };
      button.addEventListener('click', activate);
      button.addEventListener('keydown', event => {
        if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;
        event.preventDefault();
        const direction = ['ArrowRight', 'ArrowDown'].includes(event.key) ? 1 : -1;
        const next = buttons[(index + direction + buttons.length) % buttons.length];
        next.focus();
        next.click();
      });
    });
  };

  activateTabs('.pathway-tab', '.pathway-panel', 'path', 'pathPanel');
  activateTabs('.tour-tab', '.tour-view', 'tour', 'tourView');

  document.querySelectorAll('[data-year]').forEach(node => {
    node.textContent = String(new Date().getFullYear());
  });
})();
