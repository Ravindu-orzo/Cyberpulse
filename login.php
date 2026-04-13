<?php
require_once 'db.php';
session_start();

// Already logged in → go to journal
if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

init_db();

$error   = '';
$locked  = false;
$max_attempts = 5;
$window_secs  = 15 * 60; // 15 minutes

// ── Handle POST login ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = get_pdo();
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Purge old attempts
    $pdo->prepare("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL {$window_secs} SECOND)")
        ->execute();

    // Count recent attempts from this IP
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ?");
    $stmt->execute([$ip]);
    $count = (int) $stmt->fetchColumn();

    if ($count >= $max_attempts) {
        $locked = true;
        $error  = 'RATE_LIMIT';
    } else {
        $username = trim($_POST['opr_id'] ?? '');
        $password = trim($_POST['access_token'] ?? '');

        // Log attempt
        $pdo->prepare("INSERT INTO login_attempts (ip) VALUES (?)")->execute([$ip]);

        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = $username;
            $_SESSION['uid']  = $user['id'];
            // Clear their attempts on success
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
    cursor: none !important; /* Force hides the OS pointer on everything */
    user-select: none;         /* Standard */
    -webkit-user-select: none; /* Safari/Chrome */
    -moz-user-select: none;    /* Firefox */
  }

  .term-input {
    user-select: text !important;
    -webkit-user-select: text !important;
  }

  :root {
    --green: #00ff41;
    --green-dim: rgba(0,255,65,0.55);
    --green-glow: rgba(0,255,65,0.2);
    --red: #ff3333;
    --bg: #020804;
  }

  html, body {
    height: 100%;
    background: var(--bg);
    color: var(--green);
    font-family: 'Share Tech Mono', monospace;
    overflow: hidden;
    cursor: none;
  }

  /* ── CRT Effects ── */
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
      repeating-linear-gradient(
        0deg,
        transparent,
        transparent 2px,
        rgba(0,0,0,0.18) 2px,
        rgba(0,0,0,0.18) 4px
      );
    pointer-events: none;
    z-index: 10;
  }

  body::after {
    content: '';
    position: fixed;
    inset: 0;
    background: radial-gradient(ellipse at center, transparent 60%, rgba(0,0,0,0.7) 100%);
    pointer-events: none;
    z-index: 11;
  }

  /* custom cursor */
  #cursor {
    position: fixed;
    width: 4px;
    height: 18px;
    background: var(--green);
    pointer-events: none;
    z-index: 9999;
    transform: translate(-50%, -50%);
    box-shadow: 0 0 8px var(--green);
    animation: cur-blink 1s step-end infinite;
  }
  @keyframes cur-blink { 50% { opacity: 0; } }

  /* ── NOISE CANVAS ── */
  #noise {
    position: fixed;
    inset: 0;
    opacity: 0.03;
    pointer-events: none;
    z-index: 9;
  }

  /* ── SCANLINE SWEEP ── */
  .scanline {
    position: fixed;
    top: -10%;
    left: 0;
    right: 0;
    height: 8px;
    background: linear-gradient(transparent, rgba(0,255,65,0.06), transparent);
    animation: scan 6s linear infinite;
    pointer-events: none;
    z-index: 12;
  }
  @keyframes scan { to { top: 110%; } }

  /* ── MAIN TERMINAL ── */
  #terminal {
    position: fixed;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 20;
    padding: 40px;
  }

  .term-box {
    width: 100%;
    max-width: 600px;
    border: 1px solid rgba(0,255,65,0.2);
    padding: 40px 44px;
    background: rgba(0,10,2,0.85);
    box-shadow:
      0 0 0 1px rgba(0,255,65,0.05),
      0 0 40px rgba(0,255,65,0.04),
      inset 0 0 60px rgba(0,255,65,0.02);
    position: relative;
  }

  /* corner brackets */
  .term-box::before, .term-box::after,
  .bracket-bl, .bracket-br {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    border-color: var(--green-dim);
    border-style: solid;
  }
  .term-box::before { top: -1px; left: -1px; border-width: 2px 0 0 2px; }
  .term-box::after  { top: -1px; right: -1px; border-width: 2px 2px 0 0; }
  .bracket-bl { bottom: -1px; left: -1px; border-width: 0 0 2px 2px; }
  .bracket-br { bottom: -1px; right: -1px; border-width: 0 2px 2px 0; }

  .boot-header {
    font-family: 'VT323', monospace;
    font-size: 0.75rem;
    color: rgba(0,255,65,0.35);
    letter-spacing: 0.12em;
    margin-bottom: 32px;
    line-height: 1.6;
  }

  .boot-header .blink { animation: cur-blink 1s step-end infinite; }

  .sys-title {
    font-family: 'VT323', monospace;
    font-size: 2.8rem;
    letter-spacing: 0.08em;
    color: var(--green);
    text-shadow: 0 0 20px var(--green-glow), 0 0 40px rgba(0,255,65,0.1);
    margin-bottom: 6px;
    line-height: 1;
  }

  .sys-sub {
    font-size: 0.65rem;
    color: rgba(0,255,65,0.3);
    letter-spacing: 0.2em;
    margin-bottom: 40px;
  }

  /* ── FORM FIELDS ── */
  .field-row {
    margin-bottom: 18px;
  }

  .field-label {
    display: block;
    font-size: 0.65rem;
    letter-spacing: 0.2em;
    color: rgba(0,255,65,0.45);
    margin-bottom: 8px;
  }

  .field-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid rgba(0,255,65,0.2);
    padding-bottom: 6px;
  }

  .field-prompt {
    color: var(--green-dim);
    font-size: 0.9rem;
    flex-shrink: 0;
  }

  .term-input {
    background: transparent;
    border: none;
    color: var(--green);
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.9rem;
    flex: 1;
    outline: none;
    caret-color: var(--green);
    letter-spacing: 0.05em;
  }

  .term-input::placeholder { color: rgba(0,255,65,0.15); }

  /* password masking style */
  #password { letter-spacing: 0.25em; }

  /* ── HINT ── */
  .key-hint {
    font-size: 0.6rem;
    color: rgba(0,255,65,0.25);
    letter-spacing: 0.18em;
    margin-top: 28px;
    text-align: center;
  }

  /* ── STATUS LINE ── */
  #status-line {
    margin-top: 22px;
    font-size: 0.7rem;
    letter-spacing: 0.1em;
    min-height: 20px;
    transition: color 0.2s;
  }

  #status-line.ok    { color: var(--green-dim); }
  #status-line.error { color: var(--red); text-shadow: 0 0 8px rgba(255,51,51,0.4); }
  #status-line.warn  { color: #ffb800; }

  /* ── GLITCH ANIMATION on error ── */
  @keyframes glitch {
    0%   { transform: translate(0); }
    10%  { transform: translate(-3px, 1px); }
    20%  { transform: translate(3px, -1px); }
    30%  { transform: translate(-2px, 2px); }
    40%  { transform: translate(2px, -2px); }
    50%  { transform: translate(0); }
    100% { transform: translate(0); }
  }

  .glitch { animation: glitch 0.4s ease; }

  /* ── BOOT SEQUENCE ── */
  #boot-seq {
    position: fixed;
    inset: 0;
    background: var(--bg);
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 4px;
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.75rem;
    color: rgba(0,255,65,0.6);
    letter-spacing: 0.1em;
    padding: 60px;
    transition: opacity 0.5s ease;
  }

  #boot-seq p {
    opacity: 0;
    transform: translateY(2px);
    transition: opacity 0.3s ease, transform 0.3s ease;
    width: 100%;
    max-width: 500px;
  }

  #boot-seq p.show { opacity: 1; transform: translateY(0); }
  #boot-seq p .ok  { color: var(--green); }
  #boot-seq p .fail { color: var(--red); }

  /* ── ACCESS GRANTED overlay ── */
  #granted {
    display: none;
    position: fixed;
    inset: 0;
    background: var(--bg);
    z-index: 60;
    align-items: center;
    justify-content: center;
    font-family: 'VT323', monospace;
    font-size: 4rem;
    color: var(--green);
    text-shadow: 0 0 30px var(--green), 0 0 60px rgba(0,255,65,0.3);
    letter-spacing: 0.15em;
    animation: flicker 0.1s ease infinite;
  }

  @keyframes flicker {
    0%,100% { opacity: 1; }
    50% { opacity: 0.95; }
    75% { opacity: 1; }
    85% { opacity: 0.9; }
  }

  /* PHP error pre-populated */
  .php-error-msg { display: none; }
