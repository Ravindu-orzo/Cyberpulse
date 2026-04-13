<?php
require_once 'db.php';
session_start();
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
$operator = htmlspecialchars($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cyber Pulse — Intel Map</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  :root {
    --bg: #080c10;
    --surface: #0d1117;
    --surface2: #131920;
    --border: #1e2d3d;
    --text: #c9d1d9;
    --text-dim: #4a5568;
    --text-muted: #2d3748;
    --accent: #00d4ff;
    --green: rgba(80,220,120,0.9);
    --green-glow: rgba(80,220,120,0.25);
    --red: #ff4d4d;
    --sidebar-w: 300px;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; background: var(--bg); color: var(--text); font-family: 'Space Mono', monospace; overflow: hidden; }

  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background: repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,0.025) 2px,rgba(0,0,0,0.025) 4px);
    pointer-events: none;
    z-index: 1000;
  }

  /* ── LAYOUT ── */
  #app { display: flex; flex-direction: column; height: 100vh; }
  #main { display: flex; flex: 1; overflow: hidden; position: relative; }

  /* ── HEADER ── */
  header { padding: 12px 24px; display: flex; align-items: center; gap: 14px; flex-shrink: 0; border-bottom: 1px solid var(--border); background: var(--bg); z-index: 100; }
  .site-title { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1rem; letter-spacing: 0.15em; color: var(--accent); text-transform: uppercase; text-shadow: 0 0 20px rgba(0,212,255,0.2); }
  .site-sub   { font-size: 0.6rem; color: var(--text-dim); letter-spacing: 0.1em; }
  .nav-links  { margin-left: auto; display: flex; align-items: center; gap: 12px; }
  .nav-link   { font-size: 0.6rem; letter-spacing: 0.12em; text-transform: uppercase; color: var(--text-dim); text-decoration: none; padding: 4px 10px; border: 1px solid transparent; border-radius: 3px; transition: all 0.2s; }
  .nav-link:hover  { border-color: var(--border); color: var(--text); }
  .nav-link.active { border-color: rgba(80,220,120,0.3); color: var(--green); }
  .operator-tag { font-size: 0.58rem; color: var(--text-dim); }
  .operator-tag span { color: var(--accent); }
  .logout-btn { background: none; border: 1px solid rgba(255,77,77,0.2); color: rgba(255,77,77,0.6); font-family: 'Space Mono', monospace; font-size: 0.55rem; letter-spacing: 0.1em; padding: 4px 10px; border-radius: 3px; cursor: pointer; text-transform: uppercase; transition: all 0.2s; }
  .logout-btn:hover { border-color: rgba(255,77,77,0.5); color: var(--red); }

  /* ── SIDEBAR ── */
  #sidebar {
    width: var(--sidebar-w);
    background: var(--surface);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    z-index: 10;
    overflow: hidden;
  }

  .sidebar-section { padding: 14px 16px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
  .sidebar-label   { font-size: 0.55rem; letter-spacing: 0.18em; color: var(--text-dim); text-transform: uppercase; margin-bottom: 10px; }

  /* Search */
  .search-wrap { display: flex; align-items: center; gap: 8px; background: var(--surface2); border: 1px solid var(--border); border-radius: 4px; padding: 6px 10px; }
  .search-wrap svg { width: 12px; height: 12px; stroke: var(--text-dim); flex-shrink: 0; }
  #search-input { background: transparent; border: none; color: var(--text); font-family: 'Space Mono', monospace; font-size: 0.7rem; flex: 1; outline: none; }
  #search-input::placeholder { color: var(--text-muted); }

  /* Playback controls */
  .play-controls { display: flex; align-items: center; gap: 8px; }
  .play-btn {
    display: flex; align-items: center; gap: 6px;
    background: rgba(0,212,255,0.08); border: 1px solid rgba(0,212,255,0.25);
    color: var(--accent); font-family: 'Space Mono', monospace; font-size: 0.6rem;
    letter-spacing: 0.1em; padding: 6px 14px; border-radius: 4px; cursor: pointer;
    text-transform: uppercase; transition: all 0.2s; white-space: nowrap;
  }
  .play-btn:hover { background: rgba(0,212,255,0.15); box-shadow: 0 0 10px rgba(0,212,255,0.15); }
  .play-btn.stop  { border-color: rgba(255,77,77,0.3); color: var(--red); background: rgba(255,77,77,0.05); }
  .play-btn svg   { width: 10px; height: 10px; fill: currentColor; }

  #play-status { font-size: 0.6rem; color: var(--text-dim); flex: 1; }

  /* Date list */
  #date-list-wrap { flex: 1; overflow-y: auto; padding: 8px 0; }
  #date-list-wrap::-webkit-scrollbar { width: 3px; }
  #date-list-wrap::-webkit-scrollbar-thumb { background: var(--border); }

  .date-item {
    padding: 8px 16px; cursor: pointer; font-size: 0.65rem; letter-spacing: 0.05em;
    color: var(--text-dim); transition: all 0.15s; display: flex; align-items: center; gap: 10px;
    border-left: 2px solid transparent;
  }
  .date-item:hover { background: rgba(255,255,255,0.03); color: var(--text); }
  .date-item.active { border-left-color: var(--accent); color: var(--accent); background: rgba(0,212,255,0.05); }

  .date-item .pin-count {
    margin-left: auto; font-size: 0.55rem; background: var(--surface2); padding: 2px 7px;
    border-radius: 999px; color: var(--text-dim);
  }
  .date-item.active .pin-count { background: rgba(0,212,255,0.12); color: var(--accent); }

  /* Search results */
  #results-wrap { flex: 1; overflow-y: auto; padding: 8px 0; display: none; }
  #results-wrap::-webkit-scrollbar { width: 3px; }
  #results-wrap::-webkit-scrollbar-thumb { background: var(--border); }

  .result-item {
    padding: 10px 16px; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.03);
    transition: background 0.15s;
  }
  .result-item:hover { background: rgba(255,255,255,0.03); }
  .result-item .r-title  { font-size: 0.7rem; color: var(--text); margin-bottom: 3px; }
  .result-item .r-date   { font-size: 0.55rem; color: var(--text-dim); letter-spacing: 0.08em; }
  .result-item .r-desc   { font-size: 0.62rem; color: var(--text-dim); margin-top: 4px; line-height: 1.5; }
  .result-item.highlight { background: rgba(0,212,255,0.06); border-left: 2px solid var(--accent); }

  .no-results { padding: 20px 16px; font-size: 0.65rem; color: var(--text-muted); text-align: center; }

  /* ── MAP ── */
  #map-wrap { flex: 1; position: relative; }
  #map { width: 100%; height: 100%; }

  /* Leaflet dark overrides */
  .leaflet-container { background: #050a0f !important; }
  .leaflet-tile { filter: brightness(0.7) saturate(0.5) hue-rotate(180deg); }
  .leaflet-control-zoom a { background: var(--surface2) !important; color: var(--text-dim) !important; border-color: var(--border) !important; }
  .leaflet-control-zoom a:hover { background: var(--surface) !important; color: var(--text) !important; }
  .leaflet-control-attribution { background: rgba(8,12,16,0.8) !important; color: var(--text-muted) !important; font-size: 0.5rem !important; }
  .leaflet-control-attribution a { color: var(--text-dim) !important; }
  .leaflet-popup-content-wrapper { background: var(--surface2) !important; border: 1px solid var(--border) !important; color: var(--text) !important; border-radius: 6px !important; box-shadow: 0 4px 20px rgba(0,0,0,0.6) !important; }
  .leaflet-popup-tip { background: var(--surface2) !important; }
  .leaflet-popup-close-button { color: var(--text-dim) !important; }

  /* ── PIN MODAL ── */
  #pin-modal {
    display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
    background: var(--surface); border: 1px solid var(--border); border-radius: 8px;
    padding: 24px; width: 320px; z-index: 500; box-shadow: 0 8px 40px rgba(0,0,0,0.6);
    animation: modalIn 0.2s ease;
  }
  @keyframes modalIn { from { opacity:0; transform:translate(-50%,-55%); } to { opacity:1; transform:translate(-50%,-50%); } }
  #pin-modal.open { display: block; }

  .modal-title { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.85rem; margin-bottom: 16px; color: var(--text); letter-spacing: 0.05em; }
  .modal-coords { font-size: 0.6rem; color: var(--text-dim); margin-bottom: 16px; letter-spacing: 0.08em; }

  .modal-field { margin-bottom: 12px; }
  .modal-field label { display: block; font-size: 0.58rem; letter-spacing: 0.15em; color: var(--text-dim); text-transform: uppercase; margin-bottom: 5px; }
  .modal-field input, .modal-field textarea {
    width: 100%; background: var(--surface2); border: 1px solid var(--border); color: var(--text);
    font-family: 'Space Mono', monospace; font-size: 0.72rem; padding: 8px 10px; border-radius: 4px;
    outline: none; transition: border-color 0.2s;
  }
  .modal-field input:focus, .modal-field textarea:focus { border-color: rgba(0,212,255,0.4); }
  .modal-field textarea { resize: vertical; min-height: 60px; }

  .modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px; }
  .modal-cancel { background: transparent; border: 1px solid var(--border); color: var(--text-dim); font-family: 'Space Mono', monospace; font-size: 0.62rem; padding: 6px 14px; border-radius: 4px; cursor: pointer; transition: border-color 0.2s; }
  .modal-cancel:hover { border-color: var(--text-dim); }
  .modal-save { background: var(--accent); color: #000; border: none; font-family: 'Space Mono', monospace; font-size: 0.62rem; font-weight: 700; padding: 6px 16px; border-radius: 4px; cursor: pointer; letter-spacing: 0.08em; transition: box-shadow 0.2s; }
  .modal-save:hover { box-shadow: 0 0 14px rgba(0,212,255,0.3); }

  /* ── MAP OVERLAY HINT ── */
  #map-hint {
    position: absolute; bottom: 16px; right: 16px; z-index: 100;
    background: rgba(8,12,16,0.85); border: 1px solid var(--border);
    padding: 8px 14px; border-radius: 4px; font-size: 0.58rem; color: var(--text-dim);
    letter-spacing: 0.08em; pointer-events: none;
  }

  /* ── PLAYING INDICATOR ── */
  #play-indicator {
    position: absolute; top: 14px; left: 50%; transform: translateX(-50%);
    background: rgba(8,12,16,0.9); border: 1px solid rgba(0,212,255,0.3);
    padding: 8px 20px; border-radius: 999px; font-size: 0.65rem; color: var(--accent);
    letter-spacing: 0.1em; z-index: 200; display: none;
    box-shadow: 0 0 20px rgba(0,212,255,0.15);
  }
  #play-indicator.show { display: block; }

  /* ── CUSTOM PIN SVG ── */
  .cyber-pin { position: relative; }

  /* ── PULSE RING (injected via JS) ── */
  .pulse-ring {
    border-radius: 50%; animation: pulse-anim 2s ease-out infinite;
    position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
    pointer-events: none;
  }
  @keyframes pulse-anim {
    0%   { width:10px; height:10px; opacity:0.8; }
    100% { width:36px; height:36px; opacity:0; }
  }

  /* Toast */
  #toast { position: fixed; bottom: 24px; right: 24px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); font-size: 0.7rem; padding: 10px 18px; border-radius: 6px; z-index: 9999; opacity: 0; transform: translateY(8px); transition: all 0.2s; pointer-events: none; }
  #toast.show { opacity: 1; transform: translateY(0); }
  #toast.err  { border-color: rgba(255,77,77,0.4); color: #ff6b6b; }
