<?php
require __DIR__ . '/../boot.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

function out($data){ echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function fail($err,$code=400){ http_response_code($code); echo json_encode(['ok'=>false,'error'=>$err]); exit; }
function fmt($dt){ return $dt ? date('h:i a', strtotime($dt)) : null; }
function initials($name){ $p=preg_split('/\s+/',trim($name)); $i=''; foreach(array_slice($p,0,2) as $w) $i.=strtoupper(substr($w,0,1)); return $i?:'?'; }
function audit($actor,$action,$details=''){ try{ db()->prepare("INSERT INTO hs_audit_log (actor,action,details) VALUES (?,?,?)")->execute([$actor,$action,$details]); }catch(Exception $e){} }
function requireEmp(){ if(($_SESSION['role']??'')!=='emp' || ($_SESSION['tenant']??'')!==CODE) fail('auth',401); return (int)$_SESSION['emp_id']; }
function requireAdmin(){ if(PLAN==='starter' || ($_SESSION['role']??'')!=='admin' || ($_SESSION['tenant']??'')!==CODE) fail('auth',401); }
function actorName(){ return ($_SESSION['role']??'')==='admin' ? 'Admin' : ($_SESSION['emp_name'] ?? 'Unknown'); }

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $in['action'] ?? '';
if (PLAN === 'trial' && TRIAL_EXPIRED && $action !== 'logout') {
  fail('Your 7-day free trial has ended. Email info@hamarastaff.com to activate your account and continue.', 402);
}
if (PLAN !== 'trial' && SUB_EXPIRED && $action !== 'logout') {
  fail('Your plan expired on ' . date('d M Y', strtotime(PLAN_ENDS)) . '. Pay via UPI to Hamara Staff (srisomeshidfc@ybl) and email the screenshot to info@hamarastaff.com to renew.', 402);
}

try { $db = db(); } catch (Exception $e) { fail('Database not configured. Open api/install.php after filling config.php.', 500); }

/* ---------- task builder ---------- */
function buildTasks($empId=null){
  $db=db();
  $sql="SELECT t.*, e.name emp_name, e.emp_code FROM hs_tasks t JOIN hs_employees e ON e.id=t.emp_id";
  $args=[];
  if($empId){ $sql.=" WHERE t.emp_id=?"; $args[]=$empId; }
  $sql.=" ORDER BY t.created_at DESC";
  $st=$db->prepare($sql); $st->execute($args); $tasks=$st->fetchAll();
  if(!$tasks) return [];
  $ids=array_column($tasks,'id');
  $ph=implode(',',array_fill(0,count($ids),'?'));
  $evs=$db->prepare("SELECT * FROM hs_task_events WHERE task_id IN ($ph) ORDER BY event_time");
  $evs->execute($ids); $evByTask=[];
  foreach($evs->fetchAll() as $ev) $evByTask[$ev['task_id']][]=$ev;
  $rps=$db->prepare("SELECT * FROM hs_visit_reports WHERE task_id IN ($ph)");
  $rps->execute($ids); $rpByTask=[];
  foreach($rps->fetchAll() as $r) $rpByTask[$r['task_id']]=$r;
  $outT=[];
  foreach($tasks as $t){
    $tl=[];
    foreach($evByTask[$t['id']]??[] as $ev){
      $tl[]=['t'=>fmt($ev['event_time']),'type'=>$ev['type'],
        'main'=>$ev['type']==='reach'?('Reached '.($t['hospital']?:$t['doctor'])):'Visit closed — report submitted',
        'loc'=>$ev['lat']?['lat'=>$ev['lat'],'lng'=>$ev['lng'],'area'=>$ev['area']]:null];
    }
    $rep=null;
    if(isset($rpByTask[$t['id']])){ $r=$rpByTask[$t['id']];
      $rep=['met'=>$r['met'],'products'=>json_decode($r['products']?:'[]',true),
        'demo'=>$r['demo_given'],'samples'=>(int)$r['samples'],'outcome'=>$r['outcome'],
        'remarks'=>$r['remarks'],'next'=>$r['next_visit'],'locAttached'=>(bool)$r['loc_attached'],
        'sent'=>$r['sent_via']?explode('+',$r['sent_via']):[],'closedAt'=>fmt($r['closed_at'])];
    }
    $init=initials($t['emp_name']);
    $outT[]=['id'=>(int)$t['id'],'empId'=>(int)$t['emp_id'],'empName'=>$t['emp_name'],'empInit'=>$init,
      'doctor'=>$t['doctor'],'hospital'=>$t['hospital'],'area'=>$t['area'],'purpose'=>$t['purpose'],
      'planned'=>$t['planned_time'],'email'=>$t['client_email'],'phone'=>$t['client_phone'],
      'status'=>$t['status'],'createdBy'=>$t['created_by'],'createdAt'=>$t['created_at'],'timeline'=>$tl,'report'=>$rep];
  }
  return $outT;
}
function dayFor($empId,$date=null){
  $date=$date?:date('Y-m-d');
  $st=db()->prepare("SELECT * FROM hs_attendance WHERE emp_id=? AND att_date=?");
  $st->execute([$empId,$date]); $d=$st->fetch();
  if(!$d) return null;
  return ['startedAt'=>fmt($d['start_time']),'endedAt'=>fmt($d['end_time']),
    'startLoc'=>['lat'=>$d['start_lat'],'lng'=>$d['start_lng'],'area'=>$d['start_area']]];
}
function workingDays($ym){ // Mon–Sat
  [$y,$m]=explode('-',$ym); $days=(int)date('t',mktime(0,0,0,(int)$m,1,(int)$y)); $c=0;
  for($d=1;$d<=$days;$d++){ if(date('w',mktime(0,0,0,(int)$m,$d,(int)$y))!=0)$c++; }
  return $c;
}
function purgeOld(){
  $db=db(); $cut=date('Y-m-d H:i:s',strtotime('-'.RETENTION_DAYS.' days'));
  $db->prepare("DELETE FROM hs_attendance WHERE att_date < ?")->execute([date('Y-m-d',strtotime($cut))]);
  $old=$db->prepare("SELECT id FROM hs_tasks WHERE created_at < ?"); $old->execute([$cut]);
  $ids=array_column($old->fetchAll(),'id');
  if($ids){ $ph=implode(',',array_fill(0,count($ids),'?'));
    $db->prepare("DELETE FROM hs_task_events WHERE task_id IN ($ph)")->execute($ids);
    $db->prepare("DELETE FROM hs_visit_reports WHERE task_id IN ($ph)")->execute($ids);
    $db->prepare("DELETE FROM hs_tasks WHERE id IN ($ph)")->execute($ids);
  }
  $db->prepare("DELETE FROM hs_audit_log WHERE created_at < ?")->execute([$cut]);
}

/* ==================== ACTIONS ==================== */
switch($action){

case 'login': {
  $u=trim($in['username']??''); $p=$in['password']??''; $role=$in['role']??'emp';
  if($role==='admin'){
    if(PLAN==='starter') fail('The management panel is not included in your Starter plan. Contact HamaraStaff to upgrade to Professional.');
    if($u!==ADMIN_USER || $p!==ADMIN_PASS){ audit($u,'login_failed','admin'); fail('invalid'); }
    session_regenerate_id(true);
    $_SESSION=['role'=>'admin','tenant'=>CODE];
    audit('Admin','login','admin panel');
    out(['role'=>'admin']);
  }
  $st=$db->prepare("SELECT * FROM hs_employees WHERE LOWER(emp_code)=LOWER(?)");
  $st->execute([$u]); $e=$st->fetch();
  if(!$e || $e['password']!==$p){ audit($u,'login_failed','employee'); fail('invalid'); }
  if(!$e['active']){ audit($e['name'],'login_blocked','account disabled'); fail('disabled'); }
  session_regenerate_id(true);
  $_SESSION=['role'=>'emp','tenant'=>CODE,'emp_id'=>$e['id'],'emp_name'=>$e['name'],'emp_code'=>$e['emp_code']];
  audit($e['name'],'login','mobile app');
  out(['role'=>'emp','name'=>$e['name'],'emp_code'=>$e['emp_code']]);
}

case 'logout': audit(actorName(),'logout'); $_SESSION=[]; session_destroy(); out(true);

case 'me': {
  if(($_SESSION['tenant']??'')!==CODE) fail('auth',401);
  if(($_SESSION['role']??'')==='admin') out(['role'=>'admin']);
  if(($_SESSION['role']??'')==='emp') out(['role'=>'emp','name'=>$_SESSION['emp_name'],'emp_code'=>$_SESSION['emp_code']]);
  fail('auth',401);
}

/* ---------- EMPLOYEE ---------- */
case 'day_get': { $eid=requireEmp(); out(dayFor($eid)); }

case 'day_start': {
  $eid=requireEmp();
  $st=$db->prepare("INSERT IGNORE INTO hs_attendance (emp_id,att_date,start_time,start_lat,start_lng,start_area) VALUES (?,?,NOW(),?,?,?)");
  $st->execute([$eid,date('Y-m-d'),$in['lat']??'',$in['lng']??'',$in['area']??'']);
  audit($_SESSION['emp_name'],'day_start',($in['area']??'').' ('.($in['lat']??'').','.($in['lng']??'').')');
  out(dayFor($eid));
}

case 'day_end': {
  $eid=requireEmp();
  $db->prepare("UPDATE hs_attendance SET end_time=NOW() WHERE emp_id=? AND att_date=? AND end_time IS NULL")
     ->execute([$eid,date('Y-m-d')]);
  audit($_SESSION['emp_name'],'day_end','');
  out(dayFor($eid));
}

case 'task_list': { $eid=requireEmp(); out(buildTasks($eid)); }

case 'task_add': {
  $role=$_SESSION['role']??''; if($role!=='emp'&&$role!=='admin') fail('auth',401);
  $empId = $role==='admin' ? (int)($in['emp_id']??0) : (int)$_SESSION['emp_id'];
  if(!$empId || !trim($in['doctor']??'')) fail('missing');
  $st=$db->prepare("INSERT INTO hs_tasks (emp_id,doctor,hospital,area,purpose,planned_time,client_email,client_phone,created_by) VALUES (?,?,?,?,?,?,?,?,?)");
  $st->execute([$empId,trim($in['doctor']),$in['hospital']??'',$in['area']??'',$in['purpose']??'',$in['planned']??'',$in['email']??'',$in['phone']??'',$role==='admin'?'Manager':'Self']);
  audit(actorName(),'task_create',$in['doctor'].' / '.($in['hospital']??''));
  out(true);
}

case 'task_reach': {
  $eid=requireEmp(); $tid=(int)($in['id']??0);
  $st=$db->prepare("SELECT * FROM hs_tasks WHERE id=? AND emp_id=?"); $st->execute([$tid,$eid]);
  $t=$st->fetch(); if(!$t) fail('notfound',404);
  if($t['status']!=='open') fail('wrong_status');
  $db->prepare("UPDATE hs_tasks SET status='reached' WHERE id=?")->execute([$tid]);
  $db->prepare("INSERT INTO hs_task_events (task_id,type,event_time,lat,lng,area) VALUES (?,'reach',NOW(),?,?,?)")
     ->execute([$tid,$in['lat']??'',$in['lng']??'',$in['area']??'']);
  audit($_SESSION['emp_name'],'task_reach',$t['doctor'].' @ '.($in['area']??''));
  out(true);
}

case 'task_close': {
  $eid=requireEmp(); $tid=(int)($in['id']??0);
  $st=$db->prepare("SELECT * FROM hs_tasks WHERE id=? AND emp_id=?"); $st->execute([$tid,$eid]);
  $t=$st->fetch(); if(!$t) fail('notfound',404);
  if($t['status']==='closed') fail('wrong_status');
  $sent = is_array($in['sent']??null) ? implode('+',$in['sent']) : '';
  $db->prepare("UPDATE hs_tasks SET status='closed' WHERE id=?")->execute([$tid]);
  $db->prepare("INSERT INTO hs_task_events (task_id,type,event_time,lat,lng,area) VALUES (?,'close',NOW(),?,?,?)")
     ->execute([$tid,$in['lat']??'',$in['lng']??'',$in['area']??'']);
  $db->prepare("INSERT INTO hs_visit_reports (task_id,met,products,demo_given,samples,outcome,remarks,next_visit,loc_attached,sent_via,closed_at)
     VALUES (?,?,?,?,?,?,?,?,?,?,NOW())
     ON DUPLICATE KEY UPDATE met=VALUES(met)")
     ->execute([$tid,$in['met']??'',json_encode($in['products']??[]),$in['demo']??'No',(int)($in['samples']??0),
       $in['outcome']??'',$in['remarks']??'', ($in['next']??'')?:null, !empty($in['lat'])?1:0, $sent]);
  audit($_SESSION['emp_name'],'task_close',$t['doctor'].' — '.($in['outcome']??''));
  /* backend email to client (Hostinger mail) */
  if(in_array('Email',$in['sent']??[]) && filter_var($t['client_email'],FILTER_VALIDATE_EMAIL)){
    $body="Dear {$t['doctor']},\n\nThank you for your time today. Summary of our visit:\n\n"
      ."Representative: {$_SESSION['emp_name']} (".COMPANY_NAME.")\n"
      ."Purpose: {$t['purpose']}\nPerson met: ".($in['met']??'')."\n"
      ."Products discussed: ".implode(', ',$in['products']??[])."\n"
      ."Demo given: ".($in['demo']??'No')."\nSamples: ".(int)($in['samples']??0)."\n"
      ."Remarks: ".($in['remarks']??'')."\n"
      .(!empty($in['next'])?"Next visit: {$in['next']}\n":'')
      ."\nRegards,\n".COMPANY_NAME." Field Team";
    @mail($t['client_email'],'Visit Summary — '.COMPANY_NAME,$body,
      "From: noreply@hamarastaff.com\r\nReply-To: noreply@hamarastaff.com");
  }
  out(true);
}

/* ---------- ADMIN ---------- */
case 'admin_overview': {
  requireAdmin(); purgeOld();
  $today=date('Y-m-d'); $ym=date('Y-m');
  $emps=$db->query("SELECT * FROM hs_employees ORDER BY name")->fetchAll();
  $tasks=buildTasks();
  $vByEmp=[];
  foreach($tasks as $t){ $vByEmp[$t['empId']]['total']=($vByEmp[$t['empId']]['total']??0)+1;
    if($t['status']==='closed')$vByEmp[$t['empId']]['closed']=($vByEmp[$t['empId']]['closed']??0)+1; }
  $wd=workingDays($ym);
  $pc=$db->prepare("SELECT emp_id, COUNT(*) c FROM hs_attendance WHERE att_date LIKE ? GROUP BY emp_id");
  $pc->execute([$ym.'%']); $presentByEmp=[];
  foreach($pc->fetchAll() as $r)$presentByEmp[$r['emp_id']]=(int)$r['c'];
  $E=[]; $M=[];
  foreach($emps as $e){
    $init=initials($e['name']);
    $E[]=['id'=>(int)$e['id'],'emp_code'=>$e['emp_code'],'name'=>$e['name'],'init'=>$init,'pw'=>$e['password'],
      'area'=>$e['area'],'active'=>(bool)$e['active'],'day'=>dayFor($e['id']),
      'visitsClosed'=>$vByEmp[$e['id']]['closed']??0,'visitsTotal'=>$vByEmp[$e['id']]['total']??0];
    $p=min($presentByEmp[$e['id']]??0,$wd);
    $M[]=['name'=>$e['name'],'init'=>$init,'wd'=>$wd,'present'=>$p,'absent'=>$wd-$p,'pct'=>$wd?round($p/$wd*100):0];
  }
  /* feed: today's attendance + events */
  $feed=[];
  $at=$db->prepare("SELECT a.*, e.name FROM hs_attendance a JOIN hs_employees e ON e.id=a.emp_id WHERE a.att_date=?");
  $at->execute([$today]);
  foreach($at->fetchAll() as $a){
    $feed[]=['dt'=>$a['start_time'],'type'=>'start','text'=>explode(' ',$a['name'])[0].' started day from '.($a['start_area']?:'—')];
    if($a['end_time'])$feed[]=['dt'=>$a['end_time'],'type'=>'close','text'=>explode(' ',$a['name'])[0].' ended day'];
  }
  $ev=$db->prepare("SELECT ev.*, t.doctor, t.hospital, e.name, r.sent_via
    FROM hs_task_events ev JOIN hs_tasks t ON t.id=ev.task_id JOIN hs_employees e ON e.id=t.emp_id
    LEFT JOIN hs_visit_reports r ON r.task_id=t.id
    WHERE DATE(ev.event_time)=?"); $ev->execute([$today]);
  foreach($ev->fetchAll() as $x){
    $fn=explode(' ',$x['name'])[0];
    if($x['type']==='reach')$feed[]=['dt'=>$x['event_time'],'type'=>'reach','text'=>"$fn reached ".($x['hospital']?:$x['doctor']).($x['area']?', '.$x['area']:'')];
    else $feed[]=['dt'=>$x['event_time'],'type'=>'close','text'=>"$fn closed visit — {$x['doctor']}".($x['sent_via']?' (report: '.str_replace('+',' + ',$x['sent_via']).')':'')];
  }
  usort($feed,fn($a,$b)=>strcmp($b['dt'],$a['dt']));
  $feed=array_map(fn($f)=>['time'=>fmt($f['dt']),'type'=>$f['type'],'text'=>$f['text']],array_slice($feed,0,15));
  $ap=$db->prepare("SELECT 1 FROM hs_approvals WHERE ym=?"); $ap->execute([$ym]);
  out(['employees'=>$E,'tasks'=>$tasks,'feed'=>$feed,'monthly'=>$M,'workingDays'=>$wd,
    'approved'=>(bool)$ap->fetch(),'monthLabel'=>date('F Y'),'todayLabel'=>date('l, j F Y')]);
}

case 'emp_add': {
  requireAdmin();
  if(PLAN==='trial'){
    $cnt=$db->query("SELECT COUNT(*) c FROM hs_employees")->fetch()['c'];
    if($cnt>=10) fail('Trial accounts can have up to 10 employees. Activate a plan to add your full team.');
  }
  $c=trim($in['emp_code']??''); $n=trim($in['name']??''); $p=trim($in['password']??'');
  if(!$c||!$n||!$p) fail('missing');
  $ck=$db->prepare("SELECT 1 FROM hs_employees WHERE LOWER(emp_code)=LOWER(?)"); $ck->execute([$c]);
  if($ck->fetch()) fail('exists');
  $db->prepare("INSERT INTO hs_employees (emp_code,name,password,area) VALUES (?,?,?,?)")
     ->execute([$c,$n,$p,trim($in['area']??'')]);
  audit('Admin','emp_create',"$n ($c)");
  out(true);
}

case 'emp_update': {
  requireAdmin();
  $id=(int)($in['id']??0);
  $st=$db->prepare("SELECT * FROM hs_employees WHERE id=?"); $st->execute([$id]);
  $e=$st->fetch(); if(!$e) fail('notfound',404);
  $name=trim($in['name']??''); $code=trim($in['emp_code']??''); $area=trim($in['area']??''); $pw=trim($in['password']??'');
  if(!$name||!$code) fail('missing');
  $ck=$db->prepare("SELECT 1 FROM hs_employees WHERE LOWER(emp_code)=LOWER(?) AND id<>?"); $ck->execute([$code,$id]);
  if($ck->fetch()) fail('exists');
  if($pw!=='') $db->prepare("UPDATE hs_employees SET name=?, emp_code=?, area=?, password=? WHERE id=?")->execute([$name,$code,$area,$pw,$id]);
  else $db->prepare("UPDATE hs_employees SET name=?, emp_code=?, area=? WHERE id=?")->execute([$name,$code,$area,$id]);
  audit('Admin','emp_update',$e['name'].' -> '.$name.' ('.$code.')');
  out(true);
}

case 'emp_toggle': {
  requireAdmin(); $id=(int)($in['id']??0);
  $db->prepare("UPDATE hs_employees SET active=1-active WHERE id=?")->execute([$id]);
  $st=$db->prepare("SELECT name,active FROM hs_employees WHERE id=?"); $st->execute([$id]); $e=$st->fetch();
  audit('Admin',$e['active']?'emp_enable':'emp_disable',$e['name']);
  out(['active'=>(bool)$e['active'],'name'=>$e['name']]);
}

case 'emp_del': {
  requireAdmin(); $id=(int)($in['id']??0);
  $st=$db->prepare("SELECT name FROM hs_employees WHERE id=?"); $st->execute([$id]); $e=$st->fetch();
  if(!$e) fail('notfound',404);
  $db->prepare("DELETE FROM hs_employees WHERE id=?")->execute([$id]);
  audit('Admin','emp_delete',$e['name']);   /* task/attendance history kept for audit */
  out(true);
}

case 'hod_approve': {
  requireAdmin();
  $db->prepare("INSERT IGNORE INTO hs_approvals (ym,approved_at) VALUES (?,NOW())")->execute([date('Y-m')]);
  audit('Admin','hod_approve','payroll '.date('F Y'));
  out(true);
}

default: fail('unknown_action');
}