</style>
</head>
<body>

<canvas id="noise"></canvas>
<div class="scanline"></div>
<div id="cursor"></div>

<!-- BOOT SEQUENCE (shown on first load) -->
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

<!-- ACCESS GRANTED overlay -->
<div id="granted">ACCESS GRANTED</div>

<!-- MAIN TERMINAL -->
<div id="terminal" style="opacity:0;transition:opacity 0.6s ease;">
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

      <!-- Hidden submit — Enter key fires it -->
      <button type="submit" style="display:none"></button>
    </form>

    <div class="key-hint">[ PRESS ENTER TO AUTHENTICATE ]</div>

    <div id="status-line" class="ok">
      <?php if ($locked): ?>
        <span style="color:#ffb800">⚠ RATE LIMIT EXCEEDED — WAIT 15 MINUTES</span>
      <?php elseif ($error === 'ACCESS_DENIED'): ?>
        <span style="color:var(--red)">✕ ACCESS DENIED — INVALID CREDENTIALS</span>
      <?php else: ?>
        SECURE CHANNEL READY
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// ── Custom cursor ─────────────────────────────────────────────────────
const cur = document.getElementById('cursor');
document.addEventListener('mousemove', e => {
  cur.style.left = e.clientX + 'px';
  cur.style.top  = e.clientY + 'px';
});

