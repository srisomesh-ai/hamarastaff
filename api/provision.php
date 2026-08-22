<?php
require __DIR__ . '/config.php';
require __DIR__ . '/mailer.php';
session_start();
header('Content-Type: application/json; charset=utf-8');
function out($d){ echo json_encode(['ok'=>true,'data'=>$d]); exit; }
function fail($e,$c=400){ http_response_code($c); echo json_encode(['ok'=>false,'error'=>$e]); exit; }
function pv($cfg,$name){ return preg_match("/define\('$name',\s*'((?:[^'\\\\]|\\\\.)*)'\)/",$cfg,$m) ? stripslashes($m[1]) : ''; }
function notifyPlanEmail($cfg,$code,$plan,$ends,$kind){
  $email = pv($cfg,'TRIAL_EMAIL'); if(!filter_var($email,FILTER_VALIDATE_EMAIL)) return;
  $name = pv($cfg,'COMPANY_NAME') ?: strtoupper($code);
  $planName = $plan==='starter' ? '₹150 Starter' : '₹250 Professional';
  $endsNice = $ends ? date('d M Y', strtotime($ends)) : '';
  $inner = "<p>Hi ".htmlspecialchars($name)." team,</p>"
    . ($kind==='extend'
       ? "<p>&#9989; Your HamaraStaff plan has been <b>renewed</b>. Thank you for the payment!</p>"
       : "<p>&#127881; Your HamaraStaff account is now <b>activated</b> on the <b>$planName</b> plan. Welcome aboard!</p>")
    . "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background:#F0F7F6;border-radius:14px'><tr><td style='padding:18px 20px;font-family:Arial,sans-serif;font-size:14px;line-height:2'>"
    . "<b>Portal:</b> <a href='https://hamarastaff.com/$code/' style='color:#0E6B63;font-weight:700'>hamarastaff.com/$code/</a><br>"
    . "<b>Plan:</b> $planName<br>"
    . ($endsNice ? "<b>Valid till:</b> $endsNice<br>" : "")
    . "</td></tr></table>"
    . "<p>You can see your plan and validity anytime in <b>Plan &amp; Billing</b> inside the management panel. Need anything? Just reply to this email.</p>";
  @hs_send_mail($email, ($kind==='extend' ? "Plan renewed till $endsNice — " : "Account activated — ") . $name, $inner, "Open My Portal", "https://hamarastaff.com/$code/");
}
function requireSuper(){ if(($_SESSION['hs_super']??false)!==true) fail('auth',401); }

$ROOT = dirname(__DIR__);
$CLIENTS = $ROOT . '/clients';
if (!is_dir($CLIENTS)) mkdir($CLIENTS, 0755, true);

/* ---- per-client SQL backup download (owner session required) ---- */
if (($_GET['action'] ?? '') === 'backup') {
  if (($_SESSION['hs_super']??false)!==true) { http_response_code(403); die('Login required'); }
  $code = strtolower(trim($_GET['code'] ?? ''));
  if (!preg_match('/^[a-z0-9][a-z0-9-]{1,19}$/', $code)) die('bad code');
  header('Content-Type: application/sql; charset=utf-8');
  header('Content-Disposition: attachment; filename="hamarastaff-' . $code . '-backup-' . date('Ymd-His') . '.sql"');
  $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  echo "-- HamaraStaff backup for client: $code\n-- " . date('Y-m-d H:i:s') . " IST\n\n";
  foreach (['employees','attendance','tasks','task_events','visit_reports','approvals','audit_log'] as $t) {
    $tbl = $code . '_' . $t;
    try {
      $create = $pdo->query("SHOW CREATE TABLE `$tbl`")->fetch(PDO::FETCH_NUM);
      echo "DROP TABLE IF EXISTS `$tbl`;\n" . $create[1] . ";\n\n";
      $rows = $pdo->query("SELECT * FROM `$tbl`");
      while ($r = $rows->fetch(PDO::FETCH_ASSOC)) {
        $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), array_values($r));
        echo "INSERT INTO `$tbl` (`" . implode('`,`', array_keys($r)) . "`) VALUES (" . implode(',', $vals) . ");\n";
      }
      echo "\n";
    } catch (Exception $e) { echo "-- table $tbl not found\n\n"; }
  }
  echo "-- end of backup\n";
  exit;
}

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $in['action'] ?? '';

