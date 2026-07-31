<?php
/* ============ HamaraStaff PORTAL BOOTSTRAP ============
   One shared codebase for ALL clients.
   Tenant is resolved from ?c= (set by .htaccess rewrite):
     hamarastaff.com/medcy/           -> portal/index.php?c=medcy
     hamarastaff.com/medcy/app.html   -> portal/app.php?c=medcy
     hamarastaff.com/medcy/api/api.php-> portal/api/api.php?c=medcy
   Each client is ONLY a small file: /clients/{code}.php
======================================================== */
date_default_timezone_set('Asia/Kolkata');

$___code = strtolower(preg_replace('/[^a-z0-9-]/', '', $_GET['c'] ?? ''));
$___root = dirname(__DIR__);
$___cfg  = $___root . '/clients/' . $___code . '.php';
if ($___code === '' || !file_exists($___cfg)) {
  http_response_code(404);
  header('Content-Type: text/html; charset=utf-8');
  die('<div style="font-family:sans-serif;text-align:center;padding:60px"><h2>Client portal not found</h2><p>Please check your company code, or contact <a href="https://hamarastaff.com">HamaraStaff</a>.</p></div>');
}
require $___cfg;                                   // COMPANY_NAME, ADMIN_USER, ADMIN_PASS, SEED_DEMO
require $___root . '/api/config.php';              // master DB credentials (+ config.local.php override)

define('CODE', $___code);
define('TP', $___code . '_');
if (!defined('ADMIN_USER')) define('ADMIN_USER', 'admin');
if (!defined('SEED_DEMO')) define('SEED_DEMO', false);
if (!defined('PLAN')) define('PLAN', 'professional');   // starter | professional | trial (trial = full access, 7 days)
if (!defined('TRIAL_ENDS')) define('TRIAL_ENDS', '');
if (PLAN === 'trial' && TRIAL_ENDS !== '') {
  define('TRIAL_DAYS_LEFT', (int)floor((strtotime(TRIAL_ENDS) - strtotime(date('Y-m-d'))) / 86400));
  define('TRIAL_EXPIRED', TRIAL_DAYS_LEFT < 0);
} else {
  define('TRIAL_DAYS_LEFT', 0);
  define('TRIAL_EXPIRED', false);
}
if (!defined('PLAN_ENDS')) define('PLAN_ENDS', '');
if (PLAN !== 'trial' && PLAN_ENDS !== '') {
  define('SUB_DAYS_LEFT', (int)floor((strtotime(PLAN_ENDS) - strtotime(date('Y-m-d'))) / 86400));
  define('SUB_EXPIRED', SUB_DAYS_LEFT < 0);
} else {
  define('SUB_DAYS_LEFT', 99999);
  define('SUB_EXPIRED', false);
}
function sub_lock_page($cn) {
  header('Content-Type: text/html; charset=utf-8');
  echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Plan expired</title></head>'
    .'<body style="font-family:sans-serif;background:#F4F8F7;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:24px">'
    .'<div style="background:#fff;border:1px solid #E2EAE8;border-radius:20px;padding:40px;max-width:480px;text-align:center">'
    .'<div style="font-size:44px">&#128179;</div>'
    .'<h2 style="margin:14px 0 8px;color:#13211F">Your plan has expired</h2>'
    .'<p style="color:#5B6E6B;line-height:1.65;font-size:14.5px">The subscription for <b>'.$cn.'</b> ended on <b>'.date('d M Y', strtotime(PLAN_ENDS)).'</b>. Your data is completely safe &mdash; renew to continue exactly where you left off.</p>'
    .'<div style="background:#F4F8F7;border-radius:14px;padding:16px;margin-top:14px;font-size:13.5px;color:#13211F;line-height:1.8">Pay via UPI to <b>Hamara Staff</b><br><span style="font-family:monospace;font-weight:700">srisomeshidfc@ybl</span><br><span style="color:#5B6E6B;font-size:12.5px">Then send the screenshot with your portal code to info@hamarastaff.com &mdash; renewed the same day.</span></div>'
    .'<a href="mailto:info@hamarastaff.com?subject=Renew my HamaraStaff plan ('.strtoupper(CODE).')" style="display:inline-block;margin:16px 6px 0;background:#0E6B63;color:#fff;padding:13px 22px;border-radius:12px;text-decoration:none;font-weight:700">&#9993; Email Payment Proof</a>'
    .'</div></body></html>';
  exit;
}
function trial_lock_page($cn) {
  header('Content-Type: text/html; charset=utf-8');
  echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Trial ended</title></head>'
    .'<body style="font-family:sans-serif;background:#F4F8F7;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:24px">'
    .'<div style="background:#fff;border:1px solid #E2EAE8;border-radius:20px;padding:40px;max-width:470px;text-align:center">'
    .'<div style="font-size:44px">&#9203;</div>'
    .'<h2 style="margin:14px 0 8px;color:#13211F">Your free trial has ended</h2>'
    .'<p style="color:#5B6E6B;line-height:1.65;font-size:14.5px">The 7-day free trial for <b>'.$cn.'</b> is over. Your data is safe &mdash; choose a plan to continue exactly where you left off.</p>'
    .'<p style="color:#5B6E6B;font-size:14px;line-height:1.7"><b>&#8377;150</b>/employee/month &mdash; Mobile app<br><b>&#8377;250</b>/employee/month &mdash; Mobile + Desktop panel</p>'
    .'<a href="mailto:info@hamarastaff.com?subject=Activate my HamaraStaff account" style="display:inline-block;margin:10px 6px 0;background:#0E6B63;color:#fff;padding:13px 22px;border-radius:12px;text-decoration:none;font-weight:700">&#9993; Email Us to Activate</a>'
    .'<a href="https://hamarastaff.com/pricing.html" style="display:inline-block;margin:10px 6px 0;background:#fff;border:1.5px solid #E2EAE8;color:#13211F;padding:13px 22px;border-radius:12px;text-decoration:none;font-weight:700">View Plans</a>'
    .'</div></body></html>';
  exit;
}
if (!defined('RETENTION_DAYS')) define('RETENTION_DAYS', 365);
define('CLIENT_LOGO_FILE', $___root . '/clients/' . CODE . '-logo.png');
define('CLIENT_LOGO_URL', '/clients/' . CODE . '-logo.png');
function client_logo_exists() { return file_exists(CLIENT_LOGO_FILE); }

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
  $pdo = new TPDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
  );
  return $pdo;
}