</style>
</head>
<body>
<div id="app">
  <header>
    <span class="site-title">Cyber Pulse</span>
    <span class="site-sub">// intel map</span>
    <div class="nav-links">
      <a href="index.php" class="nav-link">Journal</a>
      <a href="map.php"   class="nav-link active">Intel Map</a>
      <span class="operator-tag">OPR: <span><?= $operator ?></span></span>
      <button class="logout-btn" onclick="doLogout()">Terminate</button>
    </div>
  </header>

  <div id="main">
    <!-- ── SIDEBAR ── -->
    <div id="sidebar">
      <!-- Search -->
      <div class="sidebar-section">
        <div class="sidebar-label">Intel Search</div>
        <div class="search-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
          <input id="search-input" type="text" placeholder="search events..." autocomplete="off">
        </div>
      </div>

      <!-- Playback -->
      <div class="sidebar-section">
        <div class="sidebar-label">Timeline Playback</div>
        <div class="play-controls">
          <button class="play-btn" id="play-btn" onclick="togglePlay()">
            <svg viewBox="0 0 24 24"><polygon points="5,3 19,12 5,21"/></svg>
            Play Timeline
          </button>
          <span id="play-status"></span>
        </div>
      </div>

      <!-- Date list / Results -->
      <div class="sidebar-label" style="padding: 10px 16px 4px; flex-shrink:0;">Timeline Dates</div>
      <div id="date-list-wrap"><div id="date-list"></div></div>
      <div id="results-wrap"><div id="results-list"></div></div>
    </div>

    <!-- ── MAP ── -->
    <div id="map-wrap">
      <div id="map"></div>
      <div id="map-hint">Click anywhere to place an intel pin</div>
      <div id="play-indicator"></div>

      <!-- Pin placement modal -->
      <div id="pin-modal">
        <div class="modal-title">// NEW INTEL PIN</div>
        <div class="modal-coords" id="modal-coords"></div>
        <div class="modal-field">
          <label>Title / Event</label>
          <input type="text" id="pin-title" placeholder="e.g. Ransomware attack on EU bank" autocomplete="off">
        </div>
        <div class="modal-field">
          <label>Description</label>
          <textarea id="pin-desc" placeholder="Brief details..."></textarea>
        </div>
        <div class="modal-field">
          <label>Date</label>
          <input type="date" id="pin-date">
        </div>
        <div class="modal-actions">
          <button class="modal-cancel" onclick="closeModal()">Cancel</button>
          <button class="modal-save"   onclick="savePin()">Place Pin</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
