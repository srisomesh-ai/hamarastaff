<?php
/* ================= HamaraStaff tenant — DB CONFIG =================
   Fill the 4 DB values (from Hostinger hPanel -> Databases),
   then open /medcy/api/install.php ONCE in the browser.
   All tenants can share ONE database — TP keeps tables separate.
==================================================================== */
date_default_timezone_set('Asia/Kolkata');

/* Deploy-proof credentials: create api/config.local.php on the server with your
   real define() values — git deploys will NEVER overwrite that file. */
if (file_exists(__DIR__ . '/config.local.php')) require __DIR__ . '/config.local.php';

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'YOUR_DB_NAME');
if (!defined('DB_USER')) define('DB_USER', 'YOUR_DB_USER');
if (!defined('DB_PASS')) define('DB_PASS', 'YOUR_DB_PASSWORD');

if (!defined('TP')) define('TP', 'medcy_');                    // table prefix for this client
if (!defined('COMPANY_NAME')) define('COMPANY_NAME', 'MEDCY Hospital');
if (!defined('ADMIN_USER')) define('ADMIN_USER', 'admin');
if (!defined('ADMIN_PASS')) define('ADMIN_PASS', 'Medcy@2026');
if (!defined('SEED_DEMO')) define('SEED_DEMO', true);                 // seed 3 demo employees on install
if (!defined('RETENTION_DAYS')) define('RETENTION_DAYS', 365);             // audit data kept for 1 year

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
