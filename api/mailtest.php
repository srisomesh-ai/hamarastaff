<?php
/* ============ Mail delivery test ============
   Basic test:        /api/mailtest.php?key=KEY&to=you@email.com
   Registration test: /api/mailtest.php?key=KEY&to=you@email.com&mode=signup
     -> sends the REAL welcome email (sample data) to `to`
     -> sends the REAL lead alert to someswararao.pyle@gmail.com
   Nothing is created — pure email preview.                    */
require __DIR__ . '/config.php';
require __DIR__ . '/mailer.php';
header('Content-Type: text/plain; charset=utf-8');
if (($_GET['key'] ?? '') !== DRIP_KEY) { http_response_code(403); die("Forbidden\n"); }
$to = filter_var($_GET['to'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$to) die("Add ?to=your@email.com\n");
$mode = $_GET['mode'] ?? 'basic';

$smtpOn = defined('SMTP_PASS') && SMTP_PASS !== '';
echo "Transport: " . ($smtpOn ? "SMTP (" . SMTP_HOST . ":" . SMTP_PORT . " as " . SMTP_USER . ")" : "PHP mail() fallback — SMTP not configured") . "\n\n";

if ($mode === 'signup') {
  $endsNice = date('d M Y', strtotime('+7 days'));
  echo "== Simulating a registration (company: Demo Test Company, code: demotest) ==\n";
  [$s1, $b1, $c1, $u1] = hs_welcome_email('Demo Test Company', 'demotest', 'HSdemo12@34', $endsNice);
  $ok1 = hs_send_mail($to, "[TEST] $s1", $b1, $c1, $u1);
  echo "1) Client welcome email -> $to : " . ($ok1 ? "SENT" : "FAILED") . "\n";
  [$s2, $b2, $c2, $u2] = hs_lead_email('Demo Test Company', 'demotest', $to, '+917330984620', $endsNice);
  $ok2 = hs_send_mail('someswararao.pyle@gmail.com', "[TEST] $s2", $b2, $c2, $u2);
  echo "2) Admin lead alert -> someswararao.pyle@gmail.com : " . ($ok2 ? "SENT" : "FAILED") . "\n";
  $ok3 = hs_send_mail('info@hamarastaff.com', "[TEST] $s2", $b2, $c2, $u2);
  echo "3) Admin copy -> info@hamarastaff.com : " . ($ok3 ? "SENT" : "FAILED") . "\n";
  echo "\nCheck all inboxes (and spam). Emails are marked [TEST] — no client was created.\n";
} else {
  $ok = hs_send_mail($to, "HamaraStaff mail test ✓", "<p>This is a delivery test from <b>hamarastaff.com</b>.</p><p>If you can read this, outgoing email is working.</p>", "Visit HamaraStaff", "https://hamarastaff.com");
  echo "Result: " . ($ok ? "SENT — check the inbox (and spam) of $to" : "FAILED — check SMTP password / mailbox exists") . "\n";
}
