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
<title>Cyber Pulse — Intel Map</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
:root{
  --bg:#080c10;--surface:#0d1117;--surface2:#131920;--border:#1e2d3d;
  --text:#c9d1d9;--text-dim:#4a5568;--text-muted:#2d3748;
  --accent:#00d4ff;--red:#ff4d4d;--green:rgba(80,220,120,.9);
  --sidebar-w:300px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;background:var(--bg);color:var(--text);font-family:'Space Mono',monospace;overflow:hidden;}
body::before{content:'';position:fixed;inset:0;background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,.025) 2px,rgba(0,0,0,.025) 4px);pointer-events:none;z-index:1000;}
#app{display:flex;flex-direction:column;height:100vh;}
#main{display:flex;flex:1;overflow:hidden;position:relative;}

/* HEADER */
header{padding:12px 24px;display:flex;align-items:center;gap:14px;flex-shrink:0;border-bottom:1px solid var(--border);background:var(--bg);z-index:100;}
.site-title{font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;letter-spacing:.15em;color:var(--accent);text-transform:uppercase;text-shadow:0 0 20px rgba(0,212,255,.2);}
.site-sub{font-size:.6rem;color:var(--text-dim);letter-spacing:.1em;}
.nav-links{margin-left:auto;display:flex;align-items:center;gap:12px;}
.nav-link{font-size:.6rem;letter-spacing:.12em;text-transform:uppercase;color:var(--text-dim);text-decoration:none;padding:4px 10px;border:1px solid transparent;border-radius:3px;transition:all .2s;}
.nav-link:hover{border-color:var(--border);color:var(--text);}
.nav-link.active{border-color:rgba(80,220,120,.3);color:var(--green);}
.operator-tag{font-size:.58rem;color:var(--text-dim);}
.operator-tag span{color:var(--accent);}
.logout-btn{background:none;border:1px solid rgba(255,77,77,.2);color:rgba(255,77,77,.6);font-family:'Space Mono',monospace;font-size:.55rem;letter-spacing:.1em;padding:4px 10px;border-radius:3px;cursor:pointer;text-transform:uppercase;transition:all .2s;}
.logout-btn:hover{border-color:rgba(255,77,77,.5);color:var(--red);}

