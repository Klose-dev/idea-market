/* =============================================
   IDEA MARKET — CHARTS JS
   ============================================= */

/* ── Admin Dashboard — Doughnut Chart ─────── */
function initAdminDoughnut(data) {
  const ctx = document.getElementById('doughnutChart');
  if (!ctx) return;
  const isDark = document.body.classList.contains('dark-mode');
  return new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Pending', 'Under Review', 'Approved', 'Funded', 'Closed'],
      datasets: [{
        data: data || [35, 25, 20, 15, 5],
        backgroundColor: ['#f97316','#2563eb','#16a34a','#7c3aed','#dc2626'],
        borderWidth: 3,
        borderColor: isDark ? '#1e293b' : '#fff',
        hoverBorderWidth: 4,
        hoverOffset: 8
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      cutout: '68%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: { padding: 16, usePointStyle: true, pointStyleWidth: 10, font: { size: 12, weight: '600' } }
        },
        tooltip: {
          callbacks: {
            label(ctx) { const total = ctx.dataset.data.reduce((a,b)=>a+b,0); return ` ${ctx.label}: ${ctx.parsed} (${Math.round(ctx.parsed/total*100)}%)`; }
          }
        }
      },
      animation: { animateScale: true, animateRotate: true, duration: 900, easing: 'easeOutQuart' }
    }
  });
}

/* ── Admin Dashboard — Line Chart ──────────── */
function initAdminLine(data) {
  const ctx = document.getElementById('lineChart');
  if (!ctx) return;
  const labels = data?.labels || ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
  const lineOpts = { tension: .4, pointRadius: 4, pointHoverRadius: 7, borderWidth: 2.5 };
  return new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label: 'Submitted', data: data?.submitted || [12,19,8,22,14,18,10], borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.07)', fill: true, ...lineOpts },
        { label: 'Under Review', data: data?.review || [8,14,6,16,11,13,7], borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,.07)', fill: true, ...lineOpts },
        { label: 'Approved', data: data?.approved || [4,7,3,9,6,8,4], borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.07)', fill: true, ...lineOpts },
        { label: 'Funded', data: data?.funded || [2,3,1,4,2,3,2], borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,.07)', fill: true, ...lineOpts },
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top', labels: { padding: 16, usePointStyle: true, font: { size: 11, weight: '600' } } }
      },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { grid: { color: 'rgba(100,116,139,.08)' }, ticks: { font: { size: 11 } }, beginAtZero: true }
      },
      animation: { duration: 800, easing: 'easeOutQuart' }
    }
  });
}

/* ── User Dashboard — Doughnut ─────────────── */
function initUserDoughnut(data) {
  const ctx = document.getElementById('userDoughnut');
  if (!ctx) return;
  const isDark = document.body.classList.contains('dark-mode');
  return new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Pending', 'Under Review', 'Approved', 'Funded', 'Closed'],
      datasets: [{
        data: data || [3, 2, 4, 1, 0],
        backgroundColor: ['#f97316','#2563eb','#16a34a','#7c3aed','#dc2626'],
        borderWidth: 3,
        borderColor: isDark ? '#1e293b' : '#fff',
        hoverBorderWidth: 4
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      cutout: '65%',
      plugins: {
        legend: { position: 'bottom', labels: { padding: 14, usePointStyle: true, font: { size: 11 } } }
      },
      animation: { animateScale: true, duration: 900, easing: 'easeOutQuart' }
    }
  });
}

/* ── User Dashboard — Line ─────────────────── */
function initUserLine(data) {
  const ctx = document.getElementById('userLine');
  if (!ctx) return;
  return new Chart(ctx, {
    type: 'line',
    data: {
      labels: data?.labels || ['Jan','Feb','Mar','Apr','May','Jun','Jul'],
      datasets: [{
        label: 'Ideas Submitted',
        data: data?.ideas || [1,2,1,3,2,4,2],
        borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.08)', fill: true,
        tension: .4, pointRadius: 5, pointHoverRadius: 8, borderWidth: 2.5
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { grid: { color: 'rgba(100,116,139,.08)' }, beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }
      },
      animation: { duration: 800 }
    }
  });
}

/* ── Admin Reports — Category Bar ──────────── */
function initCategoryBar(data) {
  const ctx = document.getElementById('categoryChart');
  if (!ctx) return;
  return new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data?.labels || ['Tech','Social','Health','Education','Environment','Finance','Arts'],
      datasets: [{
        label: 'Ideas',
        data: data?.counts || [42,28,35,19,24,31,16],
        backgroundColor: ['#2563eb','#f97316','#16a34a','#7c3aed','#16a34a','#f59e0b','#dc2626'],
        borderRadius: 8, borderSkipped: false
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false } },
        y: { grid: { color: 'rgba(100,116,139,.08)' }, beginAtZero: true }
      },
      animation: { duration: 900 }
    }
  });
}

/* ── Chart update on dropdown change ───────── */
function initChartPeriodToggle(chart, endpoint) {
  document.querySelectorAll('.chart-period-select').forEach(sel => {
    sel.addEventListener('change', async function() {
      try {
        const res = await fetch(`${endpoint}?period=${this.value}`);
        if (res.ok) {
          const data = await res.json();
          if (chart && data.labels) {
            chart.data.labels = data.labels;
            chart.data.datasets.forEach((ds, i) => {
              const keys = Object.keys(data).filter(k => k !== 'labels');
              if (keys[i]) ds.data = data[keys[i]];
            });
            chart.update('active');
          }
        }
      } catch(e) { /* silent */ }
    });
  });
}

/* ── Init all charts on DOM ready ──────────── */
document.addEventListener('DOMContentLoaded', () => {
  const lineChart = initAdminLine();
  initAdminDoughnut();
  initUserDoughnut();
  initUserLine();
  initCategoryBar();
  initChartPeriodToggle(lineChart, 'api/get_chart_data.php');
});
