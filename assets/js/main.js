/**
 * SealBay Australia — site behaviour (vanilla JS, no dependencies).
 * Navigation, scroll reveals, subtle parallax, drawing line animation, cylinder
 * legend highlighting, guide filter, checkout totals / delivery estimates and form helpers.
 */
(function () {
  'use strict';

  var doc = document;
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var $ = function (sel, ctx) { return (ctx || doc).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); };

  /* ---------- Header: sticky state + mobile navigation ---------- */
  var header = $('[data-header]');
  var nav = $('#site-nav');
  var burger = $('.nav-burger');
  var mobileNav = window.matchMedia('(max-width: 1199px)');

  function setNavTop() {
    if (header && nav) {
      nav.style.setProperty('--nav-top', Math.round(header.getBoundingClientRect().bottom) + 'px');
    }
  }

  function closeNav() {
    if (!nav || !burger) { return; }
    nav.classList.remove('is-open');
    burger.setAttribute('aria-expanded', 'false');
    burger.setAttribute('aria-label', 'Open menu');
    doc.body.classList.remove('nav-open');
  }

  if (burger && nav) {
    burger.addEventListener('click', function () {
      var open = !nav.classList.contains('is-open');
      setNavTop();
      nav.classList.toggle('is-open', open);
      burger.setAttribute('aria-expanded', String(open));
      burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      doc.body.classList.toggle('nav-open', open);
    });
    mobileNav.addEventListener('change', closeNav);
    doc.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') {
        closeNav();
        $$('.has-menu.is-open').forEach(function (item) { toggleMenu(item, false); });
      }
    });
  }

  function toggleMenu(item, open) {
    item.classList.toggle('is-open', open);
    var btn = $('.nav__toggle', item);
    if (btn) { btn.setAttribute('aria-expanded', String(open)); }
  }

  $$('.has-menu').forEach(function (item) {
    var btn = $('.nav__toggle', item);
    if (!btn) { return; }
    btn.addEventListener('click', function () {
      var open = !item.classList.contains('is-open');
      $$('.has-menu.is-open').forEach(function (other) { if (other !== item) { toggleMenu(other, false); } });
      toggleMenu(item, open);
    });
    item.addEventListener('mouseleave', function () {
      if (!mobileNav.matches) { toggleMenu(item, false); }
    });
  });

  doc.addEventListener('click', function (ev) {
    if (!mobileNav.matches && !ev.target.closest('.has-menu')) {
      $$('.has-menu.is-open').forEach(function (item) { toggleMenu(item, false); });
    }
  });

  /* ---------- Scroll-linked: header shadow, sticky CTA, parallax ---------- */
  var parallaxEls = reduceMotion ? [] : $$('[data-parallax]');
  var ticking = false;

  function onScroll() {
    var y = window.scrollY || window.pageYOffset;
    if (header) { header.classList.toggle('is-stuck', y > 48); }

    if (parallaxEls.length && window.innerWidth > 991) {
      var vh = window.innerHeight;
      parallaxEls.forEach(function (el) {
        var rect = el.getBoundingClientRect();
        if (rect.bottom < 0 || rect.top > vh) { return; }
        var progress = (rect.top + rect.height / 2 - vh / 2) / vh;   // -1 … 1
        var img = el.querySelector('img');
        if (img) { img.style.transform = 'translate3d(0,' + (progress * -28).toFixed(1) + 'px,0) scale(1.08)'; }
      });
    }
    ticking = false;
  }

  window.addEventListener('scroll', function () {
    if (!ticking) { ticking = true; window.requestAnimationFrame(onScroll); }
  }, { passive: true });
  onScroll();

  /* ---------- Reveal on scroll, counters, line drawing ---------- */
  function animateCount(el) {
    var target = parseInt(el.getAttribute('data-count'), 10);
    if (!target || reduceMotion) { return; }
    var start = null;
    var duration = 1400;
    el.textContent = '0';
    function step(ts) {
      if (start === null) { start = ts; }
      var p = Math.min((ts - start) / duration, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = String(Math.round(target * eased));
      if (p < 1) { window.requestAnimationFrame(step); }
    }
    window.requestAnimationFrame(step);
  }

  var drawPaths = reduceMotion ? [] : $$('.draw');
  drawPaths.forEach(function (path) {
    if (typeof path.getTotalLength !== 'function') { return; }
    var len = Math.ceil(path.getTotalLength());
    path.style.strokeDasharray = len;
    path.style.strokeDashoffset = len;
  });

  function drawIn(scope) {
    $$('.draw', scope).forEach(function (path) { path.style.strokeDashoffset = '0'; });
  }

  var revealEls = $$('[data-reveal]');
  var countEls = $$('[data-count]');
  var drawScopes = $$('.il').filter(function (svg) { return svg.querySelector('.draw'); });

  if ('IntersectionObserver' in window) {
    var revealObserver = new IntersectionObserver(function (entries, obs) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-in');
          obs.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0 });
    revealEls.forEach(function (el) { revealObserver.observe(el); });

    var onceObserver = new IntersectionObserver(function (entries, obs) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) { return; }
        if (entry.target.hasAttribute('data-count')) { animateCount(entry.target); } else { drawIn(entry.target); }
        obs.unobserve(entry.target);
      });
    }, { threshold: 0.3 });
    countEls.forEach(function (el) { onceObserver.observe(el); });
    drawScopes.forEach(function (el) { onceObserver.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add('is-in'); });
    drawIn(doc);
  }

  /* ---------- Hydraulic cylinder: legend ↔ drawing highlight ---------- */
  $$('[data-cylinder]').forEach(function (figure) {
    var svg = $('.il--cylinder', figure);
    if (!svg) { return; }
    function highlight(part) {
      $$('[data-part]', figure).forEach(function (el) {
        el.classList.toggle('is-active', part !== null && el.getAttribute('data-part') === part);
      });
      svg.classList.toggle('has-active', part !== null);
    }
    $$('[data-part]', figure).forEach(function (el) {
      var part = el.getAttribute('data-part');
      el.addEventListener('mouseenter', function () { highlight(part); });
      el.addEventListener('mouseleave', function () { highlight(null); });
      el.addEventListener('focus', function () { highlight(part); });
      el.addEventListener('blur', function () { highlight(null); });
    });
  });

  /* ---------- Knowledge Centre topic filter ---------- */
  var filter = $('[data-filter]');
  if (filter) {
    var cards = $$('[data-topic]');
    filter.addEventListener('click', function (ev) {
      var btn = ev.target.closest('button[data-topic-filter]');
      if (!btn) { return; }
      var topic = btn.getAttribute('data-topic-filter');
      $$('button', filter).forEach(function (b) { b.setAttribute('aria-pressed', String(b === btn)); });
      cards.forEach(function (card) {
        card.hidden = topic !== 'all' && card.getAttribute('data-topic') !== topic;
        card.classList.add('is-in');
      });
    });
  }

  /* ---------- Shop: filters and checkout ---------- */
  $$('form[data-autosubmit]').forEach(function (form) {
    form.addEventListener('change', function (ev) {
      if (ev.target.tagName === 'SELECT') { form.submit(); }
    });
  });

  function postcodeState(postcode) {
    if (!/^\d{4}$/.test(postcode)) { return null; }
    var n = parseInt(postcode, 10);
    var ranges = {
      ACT: [[200, 299], [2600, 2618], [2900, 2920]],
      NSW: [[1000, 2599], [2619, 2899], [2921, 2999]],
      VIC: [[3000, 3999], [8000, 8999]],
      QLD: [[4000, 4999], [9000, 9999]],
      SA: [[5000, 5999]], WA: [[6000, 6999]], TAS: [[7000, 7999]], NT: [[800, 999]]
    };
    for (var state in ranges) {
      for (var i = 0; i < ranges[state].length; i++) {
        if (n >= ranges[state][i][0] && n <= ranges[state][i][1]) { return state; }
      }
    }
    return null;
  }

  function dollars(cents) {
    return '$' + (cents / 100).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  var checkout = $('form[data-checkout]');
  if (checkout) {
    var estimates = {};
    try { estimates = JSON.parse(checkout.getAttribute('data-estimates') || '{}'); } catch (err) { estimates = {}; }
    var postcodeInput = $('#f-postcode', checkout);
    var stateSelect = $('#f-state', checkout);
    var totalEl = $('[data-total]', checkout);
    var shippingEl = $('[data-shipping]', checkout);
    var gstEl = $('[data-gst]', checkout);

    var updateEstimates = function () {
      var state = postcodeState(postcodeInput ? postcodeInput.value.trim() : '');
      if (state && stateSelect && !stateSelect.value) { stateSelect.value = state; }
      $$('[data-estimate]', checkout).forEach(function (el) {
        var text = state && estimates[state] ? estimates[state][el.getAttribute('data-estimate')] : '';
        el.textContent = text ? 'Estimated ' + text + ' to ' + state : 'Enter your postcode for a delivery estimate';
      });
    };
    var updateTotals = function () {
      var chosen = $('input[name="shipping_method"]:checked', checkout);
      if (!chosen || !totalEl) { return; }
      var shipping = parseInt(chosen.getAttribute('data-cents'), 10) || 0;
      var total = (parseInt(totalEl.getAttribute('data-subtotal'), 10) || 0) + shipping;
      if (shippingEl) { shippingEl.textContent = shipping === 0 ? 'Free' : dollars(shipping); }
      totalEl.textContent = dollars(total);
      if (gstEl) { gstEl.textContent = dollars(Math.round(total / 11)); }
    };
    if (postcodeInput) { postcodeInput.addEventListener('input', updateEstimates); }
    $$('input[name="shipping_method"]', checkout).forEach(function (radio) { radio.addEventListener('change', updateTotals); });
    checkout.addEventListener('submit', function () {
      var btn = $('button[type="submit"]', checkout);
      if (btn) { window.setTimeout(function () { btn.disabled = true; }, 0); }
    });
  }

  /* ---------- Forms ---------- */
  $$('form[data-enquiry-form]').forEach(function (form) {
    var file = $('input[type="file"]', form);
    var maxBytes = file ? parseInt(file.getAttribute('data-max-bytes'), 10) : 0;
    var note = file ? $('[data-file-note]', form) : null;

    if (file && note) {
      file.addEventListener('change', function () {
        var f = file.files && file.files[0];
        note.textContent = '';
        if (f && maxBytes && f.size > maxBytes) {
          note.textContent = 'This file is larger than the allowed size. Please choose a smaller file.';
          file.value = '';
        }
      });
    }

    form.addEventListener('submit', function () {
      var btn = $('button[type="submit"]', form);
      if (btn && form.checkValidity()) {
        btn.disabled = true;
        btn.setAttribute('data-label', btn.textContent);
        btn.firstChild.textContent = 'Sending… ';
      }
    });
  });

  // Bring the first form error into view after a failed submission.
  var formAlert = $('.alert--error');
  if (formAlert) { formAlert.scrollIntoView({ block: 'center' }); }
})();
