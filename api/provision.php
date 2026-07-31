<?php
require __DIR__ . '/config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');
function out($d){ echo json_encode(['ok'=>true,'data'=>$d]); exit; }
function fail($e,$c=400){ http_response_code($c); echo json_encode(['ok'=>false,'error'=>$e]); exit; }
function requireSuper(){ if(($_SESSION['hs_super']??false)!==true) fail('auth',401); }

$ROOT = dirname(__DIR__);
$CLIENTS = $ROOT . '/clients';
if (!is_dir($CLIENTS)) mkdir($CLIENTS, 0755, true);

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
    $list[]=['code'=>$code,'name'=>$name,'plan'=>$plan,'logo'=>file_exists("$CLIENTS/$code-logo.png")?"/clients/$code-logo.png":null];
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

  out(['code'=>$code,'url'=>'/'.$code.'/','seeded'=>$seeded,'logo'=>$logoSaved]);
}

case 'set_plan': {
  requireSuper();
  $code=strtolower(trim($in['code']??''));
  $plan=($in['plan']??'')==='starter'?'starter':'professional';
  $f="$CLIENTS/$code.php";
  if(!file_exists($f)) fail('Client not found',404);
  $cfg=file_get_contents($f);
  if(preg_match("/define\('PLAN',/",$cfg)) $cfg=preg_replace("/define\('PLAN',\s*'[a-z]+'\);/","define('PLAN', '".$plan."');",$cfg);
  else $cfg.="define('PLAN', '".$plan."');\n";
  file_put_contents($f,$cfg);
  out(['plan'=>$plan]);
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
