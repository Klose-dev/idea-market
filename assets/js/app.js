/* =============================================
   IDEA MARKET — MAIN JS
   ============================================= */

/* ── Dark Mode — applied IMMEDIATELY to prevent flash ── */
(function() {
  if (localStorage.getItem('darkMode') === 'true') {
    document.documentElement.classList.add('dark-mode-pending');
  }
})();

const DarkMode = {
  init() {
    // Apply saved preference immediately
    const saved = localStorage.getItem('darkMode') === 'true';
    if (saved) {
      document.body.classList.add('dark-mode');
    }
    document.documentElement.classList.remove('dark-mode-pending');

    // Sync all toggle UI elements to current state
    this.setUI(saved);

    // Attach click listeners to sidebar toggle switches (dashboard pages)
    document.querySelectorAll('.toggle-switch').forEach(el => {
      el.addEventListener('click', () => this.toggle());
    });
    // NOTE: landing page .toggle-pill listeners are handled separately
    // in each page's inline script to avoid double-listener conflicts
  },

  enable() {
    document.body.classList.add('dark-mode');
    this.setUI(true);
    localStorage.setItem('darkMode', 'true');
  },

  disable() {
    document.body.classList.remove('dark-mode');
    this.setUI(false);
    localStorage.setItem('darkMode', 'false');
  },

  toggle() {
    if (document.body.classList.contains('dark-mode')) {
      this.disable();
    } else {
      this.enable();
    }
  },

  setUI(on) {
    // Sync sidebar toggle switches
    document.querySelectorAll('.toggle-switch').forEach(el => {
      el.classList.toggle('active', on);
    });
    // Sync landing page pill toggles
    document.querySelectorAll('.toggle-pill').forEach(el => {
      el.classList.toggle('on', on);
      el.classList.toggle('active', on);
    });
  }
};

/* ── Sidebar Toggle (mobile) ───────────────── */
const Sidebar = {
  init() {
    const btn = document.querySelector('.sidebar-toggle');
    const sb  = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (btn && sb) {
      btn.addEventListener('click', () => { sb.classList.toggle('open'); if(overlay) overlay.classList.toggle('show'); });
      if (overlay) overlay.addEventListener('click', () => { sb.classList.remove('open'); overlay.classList.remove('show'); });
    }
  }
};

/* ── Tabs ──────────────────────────────────── */
function initTabs(container) {
  const el = container || document;
  el.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.tab;
      const parent = btn.closest('[data-tabs]') || btn.closest('.tab-container') || document;
      parent.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      parent.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const pane = parent.querySelector('#' + target) || parent.querySelector('.' + target);
      if (pane) pane.classList.add('active');
    });
  });
}

/* ── Modals ────────────────────────────────── */
const Modal = {
  open(id)  { const m = document.getElementById(id); if(m) { m.classList.add('open'); document.body.style.overflow='hidden'; } },
  close(id) { const m = document.getElementById(id); if(m) { m.classList.remove('open'); document.body.style.overflow=''; } },
  closeAll() { document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open')); document.body.style.overflow=''; },
  init() {
    document.querySelectorAll('[data-modal-open]').forEach(btn => btn.addEventListener('click', () => Modal.open(btn.dataset.modalOpen)));
    document.querySelectorAll('[data-modal-close]').forEach(btn => btn.addEventListener('click', () => Modal.close(btn.dataset.modalClose)));
    document.querySelectorAll('.modal-overlay').forEach(overlay => overlay.addEventListener('click', e => { if(e.target === overlay) Modal.closeAll(); }));
    document.addEventListener('keydown', e => { if(e.key==='Escape') Modal.closeAll(); });
  }
};

/* ── Toast Notifications ───────────────────── */
const Toast = {
  container: null,
  init() {
    this.container = document.createElement('div');
    this.container.style.cssText = 'position:fixed;top:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;pointer-events:none;';
    document.body.appendChild(this.container);
  },
  show(msg, type='info', duration=3500) {
    const colors = { success:'#16a34a', error:'#dc2626', info:'#2563eb', warning:'#f97316' };
    const icons  = { success:'fa-check-circle', error:'fa-times-circle', info:'fa-info-circle', warning:'fa-exclamation-triangle' };
    const t = document.createElement('div');
    t.style.cssText = `background:#fff;color:#1a2e55;border-left:4px solid ${colors[type]};border-radius:10px;padding:.85rem 1.25rem;box-shadow:0 8px 30px rgba(0,0,0,.15);font-size:.875rem;display:flex;align-items:center;gap:.75rem;pointer-events:all;min-width:260px;max-width:380px;animation:fadeUp .3s ease forwards;`;
    t.innerHTML = `<i class="fas ${icons[type]}" style="color:${colors[type]};font-size:1.1rem;flex-shrink:0;"></i><span>${msg}</span>`;
    this.container.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; t.style.transform='translateX(30px)'; t.style.transition='.3s'; setTimeout(()=>t.remove(), 300); }, duration);
  }
};

