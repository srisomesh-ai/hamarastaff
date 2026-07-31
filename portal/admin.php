<?php require __DIR__ . '/boot.php'; $CN = htmlspecialchars(COMPANY_NAME);
if (PLAN === 'trial' && TRIAL_EXPIRED) trial_lock_page($CN);
if (PLAN !== 'trial' && SUB_EXPIRED) sub_lock_page($CN);
if (PLAN === 'starter') {
  header('Content-Type: text/html; charset=utf-8');
  echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Upgrade required</title></head>'
    .'<body style="font-family:sans-serif;background:#F4F8F7;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:24px">'
    .'<div style="background:#fff;border:1px solid #E2EAE8;border-radius:20px;padding:40px;max-width:460px;text-align:center">'
    .'<div style="font-size:44px">🖥️</div>'
    .'<h2 style="margin:14px 0 8px;color:#13211F">Desktop Management Panel</h2>'
    .'<p style="color:#5B6E6B;line-height:1.6;font-size:14.5px">The management desktop panel is available on the <b>Professional plan (₹250/user/month)</b>. Your current plan (<b>Starter</b>) includes the mobile app for field staff.</p>'
    .'<p style="color:#5B6E6B;font-size:13.5px">To upgrade, contact HamaraStaff.</p>'
    .'<a href="https://hamarastaff.com/pricing.html" style="display:inline-block;margin-top:14px;background:#0E6B63;color:#fff;padding:13px 24px;border-radius:12px;text-decoration:none;font-weight:700">View Plans →</a>'
    .'</div></body></html>';
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $CN ?> — Management Panel</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>const HS_PLAN='<?= PLAN ?>';const HS_TRIAL_DAYS=<?= (int)TRIAL_DAYS_LEFT ?>;const HS_CODE='<?= CODE ?>';const HS_ENDS='<?= PLAN_ENDS !== '' ? date('d M Y', strtotime(PLAN_ENDS)) : '' ?>';const HS_SUB_DAYS=<?= PLAN_ENDS !== '' ? (int)SUB_DAYS_LEFT : -1 ?>;const HS_UPI='srisomeshidfc@ybl';const HS_PAYEE='Hamara Staff';</script>
<link rel="icon" type="image/png" href="/assets/favicon.png?v=2">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --teal:#0E6B63;--teal-dark:#0A4F49;--teal-soft:#E3F0EE;
  --ink:#13211F;--sub:#5B6E6B;--line:#E2EAE8;--bg:#F4F8F7;--card:#fff;
  --amber:#C77800;--amber-soft:#FFF3E0;--blue:#1859A8;--blue-soft:#E4EEFA;
  --green:#1E7B34;--green-soft:#E3F3E7;--red:#B3261E;--red-soft:#FBE9E7;
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Manrope',sans-serif;background:var(--bg);color:var(--ink);min-height:100vh}
.num{font-family:'Space Grotesk',monospace}
button{font-family:inherit;border:none;cursor:pointer;background:none}
select{font-family:inherit;font-size:14px;padding:9px 12px;border:1.5px solid var(--line);border-radius:10px;background:#fff}

/* layout */
.app{display:grid;grid-template-columns:248px 1fr;min-height:100vh}
.sidebar{background:linear-gradient(180deg,var(--teal-dark),var(--teal));color:#fff;display:flex;flex-direction:column;position:sticky;top:0;height:100vh}
.brand{display:flex;gap:12px;align-items:center;padding:22px 20px 20px;border-bottom:1px solid rgba(255,255,255,.15)}
.brand .b-ic{width:42px;height:42px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;font-size:22px}
.brand b{font-size:16px;display:block;line-height:1.2}
.brand span{font-size:11.5px;opacity:.8}
.nav{padding:16px 12px;flex:1}
.nav button{display:flex;align-items:center;gap:12px;width:100%;padding:12px 14px;border-radius:12px;color:rgba(255,255,255,.85);font-size:14px;font-weight:700;margin-bottom:4px;text-align:left;transition:.15s}
.nav button:hover{background:rgba(255,255,255,.1)}
.nav button.on{background:#fff;color:var(--teal)}
.nav .i{font-size:17px;width:22px;text-align:center}
.me{padding:16px 20px;border-top:1px solid rgba(255,255,255,.15);display:flex;gap:12px;align-items:center}
.me .avatar{width:38px;height:38px;border-radius:50%;background:#fff;color:var(--teal);font-weight:800;display:flex;align-items:center;justify-content:center;font-size:14px}
.me b{font-size:14px;display:block}
.me span{font-size:11.5px;opacity:.8}

.main{padding:26px 32px;min-width:0}
.pagehead{display:flex;align-items:center;gap:16px;margin-bottom:22px}
.pagehead h1{font-size:22px;font-weight:800}
.pagehead .date{color:var(--sub);font-size:13.5px;font-weight:600;margin-top:2px}
.pagehead .spacer{flex:1}
.btn{display:inline-flex;align-items:center;gap:8px;padding:11px 18px;border-radius:12px;font-size:13.5px;font-weight:800;transition:.15s}
.btn:active{transform:scale(.97)}
.btn.primary{background:var(--teal);color:#fff}
.btn.green{background:var(--green);color:#fff}
.btn.ghost{background:#fff;border:1.5px solid var(--line);color:var(--ink)}
.page{display:none}.page.on{display:block}

/* cards & stats */
.card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:20px;margin-bottom:20px}
.card h3{font-size:15px;font-weight:800;margin-bottom:14px}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px}
.stat{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px 20px;display:flex;align-items:center;gap:14px}
.stat .ic{width:46px;height:46px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:21px}
.stat .n{font-size:26px;font-weight:700;font-family:'Space Grotesk';line-height:1}
.stat .l{font-size:11.5px;font-weight:800;color:var(--sub);text-transform:uppercase;letter-spacing:.4px;margin-top:4px}
.grid2{display:grid;grid-template-columns:1.15fr .85fr;gap:20px;align-items:start}

/* tables */
table{width:100%;border-collapse:collapse;font-size:13.5px}
th{text-align:left;font-size:11px;font-weight:800;color:var(--sub);text-transform:uppercase;letter-spacing:.5px;padding:10px 12px;border-bottom:1.5px solid var(--line)}
td{padding:12px;border-bottom:1px solid var(--line);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr.click{cursor:pointer}
tbody tr.click:hover{background:var(--teal-soft)}
.pill{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;padding:4px 10px;border-radius:99px;white-space:nowrap}
.pill.open{background:var(--amber-soft);color:var(--amber)}
.pill.reached{background:var(--blue-soft);color:var(--blue)}
.pill.closed,.pill.present{background:var(--green-soft);color:var(--green)}
.pill.absent{background:var(--red-soft);color:var(--red)}
.ename{display:flex;align-items:center;gap:10px;font-weight:700}
.ename .avatar{width:32px;height:32px;border-radius:50%;background:var(--teal-soft);color:var(--teal);font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center}
.loc{color:var(--blue);font-weight:600;font-size:12.5px}
.muted{color:var(--sub)}

/* timeline */
.trail{position:relative;padding-left:24px}
.trail::before{content:'';position:absolute;left:7px;top:6px;bottom:6px;width:2px;background:linear-gradient(var(--teal) 60%,var(--line))}
.tr-item{position:relative;padding-bottom:16px}
.tr-item:last-child{padding-bottom:2px}
.tr-item::before{content:'';position:absolute;left:-22px;top:3px;width:11px;height:11px;border-radius:50%;background:#fff;border:3px solid var(--teal)}
.tr-item.start::before{background:var(--teal)}
.tr-item.close::before{border-color:var(--green);background:var(--green)}
.tr-time{font-family:'Space Grotesk';font-size:12px;font-weight:700;color:var(--teal)}
.tr-main{font-size:13.5px;font-weight:700;margin:1px 0 2px}
.tr-sub{font-size:12.5px;color:var(--sub)}
.remark-box{background:var(--teal-soft);border-radius:10px;padding:8px 10px;font-size:12.5px;margin-top:6px;color:var(--teal-dark)}

/* progress bar */
.bar{height:8px;background:#EDF2F1;border-radius:99px;overflow:hidden;min-width:110px}
.bar div{height:100%;background:var(--green)}

/* modal */
.overlay{position:fixed;inset:0;background:rgba(16,32,30,.45);display:none;align-items:flex-start;justify-content:center;padding:40px 20px;z-index:50;overflow-y:auto}
.overlay.show{display:flex}
.modal{background:#fff;border-radius:18px;max-width:640px;width:100%;padding:24px;position:relative}
.modal .x{position:absolute;top:14px;right:16px;font-size:20px;color:var(--sub);font-weight:800;padding:6px}
.report-grid{display:grid;grid-template-columns:auto 1fr;gap:7px 16px;font-size:13.5px}
.report-grid dt{color:var(--sub);font-weight:700}
.report-grid dd{font-weight:600}
.sendline{display:inline-flex;align-items:center;gap:8px;background:var(--green-soft);color:var(--green);font-size:12.5px;font-weight:800;border-radius:10px;padding:9px 14px;margin-top:14px}
.hod-banner{background:var(--teal-soft);border:1.5px solid var(--teal);border-radius:14px;padding:16px 20px;display:flex;align-items:center;gap:16px;margin-bottom:20px}
.hod-banner b{font-size:14.5px}
.hod-banner p{font-size:12.5px;color:var(--teal-dark);margin-top:2px}
.toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(16px);background:var(--ink);color:#fff;padding:12px 20px;border-radius:12px;font-size:13.5px;font-weight:700;opacity:0;transition:.25s;z-index:99;pointer-events:none}
.toast.show{opacity:1;transform:translateX(-50%)}
.mobile-note{display:none}
@media(max-width:900px){
 .app{grid-template-columns:1fr}
 .sidebar{position:static;height:auto;flex-direction:row;align-items:center;flex-wrap:wrap}
 .nav{display:flex;padding:8px;flex:1}.nav button{margin:0;padding:10px}
 .me{display:none}.main{padding:18px}
 .stats{grid-template-columns:1fr 1fr}.grid2{grid-template-columns:1fr}
 .mobile-note{display:block;background:var(--amber-soft);color:var(--amber);font-size:12.5px;font-weight:700;padding:10px 14px;border-radius:12px;margin-bottom:16px}
}
</style>
</head>
<body>
<?php if (PLAN !== 'trial' && PLAN_ENDS !== '' && !SUB_EXPIRED && SUB_DAYS_LEFT <= 7): ?>
<div style="background:linear-gradient(90deg,#B3261E,#D9534F);color:#fff;text-align:center;padding:9px 14px;font-size:13px;font-weight:800;font-family:'Manrope',sans-serif">
&#9203; Your plan expires <?= SUB_DAYS_LEFT<=0 ? 'today' : 'in '.SUB_DAYS_LEFT.' day'.(SUB_DAYS_LEFT>1?'s':'') ?> (<?= date('d M Y', strtotime(PLAN_ENDS)) ?>) &middot; renew from the Plan &amp; Billing section
</div>
<?php endif; ?>
<?php if (PLAN === 'trial' && !TRIAL_EXPIRED): ?>
<div style="background:linear-gradient(90deg,#C77800,#F0A322);color:#fff;text-align:center;padding:9px 14px;font-size:13px;font-weight:800;font-family:'Manrope',sans-serif">
&#127873; Free Trial &mdash; <?= TRIAL_DAYS_LEFT<=0 ? 'last day today!' : TRIAL_DAYS_LEFT.' day'.(TRIAL_DAYS_LEFT>1?'s':'').' left' ?>
&middot; <a href="https://hamarastaff.com/pricing.html" target="_blank" style="color:#fff;text-decoration:underline">Choose your plan</a>
</div>
<?php endif; ?>

<div class="app">

<aside class="sidebar">
  <div class="brand">
    <div class="b-ic"><?php if (client_logo_exists()): ?><img src="<?= CLIENT_LOGO_URL ?>?v=<?= filemtime(CLIENT_LOGO_FILE) ?>" alt="" style="width:34px;height:34px;object-fit:contain"><?php else: ?>🏥<?php endif; ?></div>
    <div><b><?= $CN ?></b><span>Management Panel</span></div>
  </div>
  <nav class="nav">
    <button class="on" onclick="go('dash',this)"><span class="i">📊</span>Dashboard</button>
    <button onclick="go('att',this)"><span class="i">✅</span>Attendance</button>
    <button onclick="go('visits',this)"><span class="i">📋</span>Field Visits</button>
    <button onclick="go('activity',this)"><span class="i">🕒</span>Activity Trail</button>
    <button onclick="go('emps',this)"><span class="i">👥</span>Employees</button>
    <button onclick="go('billing',this)"><span class="i">💳</span>Plan &amp; Billing</button>
  </nav>
  <div class="me">
    <div class="avatar">AP</div>
    <div style="flex:1"><b>Aparna.P</b><span>HOD · Marketing</span></div>
    <button onclick="fetch('api/api.php',{method:'POST',body:JSON.stringify({action:'logout'})}).finally(()=>location.href='index.html')" style="color:#fff;background:rgba(255,255,255,.15);padding:8px 12px;border-radius:10px;font-size:12px;font-weight:800;cursor:pointer">Logout</button>
  </div>
</aside>

<main class="main">
  <div class="mobile-note">💡 This is the desktop panel. Field employees should use the mobile app page.</div>

  <!-- ============ DASHBOARD ============ -->
  <section class="page on" id="dash">
    <div class="pagehead">
      <div><h1>Dashboard</h1><div class="date" id="dashDate"></div></div>
      <div class="spacer"></div>
      <button class="btn primary" onclick="downloadExcel()">⬇ Download Excel Report</button>
    </div>
    <div class="stats" id="dashStats"></div>
    <div class="grid2">
      <div class="card">
        <h3>Team Status — Today</h3>
        <table><thead><tr><th>Employee</th><th>Attendance</th><th>Day Start</th><th>Start Location</th><th>Visits</th></tr></thead>
        <tbody id="teamRows"></tbody></table>
      </div>
      <div class="card">
        <h3>Live Activity Feed</h3>
        <div class="trail" id="feed"></div>
      </div>
    </div>
  </section>

  <!-- ============ ATTENDANCE ============ -->
  <section class="page" id="att">
    <div class="pagehead">
      <div><h1>Attendance</h1><div class="date" id="attDate"></div></div>
      <div class="spacer"></div>
      <button class="btn primary" onclick="downloadExcel()">⬇ Download Excel Report</button>
    </div>
    <div class="card">
      <h3>Today — GPS Based Attendance</h3>
      <table><thead><tr><th>Employee</th><th>Status</th><th>Day Start</th><th>Start Location (GPS)</th><th>Day End</th></tr></thead>
      <tbody id="attRows"></tbody></table>
    </div>
    <div class="hod-banner" id="hodBanner">
      <div style="flex:1"><b>Monthly Review — July 2026 · 26 working days</b>
      <p>Review GPS-based attendance below and approve for payroll processing.</p></div>
      <button class="btn green" id="hodBtn" onclick="hodApprove()">✓ HOD Approve — Send to Payroll</button>
    </div>
    <div class="card">
      <h3>Monthly Summary — July 2026</h3>
      <table><thead><tr><th>Employee</th><th>Working Days</th><th>Present</th><th>Leave</th><th>Absent</th><th>Attendance %</th><th>Payroll Status</th></tr></thead>
      <tbody id="monRows"></tbody></table>
    </div>
  </section>

  <!-- ============ VISITS ============ -->
  <section class="page" id="visits">
    <div class="pagehead">
      <div><h1>Field Visits</h1><div class="date">All visit tasks — click a row for the full report</div></div>
      <div class="spacer"></div>
      <button class="btn primary" onclick="openAssignForm()">＋ Assign Visit</button>
      <select id="visitFilter" onchange="render()">
        <option value="all">All statuses</option><option value="open">Open</option>
        <option value="reached">Reached</option><option value="closed">Closed</option>
      </select>
    </div>
    <div class="card">
      <table><thead><tr><th>Employee</th><th>Doctor / Client</th><th>Hospital</th><th>Purpose</th><th>Status</th><th>Reached</th><th>Closed</th><th>Report</th></tr></thead>
      <tbody id="visitRows"></tbody></table>
    </div>
  </section>

  <!-- ============ ACTIVITY ============ -->
  <section class="page" id="activity">
    <div class="pagehead">
      <div><h1>Activity Trail</h1><div class="date">Full day movement of an employee</div></div>
      <div class="spacer"></div>
      <select id="empPick" onchange="renderActivity()"></select>
    </div>
    <div class="grid2">
      <div class="card"><h3 id="trailTitle"></h3><div class="trail" id="empTrail"></div></div>
      <div class="card"><h3>Day Summary</h3><div id="empSummary"></div></div>
    </div>
  </section>

  <!-- ============ EMPLOYEES ============ -->
  <section class="page" id="emps">
    <div class="pagehead">
      <div><h1>Employees</h1><div class="date">Create logins, set passwords, enable / disable access</div></div>
      <div class="spacer"></div>
      <button class="btn primary" onclick="openEmpForm()">＋ Add Employee</button>
    </div>
    <div class="card">
      <table><thead><tr><th>Employee</th><th>Username (ID)</th><th>Password</th><th>Area</th><th>Access</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody id="empRows"></tbody></table>
    </div>
    <p style="font-size:12.5px;color:var(--sub)">Disabled employees cannot sign in to the mobile app. Deleting an employee removes their login permanently.</p>
  </section>

  <!-- ============ PLAN & BILLING ============ -->
  <section class="page" id="billing">
    <div class="pagehead">
      <div><h1>Plan &amp; Billing</h1><div class="date">Your current plan and payment options</div></div>
    </div>
    <div id="curPlanCard"></div>
    <div class="grid2" id="planCards" style="grid-template-columns:1fr 1fr"></div>
    <p style="font-size:12.5px;color:var(--sub);line-height:1.7;margin-top:6px">Payments go to <b>Hamara Staff</b> via UPI. After paying, send the payment screenshot to <b>info@hamarastaff.com</b> or WhatsApp — your plan is activated the same day.</p>
  </section>
</main>
</div>

<!-- report modal -->
<div class="overlay" id="overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <button class="x" onclick="closeModal()">✕</button>
    <div id="modalBody"></div>
  </div>
</div>
<div class="toast" id="toast"></div>

<script>
/* =============== MEDCY ADMIN — API DRIVEN =============== */
const $=id=>document.getElementById(id);
function toast(m){const t=$('toast');t.textContent=m;t.classList.add('show');clearTimeout(t._h);t._h=setTimeout(()=>t.classList.remove('show'),2600)}
async function api(action,data={}){
 const r=await fetch('api/api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action,...data})});
 let j=null;try{j=await r.json()}catch(e){}
 if(!j||!j.ok){
  if(j&&j.error==='auth'){location.href='index.html';throw new Error('auth')}
  throw new Error(j&&j.error?j.error:'network')
 }
 return j.data;
}
function pill(st){return `<span class="pill ${st}">${st==='open'?'● Open':st==='reached'?'◉ Reached':'✓ Closed'}</span>`}
function locTxt(l){return l&&l.lat?`<span class="loc">${l.area} (${l.lat}, ${l.lng})</span>`:'<span class="muted">—</span>'}
function trItem(x){return `<div class="tr-item ${x.type==='start'?'start':x.type==='close'?'close':''}"><div class="tr-time">${x.t||x.time}</div><div class="tr-main">${x.main||x.text}</div>${x.loc&&x.loc.lat?`<div class="tr-sub">📍 ${locTxt(x.loc)}</div>`:''}${x.note?`<div class="remark-box">"${x.note}"</div>`:''}</div>`}

let D=null, showPw={};

async function boot(){
 try{const me=await api('me');if(me.role!=='admin'){location.href='index.html';return}}
 catch(e){return}
 await loadAll();
 setInterval(()=>loadAll(true),60000); /* auto-refresh every minute */
}
async function loadAll(quiet){
 try{D=await api('admin_overview');render()}
 catch(e){if(!quiet)toast('Could not load data: '+e.message)}
}
function go(id,btn){document.querySelectorAll('.page').forEach(p=>p.classList.remove('on'));$(id).classList.add('on');document.querySelectorAll('.nav button').forEach(b=>b.classList.remove('on'));btn.classList.add('on');if(D)render()}

function render(){
 if(!D)return;
 $('dashDate').textContent=D.todayLabel; $('attDate').textContent=D.todayLabel;
 const pres=D.employees.filter(e=>e.day).length;
 const open=D.tasks.filter(t=>t.status==='open').length, reach=D.tasks.filter(t=>t.status==='reached').length, closed=D.tasks.filter(t=>t.status==='closed').length;
 $('dashStats').innerHTML=`
  <div class="stat"><div class="ic" style="background:var(--green-soft)">✅</div><div><div class="n" style="color:var(--green)">${pres}/${D.employees.length}</div><div class="l">Present Today</div></div></div>
  <div class="stat"><div class="ic" style="background:var(--blue-soft)">🚶</div><div><div class="n" style="color:var(--blue)">${reach}</div><div class="l">On Field Now</div></div></div>
  <div class="stat"><div class="ic" style="background:var(--green-soft)">📋</div><div><div class="n">${closed}</div><div class="l">Visits Closed</div></div></div>
  <div class="stat"><div class="ic" style="background:var(--amber-soft)">⏳</div><div><div class="n" style="color:var(--amber)">${open}</div><div class="l">Open Tasks</div></div></div>`;
 $('teamRows').innerHTML=D.employees.map(e=>`<tr>
  <td><div class="ename"><div class="avatar">${e.init}</div>${e.name}</div></td>
  <td>${e.day?'<span class="pill present">✓ Present</span>':'<span class="pill absent">✗ Absent</span>'}</td>
  <td class="num">${e.day?e.day.startedAt:'—'}</td><td>${e.day?locTxt(e.day.startLoc):'—'}</td>
  <td class="num">${e.visitsClosed}/${e.visitsTotal}</td></tr>`).join('');
 $('feed').innerHTML=D.feed.length?D.feed.map(trItem).join(''):'<span class="muted">No activity yet today.</span>';

 /* attendance */
 $('attRows').innerHTML=D.employees.map(e=>`<tr>
  <td><div class="ename"><div class="avatar">${e.init}</div>${e.name}</div></td>
  <td>${e.day?'<span class="pill present">✓ Present</span>':'<span class="pill absent">✗ Absent</span>'}</td>
  <td class="num">${e.day?e.day.startedAt:'—'}</td><td>${e.day?locTxt(e.day.startLoc):'—'}</td>
  <td class="num">${e.day&&e.day.endedAt?e.day.endedAt:'<span class="muted">—</span>'}</td></tr>`).join('');
 document.querySelector('#hodBanner b').textContent=`Monthly Review — ${D.monthLabel} · ${D.workingDays} working days`;
 document.querySelector('#att .card:last-of-type h3').textContent=`Monthly Summary — ${D.monthLabel}`;
 $('monRows').innerHTML=D.monthly.map(m=>`<tr>
  <td><div class="ename"><div class="avatar">${m.init}</div>${m.name}</div></td>
  <td class="num">${m.wd}</td><td class="num" style="color:var(--green);font-weight:700">${m.present}</td>
  <td class="num" style="color:var(--amber)">0</td><td class="num" style="color:var(--red)">${m.absent}</td>
  <td><div style="display:flex;align-items:center;gap:10px"><div class="bar"><div style="width:${m.pct}%"></div></div><b class="num">${m.pct}%</b></div></td>
  <td>${D.approved?'<span class="pill present">✓ Approved for Payroll</span>':'<span class="pill open">Pending HOD</span>'}</td></tr>`).join('');
 $('hodBanner').style.display=D.approved?'none':'flex';

 /* visits */
 const f=$('visitFilter').value;
 const list=f==='all'?D.tasks:D.tasks.filter(t=>t.status===f);
 $('visitRows').innerHTML=list.map(t=>{const r=t.timeline.find(x=>x.type==='reach');const c=t.timeline.find(x=>x.type==='close');
  return `<tr class="click" onclick="openReport(${t.id})">
  <td><div class="ename"><div class="avatar">${t.empInit}</div>${t.empName}</div></td>
  <td style="font-weight:700">${t.doctor}</td><td>${t.hospital||'—'}</td><td class="muted">${t.purpose}</td>
  <td>${pill(t.status)}</td><td class="num">${r?r.t:'—'}</td><td class="num">${c?c.t:'—'}</td>
  <td>${t.report?(t.report.sent.length?'📤 '+t.report.sent.join(' + '):'Saved'):'<span class="muted">—</span>'}</td></tr>`}).join('')
  ||'<tr><td colspan="8" class="muted" style="text-align:center;padding:26px">No visits in this status.</td></tr>';

 /* activity */
 const pick=$('empPick');
 const cur=pick.value;
 pick.innerHTML=D.employees.map(e=>`<option value="${e.id}" ${String(e.id)===cur?'selected':''}>${e.name}</option>`).join('');
 renderActivity();

 /* employees */
 renderEmps();
 renderBilling();
}
function renderActivity(){
 const eid=parseInt($('empPick').value)||(D&&D.employees[0]?D.employees[0].id:0);
 const e=D.employees.find(x=>x.id===eid); if(!e)return;
 $('trailTitle').textContent=e.name+" — Today's Trail";
 const items=[];
 if(e.day)items.push({t:e.day.startedAt,type:'start',main:e.name.split(' ')[0]+' started day',loc:e.day.startLoc});
 D.tasks.filter(t=>t.empId===eid).forEach(t=>{t.timeline.forEach(x=>{const it={...x};if(x.type==='close'&&t.report)it.note=t.report.outcome+' — "'+t.report.remarks+'"';items.push(it)})});
 if(e.day&&e.day.endedAt)items.push({t:e.day.endedAt,type:'close',main:'Ended day'});
 $('empTrail').innerHTML=items.length?items.map(trItem).join(''):'<span class="muted">No activity today yet.</span>';
 const mine=D.tasks.filter(t=>t.empId===eid);
 const m=D.monthly.find(x=>x.name===e.name)||{present:0,wd:D.workingDays};
 $('empSummary').innerHTML=`<dl class="report-grid">
  <dt>Attendance</dt><dd>${e.day?'Present ✓':'Absent'}</dd>
  <dt>Day start</dt><dd class="num">${e.day?e.day.startedAt:'—'}</dd>
  <dt>Start location</dt><dd>${e.day?locTxt(e.day.startLoc):'—'}</dd>
  <dt>Total visits</dt><dd class="num">${mine.length}</dd>
  <dt>Closed</dt><dd class="num">${mine.filter(t=>t.status==='closed').length}</dd>
  <dt>Pending</dt><dd class="num">${mine.filter(t=>t.status!=='closed').length}</dd>
  <dt>Monthly present</dt><dd class="num">${m.present}/${m.wd} days</dd>
 </dl>`;
}

/* ---------- employees mgmt ---------- */
function renderEmps(){
 const el=$('empRows'); if(!el)return;
 el.innerHTML=D.employees.map(e=>`<tr>
   <td><div class="ename"><div class="avatar">${e.init}</div>${e.name}</div></td>
   <td class="num">${e.emp_code}</td>
   <td class="num">${showPw[e.id]?e.pw:'••••••••'} <button onclick="showPw[${e.id}]=!showPw[${e.id}];renderEmps()" title="Show / hide" style="cursor:pointer;font-size:13px">👁</button></td>
   <td class="muted">${e.area||'—'}</td>
   <td>${e.active?'<span class="pill present">✓ Active</span>':'<span class="pill absent">✗ Disabled</span>'}</td>
   <td style="text-align:right;white-space:nowrap">
     <button class="btn ghost" style="padding:7px 12px;font-size:12px" onclick="toggleEmp(${e.id})">${e.active?'Disable':'Enable'}</button>
     <button class="btn ghost" style="padding:7px 12px;font-size:12px;color:var(--red);border-color:#F1D2CF" onclick="delEmp(${e.id},'${e.name.replace(/'/g,"\\'")}')">Delete</button>
   </td></tr>`).join('')||'<tr><td colspan="6" class="muted" style="text-align:center;padding:26px">No employees yet.</td></tr>';
}
async function toggleEmp(id){try{const r=await api('emp_toggle',{id});toast(r.active?r.name+' enabled ✓':r.name+' disabled — app access blocked');await loadAll(true)}catch(e){toast('Could not update')}}
async function delEmp(id,name){
 if(!confirm('Delete '+name+'? Their login will stop working permanently. (Visit history is kept for audit.)'))return;
 try{await api('emp_del',{id});toast(name+' deleted');await loadAll(true)}catch(e){toast('Could not delete')}
}
const F=(id,label,ph,type)=>`<label style="display:block;font-size:11px;font-weight:800;color:var(--sub);text-transform:uppercase;letter-spacing:.5px;margin:12px 0 5px">${label}</label>
<input id="${id}" type="${type||'text'}" style="width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:11px;font-family:inherit;font-size:14px" placeholder="${ph}">`;
function openEmpForm(){
 $('modalBody').innerHTML=`<b style="font-size:18px">Add Employee</b><div style="margin-top:6px">
  ${F('fN','Full Name','e.g. Anil Kumar')}
  ${F('fI','Username (Employee ID)','e.g. MEDCY-1004')}
  ${F('fP','Password','Set a password')}
  ${F('fA','Area','e.g. MVP Colony')}
  <button class="btn primary" style="width:100%;margin-top:20px;justify-content:center" onclick="saveEmp()">Create Employee ✓</button></div>`;
 $('overlay').classList.add('show');
}
async function saveEmp(){
 const n=$('fN').value.trim(),i=$('fI').value.trim(),p=$('fP').value.trim(),a=$('fA').value.trim();
 if(!n||!i||!p)return toast('Name, username and password are required');
 try{await api('emp_add',{name:n,emp_code:i,password:p,area:a});closeModal();toast(n+' created ✓ They can now sign in');await loadAll(true)}
 catch(e){toast(e.message==='exists'?'That username already exists':'Could not create: '+e.message)}
}

/* ---------- assign visit ---------- */
function openAssignForm(){
 $('modalBody').innerHTML=`<b style="font-size:18px">Assign Visit Task</b><div style="margin-top:6px">
  <label style="display:block;font-size:11px;font-weight:800;color:var(--sub);text-transform:uppercase;letter-spacing:.5px;margin:12px 0 5px">Assign To</label>
  <select id="aE" style="width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:11px;font-family:inherit;font-size:14px">${D.employees.filter(e=>e.active).map(e=>`<option value="${e.id}">${e.name}</option>`).join('')}</select>
  ${F('aD','Doctor / Client Name','e.g. Dr. K. Prasad')}
  ${F('aH','Hospital / Clinic','e.g. Apollo Clinic')}
  ${F('aA','Area','e.g. Waltair')}
  <label style="display:block;font-size:11px;font-weight:800;color:var(--sub);text-transform:uppercase;letter-spacing:.5px;margin:12px 0 5px">Purpose</label>
  <select id="aP" style="width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:11px;font-family:inherit;font-size:14px">
   <option>Product Demo</option><option>New Product Introduction</option><option>Follow-up Visit</option><option>Sample Delivery</option><option>Order Collection</option></select>
  ${F('aT','Planned Time','','time')}
  ${F('aM','Client Email','doctor@clinic.com','email')}
  ${F('aW','Client WhatsApp','+91 9XXXXXXXXX','tel')}
  <button class="btn primary" style="width:100%;margin-top:20px;justify-content:center" onclick="saveAssign()">Assign Task ✓</button></div>`;
 $('overlay').classList.add('show');
}
async function saveAssign(){
 if(!$('aD').value.trim())return toast('Enter doctor / client name');
 try{
  await api('task_add',{emp_id:parseInt($('aE').value),doctor:$('aD').value,hospital:$('aH').value,area:$('aA').value,purpose:$('aP').value,planned:$('aT').value,email:$('aM').value,phone:$('aW').value});
  closeModal();toast('Task assigned ✓ Employee will see it in the app');await loadAll(true);
 }catch(e){toast('Could not assign')}
}

/* ---------- report modal ---------- */
function openReport(id){
 const t=D.tasks.find(x=>x.id===id);const r=t.report;
 $('modalBody').innerHTML=`
  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:6px">
   <div><b style="font-size:18px">${t.doctor}</b><div class="muted" style="font-size:13px;margin-top:2px">${t.hospital||'—'} · ${t.area||'—'} · MR: <b>${t.empName}</b></div></div>${pill(t.status)}
  </div>
  ${t.timeline.length?`<h3 style="font-size:13.5px;margin:16px 0 10px">Activity Trail</h3><div class="trail">${t.timeline.map(trItem).join('')}</div>`:''}
  ${r?`<h3 style="font-size:13.5px;margin:18px 0 10px">Visit Report</h3>
  <dl class="report-grid">
   <dt>Person met</dt><dd>${r.met}</dd>
   <dt>Products</dt><dd>${r.products.join(', ')||'—'}</dd>
   <dt>Demo given</dt><dd>${r.demo}</dd>
   <dt>Samples</dt><dd class="num">${r.samples}</dd>
   <dt>Outcome</dt><dd>${r.outcome}</dd>
   <dt>Client remarks</dt><dd>"${r.remarks}"</dd>
   <dt>Next visit</dt><dd>${r.next||'—'}</dd>
   <dt>GPS location</dt><dd>${r.locAttached?'Attached ✓':'Not attached'}</dd>
   <dt>Closed at</dt><dd class="num">${r.closedAt}</dd>
  </dl>
  ${r.sent.length?`<div class="sendline">📤 Report sent to client via ${r.sent.join(' + ')}</div>`:''}`
  :'<p class="muted" style="margin-top:14px">Visit not closed yet — the report appears here once the MR submits it from the mobile app.</p>'}`;
 $('overlay').classList.add('show');
}
function closeModal(){$('overlay').classList.remove('show')}

/* ---------- Plan & Billing ---------- */
function activeEmpCount(){return D?D.employees.filter(e=>e.active).length:0}
function renderBilling(){
 const el=$('curPlanCard'); if(!el)return;
 const n=activeEmpCount();
 let head='';
 if(HS_PLAN==='trial'){
  const d=HS_TRIAL_DAYS;
  head=`<div class="card" style="background:linear-gradient(120deg,#C77800,#F0A322);color:#fff;border:none">
   <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
    <div style="font-size:34px">🎁</div>
    <div style="flex:1"><b style="font-size:17px">Free Trial — Full Access</b>
     <div style="font-size:13px;opacity:.95;margin-top:3px">${d<0?'Your trial has ended':d===0?'Last day today!':d+' day'+(d>1?'s':'')+' left'} · Choose a plan below to continue without interruption</div></div>
   </div></div>`;
 }else if(HS_PLAN==='starter'){
  head=`<div class="card"><div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
   <div style="font-size:30px">📱</div>
   <div style="flex:1"><b style="font-size:16px">Current Plan: ₹150 Starter</b>
   <div class="muted" style="font-size:13px;margin-top:3px">Mobile app for field staff · ${n} active employee${n!==1?'s':''} · ₹${150*n}/month${HS_ENDS?` · <b style="color:${HS_SUB_DAYS<=7?'var(--red)':'var(--green)'}">Valid till ${HS_ENDS}${HS_SUB_DAYS>=0?' ('+HS_SUB_DAYS+'d left)':''}</b>`:''}</div></div>
   <span class="pill present">✓ Active</span>${HS_ENDS?`<button class="btn primary" style="padding:9px 14px;font-size:12.5px" onclick="openPay(150,'Starter')">Renew</button>`:''}</div></div>`;
 }else{
  head=`<div class="card"><div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
   <div style="font-size:30px">🖥️</div>
   <div style="flex:1"><b style="font-size:16px">Current Plan: ₹250 Professional</b>
   <div class="muted" style="font-size:13px;margin-top:3px">Mobile app + Desktop panel · ${n} active employee${n!==1?'s':''} · ₹${250*n}/month${HS_ENDS?` · <b style="color:${HS_SUB_DAYS<=7?'var(--red)':'var(--green)'}">Valid till ${HS_ENDS}${HS_SUB_DAYS>=0?' ('+HS_SUB_DAYS+'d left)':''}</b>`:''}</div></div>
   <span class="pill present">✓ Active</span>${HS_ENDS?`<button class="btn primary" style="padding:9px 14px;font-size:12.5px" onclick="openPay(250,'Professional')">Renew</button>`:''}</div></div>`;
 }
 el.innerHTML=head;
 const plan=(rate,title,desc,feats,active)=>`
  <div class="card" style="${active?'border:2px solid var(--teal)':''}">
   <b style="font-size:16px">${title}</b>
   <div style="font-size:26px;font-weight:700;font-family:'Space Grotesk';color:var(--teal);margin:6px 0 2px">₹${rate}<span style="font-size:13px;color:var(--sub)"> /employee/month</span></div>
   <div class="muted" style="font-size:12.5px;margin-bottom:10px">${desc}</div>
   <div style="font-size:12.5px;line-height:2">${feats.map(x=>'✓ '+x).join('<br>')}</div>
   ${active?'<div class="pill present" style="margin-top:14px">✓ Currently Active</div>'
    :`<button class="btn primary" style="margin-top:14px;width:100%;justify-content:center" onclick="openPay(${rate},'${title.replace(/'/g,'')}')">Pay ₹${rate*Math.max(activeEmpCount(),1)} &amp; Activate</button>`}
  </div>`;
 $('planCards').innerHTML=
  plan(150,'Starter','Mobile app for field staff',['GPS attendance & visit tasks','Visit reports emailed to clients','Excel reports'],HS_PLAN==='starter')+
  plan(250,'Professional','Mobile app + this desktop panel',['Everything in Starter','Desktop management panel','HOD payroll approval','Priority support'],HS_PLAN==='professional');
}
function openPay(rate,title){
 const n=Math.max(activeEmpCount(),1);
 const amt=rate*n;
 const tn=('HamaraStaff '+HS_CODE.toUpperCase()+' '+title+' plan').slice(0,50);
 const upiLink='upi://pay?pa='+encodeURIComponent(HS_UPI)+'&pn='+encodeURIComponent(HS_PAYEE)+'&am='+amt+'&cu=INR&tn='+encodeURIComponent(tn);
 $('modalBody').innerHTML=`
  <b style="font-size:18px">Pay for ${title} Plan</b>
  <div class="muted" style="font-size:13px;margin-top:4px">${n} active employee${n!==1?'s':''} × ₹${rate} = <b style="color:var(--teal);font-size:15px">₹${amt}/month</b></div>
  <div style="display:flex;flex-direction:column;align-items:center;background:var(--bg);border-radius:16px;padding:20px;margin-top:16px">
   <div style="font-size:12px;font-weight:800;color:var(--sub);letter-spacing:.5px;margin-bottom:10px">SCAN WITH ANY UPI APP</div>
   <div id="upiQr" style="background:#fff;padding:12px;border-radius:14px"></div>
   <div style="margin-top:12px;font-size:14px;font-weight:800">Paying to: <span style="color:var(--teal)">Hamara Staff</span></div>
   <div class="num" style="font-size:13px;color:var(--sub);margin-top:2px">${HS_UPI}
    <button onclick="navigator.clipboard&&navigator.clipboard.writeText(HS_UPI).then(()=>toast('UPI ID copied ✓'))" style="cursor:pointer;font-size:12px;background:var(--teal-soft);color:var(--teal);border-radius:8px;padding:4px 9px;font-weight:800;margin-left:6px">Copy</button>
   </div>
   <a href="${upiLink}" class="btn green" style="margin-top:14px;text-decoration:none">📲 Pay ₹${amt} via UPI App</a>
  </div>
  <p class="muted" style="font-size:12.5px;line-height:1.7;margin-top:14px">After payment, send the screenshot to <b>info@hamarastaff.com</b> (or WhatsApp) with your portal code <b>${HS_CODE.toUpperCase()}</b>. Your ${title} plan will be activated the same day.</p>`;
 $('overlay').classList.add('show');
 try{new QRCode(document.getElementById('upiQr'),{text:upiLink,width:190,height:190,correctLevel:QRCode.CorrectLevel.M})}catch(e){document.getElementById('upiQr').innerHTML='<div style="padding:20px;font-size:12px;color:var(--sub)">QR unavailable — use the UPI ID above</div>'}
}

/* ---------- HOD & Excel ---------- */
async function hodApprove(){try{await api('hod_approve');toast('Attendance approved by HOD ✓ Sent to payroll');await loadAll(true)}catch(e){toast('Could not approve')}}
function downloadExcel(){
 if(!D)return;
 const today=new Date().toLocaleDateString('en-IN');
 const att=[["Date","Employee","Attendance","Day Start","Start Location","Day End"]];
 D.employees.forEach(e=>{const d=e.day;att.push([today,e.name,d?'Present':'Absent',d?d.startedAt:'-',d?`${d.startLoc.area} (${d.startLoc.lat}, ${d.startLoc.lng})`:'-',d&&d.endedAt?d.endedAt:'-'])});
 const act=[["Date","Employee","Client / Doctor","Hospital","Purpose","Task Status","Reached At","Reached Location","Closed At","Outcome","Client Remarks","Report Sent Via"]];
 D.tasks.forEach(t=>{const r=t.timeline.find(x=>x.type==='reach');const c=t.timeline.find(x=>x.type==='close');
  act.push([t.createdAt?t.createdAt.slice(0,10):today,t.empName,t.doctor,t.hospital,t.purpose,t.status.toUpperCase(),r?r.t:'-',r&&r.loc&&r.loc.lat?`${r.loc.area} (${r.loc.lat}, ${r.loc.lng})`:'-',c?c.t:'-',t.report?t.report.outcome:'-',t.report?t.report.remarks:'-',t.report&&t.report.sent.length?t.report.sent.join(' + '):'-'])});
 const mon=[["Month","Employee","Working Days","Present","Absent","Attendance %","HOD Approval"]];
 D.monthly.forEach(m=>mon.push([D.monthLabel,m.name,m.wd,m.present,m.absent,m.pct+"%",D.approved?"Approved for Payroll":"Pending"]));
 const wb=XLSX.utils.book_new();
 const w1=XLSX.utils.aoa_to_sheet(att);w1['!cols']=[{wch:12},{wch:16},{wch:11},{wch:10},{wch:34},{wch:10}];
 const w2=XLSX.utils.aoa_to_sheet(act);w2['!cols']=[{wch:12},{wch:15},{wch:18},{wch:16},{wch:20},{wch:11},{wch:10},{wch:34},{wch:10},{wch:22},{wch:44},{wch:15}];
 const w3=XLSX.utils.aoa_to_sheet(mon);w3['!cols']=[{wch:11},{wch:16},{wch:13},{wch:9},{wch:8},{wch:13},{wch:20}];
 XLSX.utils.book_append_sheet(wb,w1,"Attendance");
 XLSX.utils.book_append_sheet(wb,w2,"Daily Activity");
 XLSX.utils.book_append_sheet(wb,w3,"Monthly Summary");
 XLSX.writeFile(wb,`MEDCY_Report_${new Date().toISOString().slice(0,10)}.xlsx`);
 toast('Excel report downloaded ✓');
}
boot();

</script>
</body>
</html>
