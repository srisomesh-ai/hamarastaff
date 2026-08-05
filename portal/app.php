<?php require __DIR__ . '/boot.php'; $CN = htmlspecialchars(COMPANY_NAME);
if (PLAN === 'trial' && TRIAL_EXPIRED) trial_lock_page($CN);
if (PLAN !== 'trial' && SUB_EXPIRED) sub_lock_page($CN); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title><?= $CN ?> — MR Field Visit Demo</title>
<link rel="icon" type="image/png" href="/assets/favicon.png?v=2">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js">
function hodApprove(){S.hodApproved=true;toast('Attendance approved by HOD \u2713 Sent to payroll');renderMgr()}
/* =============== EXCEL REPORT =============== */
function downloadExcel(){
 const today=new Date().toLocaleDateString('en-IN');
 const att=[["Date","Employee","Attendance","Day Start","Start Location","Day End"]];
 S.employees.forEach(e=>{const d=S.day[e.id];att.push([today,e.name,d?'Present':'Absent',d?d.startedAt:'-',d?`${d.startLoc.area} (${d.startLoc.lat}, ${d.startLoc.lng})`:'-',d&&d.endedAt?d.endedAt:'-'])});
 const act=[["Date","Employee","Client / Doctor","Hospital","Purpose","Task Status","Reached At","Reached Location","Closed At","Outcome","Client Remarks","Report Sent Via"]];
 S.tasks.forEach(t=>{const e=emp(t.empId);const reach=t.timeline.find(x=>x.type==='reach');const close=t.timeline.find(x=>x.type==='close');
  act.push([today,e.name,t.doctor,t.hospital,t.purpose,t.status.toUpperCase(),reach?reach.t:'-',reach&&reach.loc?`${reach.loc.area} (${reach.loc.lat}, ${reach.loc.lng})`:'-',close?close.t:'-',t.report?t.report.outcome:'-',t.report?t.report.remarks:'-',t.report&&t.report.sent&&t.report.sent.length?t.report.sent.join(' + '):'-'])});
 const wb=XLSX.utils.book_new();
 const ws1=XLSX.utils.aoa_to_sheet(att);ws1['!cols']=[{wch:12},{wch:16},{wch:11},{wch:10},{wch:34},{wch:10}];
 const ws2=XLSX.utils.aoa_to_sheet(act);ws2['!cols']=[{wch:12},{wch:15},{wch:18},{wch:16},{wch:18},{wch:11},{wch:10},{wch:34},{wch:10},{wch:22},{wch:42},{wch:14}];
 XLSX.utils.book_append_sheet(wb,ws1,"Attendance");
 XLSX.utils.book_append_sheet(wb,ws2,"Daily Activity");
 const WD=26;const mon=[["Month","Employee","Working Days","Present","Leave","Absent","Attendance %","HOD Approval"]];
 S.employees.forEach(e=>{const m=S.month[e.id];mon.push(["July 2026",e.name,WD,m.p,m.l,WD-m.p-m.l,Math.round(m.p/WD*100)+"%",S.hodApproved?"Approved for Payroll":"Pending"])});
 const ws3=XLSX.utils.aoa_to_sheet(mon);ws3['!cols']=[{wch:11},{wch:16},{wch:13},{wch:9},{wch:7},{wch:8},{wch:13},{wch:20}];
 XLSX.utils.book_append_sheet(wb,ws3,"Monthly Summary");
 XLSX.writeFile(wb,`MEDCY_Daily_Report_${new Date().toISOString().slice(0,10)}.xlsx`);
 toast('Excel report downloaded \u2713');
}
</script>
<style>
:root{
  --teal:#0E6B63; --teal-dark:#0A4F49; --teal-soft:#E3F0EE;
  --ink:#13211F; --sub:#5B6E6B; --line:#DCE7E5;
  --bg:#F2F7F6; --card:#FFFFFF;
  --amber:#C77800; --amber-soft:#FFF3E0;
  --blue:#1859A8; --blue-soft:#E4EEFA;
  --green:#1E7B34; --green-soft:#E3F3E7;
  --red:#B3261E;
  --r:16px;
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent}
body{font-family:'Manrope',sans-serif;background:#D8E3E1;color:var(--ink);display:flex;justify-content:center;min-height:100vh}
.phone{width:100%;max-width:420px;background:var(--bg);min-height:100vh;position:relative;display:flex;flex-direction:column;box-shadow:0 0 40px rgba(14,107,99,.15)}
.num{font-family:'Space Grotesk',monospace}
h1,h2,h3{font-weight:800}
button{font-family:inherit;border:none;cursor:pointer;background:none}
input,select,textarea{font-family:inherit;font-size:15px;width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:12px;background:#fff;color:var(--ink)}
input:focus,select:focus,textarea:focus{outline:2px solid var(--teal);border-color:var(--teal)}
label{display:block;font-size:12.5px;font-weight:700;color:var(--sub);margin:14px 0 6px;text-transform:uppercase;letter-spacing:.4px}
.screen{display:none;flex:1;flex-direction:column;min-height:100vh}
.screen.active{display:flex}

/* ---------- LOGIN ---------- */
#login{background:linear-gradient(160deg,var(--teal-dark) 0%,var(--teal) 55%,#12857B 100%);color:#fff;justify-content:center;padding:32px 24px}
.logo-badge{width:64px;height:64px;border-radius:18px;background:#fff;display:flex;align-items:center;justify-content:center;font-size:30px;margin-bottom:18px}
#login h1{font-size:28px;line-height:1.15}
#login .tag{opacity:.85;margin:8px 0 34px;font-size:14.5px}
.role-card{background:rgba(255,255,255,.10);border:1.5px solid rgba(255,255,255,.25);border-radius:var(--r);padding:18px;display:flex;gap:14px;align-items:center;margin-bottom:14px;color:#fff;width:100%;text-align:left;transition:.15s}
.role-card:active{transform:scale(.98);background:rgba(255,255,255,.2)}
.role-ic{font-size:26px;width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.role-card b{font-size:16px;display:block}
.role-card span{font-size:13px;opacity:.8}
.demo-note{font-size:12px;opacity:.7;text-align:center;margin-top:22px}

/* ---------- SHELL ---------- */
.topbar{background:var(--teal);color:#fff;padding:16px 18px 14px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:20}
.topbar .avatar{width:38px;height:38px;border-radius:50%;background:#fff;color:var(--teal);font-weight:800;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
.topbar .who{flex:1;min-width:0}
.topbar .who b{font-size:15.5px;display:block}
.topbar .who span{font-size:12px;opacity:.85}
.topbar .out{color:#fff;font-size:12.5px;font-weight:700;background:rgba(255,255,255,.15);padding:8px 12px;border-radius:10px}
.content{flex:1;padding:16px 16px 96px;overflow-y:auto}
.bottomnav{position:sticky;bottom:0;background:#fff;border-top:1px solid var(--line);display:flex;z-index:20}
.bottomnav button{flex:1;padding:10px 0 12px;font-size:11px;font-weight:700;color:var(--sub);display:flex;flex-direction:column;align-items:center;gap:3px}
.bottomnav button .i{font-size:20px}
.bottomnav button.on{color:var(--teal)}
.tabpane{display:none}.tabpane.on{display:block}

/* ---------- CARDS ---------- */
.card{background:var(--card);border:1px solid var(--line);border-radius:var(--r);padding:16px;margin-bottom:14px}
.card h3{font-size:15px;margin-bottom:10px}
.sec-title{font-size:13px;font-weight:800;color:var(--sub);text-transform:uppercase;letter-spacing:.6px;margin:20px 4px 10px}
.pill{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:800;padding:4px 10px;border-radius:99px}
.pill.open{background:var(--amber-soft);color:var(--amber)}
.pill.reached{background:var(--blue-soft);color:var(--blue)}
.pill.closed{background:var(--green-soft);color:var(--green)}
.pill.present{background:var(--green-soft);color:var(--green)}
.pill.absent{background:#FBE9E7;color:var(--red)}
.btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:14px;border-radius:14px;font-size:15px;font-weight:800;transition:.15s}
.btn:active{transform:scale(.98)}
.btn.primary{background:var(--teal);color:#fff}
.btn.blue{background:var(--blue);color:#fff}
.btn.green{background:var(--green);color:#fff}
.btn.ghost{background:#fff;border:1.5px solid var(--line);color:var(--ink)}
.btn.sm{padding:10px;font-size:13.5px;border-radius:11px}
.btn[disabled]{opacity:.5}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:10px}

/* day strip */
.daycard{background:linear-gradient(135deg,var(--teal) 0%,#128A80 100%);color:#fff;border:none}
.daycard .big{font-size:22px;font-weight:800}
.daycard .locline{font-size:12.5px;opacity:.9;margin-top:4px}
.daycard .btn{margin-top:14px;background:#fff;color:var(--teal)}

/* task list */
.task{background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:14px 16px;margin-bottom:12px;width:100%;text-align:left;display:block}
.task:active{background:var(--teal-soft)}
.task .t-top{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px}
.task b{font-size:15px}
.task .meta{font-size:12.5px;color:var(--sub);line-height:1.5}
.task .who-chip{font-size:11px;font-weight:800;color:var(--teal);background:var(--teal-soft);padding:2px 8px;border-radius:99px;margin-top:6px;display:inline-block}

/* stats */
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
.stat{background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 10px;text-align:center}
.stat .n{font-size:24px;font-weight:700;font-family:'Space Grotesk'}
.stat .l{font-size:10.5px;font-weight:800;color:var(--sub);text-transform:uppercase;letter-spacing:.4px;margin-top:2px}
.stat.a .n{color:var(--amber)}.stat.b .n{color:var(--blue)}.stat.c .n{color:var(--green)}

/* ---------- TIMELINE (signature) ---------- */
.trail{position:relative;padding-left:26px}
.trail::before{content:'';position:absolute;left:8px;top:6px;bottom:6px;width:2px;background:linear-gradient(var(--teal) 60%,var(--line))}
.tr-item{position:relative;padding-bottom:18px}
.tr-item:last-child{padding-bottom:2px}
.tr-item::before{content:'';position:absolute;left:-24px;top:3px;width:12px;height:12px;border-radius:50%;background:#fff;border:3px solid var(--teal)}
.tr-item.start::before{background:var(--teal)}
.tr-item.close::before{border-color:var(--green);background:var(--green)}
.tr-item .tr-time{font-family:'Space Grotesk';font-size:12.5px;font-weight:700;color:var(--teal)}
.tr-item .tr-main{font-size:14px;font-weight:700;margin:1px 0 2px}
.tr-item .tr-sub{font-size:12.5px;color:var(--sub);line-height:1.5}
.tr-item .tr-sub .loc{color:var(--blue);font-weight:600}
.remark-box{background:var(--teal-soft);border-radius:10px;padding:8px 10px;font-size:12.5px;margin-top:6px;color:var(--teal-dark)}

/* detail header */
.detail-head{background:#fff;border-bottom:1px solid var(--line);padding:14px 16px;display:flex;gap:12px;align-items:center;position:sticky;top:0;z-index:15}
.back{font-size:20px;font-weight:800;color:var(--teal);padding:4px 8px}
.detail-head b{font-size:16px}

/* stepper */
.stepper{display:flex;align-items:center;margin:4px 0 18px}
.step{flex:1;text-align:center;font-size:10.5px;font-weight:800;color:var(--sub);text-transform:uppercase;letter-spacing:.3px}
.step .dot{width:26px;height:26px;border-radius:50%;background:#fff;border:2px solid var(--line);margin:0 auto 5px;display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--sub)}
.step.done .dot{background:var(--green);border-color:var(--green);color:#fff}
.step.now .dot{border-color:var(--teal);color:var(--teal)}
.step.now{color:var(--teal)}
.step-line{height:2px;flex:0 0 18px;background:var(--line);margin-bottom:18px}

/* chips */
.chips{display:flex;flex-wrap:wrap;gap:8px}
.chip{border:1.5px solid var(--line);border-radius:99px;padding:8px 14px;font-size:13px;font-weight:700;background:#fff;color:var(--sub)}
.chip.on{background:var(--teal);border-color:var(--teal);color:#fff}
.seg{display:flex;background:#E9F1EF;border-radius:12px;padding:4px;gap:4px}
.seg button{flex:1;padding:9px;border-radius:9px;font-size:13px;font-weight:800;color:var(--sub)}
.seg button.on{background:#fff;color:var(--teal);box-shadow:0 1px 4px rgba(0,0,0,.08)}
.switchrow{display:flex;justify-content:space-between;align-items:center;padding:12px 0}
.switchrow small{display:block;color:var(--sub);font-weight:500;font-size:12px;margin-top:2px}
.sw{width:48px;height:28px;border-radius:99px;background:#CBD9D6;position:relative;transition:.2s;flex-shrink:0}
.sw::after{content:'';position:absolute;width:22px;height:22px;border-radius:50%;background:#fff;top:3px;left:3px;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
.sw.on{background:var(--teal)}.sw.on::after{left:23px}

/* toast + fab */
.toast{position:fixed;bottom:90px;left:50%;transform:translateX(-50%) translateY(20px);background:var(--ink);color:#fff;padding:12px 18px;border-radius:12px;font-size:13.5px;font-weight:700;opacity:0;transition:.25s;z-index:99;max-width:88%;text-align:center;pointer-events:none}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
.fab{position:fixed;bottom:78px;right:calc(50% - 210px + 18px);width:56px;height:56px;border-radius:18px;background:var(--teal);color:#fff;font-size:26px;box-shadow:0 6px 18px rgba(14,107,99,.4);z-index:25;display:flex;align-items:center;justify-content:center}
@media(max-width:768px){
 .phone{max-width:100%;box-shadow:none}
 .fab{right:18px}
}
.empty{text-align:center;color:var(--sub);padding:40px 20px;font-size:14px}
.empty .i{font-size:38px;display:block;margin-bottom:10px}
.emp-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--line);width:100%;text-align:left}
.emp-row:last-child{border-bottom:none}
.emp-row .avatar{width:42px;height:42px;border-radius:50%;background:var(--teal-soft);color:var(--teal);font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.emp-row b{font-size:14.5px;display:block}
.emp-row span{font-size:12px;color:var(--sub)}
.emp-row .st{margin-left:auto}
.report-grid{display:grid;grid-template-columns:auto 1fr;gap:6px 12px;font-size:13px}
.report-grid dt{color:var(--sub);font-weight:700}
.report-grid dd{font-weight:600}
.sendline{display:flex;align-items:center;gap:8px;background:var(--green-soft);color:var(--green);font-size:12.5px;font-weight:700;border-radius:10px;padding:10px 12px;margin-top:10px}

</style>
</head>
<body>

<div class="phone">

<!-- ============ EMPLOYEE SHELL ============ -->
<div class="screen active" id="empShell">
  <div class="topbar">
    <div class="avatar">RK</div>
    <div class="who"><b>Ravi Kumar</b><span>Field Staff · <?= $CN ?></span></div>
    <button class="out" onclick="logout()">Logout</button>
  </div>
  <div class="content">
    <!-- HOME -->
    <div class="tabpane on" id="empHome">
      <div class="card daycard" id="dayCard"></div>
      <div class="stats" id="empStats"></div>
      <div class="sec-title">Today's Visits</div>
      <div id="empTodayList"></div>
    </div>
    <!-- TASKS -->
    <div class="tabpane" id="empTasks">
      <div class="seg" style="margin-bottom:14px">
        <button class="on" onclick="empFilter('all',this)">All</button>
        <button onclick="empFilter('open',this)">Open</button>
        <button onclick="empFilter('reached',this)">Reached</button>
        <button onclick="empFilter('closed',this)">Closed</button>
      </div>
      <div id="empTaskList"></div>
    </div>
  </div>
  <button class="fab" id="empFab" onclick="openNewTask('emp')">＋</button>
  <div class="bottomnav">
    <button class="on" onclick="empTab('empHome',this)"><span class="i">🏠</span>Home</button>
    <button onclick="empTab('empTasks',this)"><span class="i">📋</span>My Visits</button>
  </div>
</div>

<!-- ============ NEW TASK ============ -->
<div class="screen" id="newTask">
  <div class="detail-head"><button class="back" onclick="closeNewTask()">‹</button><b>New Visit Task</b></div>
  <div class="content">
    <div id="assignToWrap" style="display:none">
      <label>Assign To (MR)</label>
      <select id="ntEmp"></select>
    </div>
    <label>Doctor / Client Name</label>
    <input id="ntDoctor" placeholder="e.g. Dr. K. Prasad">
    <label>Hospital / Clinic</label>
    <input id="ntHospital" placeholder="e.g. Apollo Clinic, Waltair">
    <label>Area</label>
    <input id="ntArea" placeholder="e.g. Dwaraka Nagar">
    <label>Purpose of Visit</label>
    <select id="ntPurpose">
      <option>Product Demo</option>
      <option>New Product Introduction</option>
      <option>Follow-up Visit</option>
      <option>Sample Delivery</option>
      <option>Order Collection</option>
    </select>
    <label>Planned Time</label>
    <input id="ntTime" type="time">
    <label>Client Email (for visit report)</label>
    <input id="ntEmail" type="email" placeholder="doctor@clinic.com">
    <label>Client WhatsApp Number</label>
    <input id="ntPhone" type="tel" placeholder="+91 9XXXXXXXXX">
    <div style="height:16px"></div>
    <button class="btn primary" onclick="saveNewTask()">Create Visit Task</button>
  </div>
</div>

<!-- ============ TASK DETAIL (EMPLOYEE) ============ -->
<div class="screen" id="taskDetail">
  <div class="detail-head"><button class="back" onclick="backFromDetail()">‹</button><b>Visit Details</b></div>
  <div class="content" id="taskDetailBody"></div>
</div>

<!-- ============ VISIT FORM ============ -->
<div class="screen" id="visitForm">
  <div class="detail-head"><button class="back" onclick="history.back()">‹</button><b>Visit Report &amp; Close</b></div>
  <div class="content">
    <div class="card" id="vfHead"></div>
    <label>Person Met</label>
    <input id="vfMet" placeholder="e.g. Dr. Prasad / Pharmacy In-charge">
    <label>Products Discussed</label>
    <div class="chips" id="vfProducts"></div>
    <label>Demo Given?</label>
    <div class="seg" id="vfDemo">
      <button class="on" onclick="segPick(this)">Yes</button>
      <button onclick="segPick(this)">No</button>
    </div>
    <label>Samples Given (Qty)</label>
    <input id="vfSamples" type="number" placeholder="0" min="0">
    <label>Visit Outcome</label>
    <select id="vfOutcome">
      <option>Interested — wants pricing</option>
      <option>Follow-up needed</option>
      <option>Order placed</option>
      <option>Not interested</option>
    </select>
    <label>Client Remarks</label>
    <textarea id="vfRemarks" rows="3" placeholder="What did the client say?"></textarea>
    <label>Next Visit Date (optional)</label>
    <input id="vfNext" type="date">
    <div class="card" style="margin-top:18px">
      <div class="switchrow">
        <div><b style="font-size:14px">Attach my current location</b><small>GPS lat/long is stamped on the report</small></div>
        <button class="sw on" id="vfLocSw" onclick="this.classList.toggle('on')"></button>
      </div>
      <div class="switchrow" style="border-top:1px solid var(--line)">
        <div><b style="font-size:14px">Email report to client</b><small id="vfEmailTo">—</small></div>
        <button class="sw on" id="vfEmailSw" onclick="this.classList.toggle('on')"></button>
      </div>
      <div class="switchrow" style="border-top:1px solid var(--line)">
        <div><b style="font-size:14px">WhatsApp report to client</b><small id="vfWaTo">—</small></div>
        <button class="sw" id="vfWaSw" onclick="this.classList.toggle('on')"></button>
      </div>
    </div>
    <button class="btn green" onclick="submitVisit()">✓ Submit Report &amp; Close Task</button>
    <div style="height:20px"></div>
  </div>
</div>

<div class="toast" id="toast"></div>
</div>

<script>
/* =============== MEDCY MOBILE — API DRIVEN =============== */
const PRODUCTS=['Cardio-Z 40','Neurofast SR','GlucoCare Plus','OrthoFlex Gel','PediaVit Drops'];
const $=id=>document.getElementById(id);
function toast(msg){const t=$('toast');t.textContent=msg;t.classList.add('show');clearTimeout(t._h);t._h=setTimeout(()=>t.classList.remove('show'),2600)}
async function api(action,data={}){
 const r=await fetch('api/api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action,...data})});
 let j=null;try{j=await r.json()}catch(e){}
 if(!j||!j.ok){
  if(j&&j.error==='auth'){location.href='./';throw new Error('auth')}
  throw new Error(j&&j.error?j.error:'network')
 }
 return j.data;
}
function getLocation(cb){
 if(!navigator.geolocation){toast('This device does not support GPS');return}
 toast('Getting GPS location…');
 navigator.geolocation.getCurrentPosition(
  p=>cb({lat:p.coords.latitude.toFixed(5),lng:p.coords.longitude.toFixed(5),area:'Live GPS'}),
  err=>{
   if(err.code===1)toast('Location permission denied — please allow location for this site and try again');
   else if(err.code===3)toast('GPS timed out — move to open sky / near a window and try again');
   else toast('Could not get GPS location — please try again');
  },
  {enableHighAccuracy:true,timeout:20000,maximumAge:15000}
 );
}
function pillHTML(st){return `<span class="pill ${st}">${st==='open'?'● Open':st==='reached'?'◉ Reached':'✓ Closed'}</span>`}


/* ---- push token registration (from Android app) ---- */
window.hsSetPushToken=function(t){try{localStorage.setItem('hs_push',t)}catch(e){};hsRegisterPush()};
function hsRegisterPush(){let t=null;try{t=localStorage.getItem('hs_push')}catch(e){};if(!t)return;
 fetch('api/api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'push_register',token:t})}).catch(()=>{})}
let ME=null, MYDAY=null, TASKS=[], empF='all', curTask=null;
function dayActive(){return MYDAY && !MYDAY.endedAt}

async function boot(){
 try{ME=await api('me')}catch(e){return}
 if(ME.role!=='emp'){location.href='admin.html';return}
 const b=document.querySelector('#empShell .who b');
 const sp=document.querySelector('#empShell .who span');
 const av=document.querySelector('#empShell .topbar .avatar');
 if(b)b.textContent=ME.name;
 if(sp)sp.textContent=ME.emp_code+' · <?= $CN ?>';
 if(av)av.textContent=ME.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();
 await refresh();
}
async function refresh(){hsRegisterPush();
 try{[MYDAY,TASKS]=await Promise.all([api('day_get'),api('task_list')]);}
 catch(e){toast('Network error — check connection');return}
 renderEmp();
 if(curTask){curTask=TASKS.find(t=>t.id===curTask.id)||null;
  if(curTask&&$('taskDetail').classList.contains('active'))renderTaskDetail();}
}

function show(id,push=true){document.querySelectorAll('.screen').forEach(s=>s.classList.remove('active'));$(id).classList.add('active');window.scrollTo(0,0);
 if(push&&id!=='empShell')history.pushState({s:id},'');}
window.addEventListener('popstate',()=>{
 const cur=document.querySelector('.screen.active');
 if(!cur)return;
 if(cur.id==='visitForm'){show('taskDetail',false);if(curTask)renderTaskDetail();}
 else if(cur.id==='taskDetail'||cur.id==='newTask'){curTask=null;show('empShell',false);renderEmp();}
});
function empTab(id,btn){document.querySelectorAll('#empShell .tabpane').forEach(p=>p.classList.remove('on'));$(id).classList.add('on');document.querySelectorAll('#empShell .bottomnav button').forEach(b=>b.classList.remove('on'));btn.classList.add('on');renderEmp()}
function empFilter(f,btn){empF=f;btn.parentElement.querySelectorAll('button').forEach(b=>b.classList.remove('on'));btn.classList.add('on');renderEmp()}

function renderEmp(){
 const d=MYDAY, first=(ME?ME.name.split(' ')[0]:'');
 $('dayCard').innerHTML = d
 ? `<div style="font-size:12px;opacity:.85;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Day Started</div>
    <div class="big num">${d.startedAt}</div>
    <div class="locline">📍 ${d.startLoc.area} · ${d.startLoc.lat}, ${d.startLoc.lng}</div>
    ${d.endedAt?`<div class="locline">🏁 Day ended at ${d.endedAt}</div>`:`<button class="btn" onclick="endDay()">End My Day</button>`}`
 : `<div style="font-size:12px;opacity:.85;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Good morning${first?', '+first:''}</div>
    <div class="big">Ready to start?</div>
    <div class="locline">Your start time &amp; location will be recorded</div>
    <button class="btn" onclick="startDay()">▶ Start My Day</button>`;
 const o=TASKS.filter(t=>t.status==='open').length,r=TASKS.filter(t=>t.status==='reached').length,c=TASKS.filter(t=>t.status==='closed').length;
 $('empStats').innerHTML=`<div class="stat a"><div class="n">${o}</div><div class="l">Open</div></div><div class="stat b"><div class="n">${r}</div><div class="l">Reached</div></div><div class="stat c"><div class="n">${c}</div><div class="l">Closed</div></div>`;
 $('empTodayList').innerHTML=TASKS.length?TASKS.map(taskCard).join(''):`<div class="empty"><span class="i">🗓️</span>No visits yet.<br>Tap ＋ to create your first visit task.</div>`;
 const list=empF==='all'?TASKS:TASKS.filter(t=>t.status===empF);
 $('empTaskList').innerHTML=list.length?list.map(taskCard).join(''):`<div class="empty"><span class="i">📭</span>Nothing here.</div>`;
}
function taskCard(t){
 return `<button class="task" onclick="openTask(${t.id})">
  <div class="t-top"><b>${t.doctor}</b>${pillHTML(t.status)}</div>
  <div class="meta">${t.hospital||'—'} · ${t.area||'—'}<br>${t.purpose} · Planned ${t.planned||'—'}</div>
  <span class="who-chip" style="background:#F0F0F0;color:var(--sub)">Assigned by: ${t.createdBy}</span>
 </button>`;
}
function startDay(){getLocation(async loc=>{
 try{MYDAY=await api('day_start',loc);toast('Day started — time & location saved ✓');renderEmp()}
 catch(e){toast(e.message&&e.message.length>12?e.message:'Could not save — try again')}})}
async function endDay(){try{MYDAY=await api('day_end');toast('Day ended. Good work! 🏁');renderEmp()}catch(e){toast(e.message&&e.message.length>12?e.message:'Could not save — try again')}}

/* ---- new task ---- */
function openNewTask(){['ntDoctor','ntHospital','ntArea','ntEmail','ntPhone'].forEach(i=>$(i).value='');show('newTask')}
function closeNewTask(){history.back()}
async function saveNewTask(){
 if(!$('ntDoctor').value.trim())return toast('Enter doctor / client name');
 try{
  await api('task_add',{doctor:$('ntDoctor').value,hospital:$('ntHospital').value,area:$('ntArea').value,purpose:$('ntPurpose').value,planned:$('ntTime').value,email:$('ntEmail').value,phone:$('ntPhone').value});
  toast('Visit task saved ✓');history.back();await refresh();
 }catch(e){toast(e.message&&e.message.length>12?e.message:'Could not save — try again')}
}

/* ---- task detail ---- */
function openTask(id){curTask=TASKS.find(t=>t.id===id);renderTaskDetail();show('taskDetail')}
function backFromDetail(){history.back()}
function trItem(x){return `<div class="tr-item ${x.type==='start'?'start':x.type==='close'?'close':''}">
 <div class="tr-time">${x.t}</div><div class="tr-main">${x.main}</div>
 ${x.loc&&x.loc.lat?`<div class="tr-sub">📍 <span class="loc">${x.loc.area} (${x.loc.lat}, ${x.loc.lng})</span></div>`:''}
 ${x.note?`<div class="remark-box">"${x.note}"</div>`:''}
</div>`}
function renderTaskDetail(){
 const t=curTask;
 const stepIdx=t.status==='open'?1:t.status==='reached'?2:3;
 const steps=['Created','Reached','Closed'].map((s,i)=>`<div class="step ${i+1<stepIdx?'done':i+1===stepIdx?'now':''}"><div class="dot">${i+1<stepIdx?'✓':i+1}</div>${s}</div>`).join('<div class="step-line"></div>');
 $('taskDetailBody').innerHTML=`
  <div class="stepper">${steps}</div>
  <div class="card">
   <div class="t-top"><b style="font-size:17px">${t.doctor}</b>${pillHTML(t.status)}</div>
   <div class="meta" style="font-size:13.5px;color:var(--sub);line-height:1.6">${t.hospital||'—'} · ${t.area||'—'}<br>${t.purpose} · Planned ${t.planned||'—'}<br>✉️ ${t.email||'no email'} · 📱 ${t.phone||'no WhatsApp'}</div>
  </div>
  ${t.timeline.length?`<div class="card"><h3>Activity Trail</h3><div class="trail">${t.timeline.map(trItem).join('')}</div></div>`:''}
  ${t.status==='open'?(dayActive()
    ?`<button class="btn blue" onclick="markReached()">📍 I've Reached — Update Location</button>`
    :`<button class="btn ghost" onclick="toast(MYDAY&&MYDAY.endedAt?'Your day has ended — visits cannot be updated':'Start your day first from the Home tab')" style="color:var(--sub)">🔒 Start your day to mark Reached</button>`):''}
  ${t.status==='reached'?(dayActive()
    ?`<button class="btn green" onclick="openVisitForm()">📝 Fill Visit Form &amp; Close</button>`
    :`<button class="btn ghost" onclick="toast(MYDAY&&MYDAY.endedAt?'Your day has ended — visits cannot be updated':'Start your day first from the Home tab')" style="color:var(--sub)">🔒 Start your day to close this visit</button>`):''}
  ${t.status==='closed'?`<div class="sendline">✓ Task closed at ${t.report?t.report.closedAt:''} · Report ${t.report&&t.report.sent.length?t.report.sent.join(' + '):'saved'}</div>`:''}
 `;
}
function markReached(){
 getLocation(async loc=>{
  try{await api('task_reach',{id:curTask.id,...loc});toast('Reached location saved ✓');await refresh()}
  catch(e){toast(e.message&&e.message.length>12?e.message:'Could not save — try again')}
 });
}

/* ---- visit form ---- */
function openVisitForm(){
 $('vfHead').innerHTML=`<b style="font-size:16px">${curTask.doctor}</b><div class="meta" style="color:var(--sub);font-size:13px;margin-top:3px">${curTask.hospital||''} · ${curTask.purpose}</div>`;
 $('vfProducts').innerHTML=PRODUCTS.map(p=>`<button class="chip" onclick="this.classList.toggle('on')">${p}</button>`).join('');
 $('vfMet').value=curTask.doctor;$('vfSamples').value='';$('vfRemarks').value='';$('vfNext').value='';
 $('vfEmailTo').textContent=curTask.email||'no email on task';
 $('vfWaTo').textContent=curTask.phone||'no number on task';
 show('visitForm');
}
function segPick(btn){btn.parentElement.querySelectorAll('button').forEach(b=>b.classList.remove('on'));btn.classList.add('on')}
function submitVisit(){
 const remarks=$('vfRemarks').value.trim();
 if(!remarks)return toast('Please enter client remarks');
 const finish=async(loc)=>{
  const sent=[];
  if($('vfEmailSw').classList.contains('on')&&curTask.email)sent.push('Email');
  if($('vfWaSw').classList.contains('on')&&curTask.phone)sent.push('WhatsApp');
  const payload={id:curTask.id,met:$('vfMet').value,
   products:[...$('vfProducts').querySelectorAll('.chip.on')].map(c=>c.textContent),
   demo:$('vfDemo').querySelector('.on').textContent,samples:$('vfSamples').value||0,
   outcome:$('vfOutcome').value,remarks,next:$('vfNext').value,sent};
  if(loc)Object.assign(payload,loc);
  try{
   await api('task_close',payload);
   toast(sent.length?`Task closed ✓ Report ${sent.includes('Email')?'emailed':''}${sent.length>1?' + WhatsApp':sent.includes('WhatsApp')?'WhatsApp':''}`.trim():'Task closed ✓ Report saved');
   if(sent.includes('WhatsApp')&&curTask.phone){
    const txt=encodeURIComponent(`*<?= $CN ?> — Visit Summary*\nRep: ${ME.name}\nMet: ${payload.met}\nProducts: ${payload.products.join(', ')||'—'}\nDemo: ${payload.demo} · Samples: ${payload.samples}\nRemarks: ${remarks}${payload.next?'\nNext visit: '+payload.next:''}`);
    window.open('https://wa.me/'+curTask.phone.replace(/[^0-9]/g,'')+'?text='+txt,'_blank');
   }
   await refresh();history.back();
  }catch(e){toast(e.message&&e.message.length>12?e.message:'Could not save — try again')}
 };
 if($('vfLocSw').classList.contains('on'))getLocation(finish);else finish(null);
}

async function logout(){try{await api('logout')}catch(e){}location.href='./'}
boot();

</script>
</body>
</html>
