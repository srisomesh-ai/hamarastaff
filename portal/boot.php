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
if (!defined('PLAN')) define('PLAN', 'professional');   // starter = mobile only, professional = mobile + desktop panel
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