/* ── Count Up Animation ────────────────────── */
function countUp(el, target, duration=1200) {
  let start = 0, step = target / (duration / 16);
  const timer = setInterval(() => {
    start += step;
    if (start >= target) { el.textContent = target.toLocaleString(); clearInterval(timer); }
    else { el.textContent = Math.floor(start).toLocaleString(); }
  }, 16);
}

function initCountUps() {
  document.querySelectorAll('[data-countup]').forEach(el => {
    const target = parseInt(el.dataset.countup) || parseInt(el.textContent.replace(/\D/g,'')) || 0;
    el.dataset.countup = target;
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => { if(entry.isIntersecting) { countUp(el, target); observer.disconnect(); } });
    }, { threshold: .5 });
    observer.observe(el);
  });
}

/* ── Scroll Animations ─────────────────────── */
function initScrollAnimations() {
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('visible'); observer.unobserve(entry.target); }
    });
  }, { threshold: .15 });
  document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));
}

/* ── Vote Button Toggle ────────────────────── */
function initVoteButtons() {
  document.querySelectorAll('.idea-vote-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const voted = this.classList.toggle('voted');
      const countEl = this.querySelector('.vote-count');
      if (countEl) countEl.textContent = parseInt(countEl.textContent) + (voted ? 1 : -1);
    });
  });
}

/* ── Navbar scroll effect (landing) ───────── */
function initNavbarScroll() {
  const nav = document.querySelector('.landing-nav');
  if (!nav) return;
  window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 50), { passive: true });
}

/* ── Particles (landing hero) ──────────────── */
function initParticles() {
  const container = document.querySelector('.particles');
  if (!container) return;
  const colors = ['#2563eb','#f97316','#7c3aed','#16a34a','#f59e0b'];
  for (let i = 0; i < 18; i++) {
    const p = document.createElement('div');
    const size = Math.random() * 6 + 3;
    p.className = 'particle';
    p.style.cssText = `
      width:${size}px; height:${size}px;
      background:${colors[Math.floor(Math.random()*colors.length)]};
      left:${Math.random()*100}%;
      animation-duration:${Math.random()*12+8}s;
      animation-delay:${Math.random()*8}s;
    `;
    container.appendChild(p);
  }
}

/* ── Active Nav Link ───────────────────────── */
function initActiveNav() {
  const path = window.location.pathname.split('/').pop() || 'index.php';
  document.querySelectorAll('.sidebar-nav a, .nav-links a').forEach(a => {
    const href = a.getAttribute('href') || '';
    if (href === path || (path === '' && href === 'index.php')) a.classList.add('active');
  });
}

/* ── Form Validation ───────────────────────── */
function validateForm(formEl) {
  let valid = true;
  formEl.querySelectorAll('[required]').forEach(input => {
    const err = input.nextElementSibling;
    if (!input.value.trim()) {
      input.classList.add('error');
      if (err && err.classList.contains('form-error')) err.style.display = 'block';
      valid = false;
    } else {
      input.classList.remove('error');
      if (err && err.classList.contains('form-error')) err.style.display = 'none';
    }
    if (input.type === 'email' && input.value && !/\S+@\S+\.\S+/.test(input.value)) {
      input.classList.add('error'); valid = false;
    }
  });
  return valid;
}

/* ── Loading Spinner ───────────────────────── */
const Spinner = {
  show() { const s = document.querySelector('.spinner-overlay'); if(s) s.classList.add('show'); },
  hide() { const s = document.querySelector('.spinner-overlay'); if(s) s.classList.remove('show'); }
};

/* ── AJAX Polling ──────────────────────────── */
let pollingTimer = null;
function startPolling(endpoint, callback, interval=5000) {
  if (pollingTimer) clearInterval(pollingTimer);
  pollingTimer = setInterval(async () => {
    try {
      const res = await fetch(endpoint);
      if (res.ok) { const data = await res.json(); callback(data); }
    } catch(e) { /* silent fail */ }
  }, interval);
}
function stopPolling() { if(pollingTimer) { clearInterval(pollingTimer); pollingTimer = null; } }

/* ── Chart.js default config ───────────────── */
function applyChartDefaults() {
  if (typeof Chart === 'undefined') return;
  Chart.defaults.font.family = "'DM Sans', sans-serif";
  Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim() || '#64748b';
}

/* ── Relative Time ─────────────────────────── */
function timeAgo(dateStr) {
  const now = new Date(), then = new Date(dateStr);
  const diff = Math.floor((now - then) / 1000);
  if (diff < 60) return 'just now';
  if (diff < 3600) return Math.floor(diff/60) + ' min ago';
  if (diff < 86400) return Math.floor(diff/3600) + ' hr ago';
  if (diff < 604800) return Math.floor(diff/86400) + ' d ago';
  return then.toLocaleDateString();
}

/* ── Initialise Everything ─────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  DarkMode.init();
  Sidebar.init();
  Modal.init();
  Toast.init();
  initTabs();
  initScrollAnimations();
  initCountUps();
  initVoteButtons();
  initNavbarScroll();
  initParticles();
  initActiveNav();
  applyChartDefaults();
});
