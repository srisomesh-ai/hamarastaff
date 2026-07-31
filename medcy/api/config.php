<?php
/* ================= HamaraStaff / MEDCY — DB CONFIG =================
   1. In Hostinger hPanel → Databases → create a MySQL database
   2. Fill the 4 values below with that database's details
   3. Open https://<your-site>/medcy/api/install.php ONCE in browser
==================================================================== */
date_default_timezone_set('Asia/Kolkata');

define('DB_HOST', 'localhost');
define('DB_NAME', 'YOUR_DB_NAME');
define('DB_USER', 'YOUR_DB_USER');
define('DB_PASS', 'YOUR_DB_PASSWORD');

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'Medcy@2026');

define('RETENTION_DAYS', 365);   // audit data kept for 1 year

function db() {
  static $pdo = null;
  if ($pdo) return $pdo;
  $pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
  );
  return $pdo;
}