<?php if($isViewOnly): ?>
.view-only-banner{background:rgba(234,179,8,.08);border-bottom:1px solid rgba(234,179,8,.25);padding:5px 24px;font-size:.58rem;letter-spacing:.12em;color:#eab308;text-align:center;flex-shrink:0;}
<?php endif; ?>

/* SIDEBAR */
#sidebar{width:var(--sidebar-w);background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0;z-index:10;overflow:hidden;}
.sidebar-section{padding:12px 14px;border-bottom:1px solid var(--border);flex-shrink:0;}
.sidebar-label{font-size:.52rem;letter-spacing:.18em;color:var(--text-dim);text-transform:uppercase;margin-bottom:8px;}

/* Search */
.search-wrap{display:flex;align-items:center;gap:8px;background:var(--surface2);border:1px solid var(--border);border-radius:4px;padding:6px 10px;}
.search-wrap svg{width:12px;height:12px;stroke:var(--text-dim);flex-shrink:0;}
#search-input{background:transparent;border:none;color:var(--text);font-family:'Space Mono',monospace;font-size:.68rem;flex:1;outline:none;}
#search-input::placeholder{color:var(--text-muted);}

/* EVENT LIST */
#event-list-wrap{flex:1;overflow-y:auto;display:flex;flex-direction:column;}
#event-list-wrap::-webkit-scrollbar{width:3px;}
#event-list-wrap::-webkit-scrollbar-thumb{background:var(--border);}
.events-header{padding:10px 14px 4px;display:flex;align-items:center;gap:6px;flex-shrink:0;}
.events-header-label{font-size:.52rem;letter-spacing:.18em;color:var(--text-dim);text-transform:uppercase;flex:1;}
.new-event-btn{background:rgba(0,212,255,.08);border:1px solid rgba(0,212,255,.25);color:var(--accent);font-family:'Space Mono',monospace;font-size:.55rem;padding:4px 10px;border-radius:3px;cursor:pointer;letter-spacing:.08em;text-transform:uppercase;transition:all .2s;}
.new-event-btn:hover{background:rgba(0,212,255,.15);}

.event-item{padding:10px 14px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,.03);transition:background .15s;position:relative;}
.event-item:hover{background:rgba(255,255,255,.03);}
.event-item.active{background:rgba(0,212,255,.05);border-left:2px solid var(--accent);}
.event-item-top{display:flex;align-items:center;gap:6px;margin-bottom:4px;}
.event-item-name{font-size:.7rem;color:var(--text);flex:1;font-family:'Syne',sans-serif;font-weight:600;}
.event-tag{font-size:.5rem;padding:2px 7px;border-radius:999px;letter-spacing:.08em;font-weight:700;display:flex;align-items:center;gap:4px;}
.tag-live{background:rgba(80,220,120,.12);color:#50dc78;border:1px solid rgba(80,220,120,.3);}
.tag-investigation{background:rgba(0,212,255,.1);color:var(--accent);border:1px solid rgba(0,212,255,.25);}
.tag-concluded{background:rgba(100,116,139,.1);color:#94a3b8;border:1px solid rgba(100,116,139,.25);}
.tag-monitoring{background:rgba(234,179,8,.1);color:#eab308;border:1px solid rgba(234,179,8,.25);}
.tag-archived{background:rgba(100,100,100,.1);color:#666;border:1px solid rgba(100,100,100,.2);}
.live-blip{width:7px;height:7px;border-radius:50%;background:#50dc78;display:inline-block;animation:live-pulse 1.2s ease-in-out infinite;flex-shrink:0;}
@keyframes live-pulse{0%,100%{box-shadow:0 0 0 0 rgba(80,220,120,.7);opacity:1;}70%{box-shadow:0 0 0 6px rgba(80,220,120,0);opacity:.8;}}

.event-item-sub{font-size:.58rem;color:var(--text-dim);display:flex;align-items:center;gap:6px;}
.event-pin-count{font-size:.5rem;background:var(--surface2);padding:1px 6px;border-radius:999px;color:var(--text-muted);}
.event-del{position:absolute;top:10px;right:10px;background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:.58rem;opacity:0;padding:2px 5px;transition:opacity .2s;}
.event-item:hover .event-del{opacity:1;}
.event-del:hover{color:var(--red);}

/* PIN TIMELINE (inside sidebar, shown when event selected) */
#pin-timeline{flex-shrink:0;border-top:1px solid var(--border);display:none;flex-direction:column;max-height:220px;}
#pin-timeline.open{display:flex;}
.pt-header{display:flex;align-items:center;gap:8px;padding:8px 14px;border-bottom:1px solid var(--border);flex-shrink:0;}
.pt-title{font-size:.55rem;letter-spacing:.15em;color:var(--text-dim);text-transform:uppercase;flex:1;}
.play-btn{display:flex;align-items:center;gap:5px;background:rgba(0,212,255,.08);border:1px solid rgba(0,212,255,.25);color:var(--accent);font-family:'Space Mono',monospace;font-size:.55rem;letter-spacing:.08em;padding:4px 12px;border-radius:4px;cursor:pointer;text-transform:uppercase;transition:all .2s;}
.play-btn:hover{background:rgba(0,212,255,.15);}
.play-btn.stop{border-color:rgba(255,77,77,.3);color:var(--red);background:rgba(255,77,77,.05);}
.play-btn svg{width:9px;height:9px;fill:currentColor;}
#pin-list{overflow-y:auto;flex:1;}
#pin-list::-webkit-scrollbar{width:3px;}
#pin-list::-webkit-scrollbar-thumb{background:var(--border);}
.pin-item{padding:8px 14px;border-bottom:1px solid rgba(255,255,255,.03);cursor:pointer;transition:background .15s;display:flex;align-items:flex-start;gap:8px;}
.pin-item:hover{background:rgba(255,255,255,.03);}
.pin-item.active{background:rgba(0,212,255,.06);}
.pin-num{font-size:.55rem;color:var(--accent);min-width:18px;padding-top:1px;}
.pin-info{flex:1;}
.pin-title{font-size:.65rem;color:var(--text);margin-bottom:2px;}
.pin-date-time{font-size:.55rem;color:var(--text-dim);}

/* MAP */
#map-wrap{flex:1;position:relative;}
#map{width:100%;height:100%;}
.leaflet-container{background:#050a0f!important;}
.leaflet-tile{filter:brightness(.7) saturate(.5) hue-rotate(180deg);}
.leaflet-control-zoom a{background:var(--surface2)!important;color:var(--text-dim)!important;border-color:var(--border)!important;}
.leaflet-control-zoom a:hover{background:var(--surface)!important;color:var(--text)!important;}
.leaflet-control-attribution{background:rgba(8,12,16,.8)!important;color:var(--text-muted)!important;font-size:.5rem!important;}
.leaflet-control-attribution a{color:var(--text-dim)!important;}
.leaflet-popup-content-wrapper{background:var(--surface2)!important;border:1px solid var(--border)!important;color:var(--text)!important;border-radius:6px!important;box-shadow:0 4px 20px rgba(0,0,0,.6)!important;}
.leaflet-popup-tip{background:var(--surface2)!important;}
.leaflet-popup-close-button{color:var(--text-dim)!important;}

/* PIN MODAL */
#pin-modal{display:none;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:22px;width:320px;z-index:500;box-shadow:0 8px 40px rgba(0,0,0,.6);animation:modalIn .2s ease;}
@keyframes modalIn{from{opacity:0;transform:translate(-50%,-55%);}to{opacity:1;transform:translate(-50%,-50%);}}
#pin-modal.open{display:block;}
.modal-title{font-family:'Syne',sans-serif;font-weight:700;font-size:.85rem;margin-bottom:14px;color:var(--text);}
.modal-coords{font-size:.58rem;color:var(--text-dim);margin-bottom:14px;letter-spacing:.08em;}
.modal-field{margin-bottom:10px;}
.modal-field label{display:block;font-size:.55rem;letter-spacing:.15em;color:var(--text-dim);text-transform:uppercase;margin-bottom:4px;}
.modal-field input,.modal-field textarea,.modal-field select{width:100%;background:var(--surface2);border:1px solid var(--border);color:var(--text);font-family:'Space Mono',monospace;font-size:.7rem;padding:7px 10px;border-radius:4px;outline:none;transition:border-color .2s;}
.modal-field input:focus,.modal-field textarea:focus,.modal-field select:focus{border-color:rgba(0,212,255,.4);}
.modal-field textarea{resize:vertical;min-height:52px;}
.modal-row{display:flex;gap:8px;}
.modal-row .modal-field{flex:1;}
.modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:14px;}
.modal-cancel{background:transparent;border:1px solid var(--border);color:var(--text-dim);font-family:'Space Mono',monospace;font-size:.6rem;padding:6px 12px;border-radius:4px;cursor:pointer;transition:border-color .2s;}
.modal-cancel:hover{border-color:var(--text-dim);}
.modal-save{background:var(--accent);color:#000;border:none;font-family:'Space Mono',monospace;font-size:.6rem;font-weight:700;padding:6px 16px;border-radius:4px;cursor:pointer;letter-spacing:.08em;transition:box-shadow .2s;}
.modal-save:hover{box-shadow:0 0 14px rgba(0,212,255,.3);}

/* NEW EVENT MODAL */
#new-event-modal{display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:24px;width:360px;z-index:600;box-shadow:0 8px 40px rgba(0,0,0,.7);animation:modalIn .2s ease;}
#new-event-modal.open{display:block;}

/* MAP OVERLAYS */
#map-hint{position:absolute;bottom:16px;right:16px;z-index:100;background:rgba(8,12,16,.85);border:1px solid var(--border);padding:8px 14px;border-radius:4px;font-size:.56rem;color:var(--text-dim);letter-spacing:.08em;pointer-events:none;}
#play-indicator{position:absolute;top:14px;left:50%;transform:translateX(-50%);background:rgba(8,12,16,.9);border:1px solid rgba(0,212,255,.3);padding:8px 20px;border-radius:999px;font-size:.63rem;color:var(--accent);letter-spacing:.1em;z-index:200;display:none;box-shadow:0 0 20px rgba(0,212,255,.15);}
#play-indicator.show{display:block;}

.pulse-ring{border-radius:50%;animation:pulse-anim 2s ease-out infinite;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);pointer-events:none;}
@keyframes pulse-anim{0%{width:10px;height:10px;opacity:.8;}100%{width:36px;height:36px;opacity:0;}}

#toast{position:fixed;bottom:24px;right:24px;background:var(--surface2);border:1px solid var(--border);color:var(--text);font-size:.7rem;padding:10px 18px;border-radius:6px;z-index:9999;opacity:0;transform:translateY(8px);transition:all .2s;pointer-events:none;}
#toast.show{opacity:1;transform:translateY(0);}
#toast.err{border-color:rgba(255,77,77,.4);color:#ff6b6b;}
</style>
</head>
<body>
<div id="app">
  <header>
    <span class="site-title">Cyber Pulse</span>
    <span class="site-sub">// intel map</span>
    <div class="nav-links">
      <?php if(!$isViewOnly): ?>
      <a href="index.php"    class="nav-link">Journal</a>
      <a href="map.php"      class="nav-link active">Intel Map</a>
      <a href="writeups.php" class="nav-link">Writeups</a>
      <span class="operator-tag">OPR: <span><?= $operator ?></span></span>
      <button class="logout-btn" onclick="doLogout()">Terminate</button>
      <?php else: ?>
      <a href="index.php"    class="nav-link">Journal</a>
      <a href="map.php"      class="nav-link active">Intel Map</a>
      <a href="writeups.php" class="nav-link">Writeups</a>
      <span class="operator-tag" style="color:#eab308">👁 VIEW ONLY</span>
      <button class="logout-btn" onclick="window.location='login.php'">Exit</button>
      <?php endif; ?>
    </div>
  </header>
  <?php if($isViewOnly): ?>
  <div class="view-only-banner">⚠ VIEW ONLY MODE — Read-only access. No modifications permitted.</div>
  <?php endif; ?>

  <div id="main">
    <div id="sidebar">
      <!-- Search -->
      <div class="sidebar-section">
        <div class="sidebar-label">Search Pins</div>
        <div class="search-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
          <input id="search-input" type="text" placeholder="search events..." autocomplete="off">
        </div>
      </div>

      <!-- Event list -->
      <div class="events-header">
        <span class="events-header-label">Events</span>
        <?php if(!$isViewOnly): ?>
        <button class="new-event-btn" onclick="openNewEventModal()">+ New</button>
        <?php endif; ?>
      </div>
      <div id="event-list-wrap">
        <div id="event-list"></div>
      </div>

      <!-- Pin Timeline (below event list) -->
      <div id="pin-timeline">
        <div class="pt-header">
          <span class="pt-title">Timeline</span>
          <button class="play-btn" id="play-btn" onclick="togglePlay()">
            <svg viewBox="0 0 24 24"><polygon points="5,3 19,12 5,21"/></svg>
            Play
          </button>
        </div>
        <div id="pin-list"></div>
      </div>
    </div>

    <!-- MAP -->
    <div id="map-wrap">
      <div id="map"></div>
      <div id="map-hint"><?= $isViewOnly ? 'View-only mode' : 'Select an event then click to place a pin' ?></div>
      <div id="play-indicator"></div>

      <?php if(!$isViewOnly): ?>
      <!-- Pin placement modal -->
      <div id="pin-modal">
        <div class="modal-title">// NEW INTEL PIN</div>
        <div class="modal-coords" id="modal-coords"></div>
        <div class="modal-field">
          <label>Title</label>
          <input type="text" id="pin-title" placeholder="What happened here?" autocomplete="off">
        </div>
        <div class="modal-field">
          <label>Description</label>
          <textarea id="pin-desc" placeholder="Brief details..."></textarea>
        </div>
        <div class="modal-row">
          <div class="modal-field">
            <label>Date</label>
            <input type="date" id="pin-date">
          </div>
          <div class="modal-field">
            <label>Time (optional)</label>
            <input type="time" id="pin-time">
          </div>
        </div>
        <div class="modal-actions">
          <button class="modal-cancel" onclick="closeModal()">Cancel</button>
          <button class="modal-save"   onclick="savePin()">Place Pin</button>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if(!$isViewOnly): ?>
<!-- New Event Modal -->
<div id="new-event-modal">
  <div class="modal-title">// NEW EVENT</div>
  <div class="modal-field">
    <label>Event Name</label>
    <input type="text" id="new-event-name" placeholder="e.g. Operation Blackout" autocomplete="off">
  </div>
  <div class="modal-field">
    <label>Status Tag</label>
    <select id="new-event-tag">
      <option value="live">🟢 LIVE — Ongoing</option>
      <option value="investigation" selected>🔵 Investigation</option>
      <option value="monitoring">🟡 Monitoring</option>
      <option value="concluded">⚫ Concluded</option>
      <option value="archived">📁 Archived</option>
    </select>
  </div>
  <div class="modal-field">
    <label>Description (optional)</label>
    <textarea id="new-event-desc" placeholder="Brief overview..." style="min-height:60px;"></textarea>
  </div>
  <div class="modal-actions">
    <button class="modal-cancel" onclick="closeNewEventModal()">Cancel</button>
    <button class="modal-save" onclick="createEvent()">Create Event</button>
  </div>
</div>
<?php endif; ?>

<div id="toast"></div>

<script>
const IS_VIEW_ONLY = <?= $isViewOnly ? 'true' : 'false' ?>;

let map, allPins=[], markers=[], pendingLatLng=null;
let eventGroups=[], activeGroupId=null;
let playInterval=null, playIndex=0, playPins=[];

function toast(msg,err=false){const el=document.getElementById('toast');el.textContent=msg;el.className='show'+(err?' err':'');setTimeout(()=>el.className='',2800);}
function todayStr(){return new Date().toISOString().split('T')[0];}
function fmtDate(d){const[y,m,day]=d.split('-');return new Date(y,m-1,day).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});}
function fmtTime(t){if(!t)return'';const[h,m]=t.split(':');const d=new Date();d.setHours(+h,+m);return d.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'});}
function escHtml(s){return(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
async function doLogout(){await fetch('api.php?r=logout',{method:'POST'});window.location='login.php';}

// TAG STYLES
const TAG_INFO = {
  live:{label:'LIVE',cls:'tag-live',icon:'blip'},
  investigation:{label:'INVESTIGATION',cls:'tag-investigation',icon:''},
  monitoring:{label:'MONITORING',cls:'tag-monitoring',icon:''},
  concluded:{label:'CONCLUDED',cls:'tag-concluded',icon:''},
  archived:{label:'ARCHIVED',cls:'tag-archived',icon:''},
};
function tagBadge(tag) {
  const t = TAG_INFO[tag]||TAG_INFO.investigation;
  const blip = tag==='live' ? '<span class="live-blip"></span>' : '';
  return `<span class="event-tag ${t.cls}">${blip}${t.label}</span>`;
}

// MAP INIT
function initMap() {
  const bounds = L.latLngBounds(L.latLng(-90,-180),L.latLng(90,180));
  map = L.map('map',{
    center:[20,10], zoom:2, zoomControl:true,
    maxBounds:bounds, maxBoundsViscosity:1.0, worldCopyJump:false,
    minZoom:2
  });
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{
    attribution:'&copy; OpenStreetMap &copy; CARTO',subdomains:'abcd',maxZoom:20,
    updateWhenIdle:true,keepBuffer:2
  }).addTo(map);

  if(!IS_VIEW_ONLY) {
    map.on('click', e => {
      if(!activeGroupId){toast('Select an event first.',true);return;}
      pendingLatLng = e.latlng;
      document.getElementById('modal-coords').textContent=`LAT: ${e.latlng.lat.toFixed(5)}  LNG: ${e.latlng.lng.toFixed(5)}`;
      document.getElementById('pin-title').value='';
      document.getElementById('pin-desc').value='';
      document.getElementById('pin-date').value=todayStr();
      document.getElementById('pin-time').value='';
      document.getElementById('pin-modal').classList.add('open');
      setTimeout(()=>document.getElementById('pin-title').focus(),50);
    });
  }

  const s=document.createElement('style');
  s.textContent=`@keyframes pulse-ring{0%{transform:scale(1);opacity:.7}100%{transform:scale(3.5);opacity:0}}`;
  document.head.appendChild(s);
}

// MARKER ICON
function makeIcon(highlighted=false, pinIndex=null) {
  const color = highlighted ? '#ffdd00' : '#00d4ff';
  const glow  = highlighted ? 'rgba(255,220,0,.5)' : 'rgba(0,212,255,.5)';
  const numLabel = pinIndex!==null ? `<div style="position:absolute;top:4px;left:50%;transform:translateX(-50%);font-size:7px;font-weight:700;color:#000;font-family:monospace;line-height:1;">${pinIndex+1}</div>` : '';
  const svg=`<svg xmlns="http://www.w3.org/2000/svg" width="24" height="32" viewBox="0 0 24 32">
    <circle cx="12" cy="12" r="10" fill="${color}" opacity="0.15" stroke="${color}" stroke-width="1.5"/>
    <circle cx="12" cy="12" r="6" fill="${color}" opacity="0.85"/>
    <circle cx="12" cy="12" r="2.5" fill="#fff" opacity=".8"/>
    <line x1="12" y1="22" x2="12" y2="30" stroke="${color}" stroke-width="1.5" opacity=".6"/>
  </svg>`;
  return L.divIcon({
    className:'',
    html:`<div style="position:relative;filter:drop-shadow(0 0 6px ${glow})">
      ${svg}${numLabel}
      <div style="position:absolute;top:7px;left:7px;width:10px;height:10px;border-radius:50%;border:1px solid ${color};opacity:.6;animation:pulse-ring 2s ease-out infinite;box-sizing:border-box;"></div>
    </div>`,
    iconSize:[24,32],iconAnchor:[12,30],popupAnchor:[0,-30]
  });
}

// LOAD DATA
async function loadGroups() {
  try {
    const r = await fetch('api.php?r=event_groups');
    eventGroups = await r.json();
    renderEventList();
  } catch(e){toast('Could not load events.',true);}
}

async function loadPins(groupId) {
  try {
    const r = await fetch('api.php?r=events&group='+groupId);
    if(r.status===401){window.location='login.php';return;}
    allPins = await r.json();
    renderMarkers();
    renderPinTimeline();
  } catch(e){toast('Could not load pins.',true);}
}

// RENDER EVENT LIST
function renderEventList() {
  const list = document.getElementById('event-list');
  if(!eventGroups.length){
    list.innerHTML=`<div style="padding:20px 14px;font-size:.62rem;color:var(--text-muted);text-align:center;">No events yet.${IS_VIEW_ONLY?'':' Click "+ New" to create one.'}</div>`;
    return;
  }
  list.innerHTML = eventGroups.map(g => `
    <div class="event-item${activeGroupId==g.id?' active':''}" onclick="selectEvent(${g.id})">
      <div class="event-item-top">
        <span class="event-item-name">${escHtml(g.name)}</span>
        ${tagBadge(g.tag)}
      </div>
      <div class="event-item-sub">
        <span>${fmtDate(g.created_at.split(' ')[0])}</span>
      </div>
      ${IS_VIEW_ONLY?'':`<button class="event-del" onclick="event.stopPropagation();deleteGroup(${g.id})">✕</button>`}
    </div>
  `).join('');
}

function selectEvent(gid) {
  if(playInterval) stopPlay();
  activeGroupId = gid;
  renderEventList();
  loadPins(gid);
  document.getElementById('pin-timeline').classList.add('open');
  if(!IS_VIEW_ONLY) document.getElementById('map-hint').textContent='Click map to place a pin in this event';
}

// RENDER PINS ON MAP
function renderMarkers(highlightId=null) {
  markers.forEach(m=>map.removeLayer(m));
  markers=[];
  allPins.forEach((pin,idx) => {
    const hl = pin.id===highlightId;
    const m = L.marker([parseFloat(pin.lat),parseFloat(pin.lng)], {icon:makeIcon(hl,idx)});
    const timeStr = pin.time ? `<div style="font-size:.58rem;color:#4a5568">🕒 ${fmtTime(pin.time)}</div>` : '';
    m.bindPopup(`
      <div style="font-family:'Space Mono',monospace;min-width:190px;">
        <div style="font-size:.6rem;color:#4a5568;letter-spacing:.08em;margin-bottom:4px">PIN ${idx+1}</div>
        <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:.85rem;margin-bottom:5px;color:#e6edf3">${escHtml(pin.title)}</div>
        <div style="font-size:.6rem;color:#4a5568;margin-bottom:3px">${fmtDate(pin.date)}</div>
        ${timeStr}
        ${pin.description?`<div style="font-size:.68rem;color:#c9d1d9;line-height:1.6;margin-top:6px">${escHtml(pin.description)}</div>`:''}
        ${IS_VIEW_ONLY?'':`<button onclick="deletePin(${pin.id})" style="margin-top:10px;background:transparent;border:1px solid rgba(255,77,77,.2);color:rgba(255,77,77,.7);font-family:'Space Mono',monospace;font-size:.53rem;padding:3px 10px;border-radius:3px;cursor:pointer;">Remove Pin</button>`}
      </div>
    `);
    m.addTo(map);
    m._pinId=pin.id;
    markers.push(m);
  });
}

// RENDER PIN TIMELINE (sidebar)
function renderPinTimeline() {
  const list = document.getElementById('pin-list');
  if(!allPins.length){
    list.innerHTML='<div style="padding:14px;font-size:.6rem;color:var(--text-muted);text-align:center;">No pins yet. Click the map to add one.</div>';
    playPins=[];
    return;
  }
  // Sort by date+time
  playPins = [...allPins].sort((a,b)=>{
    const da=a.date+(a.time||'00:00'), db=b.date+(b.time||'00:00');
    return da.localeCompare(db);
  });
  list.innerHTML = playPins.map((p,i) => `
    <div class="pin-item" id="ptitem-${p.id}" onclick="jumpToPin(${p.id})">
      <div class="pin-num">${i+1}</div>
      <div class="pin-info">
        <div class="pin-title">${escHtml(p.title)}</div>
        <div class="pin-date-time">${fmtDate(p.date)}${p.time?' · '+fmtTime(p.time):''}</div>
      </div>
    </div>
  `).join('');
}

function jumpToPin(pinId) {
  const pin = allPins.find(p=>p.id==pinId);
  if(!pin) return;
  renderMarkers(pin.id);
  map.flyTo([parseFloat(pin.lat),parseFloat(pin.lng)],8,{duration:1.2});
  document.querySelectorAll('.pin-item').forEach(el=>el.classList.remove('active'));
  const el=document.getElementById('ptitem-'+pinId);
  if(el){el.classList.add('active');el.scrollIntoView({block:'nearest',behavior:'smooth'});}
  setTimeout(()=>{const m=markers.find(mk=>mk._pinId==pinId);if(m)m.openPopup();},1300);
}

// PLAY
function togglePlay(){if(playInterval){stopPlay();}else{startPlay();}}

function startPlay(){
  if(!playPins.length){toast('No pins to play.',true);return;}
  playIndex=0;
  const btn=document.getElementById('play-btn');
  btn.innerHTML=`<svg viewBox="0 0 24 24" fill="currentColor"><rect x="5" y="3" width="4" height="18"/><rect x="15" y="3" width="4" height="18"/></svg> Stop`;
  btn.classList.add('stop');
  playStep();
}

function playStep(){
  if(playIndex>=playPins.length){stopPlay();return;}
  const pin=playPins[playIndex];
  jumpToPin(pin.id);
  const ind=document.getElementById('play-indicator');
  ind.textContent=`▶ Pin ${playIndex+1}/${playPins.length} — ${escHtml(pin.title)}`;
  ind.classList.add('show');
  playIndex++;
  playInterval=setTimeout(playStep,5000);
}

function stopPlay(){
  clearTimeout(playInterval);playInterval=null;
  const btn=document.getElementById('play-btn');
  btn.innerHTML=`<svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"/></svg> Play`;
  btn.classList.remove('stop');
  document.getElementById('play-indicator').classList.remove('show');
}

// NEW EVENT MODAL
function openNewEventModal(){if(IS_VIEW_ONLY)return;document.getElementById('new-event-modal').classList.add('open');document.getElementById('new-event-name').focus();}
function closeNewEventModal(){document.getElementById('new-event-modal').classList.remove('open');}

async function createEvent(){
  const name=document.getElementById('new-event-name').value.trim();
  const tag=document.getElementById('new-event-tag').value;
  const desc=document.getElementById('new-event-desc').value.trim();
  if(!name){toast('Event name required.',true);return;}
  try{
    const r=await fetch('api.php?r=event_groups',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({name,tag,description:desc})});
    if(r.status===401){window.location='login.php';return;}
    const g=await r.json();
    eventGroups.unshift(g);
    renderEventList();
    closeNewEventModal();
    selectEvent(g.id);
    document.getElementById('new-event-name').value='';
    document.getElementById('new-event-desc').value='';
    toast('Event created.');
  }catch(e){toast('Failed.',true);}
}

async function deleteGroup(id){
  if(!confirm('Delete this event and ALL its pins?'))return;
  await fetch('api.php?r=event_groups&id='+id,{method:'DELETE'});
  if(activeGroupId==id){
    activeGroupId=null;
    allPins=[];
    renderMarkers();
    document.getElementById('pin-timeline').classList.remove('open');
    if(!IS_VIEW_ONLY) document.getElementById('map-hint').textContent='Select an event then click to place a pin';
  }
  eventGroups=eventGroups.filter(g=>g.id!=id);
  renderEventList();
  toast('Event deleted.');
}

// PIN MODAL
function closeModal(){document.getElementById('pin-modal').classList.remove('open');pendingLatLng=null;}

async function savePin(){
  const title=document.getElementById('pin-title').value.trim();
  const desc=document.getElementById('pin-desc').value.trim();
  const date=document.getElementById('pin-date').value||todayStr();
  const time=document.getElementById('pin-time').value||null;
  if(!title){toast('Title required.',true);return;}
  if(!pendingLatLng){closeModal();return;}
  const btn=document.querySelector('.modal-save');
  btn.textContent='...';btn.disabled=true;
  try{
    const r=await fetch('api.php?r=events',{method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({event_group:activeGroupId,title,description:desc,lat:pendingLatLng.lat,lng:pendingLatLng.lng,date,time})});
    if(r.status===401){window.location='login.php';return;}
    const pin=await r.json();
    allPins.push(pin);
    closeModal();
    renderMarkers(pin.id);
    renderPinTimeline();
    map.flyTo([parseFloat(pin.lat),parseFloat(pin.lng)],Math.max(map.getZoom(),6),{duration:.8});
    setTimeout(()=>{const m=markers.find(mk=>mk._pinId==pin.id);if(m)m.openPopup();},900);
    toast('Pin placed.');
  }catch(e){toast('Failed.',true);}
  finally{btn.textContent='Place Pin';btn.disabled=false;}
}

async function deletePin(id){
  if(!confirm('Remove this pin?'))return;
  await fetch('api.php?r=events&id='+id,{method:'DELETE'});
  allPins=allPins.filter(p=>p.id!=id);
  renderMarkers();
  renderPinTimeline();
  map.closePopup();
  toast('Pin removed.');
}

// SEARCH (searches across all pins)
let searchTimer;
document.getElementById('search-input').addEventListener('input', e => {
  clearTimeout(searchTimer);
  const q=e.target.value.trim();
  if(!q){if(activeGroupId)renderMarkers();return;}
  searchTimer=setTimeout(()=>doSearch(q),350);
});

async function doSearch(q){
  try{
    const r=await fetch('api.php?r=events&search='+encodeURIComponent(q));
    const res=await r.json();
    markers.forEach(m=>map.removeLayer(m));markers=[];
    res.forEach(pin=>{
      const m=L.marker([parseFloat(pin.lat),parseFloat(pin.lng)],{icon:makeIcon()});
      m.bindPopup(`<div style="font-family:'Space Mono',monospace;"><div style="font-family:'Syne',sans-serif;font-weight:700;font-size:.85rem;color:#e6edf3;margin-bottom:4px">${escHtml(pin.title)}</div><div style="font-size:.6rem;color:#4a5568">${fmtDate(pin.date)}</div></div>`);
      m.addTo(map);m._pinId=pin.id;markers.push(m);
    });
    if(res.length){const bounds=L.latLngBounds(res.map(p=>[parseFloat(p.lat),parseFloat(p.lng)]));map.flyToBounds(bounds,{padding:[80,80],duration:1.2});}
  }catch(e){toast('Search failed.',true);}
}

document.addEventListener('keydown', e=>{
  if(e.key==='Escape'){
    if(document.getElementById('pin-modal').classList.contains('open'))closeModal();
    const nem=document.getElementById('new-event-modal');
    if(nem&&nem.classList.contains('open'))closeNewEventModal();
  }
});

initMap();
loadGroups();
</script>
</body>
</html>