// ── GLOBALS ────────────────────────────────────────────────────────────
let map, events = [], markers = [], pendingLatLng = null;
let playInterval = null, playIndex = 0, playDates = [];
let activeDate = null;

// ── UTILS ──────────────────────────────────────────────────────────────
function toast(msg, err=false) {
  const el = document.getElementById('toast');
  el.textContent = msg; el.className = 'show' + (err?' err':'');
  setTimeout(() => el.className = '', 2800);
}
function todayStr() { return new Date().toISOString().split('T')[0]; }
function fmtDate(d) {
  const [y,m,day] = d.split('-');
  return new Date(y,m-1,day).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
}
function escHtml(s) {
  return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
async function doLogout() {
  await fetch('api.php?r=logout', {method:'POST'});
  window.location = 'login.php';
}

// ── MAP INIT ───────────────────────────────────────────────────────────
function initMap() {
  map = L.map('map', { center: [20, 10], zoom: 2, zoomControl: true, worldCopyJump: false });

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: '© OpenStreetMap'
  }).addTo(map);

  map.on('click', e => {
    pendingLatLng = e.latlng;
    document.getElementById('modal-coords').textContent =
      `LAT: ${e.latlng.lat.toFixed(5)}  LNG: ${e.latlng.lng.toFixed(5)}`;
    document.getElementById('pin-title').value = '';
    document.getElementById('pin-desc').value  = '';
    document.getElementById('pin-date').value  = todayStr();
    document.getElementById('pin-modal').classList.add('open');
    setTimeout(() => document.getElementById('pin-title').focus(), 50);
  });
}

