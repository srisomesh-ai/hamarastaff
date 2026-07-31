<?php
/* ============ HamaraStaff OWNER (super admin) CONFIG ============
   Fill the same MySQL database details used for tenants.
   New clients created from /admin.html will reuse this database
   with their own table prefix.
================================================================= */
date_default_timezone_set('Asia/Kolkata');

/* Deploy-proof credentials: create api/config.local.php on the server with your
   real define() values — git deploys will NEVER overwrite that file. */
if (file_exists(__DIR__ . '/config.local.php')) require __DIR__ . '/config.local.php';

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'YOUR_DB_NAME');
if (!defined('DB_USER')) define('DB_USER', 'YOUR_DB_USER');
if (!defined('DB_PASS')) define('DB_PASS', 'YOUR_DB_PASSWORD');

/* Owner login for hamarastaff.com/admin.html */
if (!defined('SUPER_USER')) define('SUPER_USER', 'someswara');
if (!defined('SUPER_PASS')) define('SUPER_PASS', 'Hamara@2026');

/* Folder used as the template when creating a new client */
if (!defined('TEMPLATE_DIR')) define('TEMPLATE_DIR', 'medcy');
