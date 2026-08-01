<?php
/* Quick mail delivery test: /api/mailtest.php?key=DRIP_KEY&to=someone@example.com */
require __DIR__ . '/config.php';
require __DIR__ . '/mailer.php';
header('Content-Type: text/plain; charset=utf-8');
if (($_GET['key'] ?? '') !== DRIP_KEY) { http_response_code(403); die("Forbidden\n"); }
$to = filter_var($_GET['to'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$to) die("Add ?to=your@email.com\n");
$smtpConfigured = defined('SMTP_PASS') && SMTP_PASS !== '';
echo "Transport: " . ($smtpConfigured ? "SMTP (smtp.hostinger.com:465 as " . SMTP_USER . ")" : "PHP mail() fallback — SMTP not configured yet") . "\n";
$ok = hs_send_mail($to, "HamaraStaff mail test ✓", "<p>This is a delivery test from <b>hamarastaff.com</b>.</p><p>If you can read this, outgoing email is working.</p>", "Visit HamaraStaff", "https://hamarastaff.com");
echo $ok ? "Result: SENT — check the inbox (and spam) of $to\n" : "Result: FAILED — check SMTP password / mailbox exists\n";