// ── Noise canvas ──────────────────────────────────────────────────────
(function noiseGen() {
  const c = document.getElementById('noise');
  const ctx = c.getContext('2d');
  function resize() { c.width = innerWidth; c.height = innerHeight; }
  resize();
  window.addEventListener('resize', resize);
  function frame() {
    const img = ctx.createImageData(c.width, c.height);
    const d = img.data;
    for (let i = 0; i < d.length; i += 4) {
      const v = Math.random() * 255;
      d[i] = d[i+1] = d[i+2] = v;
      d[i+3] = 255;
    }
    ctx.putImageData(img, 0, 0);
    setTimeout(frame, 80);
  }
  frame();
})();

// ── Boot sequence ────────────────────────────────────────────────────
const bootEl = document.getElementById('boot-seq');
const termEl = document.getElementById('terminal');
const lines  = bootEl.querySelectorAll('p');

<?php if ($error || $locked): ?>
// Error state — skip boot
bootEl.style.display = 'none';
termEl.style.opacity = '1';
document.getElementById('username').focus();
<?php else: ?>
let i = 0;
function showLine() {
  if (i < lines.length) {
    lines[i].classList.add('show');
    i++;
    setTimeout(showLine, i < lines.length - 1 ? 160 : 500);
  } else {
    setTimeout(() => {
      bootEl.style.opacity = '0';
      setTimeout(() => {
        bootEl.style.display = 'none';
        termEl.style.opacity = '1';
        document.getElementById('username').focus();
      }, 500);
    }, 400);
  }
}
setTimeout(showLine, 300);
<?php endif; ?>

// ── Keyboard: Enter submits ────────────────────────────────────────────
document.addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    const form = document.getElementById('login-form');
    // Trigger validation visual
    const status = document.getElementById('status-line');
    status.textContent = '> AUTHENTICATING...';
    status.className   = 'ok';
    form.submit();
  }
});

// ── Glitch on error ───────────────────────────────────────────────────
<?php if ($error === 'ACCESS_DENIED'): ?>
(function() {
  const box = document.querySelector('.term-box');
  box.classList.add('glitch');
  setTimeout(() => box.classList.remove('glitch'), 500);
})();
<?php endif; ?>
</script>
</body>
</html>