// ── CUSTOM MARKER ─────────────────────────────────────────────────────
function makeIcon(highlighted = false) {
  const color = highlighted ? '#ffdd00' : '#00d4ff';
  const glow  = highlighted ? 'rgba(255,220,0,0.5)' : 'rgba(0,212,255,0.5)';
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32">
    <circle cx="12" cy="12" r="10" fill="none" stroke="${color}" stroke-width="1.5" opacity="0.3"/>
    <circle cx="12" cy="12" r="5"  fill="${color}" opacity="0.9"/>
    <circle cx="12" cy="12" r="2.5" fill="#fff" opacity="0.8"/>
    <line x1="12" y1="22" x2="12" y2="30" stroke="${color}" stroke-width="1.5" opacity="0.6"/>
  </svg>`;
  return L.divIcon({
    className: '',
    html: `<div style="position:relative;filter:drop-shadow(0 0 6px ${glow})">
      ${svg}
      <div style="position:absolute;top:7px;left:7px;width:10px;height:10px;border-radius:50%;
           border:1px solid ${color};opacity:0.6;animation:pulse-ring 2s ease-out infinite;
           box-sizing:border-box;"></div>
    </div>`,
    iconSize: [24, 32],
    iconAnchor: [12, 30],
    popupAnchor: [0, -30]
  });
}

// We need the keyframe in the page
const style = document.createElement('style');
style.textContent = `@keyframes pulse-ring { 0%{transform:scale(1);opacity:0.7} 100%{transform:scale(3.5);opacity:0} }`;
document.head.appendChild(style);

// ── LOAD & RENDER EVENTS ──────────────────────────────────────────────
async function loadEvents() {
  try {
    const res = await fetch('api.php?r=events');
    if (res.status === 401) { window.location='login.php'; return; }
    events = await res.json();
    renderMarkers(events);
    buildDateList();
  } catch(e) { toast('Could not load events.', true); }
}

function renderMarkers(evts, highlightId=null) {
  markers.forEach(m => map.removeLayer(m));
  markers = [];
  evts.forEach(ev => {
    const hl = ev.id === highlightId;
    const m = L.marker([parseFloat(ev.lat), parseFloat(ev.lng)], { icon: makeIcon(hl) });
    m.bindPopup(`
      <div style="font-family:'Space Mono',monospace;min-width:180px;">
        <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:.85rem;margin-bottom:6px;color:#e6edf3">
          ${escHtml(ev.title)}
        </div>
        <div style="font-size:.6rem;color:#4a5568;letter-spacing:.08em;margin-bottom:8px">${fmtDate(ev.date)}</div>
        ${ev.description ? `<div style="font-size:.68rem;color:#c9d1d9;line-height:1.6">${escHtml(ev.description)}</div>` : ''}
        <button onclick="deleteEvent(${ev.id})" style="margin-top:10px;background:transparent;border:1px solid rgba(255,77,77,0.2);color:rgba(255,77,77,0.7);font-family:'Space Mono',monospace;font-size:.55rem;padding:3px 10px;border-radius:3px;cursor:pointer;">Delete Pin</button>
      </div>
    `);
    m.addTo(map);
    m._evId = ev.id;
    markers.push(m);
  });
}

function buildDateList() {
  // Group by date
  const groups = {};
  events.forEach(ev => { if (!groups[ev.date]) groups[ev.date]=[]; groups[ev.date].push(ev); });
  playDates = Object.keys(groups).sort();

  const list = document.getElementById('date-list');
  if (playDates.length === 0) {
    list.innerHTML = '<div style="padding:16px;font-size:.62rem;color:var(--text-muted);text-align:center">No events yet. Click the map to place pins.</div>';
    return;
  }

  list.innerHTML = playDates.map(d => `
    <div class="date-item${activeDate===d?' active':''}" onclick="jumpToDate('${d}')">
      <span>${fmtDate(d)}</span>
      <span class="pin-count">${groups[d].length}</span>
    </div>
  `).join('');
}

function jumpToDate(date) {
  activeDate = date;
  buildDateList();
  const dayEvents = events.filter(ev => ev.date === date);
  if (!dayEvents.length) return;
  renderMarkers(events);

  if (dayEvents.length === 1) {
    map.flyTo([parseFloat(dayEvents[0].lat), parseFloat(dayEvents[0].lng)], 6, { duration: 1.2 });
  } else {
    const bounds = L.latLngBounds(dayEvents.map(e => [parseFloat(e.lat), parseFloat(e.lng)]));
    map.flyToBounds(bounds, { padding: [60,60], duration: 1.2 });
  }
}

// ── SEARCH ─────────────────────────────────────────────────────────────
let searchTimer;
document.getElementById('search-input').addEventListener('input', e => {
  clearTimeout(searchTimer);
  const q = e.target.value.trim();
  if (!q) { showDateList(); return; }
  searchTimer = setTimeout(() => doSearch(q), 300);
});

function showDateList() {
  document.getElementById('date-list-wrap').style.display = '';
  document.getElementById('results-wrap').style.display   = 'none';
  renderMarkers(events);
}

async function doSearch(q) {
  try {
    const res = await fetch('api.php?r=events&search=' + encodeURIComponent(q));
    const results = await res.json();
    document.getElementById('date-list-wrap').style.display = 'none';
    document.getElementById('results-wrap').style.display   = '';

    const list = document.getElementById('results-list');
    if (!results.length) {
      list.innerHTML = '<div class="no-results">No matching intel found.</div>';
      return;
    }
    list.innerHTML = results.map(ev => `
      <div class="result-item" onclick="jumpToEvent(${ev.id})">
        <div class="r-title">${escHtml(ev.title)}</div>
        <div class="r-date">${fmtDate(ev.date)}</div>
        ${ev.description ? `<div class="r-desc">${escHtml(ev.description).substring(0,80)}${ev.description.length>80?'…':''}</div>` : ''}
      </div>
    `).join('');

    // Highlight pins from results
    const ids = new Set(results.map(e=>e.id));
    renderMarkers(events.filter(e => ids.has(e.id)));
    if (results.length) {
      const bounds = L.latLngBounds(results.map(e=>[parseFloat(e.lat),parseFloat(e.lng)]));
      map.flyToBounds(bounds, {padding:[80,80], duration:1.2});
    }
  } catch(e) { toast('Search failed.', true); }
}

function jumpToEvent(id) {
  const ev = events.find(e=>e.id==id); if (!ev) return;
  renderMarkers(events, id);
  map.flyTo([parseFloat(ev.lat), parseFloat(ev.lng)], 8, {duration:1.2});
  // highlight result
  document.querySelectorAll('.result-item').forEach(el => el.classList.remove('highlight'));
  const idx = events.filter(e=>document.getElementById('search-input').value).findIndex(e=>e.id==id);
  const items = document.querySelectorAll('.result-item');
  items.forEach(el => {
    if (el.getAttribute('onclick') === `jumpToEvent(${id})`) el.classList.add('highlight');
  });
  setTimeout(() => {
    const m = markers.find(mk => mk._evId==id);
    if (m) m.openPopup();
  }, 1300);
}

// ── PLAYBACK ───────────────────────────────────────────────────────────
function togglePlay() {
  if (playInterval) { stopPlay(); return; }
  if (!playDates.length) { toast('No events to play.', true); return; }
  startPlay();
}

function startPlay() {
  playIndex = 0;
  const btn = document.getElementById('play-btn');
  btn.innerHTML = `<svg viewBox="0 0 24 24" fill="currentColor"><rect x="5" y="3" width="4" height="18"/><rect x="15" y="3" width="4" height="18"/></svg> Stop`;
  btn.classList.add('stop');

  const groups = {};
  events.forEach(ev => { if (!groups[ev.date]) groups[ev.date]=[]; groups[ev.date].push(ev); });

  function playStep() {
    if (playIndex >= playDates.length) { stopPlay(); return; }
    const date    = playDates[playIndex];
    const dayEvts = groups[date] || [];
    const duration = Math.max(dayEvts.length * 3000, 3000);

    jumpToDate(date);
    document.getElementById('play-indicator').textContent =
      `▶ ${fmtDate(date)}  —  ${dayEvts.length} event${dayEvts.length!==1?'s':''}`;
    document.getElementById('play-indicator').classList.add('show');
    document.getElementById('play-status').textContent = `${playIndex+1}/${playDates.length}`;

    playIndex++;
    playInterval = setTimeout(playStep, duration);
  }
  playStep();
}

function stopPlay() {
  clearTimeout(playInterval);
  playInterval = null;
  const btn = document.getElementById('play-btn');
  btn.innerHTML = `<svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg> Play Timeline`;
  btn.classList.remove('stop');
  document.getElementById('play-indicator').classList.remove('show');
  document.getElementById('play-status').textContent = '';
}

// ── PIN MODAL ─────────────────────────────────────────────────────────
function closeModal() {
  document.getElementById('pin-modal').classList.remove('open');
  pendingLatLng = null;
}

async function savePin() {
  const title = document.getElementById('pin-title').value.trim();
  const desc  = document.getElementById('pin-desc').value.trim();
  const date  = document.getElementById('pin-date').value || todayStr();
  if (!title) { toast('Title is required.', true); return; }
  if (!pendingLatLng) { closeModal(); return; }

  const btn = document.querySelector('.modal-save');
  btn.textContent = '...'; btn.disabled = true;

  try {
    const res = await fetch('api.php?r=events', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ title, description:desc, lat:pendingLatLng.lat, lng:pendingLatLng.lng, date })
    });
    if (res.status === 401) { window.location='login.php'; return; }
    const ev = await res.json();
    events.push(ev);
    events.sort((a,b) => a.date.localeCompare(b.date));
    closeModal();
    renderMarkers(events, ev.id);
    buildDateList();
    map.flyTo([parseFloat(ev.lat),parseFloat(ev.lng)], Math.max(map.getZoom(),6), {duration:0.8});
    setTimeout(() => {
      const m = markers.find(mk => mk._evId==ev.id);
      if (m) m.openPopup();
    }, 900);
    toast('Intel pin placed.');
  } catch(e) { toast('Failed to save pin.', true); }
  finally { btn.textContent = 'Place Pin'; btn.disabled = false; }
}

async function deleteEvent(id) {
  if (!confirm('Remove this intel pin?')) return;
  try {
    const res = await fetch('api.php?r=events&id=' + id, {method:'DELETE'});
    if (res.status===401) { window.location='login.php'; return; }
    events = events.filter(e=>e.id!=id);
    renderMarkers(events);
    buildDateList();
    map.closePopup();
    toast('Pin removed.');
  } catch(e) { toast('Delete failed.', true); }
}

// Close modal on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeModal();
});

// ── BOOT ──────────────────────────────────────────────────────────────
initMap();
loadEvents();
</script>
</body>
</html>
