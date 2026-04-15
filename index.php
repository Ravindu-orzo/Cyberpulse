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
<title>Cyber Pulse — Security Journal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="preconnect" href="https://a.basemaps.cartocdn.com">
<link rel="preconnect" href="https://b.basemaps.cartocdn.com">
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
    --daily-color: #00d4ff;
    --daily-dim: rgba(0, 212, 255, 0.08);
    --daily-glow: rgba(0, 212, 255, 0.25);
    --daily-border: rgba(0, 212, 255, 0.3);
    --weekly-color: #ff6b35;
    --weekly-dim: rgba(255, 107, 53, 0.08);
    --weekly-glow: rgba(255, 107, 53, 0.25);
    --weekly-border: rgba(255, 107, 53, 0.3);
    --timeline-h: 80px;
    --dot-size: 8px;
    --dot-active: 14px;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; background: var(--bg); color: var(--text); font-family: 'Space Mono', monospace; overflow: hidden; }

  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,0,0,0.03) 2px, rgba(0,0,0,0.03) 4px);
    pointer-events: none;
    z-index: 1000;
  }

  #app { display: flex; flex-direction: column; height: 100vh; }

  /* ── HEADER ── */
  header {
    padding: 14px 32px 0;
    display: flex;
    align-items: center;
    gap: 16px;
    flex-shrink: 0;
  }
  .site-title { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.1rem; letter-spacing: 0.15em; color: var(--accent); text-transform: uppercase; text-shadow: 0 0 20px var(--daily-glow); }
  .site-sub   { font-size: 0.65rem; color: var(--text-dim); letter-spacing: 0.1em; }

  .nav-links { margin-left: auto; display: flex; align-items: center; gap: 16px; }
  .nav-link {
    font-size: 0.6rem; letter-spacing: 0.12em; text-transform: uppercase; color: var(--text-dim);
    text-decoration: none; padding: 4px 10px; border: 1px solid transparent; border-radius: 3px;
    transition: all 0.2s;
  }
  .nav-link:hover { border-color: var(--border); color: var(--text); }
  .nav-link.active { border-color: var(--daily-border); color: var(--daily-color); }

  .operator-tag { font-size: 0.58rem; color: var(--text-dim); letter-spacing: 0.1em; }
  .operator-tag span { color: var(--accent); }

  .logout-btn {
    background: none; border: 1px solid rgba(255,77,77,0.2); color: rgba(255,77,77,0.6);
    font-family: 'Space Mono', monospace; font-size: 0.55rem; letter-spacing: 0.1em;
    padding: 4px 10px; border-radius: 3px; cursor: pointer; text-transform: uppercase; transition: all 0.2s;
  }
  .logout-btn:hover { border-color: rgba(255,77,77,0.5); color: #ff4d4d; }

  /* ── TIMELINE ── */
  #timeline-wrap {
    position: relative; height: var(--timeline-h); flex-shrink: 0; margin-top: 8px; overflow: hidden;
  }
  #timeline-wrap::before, #timeline-wrap::after {
    content: ''; position: absolute; top: 0; bottom: 0; width: 120px; z-index: 2; pointer-events: none;
  }
  #timeline-wrap::before { left: 0; background: linear-gradient(to right, var(--bg), transparent); }
  #timeline-wrap::after  { right: 0; background: linear-gradient(to left, var(--bg), transparent); }
  #timeline-track {
    position: absolute; top: 50%; transform: translateY(-50%); height: 1px;
    background: var(--border); transition: left 0.35s cubic-bezier(0.25,0.46,0.45,0.94);
    display: flex; align-items: center;
  }
  .tl-dot-wrap { position: relative; display: flex; align-items: center; justify-content: center; width: 80px; flex-shrink: 0; }
  .tl-dot { width: var(--dot-size); height: var(--dot-size); border-radius: 50%; background: var(--text-muted); transition: all 0.35s ease; position: relative; }
  .tl-dot.daily  { background: var(--daily-color);  opacity: 0.4; }
  .tl-dot.weekly { background: var(--weekly-color); opacity: 0.4; }
  .tl-dot.active { width: var(--dot-active); height: var(--dot-active); opacity: 1 !important; }
  .tl-dot.active.daily  { box-shadow: 0 0 0 3px rgba(0,212,255,0.15), 0 0 16px var(--daily-glow); }
  .tl-dot.active.weekly { box-shadow: 0 0 0 3px rgba(255,107,53,0.15), 0 0 16px var(--weekly-glow); }
  .tl-date { position: absolute; top: calc(50% + 16px); left: 50%; transform: translateX(-50%); font-size: 0.6rem; color: var(--text-dim); white-space: nowrap; letter-spacing: 0.05em; opacity: 0; transition: opacity 0.3s; pointer-events: none; }
  .tl-dot-wrap.active .tl-date { opacity: 1; color: var(--text); }
  #timeline-wrap .center-line { position: absolute; left: 50%; top: 0; bottom: 0; width: 1px; background: rgba(255,255,255,0.06); transform: translateX(-50%); z-index: 1; pointer-events: none; }

  /* ── ACTION BAR ── */
  #action-bar { display: flex; gap: 10px; padding: 0 32px 14px; flex-shrink: 0; align-items: center; border-bottom: 1px solid var(--border); }
  .add-btn { display: flex; align-items: center; gap: 8px; padding: 7px 16px; border-radius: 999px; border: 1px solid; font-family: 'Space Mono', monospace; font-size: 0.7rem; letter-spacing: 0.08em; cursor: pointer; background: transparent; transition: all 0.2s; text-transform: uppercase; }
  .add-btn.daily-btn  { border-color: var(--daily-border);  color: var(--daily-color); }
  .add-btn.daily-btn:hover  { background: var(--daily-dim);  box-shadow: 0 0 12px var(--daily-glow); }
  .add-btn.weekly-btn { border-color: var(--weekly-border); color: var(--weekly-color); }
  .add-btn.weekly-btn:hover { background: var(--weekly-dim); box-shadow: 0 0 12px var(--weekly-glow); }
  .add-btn svg { width: 12px; height: 12px; stroke: currentColor; flex-shrink: 0; }

  .map-link-btn {
    display: flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px;
    border: 1px solid rgba(100,200,100,0.3); color: rgba(100,220,100,0.8); background: transparent;
    font-family: 'Space Mono', monospace; font-size: 0.65rem; letter-spacing: 0.08em; text-decoration: none;
    text-transform: uppercase; transition: all 0.2s;
  }
  .map-link-btn:hover { background: rgba(100,200,100,0.08); box-shadow: 0 0 10px rgba(100,200,100,0.15); }

  .post-count { margin-left: auto; font-size: 0.6rem; color: var(--text-dim); letter-spacing: 0.1em; }

  /* ── COMPOSE ── */
  #compose-panel { display: none; flex-direction: column; gap: 10px; padding: 14px 32px; border-bottom: 1px solid var(--border); flex-shrink: 0; animation: slideDown 0.2s ease; }
  @keyframes slideDown { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
  #compose-panel.open { display: flex; }
  #compose-panel.daily  { border-left: 3px solid var(--daily-color);  background: var(--daily-dim); }
  #compose-panel.weekly { border-left: 3px solid var(--weekly-color); background: var(--weekly-dim); }
  .compose-header { display: flex; align-items: center; gap: 10px; }
  .compose-type-badge { font-size: 0.6rem; letter-spacing: 0.12em; text-transform: uppercase; padding: 3px 10px; border-radius: 999px; font-family: 'Syne', sans-serif; font-weight: 700; }
  .daily  .compose-type-badge { background: var(--daily-dim);  color: var(--daily-color);  border: 1px solid var(--daily-border); }
  .weekly .compose-type-badge { background: var(--weekly-dim); color: var(--weekly-color); border: 1px solid var(--weekly-border); }
  .compose-date-input { margin-left: auto; background: transparent; border: 1px solid var(--border); color: var(--text-dim); font-family: 'Space Mono', monospace; font-size: 0.65rem; padding: 4px 10px; border-radius: 4px; cursor: pointer; }
  .compose-date-input:focus { outline: none; }
  .compose-title-input { width: 100%; background: transparent; border: none; border-bottom: 1px solid var(--border); color: var(--text); font-family: 'Syne', sans-serif; font-weight: 600; font-size: 0.9rem; padding: 6px 0; }
  .compose-title-input:focus { outline: none; }
  .compose-title-input::placeholder { color: var(--text-muted); }
  .compose-body { width: 100%; min-height: 90px; max-height: 180px; background: transparent; border: none; color: var(--text); font-family: 'Space Mono', monospace; font-size: 0.75rem; line-height: 1.7; resize: vertical; padding: 4px 0; }
  .compose-body:focus { outline: none; }
  .compose-body::placeholder { color: var(--text-muted); }
  .compose-actions { display: flex; gap: 8px; justify-content: flex-end; }
  .btn-cancel { background: transparent; border: 1px solid var(--border); color: var(--text-dim); font-family: 'Space Mono', monospace; font-size: 0.65rem; padding: 6px 14px; border-radius: 4px; cursor: pointer; transition: border-color 0.2s; }
  .btn-cancel:hover { border-color: var(--text-dim); }
  .btn-post { border: none; font-family: 'Space Mono', monospace; font-size: 0.65rem; padding: 6px 18px; border-radius: 4px; cursor: pointer; letter-spacing: 0.08em; text-transform: uppercase; font-weight: 700; transition: all 0.2s; }
  .daily  .btn-post { background: var(--daily-color);  color: #000; }
  .daily  .btn-post:hover { box-shadow: 0 0 14px var(--daily-glow); }
  .weekly .btn-post { background: var(--weekly-color); color: #000; }
  .weekly .btn-post:hover { box-shadow: 0 0 14px var(--weekly-glow); }

  /* ── FEED ── */
  #feed { flex: 1; overflow-y: auto; padding: 24px 32px 40px; scroll-behavior: smooth; }
  #feed::-webkit-scrollbar { width: 4px; }
  #feed::-webkit-scrollbar-track { background: transparent; }
  #feed::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }
  .date-group { margin-bottom: 32px; }
  .date-label { font-size: 0.6rem; letter-spacing: 0.15em; color: var(--text-dim); text-transform: uppercase; margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
  .date-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }
  .post-card { position: relative; padding: 16px 20px; border-radius: 6px; margin-bottom: 12px; border: 1px solid transparent; transition: border-color 0.2s, box-shadow 0.2s; animation: fadeUp 0.3s ease both; }
  @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  .post-card.daily  { background: var(--daily-dim);  border-color: var(--daily-border); }
  .post-card.daily:hover  { box-shadow: 0 0 20px rgba(0,212,255,0.08);  border-color: rgba(0,212,255,0.5); }
  .post-card.weekly { background: var(--weekly-dim); border-color: var(--weekly-border); }
  .post-card.weekly:hover { box-shadow: 0 0 20px rgba(255,107,53,0.08); border-color: rgba(255,107,53,0.5); }
  .post-meta { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
  .post-type-tag { font-size: 0.55rem; letter-spacing: 0.12em; text-transform: uppercase; padding: 2px 8px; border-radius: 999px; font-family: 'Syne', sans-serif; font-weight: 700; }
  .daily  .post-type-tag { background: rgba(0,212,255,0.12);  color: var(--daily-color); }
  .weekly .post-type-tag { background: rgba(255,107,53,0.12); color: var(--weekly-color); }
  .post-time   { font-size: 0.6rem; color: var(--text-dim); }
  .post-delete { margin-left: auto; background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 0.65rem; padding: 2px 6px; border-radius: 3px; font-family: 'Space Mono', monospace; opacity: 0; transition: opacity 0.2s; }
  .post-card:hover .post-delete { opacity: 1; }
  .post-delete:hover { color: #ff4d4d; background: rgba(255,77,77,0.08); }
  .post-title { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.95rem; color: #e6edf3; margin-bottom: 8px; line-height: 1.4; }
  .post-body  { font-size: 0.75rem; line-height: 1.8; color: var(--text); white-space: pre-wrap; word-break: break-word; }
  .post-card::before { content: ''; position: absolute; left: 0; top: 12px; bottom: 12px; width: 2px; border-radius: 1px; }
  .post-card.daily::before  { background: var(--daily-color); }
  .post-card.weekly::before { background: var(--weekly-color); }

  #empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; gap: 12px; color: var(--text-dim); text-align: center; }
  #empty-state p { font-size: 0.7rem; letter-spacing: 0.08em; line-height: 1.8; }
  #loading { display: flex; align-items: center; justify-content: center; height: 100%; gap: 6px; }
  .ld { width: 5px; height: 5px; border-radius: 50%; background: var(--text-dim); animation: ld 1s ease infinite; }
  .ld:nth-child(2) { animation-delay: 0.15s; }
  .ld:nth-child(3) { animation-delay: 0.3s; }
  @keyframes ld { 0%,80%,100% { transform: scale(0.6); opacity: 0.3; } 40% { transform: scale(1); opacity: 1; } }
  #toast { position: fixed; bottom: 24px; right: 24px; background: var(--surface2); border: 1px solid var(--border); color: var(--text); font-size: 0.7rem; padding: 10px 18px; border-radius: 6px; z-index: 999; opacity: 0; transform: translateY(8px); transition: all 0.2s; pointer-events: none; letter-spacing: 0.05em; }
  #toast.show { opacity: 1; transform: translateY(0); }
  #toast.err  { border-color: rgba(255,77,77,0.4); color: #ff6b6b; }
</style>
</head>
<body>
<div id="app">
  <header>
    <span class="site-title">Cyber Pulse</span>
    <span class="site-sub">// security intel journal</span>
    <div class="nav-links">
      <a href="index.php" class="nav-link active">Journal</a>
      <a href="map.php" class="nav-link">Intel Map</a>
      <a href="writeups.php" class="nav-link">Writeups</a>
      <span class="operator-tag">OPR: <span><?= $operator ?></span></span>
      <button class="logout-btn" onclick="doLogout()">Terminate</button>
    </div>
  </header>

  <div id="timeline-wrap">
    <div class="center-line"></div>
    <div id="timeline-track"></div>
  </div>

  <div id="action-bar">
    <button class="add-btn daily-btn"  onclick="openCompose('daily')">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
      Daily Entry
    </button>
    <button class="add-btn weekly-btn" onclick="openCompose('weekly')">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
      Weekly Summary
    </button>
    <a href="map.php" class="map-link-btn">
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
      Intel Map
    </a>
    <span class="post-count" id="post-count"></span>
  </div>

  <div id="compose-panel">
    <div class="compose-header">
      <span class="compose-type-badge" id="compose-badge">DAILY</span>
      <input type="date" class="compose-date-input" id="compose-date">
    </div>
    <input class="compose-title-input" id="compose-title" placeholder="Entry title (optional)">
    <textarea class="compose-body" id="compose-body" placeholder="What happened in the cyber world today..."></textarea>
    <div class="compose-actions">
      <button class="btn-cancel" onclick="closeCompose()">Cancel</button>
      <button class="btn-post" onclick="submitPost()">Publish</button>
    </div>
  </div>

  <div id="feed">
    <div id="loading"><div class="ld"></div><div class="ld"></div><div class="ld"></div></div>
  </div>
</div>
<div id="toast"></div>

<script>
const API = 'api.php?r=posts';
let posts = [], currentType = 'daily', activeDotIndex = 0;

function toast(msg, err=false) {
  const el = document.getElementById('toast');
  el.textContent = msg; el.className = 'show' + (err ? ' err' : '');
  setTimeout(() => { el.className = ''; }, 2800);
}
function fmtDate(d) { const [y,m,day]=d.split('-'); return new Date(y,m-1,day).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}); }
function fmtTime(dt) { if(!dt) return ''; return new Date(dt).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'}); }
function todayStr() { return new Date().toISOString().split('T')[0]; }

async function doLogout() {
  await fetch('api.php?r=logout', { method: 'POST' });
  window.location = 'login.php';
}

async function loadPosts() {
  try {
    const res = await fetch(API);
    if (res.status === 401) { window.location = 'login.php'; return; }
    posts = await res.json();
    render();
  } catch(e) {
    document.getElementById('loading').innerHTML = '<span style="color:var(--text-dim);font-size:.7rem">Could not connect to server.</span>';
  }
}

async function submitPost() {
  const title   = document.getElementById('compose-title').value.trim();
  const content = document.getElementById('compose-body').value.trim();
  const date    = document.getElementById('compose-date').value || todayStr();
  if (!content) { toast('Write something first.', true); return; }
  const btn = document.querySelector('.btn-post');
  btn.textContent = '...'; btn.disabled = true;
  try {
    const res  = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({type:currentType,title,content,date}) });
    if (res.status === 401) { window.location = 'login.php'; return; }
    const post = await res.json();
    posts.unshift(post);
    posts.sort((a,b) => b.date.localeCompare(a.date) || b.created_at.localeCompare(a.created_at));
    closeCompose(); render(); toast('Entry published.');
  } catch(e) { toast('Failed to save.', true); }
  finally { btn.textContent = 'Publish'; btn.disabled = false; }
}

async function deletePost(id) {
  if (!confirm('Delete this entry?')) return;
  try {
    const res = await fetch('api.php?r=posts&id=' + encodeURIComponent(id), { method:'DELETE' });
    if (res.status === 401) { window.location = 'login.php'; return; }
    posts = posts.filter(p => p.id !== id);
    render(); toast('Entry deleted.');
  } catch(e) { toast('Delete failed.', true); }
}

function openCompose(type) {
  currentType = type;
  const panel = document.getElementById('compose-panel');
  panel.className = 'open ' + type;
  document.getElementById('compose-badge').textContent = type==='daily' ? 'DAILY ENTRY' : 'WEEKLY SUMMARY';
  document.getElementById('compose-date').value = todayStr();
  document.getElementById('compose-title').value = '';
  document.getElementById('compose-body').value  = '';
  document.getElementById('compose-body').focus();
}
function closeCompose() { document.getElementById('compose-panel').className = ''; }

function groupByDate(posts) {
  const g = {};
  posts.forEach(p => { if(!g[p.date]) g[p.date]=[]; g[p.date].push(p); });
  return g;
}

function escHtml(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function render() {
  const feed = document.getElementById('feed');
  const count = document.getElementById('post-count');
  if (posts.length === 0) {
    feed.innerHTML = `<div id="empty-state"><p>No entries yet.<br>Start documenting the cyber landscape.</p></div>`;
    count.textContent = '';
    buildTimeline([]);
    return;
  }
  count.textContent = posts.length + (posts.length===1?' entry':' entries');
  const groups = groupByDate(posts);
  const sortedDates = Object.keys(groups).sort((a,b) => b.localeCompare(a));
  feed.innerHTML = sortedDates.map((date,di) => `
    <div class="date-group" data-date="${date}">
      <div class="date-label">${fmtDate(date)}</div>
      ${groups[date].map((p,pi) => `
        <div class="post-card ${p.type}" style="animation-delay:${(di*2+pi)*0.05}s">
          <div class="post-meta">
            <span class="post-type-tag">${p.type==='daily'?'Daily':'Weekly'}</span>
            <span class="post-time">${fmtTime(p.created_at)}</span>
            <button class="post-delete" onclick="deletePost('${p.id}')">✕</button>
          </div>
          ${p.title ? `<div class="post-title">${escHtml(p.title)}</div>` : ''}
          <div class="post-body">${escHtml(p.content)}</div>
        </div>
      `).join('')}
    </div>`).join('');
  buildTimeline(sortedDates);
  setupScrollSync();
}

function buildTimeline(dates) {
  const track  = document.getElementById('timeline-track');
  const wrap   = document.getElementById('timeline-wrap');
  const centerX = wrap.offsetWidth / 2;
  const itemW   = 80;
  if (!dates.length) { track.innerHTML=''; track.style.width='100%'; track.style.left='0'; return; }
  track.style.width = (dates.length * itemW) + 'px';
  const groups = groupByDate(posts);
  track.innerHTML = dates.map((d,i) => {
    const type = groups[d][0].type;
    const active = i===0;
    return `<div class="tl-dot-wrap${active?' active':''}" data-index="${i}">
      <div class="tl-dot ${type}${active?' active':''}"></div>
      <div class="tl-date">${fmtDate(d)}</div>
    </div>`;
  }).join('');
  track.style.left = (centerX - itemW/2) + 'px';
}

function setActiveDot(index) {
  if (index === activeDotIndex) return;
  activeDotIndex = index;
  const wrap  = document.getElementById('timeline-wrap');
  const track = document.getElementById('timeline-track');
  const centerX = wrap.offsetWidth / 2, itemW = 80;
  track.style.left = (centerX - index*itemW - itemW/2) + 'px';
  track.querySelectorAll('.tl-dot-wrap').forEach((w,i) => w.classList.toggle('active', i===index));
  track.querySelectorAll('.tl-dot').forEach((d,i) => d.classList.toggle('active', i===index));
}

function setupScrollSync() {
  const feed = document.getElementById('feed');
  let ticking = false;
  feed.onscroll = () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      const groups = feed.querySelectorAll('.date-group');
      let closest=0, minDist=Infinity;
      groups.forEach((g,i) => {
        const dist = Math.abs(g.getBoundingClientRect().top - feed.getBoundingClientRect().top);
        if (dist < minDist) { minDist=dist; closest=i; }
      });
      setActiveDot(closest);
      ticking = false;
    });
  };
}

loadPosts();
</script>
</body>
</html>