/* prefix-aware DB wrapper (same trick as portal TPDO) */
class TPDO extends PDO {
  #[\ReturnTypeWillChange]
  public function prepare($sql, $options = []) { return parent::prepare(str_replace('hs_', TP, $sql), $options); }
  #[\ReturnTypeWillChange]
  public function query($sql, ...$rest) { return parent::query(str_replace('hs_', TP, $sql), ...$rest); }
  #[\ReturnTypeWillChange]
  public function exec($sql) { return parent::exec(str_replace('hs_', TP, $sql)); }
}
function db() {
  static $pdo = null;
  if ($pdo) return $pdo;
  $pdo = new TPDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
  return $pdo;
}

function saveLogo($CLIENTS,$code,$dataUrl){
  if(!$dataUrl) return false;
  if(!preg_match('#^data:image/(png|jpe?g|webp);base64,(.+)$#',$dataUrl,$m)) fail('Logo must be a PNG/JPG image');
  $bin=base64_decode($m[2],true);
  if($bin===false || strlen($bin)>2*1024*1024) fail('Logo file is invalid or larger than 2 MB');
  if(function_exists('imagecreatefromstring')){
    $img=@imagecreatefromstring($bin);
    if($img===false) fail('Could not read the logo image');
    imagesavealpha($img,true);
    imagepng($img,"$CLIENTS/$code-logo.png");
    imagedestroy($img);
  } else {
    if(@getimagesizefromstring($bin)===false) fail('Logo is not a valid image');
    file_put_contents("$CLIENTS/$code-logo.png",$bin);
  }
  return true;
}
function clientList($CLIENTS){
  $list=[];
  foreach(glob("$CLIENTS/*.php") as $f){
    $code=basename($f,'.php');
    if(!preg_match('/^[a-z0-9][a-z0-9-]{1,19}$/',$code)) continue;
    $cfg=file_get_contents($f);
    $name=strtoupper($code);
    if(preg_match("/define\('COMPANY_NAME',\s*'((?:[^'\\\\]|\\\\.)*)'\)/",$cfg,$m)) $name=stripslashes($m[1]);
    $plan='professional';
    if(preg_match("/define\('PLAN',\s*'([a-z]+)'\)/",$cfg,$pm)) $plan=$pm[1];
    $days=null; $ends=null;
    if($plan==='trial' && preg_match("/define\('TRIAL_ENDS',\s*'([0-9-]+)'\)/",$cfg,$tm))
      $days=(int)floor((strtotime($tm[1])-strtotime(date('Y-m-d')))/86400);
    if($plan!=='trial' && preg_match("/define\('PLAN_ENDS',\s*'([0-9-]+)'\)/",$cfg,$pe)){
      $ends=$pe[1];
      $days=(int)floor((strtotime($ends)-strtotime(date('Y-m-d')))/86400);
    }
    $email=null;
    if(preg_match("/define\('TRIAL_EMAIL',\s*'((?:[^'\\\\]|\\\\.)*)'\)/",$cfg,$em)) $email=stripslashes($em[1]);
    $phone=pv($cfg,'TRIAL_PHONE'); $loc=trim(pv($cfg,'TRIAL_CITY').(pv($cfg,'TRIAL_STATE')?', '.pv($cfg,'TRIAL_STATE'):''),', ');
    $reg = pv($cfg,'CREATED_AT');
    if(!$reg){ $te=pv($cfg,'TRIAL_ENDS'); $reg = $te ? date('Y-m-d', strtotime($te.' -7 days')) : date('Y-m-d', filectime($f)); }
    $drip=null; if($plan==='trial'){ $drip = preg_match("/define\('DRIP_STAGE',\s*(\d+)\)/",$cfg,$dm) ? (int)$dm[1] : 0; }
    $emps=null; $monthly=null;
    try{
      $pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
      $emps=(int)$pdo->query("SELECT COUNT(*) FROM `{$code}_employees`")->fetchColumn();
      if($plan!=='trial'){
        $rate=$plan==='starter'?150:250; $minn=$plan==='starter'?10:20;
        $monthly=max($emps,$minn)*$rate;
      }
    }catch(Exception $e){}
    $list[]=['code'=>$code,'name'=>$name,'plan'=>$plan,'days'=>$days,'ends'=>$ends,'email'=>$email,'phone'=>$phone,'loc'=>$loc,'adminAct'=>($adminAct??null),'reg'=>$reg,'drip'=>$drip,'emps'=>$emps,'monthly'=>$monthly,'logo'=>file_exists("$CLIENTS/$code-logo.png")?"/clients/$code-logo.png":null];
  }
  return $list;
}

