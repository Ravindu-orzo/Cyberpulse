<?php
require_once 'db.php';
session_start();

// View profile session initiation
if (isset($_GET['action']) && $_GET['action'] === 'view_profile') {
    session_destroy();
    session_start();
    $_SESSION['view_only'] = true;
    header('Location: writeups.php');
    exit();
}

// Already logged in (real) → go to journal
if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}
// Already in view-only → send to writeups
if (!empty($_SESSION['view_only'])) {
    header('Location: writeups.php');
    exit();
}

init_db();

$error   = '';
$locked  = false;
$max_attempts = 5;
$window_secs  = 15 * 60;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = get_pdo();
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $pdo->prepare("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL {$window_secs} SECOND)")
        ->execute();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ?");
    $stmt->execute([$ip]);
    $count = (int) $stmt->fetchColumn();

    if ($count >= $max_attempts) {
        $locked = true;
        $error  = 'RATE_LIMIT';
    } else {
        $username = trim($_POST['opr_id'] ?? '');
        $password = trim($_POST['access_token'] ?? '');

        $pdo->prepare("INSERT INTO login_attempts (ip) VALUES (?)")->execute([$ip]);

        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = $username;
            $_SESSION['uid']  = $user['id'];
            $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
            header('Location: index.php');
            exit();
        } else {
            $error = 'ACCESS_DENIED';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SYSTEM ACCESS — CyberPulse Terminal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=VT323&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    cursor: none !important;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
  }
  .term-input, .term-input * {
    user-select: text !important;
    -webkit-user-select: text !important;
  }
  :root {
    --green: #00ff41;
    --green-dim: rgba(0,255,65,0.55);
    --green-glow: rgba(0,255,65,0.2);
    --red: #ff3333;
    --bg: #020804;
    --amber: #ffb800;
  }
  html, body { height: 100%; background: var(--bg); color: var(--green); font-family: 'Share Tech Mono', monospace; overflow: hidden; cursor: none; }
  body::before { content:''; position:fixed; inset:0; background: repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,.18) 2px,rgba(0,0,0,.18) 4px); pointer-events:none; z-index:10; }
  body::after  { content:''; position:fixed; inset:0; background: radial-gradient(ellipse at center,transparent 60%,rgba(0,0,0,.7) 100%); pointer-events:none; z-index:11; }

  #cursor { position:fixed; width:4px; height:18px; background:var(--green); pointer-events:none; z-index:9999; transform:translate(-50%,-50%); box-shadow:0 0 8px var(--green); animation:cur-blink 1s step-end infinite; }
  @keyframes cur-blink { 50%{opacity:0;} }
  #noise { position:fixed; inset:0; opacity:.03; pointer-events:none; z-index:9; }
  .scanline { position:fixed; top:-10%; left:0; right:0; height:8px; background:linear-gradient(transparent,rgba(0,255,65,.06),transparent); animation:scan 6s linear infinite; pointer-events:none; z-index:12; }
  @keyframes scan { to{top:110%;} }

  /* ── MAIN TERMINAL ── */
  #terminal { position:fixed; inset:0; display:flex; flex-direction:column; justify-content:center; align-items:center; z-index:20; padding:40px; gap:20px; }
  .term-box { width:100%; max-width:600px; border:1px solid rgba(0,255,65,.2); padding:40px 44px; background:rgba(0,10,2,.85); box-shadow:0 0 0 1px rgba(0,255,65,.05),0 0 40px rgba(0,255,65,.04),inset 0 0 60px rgba(0,255,65,.02); position:relative; }
  .term-box::before,.term-box::after,.bracket-bl,.bracket-br { content:''; position:absolute; width:16px; height:16px; border-color:var(--green-dim); border-style:solid; }
  .term-box::before { top:-1px; left:-1px; border-width:2px 0 0 2px; }
  .term-box::after  { top:-1px; right:-1px; border-width:2px 2px 0 0; }
  .bracket-bl { bottom:-1px; left:-1px; border-width:0 0 2px 2px; }
  .bracket-br { bottom:-1px; right:-1px; border-width:0 2px 2px 0; }

  .boot-header { font-family:'VT323',monospace; font-size:.75rem; color:rgba(0,255,65,.35); letter-spacing:.12em; margin-bottom:32px; line-height:1.6; }
  .boot-header .blink { animation:cur-blink 1s step-end infinite; }
  .sys-title { font-family:'VT323',monospace; font-size:2.8rem; letter-spacing:.08em; color:var(--green); text-shadow:0 0 20px var(--green-glow),0 0 40px rgba(0,255,65,.1); margin-bottom:6px; line-height:1; }
  .sys-sub { font-size:.65rem; color:rgba(0,255,65,.3); letter-spacing:.2em; margin-bottom:40px; }

  .field-row { margin-bottom:18px; }
  .field-label { display:block; font-size:.65rem; letter-spacing:.2em; color:rgba(0,255,65,.45); margin-bottom:8px; }
  .field-wrap { display:flex; align-items:center; gap:10px; border-bottom:1px solid rgba(0,255,65,.2); padding-bottom:6px; }
  .field-prompt { color:var(--green-dim); font-size:.9rem; flex-shrink:0; }
  .term-input { background:transparent; border:none; color:var(--green); font-family:'Share Tech Mono',monospace; font-size:.9rem; flex:1; outline:none; caret-color:var(--green); letter-spacing:.05em; }
  .term-input::placeholder { color:rgba(0,255,65,.15); }
  #password { letter-spacing:.25em; }
  .key-hint { font-size:.6rem; color:rgba(0,255,65,.25); letter-spacing:.18em; margin-top:28px; text-align:center; }
  #status-line { margin-top:22px; font-size:.7rem; letter-spacing:.1em; min-height:20px; transition:color .2s; }
  #status-line.ok    { color:var(--green-dim); }
  #status-line.error { color:var(--red); text-shadow:0 0 8px rgba(255,51,51,.4); }
  #status-line.warn  { color:var(--amber); }

  @keyframes glitch { 0%{transform:translate(0);} 10%{transform:translate(-3px,1px);} 20%{transform:translate(3px,-1px);} 30%{transform:translate(-2px,2px);} 40%{transform:translate(2px,-2px);} 50%,100%{transform:translate(0);} }
  .glitch { animation:glitch .4s ease; }

  #boot-seq { position:fixed; inset:0; background:var(--bg); z-index:50; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:4px; font-family:'Share Tech Mono',monospace; font-size:.75rem; color:rgba(0,255,65,.6); letter-spacing:.1em; padding:60px; transition:opacity .5s ease; }
  #boot-seq p { opacity:0; transform:translateY(2px); transition:opacity .3s ease,transform .3s ease; width:100%; max-width:500px; }
  #boot-seq p.show { opacity:1; transform:translateY(0); }
  #boot-seq p .ok { color:var(--green); }
  #boot-seq p .fail { color:var(--red); }

  #granted { display:none; position:fixed; inset:0; background:var(--bg); z-index:60; align-items:center; justify-content:center; font-family:'VT323',monospace; font-size:4rem; color:var(--green); text-shadow:0 0 30px var(--green),0 0 60px rgba(0,255,65,.3); letter-spacing:.15em; animation:flicker .1s ease infinite; }
  @keyframes flicker { 0%,100%{opacity:1;} 50%{opacity:.95;} 75%{opacity:1;} 85%{opacity:.9;} }

  /* ── PROFILE BUTTON ── */
  .profile-btn-wrap { width:100%; max-width:600px; display:flex; justify-content:center; }
  .profile-btn {
    background:transparent;
    border:1px solid rgba(0,255,65,.15);
    color:rgba(0,255,65,.4);
    font-family:'Share Tech Mono',monospace;
    font-size:.65rem;
    letter-spacing:.18em;
    padding:10px 24px;
    cursor:pointer !important;
    transition:all .25s;
    text-transform:uppercase;
    display:flex;
    align-items:center;
    gap:10px;
    width:100%;
    justify-content:center;
  }
  .profile-btn:hover { border-color:rgba(0,255,65,.4); color:rgba(0,255,65,.75); background:rgba(0,255,65,.03); }
  .profile-btn .pb-arrow { transition:transform .2s; }
  .profile-btn:hover .pb-arrow { transform:translateX(4px); }

  /* ── PROFILE MODAL ── */
  #profile-modal {
    display:none; position:fixed; inset:0; background:rgba(0,0,0,.85);
    z-index:100; align-items:center; justify-content:center; padding:40px;
  }
  #profile-modal.open { display:flex; }
  .profile-box {
    width:100%; max-width:700px; max-height:85vh; overflow-y:auto;
    background:#020804; border:1px solid rgba(0,255,65,.2);
    padding:36px 40px; position:relative;
    box-shadow:0 0 60px rgba(0,255,65,.04);
  }
  .profile-box::-webkit-scrollbar { width:3px; }
  .profile-box::-webkit-scrollbar-thumb { background:rgba(0,255,65,.2); }
  .pb-close { position:absolute; top:16px; right:16px; background:none; border:1px solid rgba(0,255,65,.15); color:rgba(0,255,65,.4); font-family:'Share Tech Mono',monospace; font-size:.6rem; padding:4px 10px; cursor:pointer !important; letter-spacing:.1em; transition:all .2s; }
  .pb-close:hover { border-color:rgba(0,255,65,.4); color:var(--green); }

  .pb-header { margin-bottom:28px; }
  .pb-prompt { font-size:.62rem; color:rgba(0,255,65,.35); letter-spacing:.15em; margin-bottom:8px; }
  .pb-name { font-family:'VT323',monospace; font-size:2.4rem; color:var(--green); text-shadow:0 0 16px var(--green-glow); letter-spacing:.08em; line-height:1; margin-bottom:4px; }
  .pb-handle { font-size:.62rem; color:rgba(0,255,65,.4); letter-spacing:.2em; }
  .pb-divider { border:none; border-top:1px solid rgba(0,255,65,.1); margin:20px 0; }

  .pb-section-title { font-size:.58rem; letter-spacing:.22em; color:rgba(0,255,65,.35); text-transform:uppercase; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
  .pb-section-title::after { content:''; flex:1; height:1px; background:rgba(0,255,65,.08); }

  .pb-notice { display:inline-flex; align-items:center; gap:8px; background:rgba(255,184,0,.06); border:1px solid rgba(255,184,0,.2); color:var(--amber); font-size:.58rem; letter-spacing:.12em; padding:6px 14px; border-radius:2px; margin-bottom:20px; }

  .pb-nav { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
  .pb-nav-btn { background:none; border:1px solid rgba(0,255,65,.15); color:rgba(0,255,65,.45); font-family:'Share Tech Mono',monospace; font-size:.58rem; padding:5px 14px; cursor:pointer !important; letter-spacing:.1em; transition:all .2s; }
  .pb-nav-btn:hover,.pb-nav-btn.active { border-color:rgba(0,255,65,.4); color:var(--green); background:rgba(0,255,65,.04); }

  .pb-content { min-height:200px; }
  .pb-loading { font-size:.62rem; color:rgba(0,255,65,.3); letter-spacing:.15em; padding:20px 0; }

  /* Writeup list in profile */
  .pb-cat-title { font-size:.58rem; letter-spacing:.18em; color:rgba(0,255,65,.35); margin:16px 0 8px; text-transform:uppercase; }
  .pb-writeup-item { padding:10px 0; border-bottom:1px solid rgba(0,255,65,.05); cursor:pointer !important; transition:all .2s; display:flex; align-items:center; gap:10px; }
  .pb-writeup-item:hover { padding-left:8px; border-bottom-color:rgba(0,255,65,.2); }
  .pb-writeup-title { font-size:.72rem; color:rgba(0,255,65,.75); flex:1; }
  .pb-writeup-meta { font-size:.55rem; color:rgba(0,255,65,.3); letter-spacing:.08em; }
  .pb-diff { font-size:.5rem; padding:1px 7px; border-radius:999px; font-weight:700; }
  .pb-diff.easy   { background:rgba(34,197,94,.1); color:#22c55e; }
  .pb-diff.medium { background:rgba(234,179,8,.1); color:#eab308; }
  .pb-diff.hard   { background:rgba(249,115,22,.1); color:#f97316; }
  .pb-diff.insane { background:rgba(239,68,68,.1); color:#ef4444; }
  .pb-diff.beginner{background:rgba(6,182,212,.1); color:#06b6d4; }
  .pb-empty { font-size:.62rem; color:rgba(0,255,65,.25); letter-spacing:.12em; padding:12px 0; }

  /* Map preview link in profile */
  .pb-map-link { display:inline-flex; align-items:center; gap:8px; background:rgba(0,255,65,.04); border:1px solid rgba(0,255,65,.2); color:rgba(0,255,65,.7); font-family:'Share Tech Mono',monospace; font-size:.62rem; padding:10px 20px; letter-spacing:.12em; text-decoration:none; margin-top:8px; transition:all .2s; cursor:pointer !important; }
  .pb-map-link:hover { background:rgba(0,255,65,.08); border-color:rgba(0,255,65,.4); }
</style>
</head>
<body>
<input type="text" style="display:none">
<input type="password" style="display:none">

<canvas id="noise"></canvas>
<div class="scanline"></div>
<div id="cursor"></div>

<div id="boot-seq">
  <p>CYBERPULSE OS v2.4.1 ................ <span class="ok">LOADED</span></p>
  <p>SECURITY MODULE ..................... <span class="ok">ACTIVE</span></p>
  <p>ENCRYPTION LAYER ................... <span class="ok">AES-256</span></p>
  <p>NETWORK MONITOR .................... <span class="ok">RUNNING</span></p>
  <p>THREAT DETECTION ENGINE ............ <span class="ok">ONLINE</span></p>
  <p>SESSION VALIDATOR .................. <span class="ok">READY</span></p>
  <p>&nbsp;</p>
  <p>AWAITING OPERATOR AUTHENTICATION...</p>
</div>

<div id="granted">ACCESS GRANTED</div>

<div id="terminal" style="opacity:0;transition:opacity .6s ease;">
  <div class="term-box">
    <div class="bracket-bl"></div>
    <div class="bracket-br"></div>

    <div class="boot-header">
      > CYBERPULSE SECURE NODE — RESTRICTED ACCESS<br>
      > AUTHENTICATED OPERATORS ONLY<br>
      > UNAUTHORIZED ACCESS IS MONITORED &amp; LOGGED <span class="blink">_</span>
    </div>

    <div class="sys-title">SYSTEM OVERRIDE</div>
    <div class="sys-sub">TERMINAL ACCESS PROTOCOL v2</div>

    <form id="login-form" method="POST" action="login.php" autocomplete="off">
      <div class="field-row">
        <label class="field-label" for="username">OPERATOR ID</label>
        <div class="field-wrap">
          <span class="field-prompt">&gt;</span>
          <input class="term-input" type="text" id="username" name="opr_id"
                 placeholder="enter username" spellcheck="false" autocomplete="off">
        </div>
      </div>
      <div class="field-row">
        <label class="field-label" for="password">ACCESS KEY</label>
        <div class="field-wrap">
          <span class="field-prompt">&gt;</span>
          <input class="term-input" type="password" id="password" name="access_token"
                 placeholder="enter key" autocomplete="off">
        </div>
      </div>
      <button type="submit" style="display:none"></button>
    </form>

    <div class="key-hint">[ PRESS ENTER TO AUTHENTICATE ]</div>

    <div id="status-line" class="ok">
      <?php if ($locked): ?>
        <span style="color:var(--amber)">⚠ RATE LIMIT EXCEEDED — WAIT 15 MINUTES</span>
      <?php elseif ($error === 'ACCESS_DENIED'): ?>
        <span style="color:var(--red)">✕ ACCESS DENIED — INVALID CREDENTIALS</span>
      <?php else: ?>
        SECURE CHANNEL READY
      <?php endif; ?>
    </div>
  </div>

  <!-- PROFILE BUTTON -->
  <div class="profile-btn-wrap">
    <button class="profile-btn" onclick="openProfile()">
      <span style="color:rgba(0,255,65,.3)">$</span>
      view --profile wijayarathna.p.r.m
      <span class="pb-arrow">→</span>
    </button>
  </div>
</div>

<!-- PROFILE MODAL -->
<div id="profile-modal">
  <div class="profile-box">
    <button class="pb-close" onclick="closeProfile()">[ CLOSE ]</button>

    <div class="pb-header">
      <div class="pb-prompt">> OPERATOR PROFILE // PUBLIC READ ACCESS</div>
      <div class="pb-name">WIJAYARATHNA P.R.M</div>
      <div class="pb-handle">> @ravindu · cybersecurity researcher</div>
    </div>

    <div class="pb-notice">
      ⚠ VIEW ONLY MODE — All content is read-only. No modifications permitted.
    </div>

    <div class="pb-nav">
      <button class="pb-nav-btn active" onclick="pbTab('writeups',this)">[ WRITEUPS ]</button>
      <button class="pb-nav-btn" onclick="pbTab('map',this)">[ INTEL MAP ]</button>
    </div>

    <div class="pb-content" id="pb-content">
      <div class="pb-loading">> Loading writeup index...</div>
    </div>
  </div>
</div>

<script>
const cur = document.getElementById('cursor');
document.addEventListener('mousemove', e => {
  cur.style.left = e.clientX + 'px';
  cur.style.top  = e.clientY + 'px';
});

(function noiseGen() {
  const c = document.getElementById('noise');
  const ctx = c.getContext('2d');
  function resize() { c.width = innerWidth; c.height = innerHeight; }
  resize(); window.addEventListener('resize', resize);
  function frame() {
    const img = ctx.createImageData(c.width, c.height);
    const d = img.data;
    for (let i = 0; i < d.length; i += 4) {
      const v = Math.random() * 255;
      d[i] = d[i+1] = d[i+2] = v; d[i+3] = 255;
    }
    ctx.putImageData(img, 0, 0);
    setTimeout(frame, 80);
  }
  frame();
})();

const bootEl = document.getElementById('boot-seq');
const termEl = document.getElementById('terminal');
const lines  = bootEl.querySelectorAll('p');

<?php if ($error || $locked): ?>
bootEl.style.display = 'none';
termEl.style.opacity = '1';
document.getElementById('username').focus();
<?php else: ?>
let i = 0;
function showLine() {
  if (i < lines.length) {
    lines[i].classList.add('show'); i++;
    setTimeout(showLine, i < lines.length - 1 ? 160 : 500);
  } else {
    setTimeout(() => {
      bootEl.style.opacity = '0';
      setTimeout(() => { bootEl.style.display='none'; termEl.style.opacity='1'; document.getElementById('username').focus(); }, 500);
    }, 400);
  }
}
setTimeout(showLine, 300);
<?php endif; ?>

document.addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    if (document.getElementById('profile-modal').classList.contains('open')) return;
    const status = document.getElementById('status-line');
    status.textContent = '> AUTHENTICATING...'; status.className = 'ok';
    document.getElementById('login-form').submit();
  }
  if (e.key === 'Escape') closeProfile();
});

<?php if ($error === 'ACCESS_DENIED'): ?>
(function() { const box = document.querySelector('.term-box'); box.classList.add('glitch'); setTimeout(() => box.classList.remove('glitch'), 500); })();
<?php endif; ?>

// ── PROFILE MODAL ─────────────────────────────────────────────────────
const CATS = [
  {id:'web',   name:'Web Exploitation'},
  {id:'re',    name:'Reverse Engineering'},
  {id:'pwn',   name:'Binary Exploitation (Pwn)'},
  {id:'for',   name:'Forensics'},
  {id:'cry',   name:'Cryptography'},
  {id:'osint', name:'OSINT'},
];
const PLAT = {'':'','ctf':'CTF','htb':'HackTheBox','thm':'TryHackMe','vulnhub':'VulnHub','picoctf':'picoCTF','sans':'SANS'};

function openProfile() {
  document.getElementById('profile-modal').classList.add('open');
  pbTab('writeups', document.querySelector('.pb-nav-btn'));
}
function closeProfile() { document.getElementById('profile-modal').classList.remove('open'); }

let activeTab = 'writeups';
async function pbTab(tab, btn) {
  activeTab = tab;
  document.querySelectorAll('.pb-nav-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  const content = document.getElementById('pb-content');
  content.innerHTML = `<div class="pb-loading">> Loading ${tab}...</div>`;

  if (tab === 'writeups') {
    try {
      const res = await fetch('api.php?r=writeups');
      const all = await res.json();
      const bycat = {};
      all.forEach(w => { if (!bycat[w.category]) bycat[w.category]=[]; bycat[w.category].push(w); });
      let html = '';
      CATS.forEach(c => {
        const items = bycat[c.id]||[];
        html += `<div class="pb-section-title">${c.name} <span style="font-size:.5rem;opacity:.5">${items.length}</span></div>`;
        if (!items.length) {
          html += `<div class="pb-empty">> no entries</div>`;
        } else {
          items.forEach(w => {
            html += `
              <div class="pb-writeup-item" onclick="goViewWriteup(${w.id})">
                <span style="color:rgba(0,255,65,.25);font-size:.55rem;">&gt;</span>
                <span class="pb-writeup-title">${escHtml(w.title)}</span>
                <span class="pb-diff ${w.difficulty||'medium'}">${w.difficulty||'medium'}</span>
                ${w.platform_tag ? `<span class="pb-writeup-meta">${escHtml(PLAT[w.platform_tag]||w.platform_tag)}</span>` : ''}
              </div>`;
          });
        }
      });
      content.innerHTML = html || `<div class="pb-empty">> no writeups found</div>`;
    } catch(e) { content.innerHTML = `<div class="pb-loading" style="color:var(--red);">> Error loading writeups.</div>`; }

  } else if (tab === 'map') {
    content.innerHTML = `
      <div class="pb-section-title">Intel Map</div>
      <p style="font-size:.65rem;color:rgba(0,255,65,.45);line-height:1.9;margin-bottom:16px;">
        > The Intel Map tracks real-time cyber events and their progressions.<br>
        > Each event contains a series of geo-tagged pins with timestamps,<br>
        > forming a timeline of intelligence entries. Access is view-only.
      </p>
      <div class="pb-notice" style="margin-bottom:16px;">⚠ You will enter VIEW ONLY mode. No data can be added or removed.</div>
      <a class="pb-map-link" onclick="goViewMap()">
        <span style="color:rgba(0,255,65,.4)">$</span>
        open --view-only intel_map
        <span>→</span>
      </a>`;
  }
}

function escHtml(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function goViewWriteup(id) {
  // Set view-only session then navigate
  window.location = 'login.php?action=view_profile';
}
function goViewMap() {
  window.location = 'login.php?action=view_profile';
}

// Actually navigate to write page after view mode
// We use the PHP redirect at top. For writeup open we store target.
function goViewWriteupDirect(id) {
  fetch('api.php?r=view_profile', {method:'POST'}).then(()=>{
    window.location = 'writeups.php';
  });
}
</script>
</body>
</html>
