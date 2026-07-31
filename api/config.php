<?php
/* ============ HamaraStaff OWNER (super admin) CONFIG ============
   Fill the same MySQL database details used for tenants.
   New clients created from /admin.html will reuse this database
   with their own table prefix.
================================================================= */
date_default_timezone_set('Asia/Kolkata');

define('DB_HOST', 'localhost');
define('DB_NAME', 'YOUR_DB_NAME');
define('DB_USER', 'YOUR_DB_USER');
define('DB_PASS', 'YOUR_DB_PASSWORD');

/* Owner login for hamarastaff.com/admin.html */
define('SUPER_USER', 'someswara');
define('SUPER_PASS', 'Hamara@2026');

/* Folder used as the template when creating a new client */
define('TEMPLATE_DIR', 'medcy');