switch($action){

case 'login': {
  if(($in['username']??'')!==SUPER_USER || ($in['password']??'')!==SUPER_PASS) fail('invalid');
  session_regenerate_id(true);
  $_SESSION['hs_super']=true;
  out(true);
}
case 'logout': $_SESSION=[]; session_destroy(); out(true);
case 'me': (($_SESSION['hs_super']??false)===true) ? out(true) : fail('auth',401);

case 'list': { requireSuper(); out(clientList($CLIENTS)); }

case 'create': {
  requireSuper();
  $code=strtolower(trim($in['code']??''));
  $name=trim($in['name']??'');
  $apass=trim($in['admin_pass']??'');
  $seed=!empty($in['seed_demo']);
  $plan=($in['plan']??'professional')==='starter'?'starter':'professional';
  if(!preg_match('/^[a-z0-9][a-z0-9-]{1,19}$/',$code)) fail('Code must be 2–20 letters/numbers (e.g. APOLLO)');
  if(in_array($code,['api','portal','clients','assets','admin','login','pricing','index'])) fail('That code is reserved — choose another');
  if(file_exists("$CLIENTS/$code.php")) fail('A client with this code already exists');
  if(!$name) fail('Company name is required');
  if(strlen($apass)<6) fail('Admin password must be at least 6 characters');

  define('TP', $code.'_');
  try{
    require __DIR__ . '/../portal/api/schema.php';
    hs_create_tables();
    $seeded = $seed ? hs_seed_demo() : false;
  }catch(Exception $e){ fail('Database error: '.$e->getMessage(),500); }

  /* the WHOLE client = one small config file (+ optional logo) */
  $cfg = "<?php\n"
    ."define('COMPANY_NAME', '".addslashes($name)."');\n"
    ."define('ADMIN_USER', 'admin');\n"
    ."define('ADMIN_PASS', '".addslashes($apass)."');\n"
    ."define('SEED_DEMO', ".($seed?'true':'false').");\n"
    ."define('PLAN', '".$plan."');\n";
  file_put_contents("$CLIENTS/$code.php",$cfg);
  $logoSaved=saveLogo($CLIENTS,$code,$in['logo']??null);

  /* alert Someswara about every fresh client, owner-created included */
  try {
    [$lSub, $lBody, $lCta, $lUrl] = hs_lead_email($name, $code, 'created-by-owner', '', 'no expiry set');
    $lSub = str_replace('New trial:', 'New client created:', $lSub);
    foreach (['someswararao.pyle@gmail.com', 'info@hamarastaff.com'] as $ownerTo) {
      hs_send_mail($ownerTo, $lSub, $lBody, $lCta, $lUrl);
    }
  } catch (Exception $e) { /* never block creation on mail issues */ }
  out(['code'=>$code,'url'=>'/'.$code.'/','seeded'=>$seeded,'logo'=>$logoSaved]);
}

case 'set_plan': {
  requireSuper();
  $code=strtolower(trim($in['code']??''));
  $plan=($in['plan']??'')==='starter'?'starter':'professional';
  $months=max(0,min(36,(int)($in['months']??0)));
  $f="$CLIENTS/$code.php";
  if(!file_exists($f)) fail('Client not found',404);
  $cfg=file_get_contents($f);
  if(preg_match("/define\('PLAN',/",$cfg)) $cfg=preg_replace("/define\('PLAN',\s*'[a-z]+'\);/","define('PLAN', '".$plan."');",$cfg);
  else $cfg.="define('PLAN', '".$plan."');\n";
  $ends=null;
  if($months>0){
    $base=date('Y-m-d');
    if(preg_match("/define\('PLAN_ENDS',\s*'([0-9-]+)'\)/",$cfg,$pe) && strtotime($pe[1])>strtotime($base)) $base=$pe[1];
    $ends=date('Y-m-d',strtotime($base." +$months month"));
    if(preg_match("/define\('PLAN_ENDS',/",$cfg)) $cfg=preg_replace("/define\('PLAN_ENDS',\s*'[0-9-]*'\);/","define('PLAN_ENDS', '".$ends."');",$cfg);
    else $cfg.="define('PLAN_ENDS', '".$ends."');\n";
  }
  file_put_contents($f,$cfg);
  notifyPlanEmail($cfg,$code,$plan,$ends,'activate');
  out(['plan'=>$plan,'ends'=>$ends]);
}

case 'extend': {
  requireSuper();
  $code=strtolower(trim($in['code']??''));
  $months=max(1,min(36,(int)($in['months']??1)));
  $f="$CLIENTS/$code.php";
  if(!file_exists($f)) fail('Client not found',404);
  $cfg=file_get_contents($f);
  $base=date('Y-m-d');
  if(preg_match("/define\('PLAN_ENDS',\s*'([0-9-]+)'\)/",$cfg,$pe) && strtotime($pe[1])>strtotime($base)) $base=$pe[1];
  $ends=date('Y-m-d',strtotime($base." +$months month"));
  if(preg_match("/define\('PLAN_ENDS',/",$cfg)) $cfg=preg_replace("/define\('PLAN_ENDS',\s*'[0-9-]*'\);/","define('PLAN_ENDS', '".$ends."');",$cfg);
  else $cfg.="define('PLAN_ENDS', '".$ends."');\n";
  file_put_contents($f,$cfg);
  $curPlan = pv($cfg,'PLAN') ?: 'professional';
  notifyPlanEmail($cfg,$code,$curPlan,$ends,'extend');
  out(['ends'=>$ends]);
}

case 'client_detail': {
  requireSuper();
  $code=strtolower(trim($in['code']??$_GET['code']??''));
  if(!preg_match('/^[a-z0-9][a-z0-9-]{1,19}$/',$code) || !file_exists("$CLIENTS/$code.php")) fail('Client not found',404);
  $pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
  $pdo->exec("SET time_zone='+05:30'");
  $staff=[];
  try{
    $staff=$pdo->query("SELECT e.emp_code,e.name,e.active,
        e.last_seen,e.last_login,e.last_logout,
        a.start_time,a.end_time
      FROM `{$code}_employees` e
      LEFT JOIN `{$code}_attendance` a ON a.emp_id=e.id AND a.att_date=CURDATE()
      ORDER BY e.name")->fetchAll();
  }catch(Exception $e){
    try{ $staff=$pdo->query("SELECT emp_code,name,active,NULL last_seen,NULL last_login,NULL last_logout,NULL start_time,NULL end_time FROM `{$code}_employees` ORDER BY name")->fetchAll(); }catch(Exception $e2){}
  }
  foreach($staff as &$r){
    $r['online'] = $r['last_seen'] && (time()-strtotime($r['last_seen']) < 150);
    foreach(['last_seen','last_login','last_logout','start_time','end_time'] as $kk)
      if(!empty($r[$kk])) $r[$kk]=date('d M h:i A',strtotime($r[$kk]));
  }
  $adm=['last_seen'=>null,'last_login'=>null,'last_logout'=>null,'online'=>false];
  try{
    foreach($pdo->query("SELECT k,v FROM `{$code}_kv` WHERE k IN ('admin_last_seen','admin_last_login','admin_last_logout')")->fetchAll() as $kvr){
      $key=str_replace('admin_','',$kvr['k']);
      if($key==='last_seen') $adm['online'] = (time()-strtotime($kvr['v']) < 150);
      $adm[$key]=date('d M h:i A',strtotime($kvr['v']));
    }
  }catch(Exception $e){}
  out(['staff'=>$staff,'admin'=>$adm]);
}

case 'owner_push_register': {
  requireSuper();
  require_once __DIR__ . '/ownerpush.php';
  out(['ok'=>owner_push_register(trim($in['token']??''))]);
}

case 'set_admin_pass': {
  requireSuper();
  $code=strtolower(trim($in['code']??''));
  $f="$CLIENTS/$code.php";
  if(!file_exists($f)) fail('Client not found',404);
  $pass=trim($in['pass']??'');
  if($pass==='') $pass='HS'.substr(str_shuffle('23456789abcdefghjkmnpqrstuvwxyz'),0,6).'@'.rand(10,99);
  if(strlen($pass)<6) fail('Password must be at least 6 characters');
  $cfg=file_get_contents($f);
  $cfg=preg_replace("/define\('ADMIN_PASS',\s*'(?:[^'\\\\]|\\\\.)*'\);/","define('ADMIN_PASS', '".addslashes($pass)."');",$cfg,1);
  file_put_contents($f,$cfg);
  /* email the client their new password */
  $email=pv($cfg,'TRIAL_EMAIL'); $name=pv($cfg,'COMPANY_NAME')?:strtoupper($code);
  if(filter_var($email,FILTER_VALIDATE_EMAIL)){
    @hs_send_mail($email,"Your HamaraStaff admin password was reset — $name",
      "<p>Hi ".htmlspecialchars($name)." team,</p><p>Your management panel password has been reset by HamaraStaff support.</p>"
      ."<table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background:#F0F7F6;border-radius:14px'><tr><td style='padding:18px 20px;font-family:Arial,sans-serif;font-size:14px;line-height:2'>"
      ."<b>Portal:</b> <a href='https://hamarastaff.com/$code/' style='color:#0E6B63;font-weight:700'>hamarastaff.com/$code/</a><br>"
      ."<b>Username:</b> admin<br><b>New password:</b> <b style='font-family:monospace'>".htmlspecialchars($pass)."</b>"
      ."</td></tr></table><p>If you did not request this, reply to this email immediately.</p>",
      "Open Management Panel","https://hamarastaff.com/$code/admin.html");
  }
  out(['pass'=>$pass,'emailed'=>filter_var($email,FILTER_VALIDATE_EMAIL)?true:false]);
}

case 'set_logo': {
  requireSuper();
  $code=strtolower(trim($in['code']??''));
  if(!file_exists("$CLIENTS/$code.php")) fail('Client not found',404);
  saveLogo($CLIENTS,$code,$in['logo']??null) ? out(true) : fail('No logo provided');
}

case 'remove': {
  requireSuper();
  $code=strtolower(trim($in['code']??''));
  if(!file_exists("$CLIENTS/$code.php")) fail('Client not found',404);
  if(($in['confirm']??'')!==$code) fail('Confirmation text does not match the code');
  unlink("$CLIENTS/$code.php");
  if(file_exists("$CLIENTS/$code-logo.png")) unlink("$CLIENTS/$code-logo.png");
  out(true);   /* database tables kept for audit */
}

default: fail('unknown_action');
}
