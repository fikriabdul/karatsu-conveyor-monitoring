<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>唐津砕石 Conveyor Belt Monitor — B1</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --bg: #F7F6F3;
    --surface: #FFFFFF;
    --surface-2: #F1F0ED;
    --border: #E5E4E0;
    --text-primary: #1A1A18;
    --text-secondary: #6B6A65;
    --text-muted: #9E9D98;
    --blue: #2563EB;
    --green: #16A34A;
    --green-light: #F0FDF4;
    --amber: #B45309;
    --red: #DC2626;
    --red-light: #FEF2F2;
    --font: 'DM Sans', sans-serif;
    --mono: 'DM Mono', monospace;
  }
  body { font-family: var(--font); background: var(--bg); color: var(--text-primary); min-height: 100vh; font-size: 14px; line-height: 1.5; }
  .shell { max-width: 1500px; margin: 0 auto; padding: 2rem 1.5rem; }
  .topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap; }
  .topbar-title { font-size: 28px; font-weight: 500; color: var(--text-primary); letter-spacing: -0.01em; }
  .topbar-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
  .live-badge { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 500; color: var(--green); background: var(--green-light); padding: 5px 12px; border-radius: 999px; border: 1px solid #BBF7D0; }
  .live-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--green); animation: pulse 2s infinite; }
  @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
  .datetime { font-size: 12px; color: var(--text-muted); font-family: var(--mono); }
  .metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 1.5rem; }
  @media (max-width: 600px) { .metrics { grid-template-columns: repeat(2, 1fr); } }
  .metric-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 16px 18px; position: relative; overflow: hidden; }
  .metric-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 12px 12px 0 0; background: var(--blue); }
  .metric-card.error-card::before { background: var(--amber); }
  .metric-label { font-size: 11px; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
  .metric-value { font-size: 28px; font-weight: 300; color: var(--text-primary); letter-spacing: -0.02em; font-family: var(--mono); line-height: 1; }
  .metric-value.error-positive { color: var(--amber); }
  .metric-unit { font-size: 12px; color: var(--text-muted); font-weight: 400; margin-left: 2px; }
  .metric-sub { font-size: 11px; margin-top: 6px; font-family: var(--mono); color: var(--text-muted); }
  .belt-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--blue); display: inline-block; flex-shrink: 0; }
  .chart-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; }
  .chart-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 10px; }
  .chart-title { font-size: 14px; font-weight: 500; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
  .chart-meta { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
  .rmse-badge { font-size: 11px; font-family: var(--mono); padding: 4px 10px; border-radius: 6px; background: var(--surface-2); color: var(--text-secondary); border: 1px solid var(--border); }
  .chart-legend { display: flex; gap: 16px; }
  .legend-item { display: flex; align-items: center; gap: 7px; font-size: 12px; color: var(--text-secondary); }
  .legend-line { width: 18px; height: 2px; border-radius: 1px; }
  .chart-wrap { height: 380px; position: relative; }
  .section-label { font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); font-family: var(--mono); margin-bottom: 0.75rem; margin-top: 1.75rem; }
  /* Stopped belt state */
  .live-badge.stopped { color: var(--text-muted); background: var(--surface-2); border-color: var(--border); }
  .live-badge.stopped .live-dot { background: var(--text-muted); animation: none; }
  /* Offline state — feed hasn't sent new data in a while */
  .live-badge.offline { color: var(--red); background: var(--red-light); border-color: #FECACA; }
  .live-badge.offline .live-dot { background: var(--red); animation: none; }
  .metric-value.dim { color: var(--text-muted) !important; }
  .last-updated { font-size: 11px; font-family: var(--mono); color: var(--text-muted); margin-top: -0.5rem; margin-bottom: 0.75rem; }
</style>
</head>
<body>
<div class="shell">

  <div class="topbar">
    <div class="topbar-title">唐津砕石 CONVEYOR BELT MONITOR</div>
    <div class="topbar-right">
      <div class="live-badge" id="status-badge"><div class="live-dot" id="status-dot"></div><span id="status-text">Live</span></div>
      <div class="datetime" id="datetime">—</div>
    </div>
  </div>

  <div class="section-label">Operating Status</div>
  <div class="metrics">
    <div class="metric-card">
      <div class="metric-label"><span class="belt-dot"></span> Actual</div>
      <div class="metric-value"><span id="b1-actual">—</span><span class="metric-unit">t/h</span></div>
      <div class="metric-sub">Belt scale reading</div>
    </div>
    <div class="metric-card">
      <div class="metric-label"><span class="belt-dot"></span> Calculated from Current</div>
      <div class="metric-value"><span id="b1-predicted">—</span><span class="metric-unit">t/h</span></div>
    </div>
    <div class="metric-card">
      <div class="metric-label">Current</div>
      <div class="metric-value"><span id="b1-current">—</span><span class="metric-unit">A</span></div>
      <div class="metric-sub" id="b1-status">—</div>
    </div>
    <div class="metric-card error-card">
      <div class="metric-label">Error</div>
      <div class="metric-value" id="b1-error-wrap"><span id="b1-error">—</span><span class="metric-unit">t/h</span></div>
      <div class="metric-sub">actual − calculation</div>
    </div>
  </div>

  <div class="last-updated" id="b1-last-updated"></div>

  <div class="section-label">Trend — actual vs calculation</div>
  <div class="chart-card">
    <div class="chart-header">
      <div class="chart-title"><span class="belt-dot"></span> Belt B1 — Throughput</div>
      <div class="chart-meta">
        <span class="rmse-badge">RMSE 12.67 t/h &nbsp;·&nbsp; R² 0.84</span>
        <span class="rmse-badge" id="avg-error-badge" style="display:none;"></span>
        <div class="chart-legend">
          <div class="legend-item"><div class="legend-line" style="background:#2563EB;"></div> Actual</div>
          <div class="legend-item"><div class="legend-line" style="background:#F59E0B;"></div> Calculation</div>
        </div>
      </div>
    </div>
    <div class="chart-wrap"><canvas id="chartB1"></canvas></div>
  </div>

</div>

<script>
  const API_URL = 'api.php';
  const REFRESH_INTERVAL = 1 * 60 * 1000; // 1 minute — catches delayed FTP uploads sooner
  const activeRange = 'today';
  let chart = null;

  // ── Chart ────────────────────────────────────────────────
  function initChart() {
    const ctx = document.getElementById('chartB1').getContext('2d');
    chart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: [],
        datasets: [
          {
            label: 'Actual',
            data: [],
            borderColor: '#2563EB',
            borderWidth: 3,
            pointRadius: 0,
            pointHoverRadius: 4,
            pointStyle: false,
            pointBackgroundColor: '#2563EB',
            tension: 0.35,
            fill: false,
            spanGaps: false,
          },
          {
            label: 'Calculation',
            data: [],
            borderColor: '#F59E0B',
            borderWidth: 2.5,
            pointRadius: 0,
            pointHoverRadius: 3,
            pointStyle: false,
            tension: 0.35,
            fill: false,
            spanGaps: false,
          },
          {
            label: 'No data',
            data: [],
            borderColor: '#D4D4D2',
            borderWidth: 1.5,
            borderDash: [4, 4],
            pointRadius: 0,
            pointStyle: false,
            tension: 0,
            fill: false,
            spanGaps: false,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        elements: { point: { radius: 0 } },
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#fff',
            borderColor: '#E5E4E0',
            borderWidth: 1,
            titleColor: '#1A1A18',
            bodyColor: '#6B6A65',
            padding: 10,
            titleFont: { family: 'DM Mono', size: 11 },
            bodyFont: { family: 'DM Sans', size: 12 }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            border: { display: false },
            ticks: { font: { family: 'DM Mono', size: 10 }, color: '#9E9D98', autoSkip: true, maxRotation: 0 },
            title: { display: true, text: 'Time', font: { family: 'DM Sans', size: 11 }, color: '#9E9D98', padding: { top: 8 } }
          },
          y: {
            grid: { color: '#F1F0ED' },
            border: { display: false },
            ticks: { font: { family: 'DM Mono', size: 10 }, color: '#9E9D98' },
            beginAtZero: false,
            title: { display: true, text: 'ton/h', font: { family: 'DM Sans', size: 11 }, color: '#9E9D98', padding: { bottom: 8 } }
          }
        }
      }
    });
  }

  function updateChart(trend) {
    if (!chart || !trend.length) return;
    chart.data.labels              = trend.map(p => p.label);
    chart.data.datasets[0].data   = trend.map(p => p.actual);
    chart.data.datasets[1].data   = trend.map(p => p.predicted);
    // "No data" dataset: a flat dashed line at 0 that only appears where
    // actual/predicted are missing, marking gaps in the FTP feed
    chart.data.datasets[2].data   = trend.map(p => p.actual === null ? 0 : null);
    chart.update('active');
  }

  // ── Fetch ────────────────────────────────────────────────
  async function fetchLatest() {
    try {
      const res = await fetch(`${API_URL}?action=latest`);
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();

      // offline: feed itself is stale — readings can't be trusted
      // plantIdle: belt isn't moving (no actual throughput) — drives badge/actual
      // calcIdle: ampere reading too low for a calculation — drives "Calculated from Current"
      const offline   = data.status === 'offline';
      const plantIdle = offline || data.b1_actual <= 0;
      const calcIdle  = offline || data.status === 'idle';

      // Badge: green "Live" when running, grey "Stopped" when idle, red "No Signal" when offline
      const badge = document.getElementById('status-badge');
      badge.classList.toggle('offline', offline);
      badge.classList.toggle('stopped', plantIdle && !offline);
      document.getElementById('status-text').textContent = offline ? 'No Signal' : (plantIdle ? 'Stopped' : 'Live');

      // Metric values
      document.getElementById('b1-actual').textContent    = plantIdle ? '0' : data.b1_actual;
      document.getElementById('b1-predicted').textContent = calcIdle ? '0' : data.b1_predicted;
      document.getElementById('b1-current').textContent   = data.b1_ampere;

      // Dim actual/predicted when idle
      document.getElementById('b1-actual').closest('.metric-value').classList.toggle('dim', plantIdle);
      document.getElementById('b1-predicted').closest('.metric-value').classList.toggle('dim', calcIdle);

      // Error: needs both actual and calculation to be meaningful
      const errSpan = document.getElementById('b1-error');
      const errWrap = document.getElementById('b1-error-wrap');
      const errUnit = errWrap.querySelector('.metric-unit');
      if (plantIdle || calcIdle) {
        errSpan.textContent = '—';
        errUnit.style.visibility = 'hidden';
        errWrap.classList.remove('error-positive');
      } else {
        const prefix = data.b1_error > 0 ? '+' : '';
        errSpan.textContent = prefix + data.b1_error;
        errUnit.style.visibility = 'visible';
        errWrap.classList.toggle('error-positive', data.b1_error !== 0);
      }

      // Status sub-text on current card
      document.getElementById('b1-status').textContent =
        offline ? 'No signal' : (plantIdle ? 'Belt stopped' : 'Running');

      // Last updated timestamp
      document.getElementById('b1-last-updated').textContent = offline
        ? `No data for ${data.data_age_minutes} min · last seen ${data.timestamp}`
        : 'Last updated · ' + data.timestamp;

    } catch (err) {
      console.error('fetchLatest failed:', err);
    }
  }

  async function fetchTrend(range) {
    try {
      const res = await fetch(`${API_URL}?action=trend&range=${range}`);
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      updateChart(data.trend);

      const avgErrorBadge = document.getElementById('avg-error-badge');
      if (data.avg_error !== null && data.avg_error !== undefined) {
        avgErrorBadge.textContent = `Avg error (${range}): ${data.avg_error} t/h`;
        avgErrorBadge.style.display = '';
      } else {
        avgErrorBadge.style.display = 'none';
      }
    } catch (err) {
      console.error('fetchTrend failed:', err);
    }
  }

  // ── Clock ────────────────────────────────────────────────
  function updateDatetime() {
    const now  = new Date();
    const date = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    const time = now.toTimeString().slice(0, 8);
    document.getElementById('datetime').textContent = date + ' · ' + time;
  }

  // ── Init ─────────────────────────────────────────────────
  updateDatetime();
  setInterval(updateDatetime, 1000);

  initChart();
  fetchLatest();
  fetchTrend(activeRange);
  setInterval(() => { fetchLatest(); fetchTrend(activeRange); }, REFRESH_INTERVAL);
</script>
</body>
</html>
