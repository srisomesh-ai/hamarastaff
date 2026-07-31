<?php
/* HamaraStaff tenant health check — open in browser to diagnose problems */
require __DIR__ . '/../boot.php';
header('Content-Type: text/plain; charset=utf-8');
echo "HamaraStaff Health Check — " . (defined('COMPANY_NAME') ? COMPANY_NAME : 'tenant') . "\n";
echo str_repeat('=', 50) . "\n";
echo "PHP version        : " . PHP_VERSION . "\n";
echo "config.local.php   : " . (file_exists(dirname(dirname(__DIR__)) . '/api/config.local.php') ? "FOUND ✓ (deploy-proof)" : "not found (using master config.php values)") . "\n";
echo "DB credentials     : " . (strpos(DB_NAME, 'YOUR_DB') === false ? "filled ✓" : "NOT FILLED ✗ — create api/config.local.php with your DB details") . "\n";
echo "Table prefix       : " . TP . "\n";
try {
  $db = db();
  echo "DB connection      : OK ✓\n";
  $tables = ['employees','attendance','tasks','task_events','visit_reports','approvals','audit_log'];
  $missing = [];
  foreach ($tables as $t) {
    try { $db->query("SELECT 1 FROM hs_$t LIMIT 1"); } catch (Exception $e) { $missing[] = TP . $t; }
  }
  if ($missing) echo "Tables             : MISSING ✗ → " . implode(', ', $missing) . "\n                     Run api/install.php once in your browser.\n";
  else {
    echo "Tables             : all 7 present ✓\n";
    echo "Employees in DB    : " . $db->query("SELECT COUNT(*) c FROM hs_employees")->fetch()['c'] . "\n";
    echo "Attendance rows    : " . $db->query("SELECT COUNT(*) c FROM hs_attendance")->fetch()['c'] . "\n";
    echo "Tasks              : " . $db->query("SELECT COUNT(*) c FROM hs_tasks")->fetch()['c'] . "\n";
  }
} catch (Exception $e) {
  echo "DB connection      : FAILED ✗\n";
  echo "Error              : " . $e->getMessage() . "\n";
  echo "→ Check DB name/user/password, and that the DB user is added to the database in hPanel.\n";
}
session_start();
$_SESSION['hc'] = 1;
echo "PHP sessions       : " . (session_id() ? "working ✓" : "FAILED ✗") . "\n";
echo str_repeat('=', 50) . "\n";
echo "If everything shows ✓ the app will work. Delete this file if you wish.\n";
