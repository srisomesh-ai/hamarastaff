<?php
/* ============ HamaraStaff TRIAL DRIP CAMPAIGN ============
   Run daily via Hostinger cron (10:00 AM IST):
   wget -qO- "https://hamarastaff.com/api/drip.php?key=DRIP_KEY_VALUE"
   Sends the approved 10-email sequence to trial clients.
   Stops automatically when a client activates a plan.
========================================================== */
require __DIR__ . '/config.php';
require __DIR__ . '/mailer.php';
header('Content-Type: text/plain; charset=utf-8');

$key = $_GET['key'] ?? ($argv[1] ?? '');
if (!defined('DRIP_KEY') || $key !== DRIP_KEY) { http_response_code(403); die("Forbidden\n"); }

$ROOT = dirname(__DIR__);
$CLIENTS = $ROOT . '/clients';
$LOG = __DIR__ . '/drip.log';
$TEST = getenv('DRIP_TEST') === '1';

/* stage => day it becomes due (stage 1 = welcome, sent at signup) */
$DUE = [2 => 1, 3 => 3, 4 => 5, 5 => 6, 6 => 7, 7 => 8, 8 => 10, 9 => 12, 10 => 14];

function cfgval($cfg, $name) {
  return preg_match("/define\('$name',\s*'((?:[^'\\\\]|\\\\.)*)'\)/", $cfg, $m) ? stripslashes($m[1]) : null;
}
function setStage($file, $n) {
  $cfg = file_get_contents($file);
  if (preg_match("/define\('DRIP_STAGE',/", $cfg))
    $cfg = preg_replace("/define\('DRIP_STAGE',\s*\d+\);/", "define('DRIP_STAGE', $n);", $cfg);
  else $cfg .= "define('DRIP_STAGE', $n);\n";
  file_put_contents($file, $cfg);
}

/* ---------- the approved emails ---------- */
function drip_email($stage, $name, $CODE, $code, $ends) {
  $panel = "https://hamarastaff.com/$code/admin.html";
  $billing = $panel;   /* billing lives inside the panel */
  $endsNice = date('d M Y', strtotime($ends));
  $keep30 = date('d M Y', strtotime($ends . ' +30 days'));
  switch ($stage) {
  case 2: return [
    "How was your first day with HamaraStaff, $name?",
    "<p>Hi $name team,</p>
     <p>Hope the setup was smooth! Here's a quick 10-minute checklist to test everything:</p>
     <p>&#9312; Sign in as a sample employee on a phone and tap <b>Start My Day</b> &mdash; watch GPS attendance appear<br>
     &#9313; Create a visit, mark <b>Reached</b>, and close it with a report<br>
     &#9314; Open the <b>management panel</b> and see the live activity trail<br>
     &#9315; Download the <b>Excel report</b></p>
     <p>Stuck anywhere? Just reply to this email &mdash; we'll help you personally.</p>",
    "Open My Portal", "https://hamarastaff.com/$code/"];
  case 3: return [
    "Did you see who reached office on time today?",
    "<p>Hi $name team,</p>
     <p>By now your dashboard is showing real data. Two features our clients love the most:</p>
     <p>&#128205; <b>Activity Trail</b> &mdash; every employee's full day, minute by minute, with GPS proof<br>
     &#128202; <b>One-click Excel</b> &mdash; attendance ready for payroll</p>
     <p><b>Tip:</b> add 2&ndash;3 of your real staff (your trial allows up to 10 employees) and try it in the actual field today.</p>",
    "Open Management Panel", $panel];
  case 4: return [
    "5 things $name can stop doing from next month",
    "<p>Hi $name team,</p>
     <p>&#9312; Calling staff to ask <i>&ldquo;where are you?&rdquo;</i><br>
     &#9313; Maintaining attendance registers<br>
     &#9314; Chasing visit reports<br>
     &#9315; Counting attendance manually for payroll<br>
     &#9316; Doubting field claims &mdash; GPS doesn't lie</p>
     <p>Plans start at just <b>&#8377;150 per employee per month</b>. Your trial ends in <b>2 days</b> &mdash; reply <b>PLAN</b> and we'll help you choose the right one.</p>",
    "See Plans & Pricing", "https://hamarastaff.com/pricing.html"];
  case 5: return [
    "\u{23F3} 1 day left — keep your data & momentum, $name",
    "<p>Hi $name team,</p>
     <p>Your free trial ends <b>tomorrow</b>. Everything you've set up &mdash; employees, attendance history, visit reports &mdash; continues untouched when you activate.</p>
     <p><b>&#8377;150</b>/employee/month &mdash; Mobile app (minimum 10 staff)<br>
     <b>&#8377;250</b>/employee/month &mdash; Mobile + Management panel (minimum &#8377;5,000/month)</p>
     <p>Pay via UPI from your <b>Plan &amp; Billing</b> page &mdash; activated the same day.</p>",
    "Activate Now", $billing];
  case 6: return [
    "Last day of your free trial, $name",
    "<p>Hi $name team,</p>
     <p>Today is the final day of your trial. Activation takes 2 minutes:</p>
     <p>Open <b>Plan &amp; Billing</b> &rarr; scan the UPI QR &rarr; send us the payment screenshot with your code <b>$CODE</b>. Done &mdash; you're activated the same day.</p>
     <p>Questions about pricing for your team size? Reply to this email &mdash; we respond within the hour.</p>",
    "Open Plan & Billing", $billing];
  case 7: return [
    "Your trial ended — but your data is 100% safe, $name",
    "<p>Hi $name team,</p>
     <p>Your portal is <b>paused, not deleted</b>. Every employee, attendance record and visit report is waiting exactly as you left it.</p>
     <p>Reactivate anytime: pay via UPI to <b>Hamara Staff</b> (<b>srisomeshidfc@ybl</b>), send the screenshot with code <b>$CODE</b> to this email, and you're live again the same day.</p>",
    "View Plans", "https://hamarastaff.com/pricing.html"];
  case 8: return [
    "Is price the concern? Start with just \u{20B9}1,500/month",
    "<p>Hi $name team,</p>
     <p>No need for the full plan on day one. The <b>&#8377;150 Starter</b> (10 employees &mdash; &#8377;1,500/month) gives your field team GPS attendance and visit reports.</p>
     <p>Upgrade to the management panel whenever you're ready. That's about <b>&#8377;50 a day</b> for complete field-force visibility.</p>",
    "Start with \u{20B9}1,500/month", "https://hamarastaff.com/pricing.html"];
  case 9: return [
    "How a Vizag hospital tracks 20 field staff with zero phone calls",
    "<p>Hi $name team,</p>
     <p>Their marketing reps start the day from the field, every doctor visit is GPS-stamped, and the HOD approves the whole month's attendance in <b>one click</b> for payroll.</p>
     <p>Setup took one day. Your portal is <b>already built</b> &mdash; it's one payment away from doing the same for $name.</p>",
    "Reactivate My Portal", $billing];
  case 10: return [
    "Last email from us, $name — your portal is reserved till $keep30",
    "<p>Hi $name team,</p>
     <p>We won't fill your inbox after this. Your portal and all your data stay reserved till <b>$keep30</b>.</p>
     <p>If field-staff tracking is still on your list, we're one reply away &mdash; and your rate stays <b>locked at today's pricing</b>.</p>
     <p>Thank you for trying HamaraStaff &#128591;</p>",
    "Come Back Anytime", "https://hamarastaff.com/$code/"];
  }
  return null;
}

