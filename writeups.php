<?php
require_once 'db.php';
session_start();
$isViewOnly = !empty($_SESSION['view_only']);
if (empty($_SESSION['user']) && !$isViewOnly) {
    header('Location: login.php');
    exit();
}
$operator = $isViewOnly ? 'VIEWER' : htmlspecialchars($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cyber Pulse — Writeups</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #080c10; --surface: #0d1117; --surface2: #131920; --border: #1e2d3d;
  --text: #c9d1d9; --text-dim: #4a5568; --text-muted: #2d3748;
  --accent: #00d4ff; --accent-glow: rgba(0,212,255,0.2);
  --web: #f97316; --re: #a855f7; --pwn: #ef4444; --for: #22c55e; --cry: #eab308; --osint: #06b6d4;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;background:var(--bg);color:var(--text);font-family:'Space Mono',monospace;overflow:hidden;}
body::before{content:'';position:fixed;inset:0;background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,0.03) 2px,rgba(0,0,0,0.03) 4px);pointer-events:none;z-index:1000;}
#app{display:flex;flex-direction:column;height:100vh;}

/* HEADER */
header{padding:12px 24px;display:flex;align-items:center;gap:14px;flex-shrink:0;border-bottom:1px solid var(--border);background:var(--bg);}
.site-title{font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;letter-spacing:.15em;color:var(--accent);text-transform:uppercase;text-shadow:0 0 20px var(--accent-glow);}
.site-sub{font-size:.6rem;color:var(--text-dim);letter-spacing:.1em;}
.nav-links{margin-left:auto;display:flex;align-items:center;gap:12px;}
.nav-link{font-size:.6rem;letter-spacing:.12em;text-transform:uppercase;color:var(--text-dim);text-decoration:none;padding:4px 10px;border:1px solid transparent;border-radius:3px;transition:all .2s;}
.nav-link:hover{border-color:var(--border);color:var(--text);}
.nav-link.active{border-color:rgba(168,85,247,.35);color:#a855f7;}
.operator-tag{font-size:.58rem;color:var(--text-dim);}
.operator-tag span{color:var(--accent);}
.logout-btn{background:none;border:1px solid rgba(255,77,77,.2);color:rgba(255,77,77,.6);font-family:'Space Mono',monospace;font-size:.55rem;letter-spacing:.1em;padding:4px 10px;border-radius:3px;cursor:pointer;text-transform:uppercase;transition:all .2s;}
.logout-btn:hover{border-color:rgba(255,77,77,.5);color:#ff4d4d;}

<?php if($isViewOnly): ?>
.view-only-banner{background:rgba(234,179,8,.08);border-bottom:1px solid rgba(234,179,8,.25);padding:6px 24px;font-size:.6rem;letter-spacing:.12em;color:#eab308;text-align:center;flex-shrink:0;}
<?php endif; ?>

/* MAIN CONTENT */
#content{flex:1;overflow:hidden;display:flex;flex-direction:column;}

/* VIEWS */
.view{display:none;flex:1;overflow:hidden;}
.view.active{display:flex;flex-direction:column;}

/* ── CATEGORY VIEW ── */
#cat-view{padding:32px;overflow-y:auto;align-items:flex-start;}
.cat-header{font-size:.6rem;letter-spacing:.2em;color:var(--text-dim);text-transform:uppercase;margin-bottom:24px;}
.cat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;width:100%;}
.cat-card{padding:24px;border-radius:8px;border:1px solid;cursor:pointer;transition:all .25s;position:relative;overflow:hidden;}
.cat-card::before{content:'';position:absolute;inset:0;opacity:0;transition:opacity .25s;}
.cat-card:hover::before{opacity:1;}
.cat-card:hover{transform:translateY(-2px);}
.cat-icon{font-size:1.6rem;margin-bottom:12px;}
.cat-name{font-family:'Syne',sans-serif;font-weight:700;font-size:.95rem;margin-bottom:6px;letter-spacing:.03em;}
.cat-desc{font-size:.62rem;color:var(--text-dim);line-height:1.6;}
.cat-count{position:absolute;top:16px;right:16px;font-size:.55rem;padding:2px 8px;border-radius:999px;letter-spacing:.1em;}

.cat-web  {border-color:rgba(249,115,22,.3);background:rgba(249,115,22,.05);}
.cat-web:hover{border-color:rgba(249,115,22,.6);box-shadow:0 8px 32px rgba(249,115,22,.12);}
.cat-web .cat-name{color:#f97316;}.cat-web .cat-count{background:rgba(249,115,22,.15);color:#f97316;}
.cat-web::before{background:radial-gradient(circle at 80% 20%,rgba(249,115,22,.08),transparent 60%);}

.cat-re   {border-color:rgba(168,85,247,.3);background:rgba(168,85,247,.05);}
.cat-re:hover{border-color:rgba(168,85,247,.6);box-shadow:0 8px 32px rgba(168,85,247,.12);}
.cat-re .cat-name{color:#a855f7;}.cat-re .cat-count{background:rgba(168,85,247,.15);color:#a855f7;}
.cat-re::before{background:radial-gradient(circle at 80% 20%,rgba(168,85,247,.08),transparent 60%);}

.cat-pwn  {border-color:rgba(239,68,68,.3);background:rgba(239,68,68,.05);}
.cat-pwn:hover{border-color:rgba(239,68,68,.6);box-shadow:0 8px 32px rgba(239,68,68,.12);}
.cat-pwn .cat-name{color:#ef4444;}.cat-pwn .cat-count{background:rgba(239,68,68,.15);color:#ef4444;}
.cat-pwn::before{background:radial-gradient(circle at 80% 20%,rgba(239,68,68,.08),transparent 60%);}

.cat-for  {border-color:rgba(34,197,94,.3);background:rgba(34,197,94,.05);}
.cat-for:hover{border-color:rgba(34,197,94,.6);box-shadow:0 8px 32px rgba(34,197,94,.12);}
.cat-for .cat-name{color:#22c55e;}.cat-for .cat-count{background:rgba(34,197,94,.15);color:#22c55e;}
.cat-for::before{background:radial-gradient(circle at 80% 20%,rgba(34,197,94,.08),transparent 60%);}

.cat-cry  {border-color:rgba(234,179,8,.3);background:rgba(234,179,8,.05);}
.cat-cry:hover{border-color:rgba(234,179,8,.6);box-shadow:0 8px 32px rgba(234,179,8,.12);}
.cat-cry .cat-name{color:#eab308;}.cat-cry .cat-count{background:rgba(234,179,8,.15);color:#eab308;}
.cat-cry::before{background:radial-gradient(circle at 80% 20%,rgba(234,179,8,.08),transparent 60%);}

.cat-osint{border-color:rgba(6,182,212,.3);background:rgba(6,182,212,.05);}
.cat-osint:hover{border-color:rgba(6,182,212,.6);box-shadow:0 8px 32px rgba(6,182,212,.12);}
.cat-osint .cat-name{color:#06b6d4;}.cat-osint .cat-count{background:rgba(6,182,212,.15);color:#06b6d4;}
.cat-osint::before{background:radial-gradient(circle at 80% 20%,rgba(6,182,212,.08),transparent 60%);}

/* ── LIST VIEW ── */
#list-view{flex-direction:column;}
.list-header{display:flex;align-items:center;gap:12px;padding:16px 24px;border-bottom:1px solid var(--border);flex-shrink:0;}
.back-btn{background:none;border:1px solid var(--border);color:var(--text-dim);font-family:'Space Mono',monospace;font-size:.6rem;padding:5px 12px;border-radius:4px;cursor:pointer;transition:all .2s;letter-spacing:.08em;}
.back-btn:hover{border-color:var(--text-dim);color:var(--text);}
.list-cat-name{font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;}
.list-actions{margin-left:auto;display:flex;gap:8px;}
.new-writeup-btn,.resources-btn{display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:4px;font-family:'Space Mono',monospace;font-size:.62rem;letter-spacing:.08em;cursor:pointer;text-transform:uppercase;transition:all .2s;border:1px solid;}
.new-writeup-btn{background:rgba(0,212,255,.08);border-color:rgba(0,212,255,.3);color:var(--accent);}
.new-writeup-btn:hover{background:rgba(0,212,255,.15);box-shadow:0 0 12px rgba(0,212,255,.15);}
.resources-btn{background:rgba(168,85,247,.08);border-color:rgba(168,85,247,.3);color:#a855f7;}
.resources-btn:hover{background:rgba(168,85,247,.15);}

.writeup-grid{padding:20px 24px;overflow-y:auto;flex:1;display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px;align-content:start;}
.writeup-card{padding:18px;border:1px solid var(--border);border-radius:6px;background:var(--surface);cursor:pointer;transition:all .2s;position:relative;}
.writeup-card:hover{border-color:rgba(0,212,255,.25);box-shadow:0 4px 20px rgba(0,0,0,.3);transform:translateY(-1px);}
.wc-title{font-family:'Syne',sans-serif;font-weight:700;font-size:.9rem;color:#e6edf3;margin-bottom:8px;line-height:1.4;}
.wc-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.tag-platform{font-size:.55rem;padding:2px 8px;border-radius:999px;border:1px solid rgba(0,212,255,.3);color:var(--accent);background:rgba(0,212,255,.06);letter-spacing:.08em;}
.tag-diff{font-size:.55rem;padding:2px 8px;border-radius:999px;letter-spacing:.08em;font-weight:700;}
.tag-diff.easy{background:rgba(34,197,94,.12);color:#22c55e;border:1px solid rgba(34,197,94,.3);}
.tag-diff.medium{background:rgba(234,179,8,.12);color:#eab308;border:1px solid rgba(234,179,8,.3);}
.tag-diff.hard{background:rgba(249,115,22,.12);color:#f97316;border:1px solid rgba(249,115,22,.3);}
.tag-diff.insane{background:rgba(239,68,68,.12);color:#ef4444;border:1px solid rgba(239,68,68,.3);}
.tag-diff.beginner{background:rgba(6,182,212,.12);color:#06b6d4;border:1px solid rgba(6,182,212,.3);}
.wc-date{font-size:.55rem;color:var(--text-muted);margin-left:auto;letter-spacing:.06em;}
.wc-delete{position:absolute;top:10px;right:10px;background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:.65rem;padding:3px 6px;border-radius:3px;opacity:0;transition:opacity .2s;}
.writeup-card:hover .wc-delete{opacity:1;}
.wc-delete:hover{color:#ff4d4d;background:rgba(255,77,77,.08);}

.empty-list{padding:40px 24px;text-align:center;color:var(--text-muted);font-size:.7rem;letter-spacing:.1em;line-height:2;}

/* ── EDITOR VIEW ── */
#editor-view{flex-direction:column;}
.editor-header{display:flex;align-items:center;gap:10px;padding:12px 24px;border-bottom:1px solid var(--border);flex-shrink:0;}
.editor-title-input{flex:1;background:transparent;border:none;color:#e6edf3;font-family:'Syne',sans-serif;font-weight:700;font-size:1.1rem;outline:none;}
.editor-title-input::placeholder{color:var(--text-muted);}
.editor-tags{display:flex;gap:8px;padding:8px 24px;border-bottom:1px solid var(--border);flex-shrink:0;align-items:center;flex-wrap:wrap;}
.editor-tags label{font-size:.55rem;letter-spacing:.15em;color:var(--text-dim);text-transform:uppercase;}
.editor-tags select{background:var(--surface2);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:.65rem;padding:5px 10px;border-radius:4px;outline:none;cursor:pointer;}
.autosave-indicator{margin-left:auto;font-size:.55rem;color:var(--text-muted);letter-spacing:.1em;display:flex;align-items:center;gap:6px;}
.autosave-dot{width:6px;height:6px;border-radius:50%;background:var(--text-muted);transition:background .3s;}
.autosave-dot.saving{background:#eab308;animation:pulse-dot .7s ease infinite;}
.autosave-dot.saved{background:#22c55e;}
@keyframes pulse-dot{0%,100%{opacity:1;}50%{opacity:.4;}}

.toolbar{display:flex;gap:4px;padding:8px 24px;border-bottom:1px solid var(--border);flex-shrink:0;flex-wrap:wrap;background:var(--surface);}
.tb-btn{background:none;border:1px solid var(--border);color:var(--text-dim);font-family:'Space Mono',monospace;font-size:.62rem;padding:5px 10px;border-radius:4px;cursor:pointer;transition:all .2s;letter-spacing:.05em;}
.tb-btn:hover{border-color:rgba(0,212,255,.3);color:var(--accent);background:rgba(0,212,255,.05);}
.tb-sep{width:1px;background:var(--border);margin:0 4px;align-self:stretch;}
.tb-img-input{display:none;}

.editor-body{flex:1;display:flex;overflow:hidden;}
#editor-area{flex:1;overflow-y:auto;padding:24px;background:var(--surface);}
#editor-area:focus{outline:none;}
#editor-area:empty::before{content:attr(data-placeholder);color:var(--text-muted);pointer-events:none;}

/* Rich editor elements */
#editor-area h1,#editor-area h2,#editor-area h3{font-family:'Syne',sans-serif;margin:16px 0 8px;line-height:1.3;}
#editor-area h1{font-size:1.5rem;color:#e6edf3;}
#editor-area h2{font-size:1.2rem;color:#e6edf3;}
#editor-area h3{font-size:1rem;color:#c9d1d9;}
#editor-area p{margin:8px 0;line-height:1.8;font-size:.82rem;}
#editor-area ul,#editor-area ol{padding-left:24px;margin:8px 0;}
#editor-area li{margin:4px 0;font-size:.82rem;line-height:1.7;}
#editor-area strong{color:#e6edf3;}
#editor-area em{color:#a5b4fc;}
#editor-area a{color:var(--accent);}
#editor-area img{max-width:100%;border-radius:6px;border:1px solid var(--border);margin:8px 0;display:block;}
#editor-area pre{background:#0a0f14;border:1px solid var(--border);border-radius:6px;padding:16px;margin:12px 0;overflow-x:auto;font-size:.75rem;line-height:1.7;color:#a8d8ea;font-family:'Space Mono',monospace;}
#editor-area code:not(pre code){background:rgba(0,212,255,.08);border:1px solid rgba(0,212,255,.2);border-radius:3px;padding:1px 6px;font-size:.78rem;color:var(--accent);}
#editor-area blockquote{border-left:3px solid var(--accent);margin:12px 0;padding:8px 16px;background:rgba(0,212,255,.04);color:var(--text-dim);font-size:.8rem;}
#editor-area hr{border:none;border-top:1px solid var(--border);margin:16px 0;}
#editor-area mark{background:rgba(234,179,8,.2);color:#eab308;border-radius:2px;padding:0 3px;}

.editor-footer{display:flex;gap:10px;padding:12px 24px;border-top:1px solid var(--border);flex-shrink:0;background:var(--bg);}
.save-btn{background:var(--accent);color:#000;border:none;font-family:'Space Mono',monospace;font-size:.7rem;font-weight:700;padding:8px 22px;border-radius:4px;cursor:pointer;letter-spacing:.08em;text-transform:uppercase;transition:all .2s;}
.save-btn:hover{box-shadow:0 0 16px rgba(0,212,255,.3);}
.print-btn{background:none;border:1px solid rgba(168,85,247,.3);color:#a855f7;font-family:'Space Mono',monospace;font-size:.62rem;padding:8px 16px;border-radius:4px;cursor:pointer;letter-spacing:.08em;transition:all .2s;}
.print-btn:hover{background:rgba(168,85,247,.08);}
.discard-btn{background:none;border:1px solid var(--border);color:var(--text-dim);font-family:'Space Mono',monospace;font-size:.62rem;padding:8px 14px;border-radius:4px;cursor:pointer;transition:all .2s;}
.discard-btn:hover{border-color:var(--text-dim);}

/* CODE BLOCK INSERT MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:500;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal-box{background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:24px;width:560px;max-width:90vw;}
.modal-box h3{font-family:'Syne',sans-serif;font-weight:700;font-size:.9rem;margin-bottom:16px;color:var(--text);}
.modal-box textarea{width:100%;background:var(--surface2);border:1px solid var(--border);color:#a8d8ea;font-family:'Space Mono',monospace;font-size:.72rem;padding:12px;border-radius:4px;outline:none;min-height:140px;resize:vertical;line-height:1.6;}
.modal-box select{background:var(--surface2);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:.65rem;padding:6px 10px;border-radius:4px;outline:none;margin-bottom:12px;width:100%;}
.modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:14px;}
.modal-confirm{background:var(--accent);color:#000;border:none;font-family:'Space Mono',monospace;font-size:.65rem;font-weight:700;padding:7px 18px;border-radius:4px;cursor:pointer;}
.modal-close-btn{background:none;border:1px solid var(--border);color:var(--text-dim);font-family:'Space Mono',monospace;font-size:.65rem;padding:7px 14px;border-radius:4px;cursor:pointer;}

/* RESOURCES PANEL */
#resources-panel{width:320px;border-left:1px solid var(--border);background:var(--surface);flex-shrink:0;display:none;flex-direction:column;}
#resources-panel.open{display:flex;}
.res-header{display:flex;align-items:center;gap:8px;padding:14px 16px;border-bottom:1px solid var(--border);flex-shrink:0;}
.res-title{font-family:'Syne',sans-serif;font-weight:700;font-size:.8rem;color:var(--text);}
.res-close{margin-left:auto;background:none;border:none;color:var(--text-dim);cursor:pointer;font-size:1rem;padding:2px 6px;}
.res-add{padding:12px 16px;border-bottom:1px solid var(--border);flex-shrink:0;}
.res-input{width:100%;background:var(--surface2);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:.65rem;padding:7px 10px;border-radius:4px;outline:none;margin-bottom:6px;}
.res-input::placeholder{color:var(--text-muted);}
.res-input:focus{border-color:rgba(168,85,247,.4);}
.res-add-btn{width:100%;background:rgba(168,85,247,.1);border:1px solid rgba(168,85,247,.3);color:#a855f7;font-family:'Space Mono',monospace;font-size:.6rem;padding:6px;border-radius:4px;cursor:pointer;text-transform:uppercase;letter-spacing:.08em;transition:all .2s;}
.res-add-btn:hover{background:rgba(168,85,247,.2);}
.res-list{flex:1;overflow-y:auto;padding:8px 0;}
.res-list::-webkit-scrollbar{width:3px;}
.res-list::-webkit-scrollbar-thumb{background:var(--border);}
.res-item{padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.03);position:relative;}
.res-item:hover{background:rgba(255,255,255,.02);}
.res-item-title{font-size:.72rem;color:#e6edf3;margin-bottom:4px;}
.res-item-url{font-size:.6rem;color:var(--accent);word-break:break-all;}
.res-item-url a{color:inherit;text-decoration:none;}
.res-item-url a:hover{text-decoration:underline;}
.res-item-desc{font-size:.6rem;color:var(--text-dim);margin-top:4px;line-height:1.5;}
.res-del{position:absolute;top:10px;right:10px;background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:.6rem;opacity:0;transition:opacity .2s;}
.res-item:hover .res-del{opacity:1;}
.res-del:hover{color:#ff4d4d;}

#toast{position:fixed;bottom:24px;right:24px;background:var(--surface2);border:1px solid var(--border);color:var(--text);font-size:.7rem;padding:10px 18px;border-radius:6px;z-index:9999;opacity:0;transform:translateY(8px);transition:all .2s;pointer-events:none;letter-spacing:.05em;}
#toast.show{opacity:1;transform:translateY(0);}
#toast.err{border-color:rgba(255,77,77,.4);color:#ff6b6b;}

@media print {
  header,.editor-header,.editor-tags,.toolbar,.editor-footer,.view-only-banner{display:none!important;}
  body{overflow:visible;background:#fff;color:#000;}
  #editor-area{padding:0;overflow:visible;}
  #editor-area pre{background:#f4f4f4;color:#333;}
  .editor-body{overflow:visible;}
  #editor-view,#content,#app,html,body{overflow:visible!important;height:auto!important;}
}
</style>
</head>
<body>
<div id="app">
  <header>
    <span class="site-title">Cyber Pulse</span>
    <span class="site-sub">// writeups</span>
    <div class="nav-links">
      <?php if(!$isViewOnly): ?>
      <a href="index.php" class="nav-link">Journal</a>
      <a href="map.php"   class="nav-link">Intel Map</a>
      <a href="writeups.php" class="nav-link active">Writeups</a>
      <span class="operator-tag">OPR: <span><?= $operator ?></span></span>
      <button class="logout-btn" onclick="doLogout()">Terminate</button>
      <?php else: ?>
      <a href="index.php" class="nav-link">Journal</a>
      <a href="map.php"   class="nav-link">Intel Map</a>
      <a href="writeups.php" class="nav-link active">Writeups</a>
      <span class="operator-tag" style="color:#eab308">👁 VIEW ONLY MODE</span>
      <button class="logout-btn" onclick="exitViewMode()">Exit</button>
      <?php endif; ?>
    </div>
  </header>
  <?php if($isViewOnly): ?>
  <div class="view-only-banner">⚠ VIEW ONLY MODE — You are browsing as a guest. No modifications allowed.</div>
  <?php endif; ?>

  <div id="content">
    <!-- CATEGORY VIEW -->
    <div id="cat-view" class="view active" style="display:flex;flex-direction:column;">
      <div style="padding:24px 32px 0;flex-shrink:0;">
        <div class="cat-header">// Select a Category</div>
      </div>
      <div id="cat-grid-wrap" style="padding:0 32px 32px;overflow-y:auto;flex:1;">
        <div class="cat-grid" id="cat-grid"></div>
      </div>
    </div>

    <!-- LIST VIEW -->
    <div id="list-view" class="view">
      <div class="list-header">
        <button class="back-btn" onclick="showCatView()">← Back</button>
        <span class="list-cat-name" id="list-cat-name"></span>
        <?php if(!$isViewOnly): ?>
        <div class="list-actions">
          <button class="resources-btn" onclick="toggleResources()">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            Resources
          </button>
          <button class="new-writeup-btn" onclick="openEditor()">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            New Writeup
          </button>
        </div>
        <?php else: ?>
        <div class="list-actions">
          <button class="resources-btn" onclick="toggleResources()">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            Resources
          </button>
        </div>
        <?php endif; ?>
      </div>
      <div style="display:flex;flex:1;overflow:hidden;">
        <div id="writeup-grid" class="writeup-grid"></div>
        <div id="resources-panel">
          <div class="res-header">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            <span class="res-title">Resources</span>
            <button class="res-close" onclick="toggleResources()">✕</button>
          </div>
          <?php if(!$isViewOnly): ?>
          <div class="res-add">
            <input class="res-input" id="res-title" placeholder="Resource title..." autocomplete="off">
            <input class="res-input" id="res-url" placeholder="URL (https://...)" autocomplete="off">
            <input class="res-input" id="res-desc" placeholder="Notes (optional)" autocomplete="off">
            <button class="res-add-btn" onclick="addResource()">+ Add Resource</button>
          </div>
          <?php endif; ?>
          <div class="res-list" id="res-list"></div>
        </div>
      </div>
    </div>

    <!-- EDITOR VIEW -->
    <div id="editor-view" class="view">
      <div class="editor-header">
        <button class="back-btn" onclick="closeEditor()">← Back</button>
        <input type="text" class="editor-title-input" id="editor-title" placeholder="Writeup title...">
        <div class="autosave-indicator">
          <div class="autosave-dot" id="autosave-dot"></div>
          <span id="autosave-label">Draft saved</span>
        </div>
      </div>
      <div class="editor-tags">
        <label>Platform:</label>
        <select id="tag-platform">
          <option value="">— Select Platform —</option>
          <option value="ctf">CTFs (Capture the Flag)</option>
          <option value="htb">HackTheBox</option>
          <option value="thm">TryHackMe</option>
          <option value="vulnhub">VulnHub</option>
          <option value="picoctf">picoCTF</option>
          <option value="sans">SANS Holiday Hack Challenge</option>
        </select>
        <label style="margin-left:12px;">Difficulty:</label>
        <select id="tag-difficulty">
          <option value="beginner">Beginner</option>
          <option value="easy">Easy</option>
          <option value="medium" selected>Medium</option>
          <option value="hard">Hard</option>
          <option value="insane">Insane</option>
        </select>
      </div>
      <div class="toolbar">
        <button class="tb-btn" title="Bold" onclick="fmt('bold')"><b>B</b></button>
        <button class="tb-btn" title="Italic" onclick="fmt('italic')"><i>I</i></button>
        <button class="tb-btn" title="Underline" onclick="fmt('underline')"><u>U</u></button>
        <button class="tb-btn" title="Strikethrough" onclick="fmt('strikeThrough')"><s>S</s></button>
        <button class="tb-btn" title="Highlight" onclick="fmt('backColor','#eab30840')">▐</button>
        <div class="tb-sep"></div>
        <button class="tb-btn" onclick="fmtBlock('h1')">H1</button>
        <button class="tb-btn" onclick="fmtBlock('h2')">H2</button>
        <button class="tb-btn" onclick="fmtBlock('h3')">H3</button>
        <button class="tb-btn" onclick="fmtBlock('p')">¶</button>
        <div class="tb-sep"></div>
        <button class="tb-btn" onclick="fmt('insertUnorderedList')">• List</button>
        <button class="tb-btn" onclick="fmt('insertOrderedList')">1. List</button>
        <button class="tb-btn" onclick="insertBlockquote()">❝</button>
        <button class="tb-btn" onclick="insertHR()">─ HR</button>
        <div class="tb-sep"></div>
        <button class="tb-btn" onclick="insertLink()">🔗 Link</button>
        <button class="tb-btn" onclick="openCodeModal()">{'&lt;/&gt;'} Code</button>
        <label class="tb-btn" title="Insert Image" style="cursor:pointer;display:flex;align-items:center;gap:4px;">
          🖼 Image
          <input type="file" class="tb-img-input" id="img-upload" accept="image/*" onchange="uploadImage(this)">
        </label>
      </div>
      <div class="editor-body">
        <div id="editor-area" contenteditable="true" spellcheck="true"
             data-placeholder="Start writing your writeup... Paste images directly or use the toolbar."></div>
      </div>
      <div class="editor-footer">
        <?php if(!$isViewOnly): ?>
        <button class="save-btn" onclick="saveWriteup()">Save Writeup</button>
        <?php endif; ?>
        <button class="print-btn" onclick="window.print()">🖨 Print to PDF</button>
        <button class="discard-btn" onclick="closeEditor()">Cancel</button>
      </div>
    </div>
  </div>
</div>

<!-- Code Block Modal -->
<div class="modal-overlay" id="code-modal">
  <div class="modal-box">
    <h3>// Insert Code Block</h3>
    <select id="code-lang">
      <option value="">Plain text / Terminal</option>
      <option value="python">Python</option>
      <option value="javascript">JavaScript</option>
      <option value="bash">Bash / Shell</option>
      <option value="c">C / C++</option>
      <option value="php">PHP</option>
      <option value="sql">SQL</option>
      <option value="java">Java</option>
      <option value="assembly">Assembly</option>
      <option value="hexdump">Hex Dump</option>
      <option value="yaml">YAML / Config</option>
    </select>
    <textarea id="code-input" placeholder="Paste or type your code here..."></textarea>
    <div class="modal-actions">
      <button class="modal-close-btn" onclick="closeCodeModal()">Cancel</button>
      <button class="modal-confirm" onclick="insertCode()">Insert</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const IS_VIEW_ONLY = <?= $isViewOnly ? 'true' : 'false' ?>;

const CATEGORIES = [
  { id:'web',   name:'Web Exploitation',                    icon:'🌐', cls:'cat-web',   desc:'SQL injection, XSS, SSRF, authentication bypass and more.' },
  { id:'re',    name:'Reverse Engineering',                 icon:'⚙️', cls:'cat-re',    desc:'Binary analysis, decompiling, obfuscation and crackmes.' },
  { id:'pwn',   name:'Binary Exploitation (Pwn)',           icon:'💥', cls:'cat-pwn',   desc:'Buffer overflows, ROP chains, heap exploitation.' },
  { id:'for',   name:'Forensics',                           icon:'🔍', cls:'cat-for',   desc:'Memory dumps, PCAP analysis, steganography, log analysis.' },
  { id:'cry',   name:'Cryptography',                        icon:'🔐', cls:'cat-cry',   desc:'Classical ciphers, modern crypto, hash attacks.' },
  { id:'osint', name:'OSINT (Open Source Intelligence)',    icon:'🌍', cls:'cat-osint', desc:'Geolocation, SOCMINT, public records and digital footprinting.' },
];

const PLATFORM_LABELS = {
  '':'Unknown','ctf':'CTF','htb':'HackTheBox','thm':'TryHackMe',
  'vulnhub':'VulnHub','picoctf':'picoCTF','sans':'SANS Holiday Hack'
};

let currentCat = null, currentWriteupId = null, autosaveTimer = null;
let counts = {};

function toast(msg, err=false) {
  const el = document.getElementById('toast');
  el.textContent = msg; el.className = 'show' + (err?' err':'');
  setTimeout(() => el.className='', 2800);
}
function escHtml(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function fmtDate(s) { return new Date(s).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}); }
async function doLogout() { await fetch('api.php?r=logout',{method:'POST'}); window.location='login.php'; }
function exitViewMode() { window.location='login.php'; }

// ── CATEGORY VIEW ──────────────────────────────────────────────────────
async function loadCounts() {
  try {
    const res = await fetch('api.php?r=writeups');
    const all = await res.json();
    counts = {};
    all.forEach(w => { counts[w.category] = (counts[w.category]||0)+1; });
  } catch(e) {}
  renderCategories();
}

function renderCategories() {
  const grid = document.getElementById('cat-grid');
  grid.innerHTML = CATEGORIES.map(c => `
    <div class="cat-card ${c.cls}" onclick="showListView('${c.id}')">
      <div class="cat-icon">${c.icon}</div>
      <div class="cat-name">${c.name}</div>
      <div class="cat-desc">${c.desc}</div>
      <div class="cat-count">${counts[c.id]||0} writeup${(counts[c.id]||0)!==1?'s':''}</div>
    </div>
  `).join('');
}

// ── LIST VIEW ──────────────────────────────────────────────────────────
function showCatView() {
  setView('cat-view');
  loadCounts();
}

async function showListView(catId) {
  currentCat = catId;
  const cat = CATEGORIES.find(c=>c.id===catId);
  document.getElementById('list-cat-name').textContent = cat.name;
  document.getElementById('list-cat-name').style.color = getCatColor(catId);
  setView('list-view');
  await loadWriteups();
  await loadResources();
}

function getCatColor(id) {
  const m = {'web':'#f97316','re':'#a855f7','pwn':'#ef4444','for':'#22c55e','cry':'#eab308','osint':'#06b6d4'};
  return m[id]||'var(--accent)';
}

async function loadWriteups() {
  try {
    const res = await fetch('api.php?r=writeups&category='+currentCat);
    const list = await res.json();
    const grid = document.getElementById('writeup-grid');
    if (!list.length) {
      grid.innerHTML = `<div class="empty-list">No writeups yet for this category.<br>${IS_VIEW_ONLY ? '' : 'Click "New Writeup" to add one.'}</div>`;
      return;
    }
    grid.innerHTML = list.map(w => `
      <div class="writeup-card" onclick="openWriteup(${w.id})">
        <div class="wc-title">${escHtml(w.title)}</div>
        <div class="wc-meta">
          ${w.platform_tag ? `<span class="tag-platform">${escHtml(PLATFORM_LABELS[w.platform_tag]||w.platform_tag)}</span>` : ''}
          <span class="tag-diff ${w.difficulty}">${w.difficulty||'medium'}</span>
          <span class="wc-date">${fmtDate(w.updated_at||w.created_at)}</span>
        </div>
        ${IS_VIEW_ONLY ? '' : `<button class="wc-delete" onclick="event.stopPropagation();deleteWriteup(${w.id})">✕</button>`}
      </div>
    `).join('');
  } catch(e) { toast('Failed to load writeups.',true); }
}

async function deleteWriteup(id) {
  if (!confirm('Delete this writeup?')) return;
  await fetch('api.php?r=writeups&id='+id, {method:'DELETE'});
  toast('Writeup deleted.');
  loadWriteups();
  loadCounts();
}

async function openWriteup(id) {
  try {
    const res = await fetch('api.php?r=writeups&id='+id);
    const w = await res.json();
    currentWriteupId = id;
    document.getElementById('editor-title').value = w.title;
    document.getElementById('tag-platform').value = w.platform_tag||'';
    document.getElementById('tag-difficulty').value = w.difficulty||'medium';
    document.getElementById('editor-area').innerHTML = w.content||'';
    if(IS_VIEW_ONLY) document.getElementById('editor-area').contentEditable='false';
    setView('editor-view');
    setAutosaveState('saved');
  } catch(e) { toast('Failed to open writeup.',true); }
}

// ── EDITOR ─────────────────────────────────────────────────────────────
function openEditor() {
  if(IS_VIEW_ONLY) return;
  currentWriteupId = null;
  document.getElementById('editor-title').value = '';
  document.getElementById('tag-platform').value = '';
  document.getElementById('tag-difficulty').value = 'medium';
  document.getElementById('editor-area').innerHTML = '';
  setView('editor-view');
  loadDraft();
  document.getElementById('editor-title').focus();
}

function closeEditor() {
  if(currentCat) setView('list-view');
  else showCatView();
}

function setView(id) {
  document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
  document.getElementById(id).classList.add('active');
}

// Toolbar commands
function fmt(cmd, val=null) { document.execCommand(cmd, false, val); document.getElementById('editor-area').focus(); triggerAutosave(); }
function fmtBlock(tag) { document.execCommand('formatBlock', false, tag); document.getElementById('editor-area').focus(); triggerAutosave(); }
function insertBlockquote() {
  document.execCommand('formatBlock', false, 'blockquote');
  document.getElementById('editor-area').focus();
  triggerAutosave();
}
function insertHR() {
  document.execCommand('insertHTML', false, '<hr>');
  document.getElementById('editor-area').focus();
  triggerAutosave();
}
function insertLink() {
  const url = prompt('Enter URL:');
  if (url) { document.execCommand('createLink', false, url); triggerAutosave(); }
}

// Paste image support
document.addEventListener('DOMContentLoaded', () => {
  const area = document.getElementById('editor-area');
  area.addEventListener('paste', async e => {
    if(IS_VIEW_ONLY) return;
    const items = e.clipboardData.items;
    for (const item of items) {
      if (item.type.startsWith('image/')) {
        e.preventDefault();
        const file = item.getAsFile();
        await uploadImageFile(file);
        break;
      }
    }
  });
  area.addEventListener('input', triggerAutosave);

  // Load counts on start
  loadCounts();
});

async function uploadImage(input) {
  if (!input.files[0]) return;
  await uploadImageFile(input.files[0]);
  input.value = '';
}

async function uploadImageFile(file) {
  toast('Uploading image...');
  const fd = new FormData();
  fd.append('image', file);
  try {
    const res = await fetch('api.php?r=upload_image', { method:'POST', body: fd });
    const data = await res.json();
    if (data.url) {
      document.execCommand('insertHTML', false, `<img src="${data.url}" alt="writeup image">`);
      toast('Image inserted.');
      triggerAutosave();
    } else { toast('Image upload failed.', true); }
  } catch(e) { toast('Upload error.', true); }
}

// Code modal
function openCodeModal() { document.getElementById('code-modal').classList.add('open'); document.getElementById('code-input').focus(); }
function closeCodeModal() { document.getElementById('code-modal').classList.remove('open'); document.getElementById('code-input').value=''; }
function insertCode() {
  const code = document.getElementById('code-input').value;
  const lang = document.getElementById('code-lang').value;
  if (!code.trim()) { closeCodeModal(); return; }
  const escaped = escHtml(code);
  const langAttr = lang ? ` data-lang="${lang}"` : '';
  document.execCommand('insertHTML', false, `<pre${langAttr}><code>${escaped}</code></pre><p><br></p>`);
  closeCodeModal();
  triggerAutosave();
}

// Autosave to localStorage
function triggerAutosave() {
  if(IS_VIEW_ONLY) return;
  setAutosaveState('saving');
  clearTimeout(autosaveTimer);
  autosaveTimer = setTimeout(saveDraft, 1200);
}

function setAutosaveState(state) {
  const dot = document.getElementById('autosave-dot');
  const lbl = document.getElementById('autosave-label');
  dot.className = 'autosave-dot ' + state;
  lbl.textContent = state==='saving' ? 'Saving...' : state==='saved' ? 'Draft saved' : '';
}

function saveDraft() {
  const key = 'cp_draft_' + (currentCat||'general');
  const data = {
    title: document.getElementById('editor-title').value,
    content: document.getElementById('editor-area').innerHTML,
    platform: document.getElementById('tag-platform').value,
    difficulty: document.getElementById('tag-difficulty').value,
    id: currentWriteupId
  };
  try { localStorage.setItem(key, JSON.stringify(data)); } catch(e){}
  setAutosaveState('saved');
}

function loadDraft() {
  if(IS_VIEW_ONLY) return;
  const key = 'cp_draft_' + (currentCat||'general');
  try {
    const raw = localStorage.getItem(key);
    if (!raw) return;
    const data = JSON.parse(raw);
    if (data.title) document.getElementById('editor-title').value = data.title;
    if (data.content) document.getElementById('editor-area').innerHTML = data.content;
    if (data.platform) document.getElementById('tag-platform').value = data.platform;
    if (data.difficulty) document.getElementById('tag-difficulty').value = data.difficulty;
    if (data.id) currentWriteupId = data.id;
    if (data.title||data.content) setAutosaveState('saved');
  } catch(e){}
}

function clearDraft() {
  const key = 'cp_draft_' + (currentCat||'general');
  try { localStorage.removeItem(key); } catch(e){}
}

async function saveWriteup() {
  if(IS_VIEW_ONLY) return;
  const title   = document.getElementById('editor-title').value.trim();
  const content = document.getElementById('editor-area').innerHTML.trim();
  const platform= document.getElementById('tag-platform').value;
  const diff    = document.getElementById('tag-difficulty').value;
  if (!title) { toast('Enter a title.', true); return; }
  try {
    const body = { title, content, category: currentCat, platform_tag: platform, difficulty: diff };
    if (currentWriteupId) body.id = currentWriteupId;
    const res = await fetch('api.php?r=writeups', {
      method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)
    });
    const w = await res.json();
    currentWriteupId = w.id;
    clearDraft();
    toast('Writeup saved!');
    loadCounts();
  } catch(e) { toast('Save failed.',true); }
}

// ── RESOURCES ─────────────────────────────────────────────────────────
function toggleResources() {
  document.getElementById('resources-panel').classList.toggle('open');
}

async function loadResources() {
  try {
    const res = await fetch('api.php?r=resources&category='+currentCat);
    const list = await res.json();
    renderResources(list);
  } catch(e) {}
}

function renderResources(list) {
  const el = document.getElementById('res-list');
  if (!list.length) {
    el.innerHTML = '<div style="padding:20px 16px;font-size:.62rem;color:var(--text-muted);text-align:center;">No resources saved yet.</div>';
    return;
  }
  el.innerHTML = list.map(r => `
    <div class="res-item">
      <div class="res-item-title">${escHtml(r.title)}</div>
      <div class="res-item-url"><a href="${escHtml(r.url)}" target="_blank">${escHtml(r.url)}</a></div>
      ${r.description ? `<div class="res-item-desc">${escHtml(r.description)}</div>` : ''}
      ${IS_VIEW_ONLY ? '' : `<button class="res-del" onclick="deleteResource(${r.id})">✕</button>`}
    </div>
  `).join('');
}

async function addResource() {
  if(IS_VIEW_ONLY) return;
  const title = document.getElementById('res-title').value.trim();
  const url   = document.getElementById('res-url').value.trim();
  const desc  = document.getElementById('res-desc').value.trim();
  if (!title||!url) { toast('Title and URL required.',true); return; }
  try {
    const res = await fetch('api.php?r=resources', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({category:currentCat, title, url, description:desc})
    });
    const r = await res.json();
    document.getElementById('res-title').value='';
    document.getElementById('res-url').value='';
    document.getElementById('res-desc').value='';
    toast('Resource saved.');
    loadResources();
  } catch(e) { toast('Failed.',true); }
}

async function deleteResource(id) {
  if(IS_VIEW_ONLY) return;
  await fetch('api.php?r=resources&id='+id, {method:'DELETE'});
  toast('Resource deleted.');
  loadResources();
}

// Close code modal on Escape
document.addEventListener('keydown', e => {
  if (e.key==='Escape') {
    document.getElementById('code-modal').classList.remove('open');
    if (document.getElementById('code-input').value) document.getElementById('code-input').value='';
  }
});
</script>
</body>
</html>
