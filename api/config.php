<?php
/* ============ HamaraStaff MASTER CONFIG ============
   Shared by the owner panel and ALL client portals.
   Optional override: create api/config.local.php with
   your own define() lines — it always wins and git
   deploys never touch it.
==================================================== */
date_default_timezone_set('Asia/Kolkata');
if (file_exists(__DIR__ . '/config.local.php')) require __DIR__ . '/config.local.php';

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'u943205660_pavanmidway');
if (!defined('DB_USER')) define('DB_USER', 'u943205660_pavanmidway');
if (!defined('DB_PASS')) define('DB_PASS', 'Simhadriappanna@143');

/* Owner login for hamarastaff.com/admin.html */
if (!defined('SUPER_USER')) define('SUPER_USER', 'someswara');
if (!defined('SUPER_PASS')) define('SUPER_PASS', 'Hamara@2026');

/* SMTP for outgoing mail — create mailbox info@hamarastaff.com in hPanel > Emails,
   then set its password here (or in config.local.php): */
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.hostinger.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 465);
if (!defined('SMTP_USER')) define('SMTP_USER', 'info@hamarastaff.com');
if (!defined('SMTP_PASS')) define('SMTP_PASS', 'Simhadriappanna@143');

/* secret key for the daily email cron (api/drip.php) */
if (!defined('DRIP_KEY')) define('DRIP_KEY', 'hs-drip-8k2m9x-2026');