/* ---------- run ---------- */
$sent = 0; $checked = 0;
foreach (glob("$CLIENTS/*.php") as $file) {
  $code = basename($file, '.php');
  if (!preg_match('/^[a-z0-9][a-z0-9-]{1,19}$/', $code)) continue;
  $cfg = file_get_contents($file);
  $checked++;

  $plan = cfgval($cfg, 'PLAN') ?: 'professional';
  if ($plan !== 'trial') continue;                      /* purchased or non-trial: stop forever */
  $email = cfgval($cfg, 'TRIAL_EMAIL');
  $ends  = cfgval($cfg, 'TRIAL_ENDS');
  if (!$email || !$ends) continue;
  $name  = cfgval($cfg, 'COMPANY_NAME') ?: strtoupper($code);
  $stage = 1;
  if (preg_match("/define\('DRIP_STAGE',\s*(\d+)\);/", $cfg, $m)) $stage = (int)$m[1];
  if ($stage >= 10) continue;

  $signup = strtotime($ends . ' -7 days');
  $day = (int)floor((strtotime(date('Y-m-d')) - $signup) / 86400);

  $next = $stage + 1;
  if (!isset($DUE[$next]) || $day < $DUE[$next]) continue;   /* not due yet */

  $CODE = strtoupper($code);
  $e = drip_email($next, htmlspecialchars($name), $CODE, $code, $ends);
  if (!$e) continue;
  [$subject, $body, $ctaText, $ctaUrl] = $e;
  $ok = hs_send_mail($email, $subject, $body, $ctaText, $ctaUrl);
  if ($ok || $TEST) {
    setStage($file, $next);
    $sent++;
    @file_put_contents($LOG, date('Y-m-d H:i') . "\t$code\tday=$day\temail#$next\t$email\t" . ($ok ? 'sent' : 'test') . "\n", FILE_APPEND);
    echo "sent email #$next to $code ($email)\n";
  } else {
    @file_put_contents($LOG, date('Y-m-d H:i') . "\t$code\tday=$day\temail#$next\t$email\tMAIL-FAILED\n", FILE_APPEND);
    echo "MAIL FAILED for $code — will retry tomorrow\n";
  }
}
echo "done: checked $checked clients, sent $sent emails\n";
