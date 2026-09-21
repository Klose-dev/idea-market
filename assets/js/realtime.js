/* =============================================
   IDEA MARKET — REAL-TIME JS
   Polls API every 5s for live updates
   ============================================= */

const RealTime = {
  timer: null,
  userId: null,
  isAdmin: false,

  init(userId, isAdmin = false) {
    this.userId  = userId;
    this.isAdmin = isAdmin;
    this.poll(); // immediate first call
    this.timer = setInterval(() => this.poll(), 5000);
  },

  stop() { if (this.timer) clearInterval(this.timer); },

  async poll() {
    try {
      const endpoint = this.isAdmin
        ? `api/get_updates.php?admin=1`
        : `api/get_updates.php?user_id=${this.userId}`;
      const res = await fetch(endpoint, { cache: 'no-store' });
      if (!res.ok) return;
      const data = await res.json();
      this.updateDashboard(data);
    } catch (e) { /* silent */ }
  },

  updateDashboard(data) {
    // Stat cards
    if (data.stats) {
      Object.entries(data.stats).forEach(([key, val]) => {
        const el = document.querySelector(`[data-stat="${key}"]`);
        if (el && el.textContent !== String(val)) {
          el.textContent = parseInt(val).toLocaleString();
          el.closest('.stat-card')?.classList.add('pulse');
          setTimeout(() => el.closest('.stat-card')?.classList.remove('pulse'), 600);
        }
      });
    }
    // Activity feed
    if (data.activity && Array.isArray(data.activity)) {
      const feed = document.querySelector('.activity-feed');
      if (!feed) return;
      data.activity.slice(0,8).forEach(item => {
        const existing = feed.querySelector(`[data-activity-id="${item.id}"]`);
        if (existing) return;
        const color = { pending:'orange', review:'blue', approved:'green', funded:'purple', closed:'red' }[item.status] || 'blue';
        const div = document.createElement('div');
        div.className = 'activity-item fade-up';
        div.dataset.activityId = item.id;
        div.innerHTML = `
          <span class="activity-dot ${color}"></span>
          <div class="activity-content">
            <p><strong>${escHtml(item.idea_title)}</strong> — ${escHtml(item.action)}</p>
            <span><i class="fas fa-clock me-1"></i>${timeAgo(item.timestamp)}</span>
          </div>`;
        feed.insertBefore(div, feed.firstChild);
        // keep max 8 items
        while (feed.children.length > 8) feed.removeChild(feed.lastChild);
      });
    }
    // Notification badge
    if (data.unread_notifications !== undefined) {
      const badge = document.querySelector('[data-notification-badge]');
      if (badge) {
        badge.textContent = data.unread_notifications || '';
        badge.style.display = data.unread_notifications ? 'inline-flex' : 'none';
      }
    }
  }
};

/* ── Voting via AJAX ───────────────────────── */
async function voteIdea(ideaId, btn) {
  if (btn._loading) return;
  btn._loading = true;
  try {
    const res = await fetch('api/vote_idea.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ idea_id: ideaId })
    });
    const data = await res.json();
    if (data.success) {
      btn.classList.toggle('voted', data.voted);
      const countEl = btn.querySelector('.vote-count');
      if (countEl) countEl.textContent = data.vote_count;
      Toast.show(data.voted ? 'Vote added!' : 'Vote removed', 'success', 2000);
    } else {
      Toast.show(data.message || 'Please log in to vote', 'warning');
    }
  } catch (e) {
    Toast.show('Connection error', 'error');
  }
  btn._loading = false;
}

/* ── Live Search on Tables ─────────────────── */
function initLiveSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;
  input.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

/* ── Status Filter ─────────────────────────── */
function initStatusFilter(selectId, tableId) {
  const sel = document.getElementById(selectId);
  const table = document.getElementById(tableId);
  if (!sel || !table) return;
  sel.addEventListener('change', function() {
    const val = this.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      const badge = row.querySelector('.badge');
      const status = badge ? badge.textContent.trim().toLowerCase() : '';
      row.style.display = (!val || status.includes(val)) ? '' : 'none';
    });
  });
}

/* ── Helpers ───────────────────────────────── */
function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function timeAgo(dateStr) {
  // Treat timestamps as WAT (UTC+1) from the server
  const now  = new Date();
  const then = new Date(dateStr);
  // If server stores naive datetime (no Z suffix), treat as WAT = UTC+1
  const offset = then.toString().includes('Z') ? 0 : 0; // server already sets tz
  const diff = Math.floor((now - then - offset) / 1000);
  if (diff < 0)      return 'just now';
  if (diff < 60)     return 'just now';
  if (diff < 3600)   return Math.floor(diff/60) + ' min ago';
  if (diff < 86400)  return Math.floor(diff/3600) + ' hr ago';
  if (diff < 604800) return Math.floor(diff/86400) + ' d ago';
  return then.toLocaleDateString('en-CM', { timeZone: 'Africa/Douala' });
}

/* ── Pulse animation CSS (injected) ─────────── */
const pulseStyle = document.createElement('style');
pulseStyle.textContent = `
  @keyframes statPulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.06)} }
  .stat-card.pulse .stat-value { animation: statPulse .5s ease; }
`;
document.head.appendChild(pulseStyle);
