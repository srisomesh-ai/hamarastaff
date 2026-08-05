<?php
/* ============ HamaraStaff branded HTML mailer ============ */

function hs_wrap_email($inner, $ctaText = '', $ctaUrl = '') {
  $cta = '';
  if ($ctaText && $ctaUrl) {
    $cta = '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px auto 6px"><tr><td style="background:#0E6B63;border-radius:12px">'
      . '<a href="' . $ctaUrl . '" style="display:inline-block;padding:14px 30px;color:#ffffff;font-weight:700;text-decoration:none;font-size:15px;font-family:Arial,Helvetica,sans-serif">' . $ctaText . '</a>'
      . '</td></tr></table>';
  }
  return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#F0F5F4">'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F0F5F4;padding:28px 12px"><tr><td align="center">'
    . '<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%">'
    . '<tr><td align="center" style="padding-bottom:18px">'
    . '<img src="https://hamarastaff.com/assets/logo.png?v=2" alt="HamaraStaff" width="190" style="display:block;max-width:190px;height:auto">'
    . '</td></tr>'
    . '<tr><td style="background:#ffffff;border-radius:18px;padding:32px 30px;font-family:Arial,Helvetica,sans-serif;color:#13211F;font-size:14.5px;line-height:1.7">'
    . $inner . $cta
    . '</td></tr>'
    . '<tr><td align="center" style="padding:20px 10px;font-family:Arial,Helvetica,sans-serif;font-size:11.5px;color:#7A8C89;line-height:1.7">'
    . 'HamaraStaff &mdash; GPS Attendance &amp; Field Staff Tracking<br>'
    . 'A BharatGPS product &middot; Visakhapatnam, India<br>'
    . '<a href="https://hamarastaff.com" style="color:#0E6B63;text-decoration:none;font-weight:700">hamarastaff.com</a> &middot; '
    . '<a href="mailto:info@hamarastaff.com" style="color:#0E6B63;text-decoration:none">info@hamarastaff.com</a>'
    . '</td></tr></table></td></tr></table></body></html>';
}

/* ---- minimal SMTP client (Hostinger: smtp.hostinger.com:465 SSL) ---- */
function hs_smtp_send($to, $subject, $html) {
  $host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.hostinger.com';
  $port = defined('SMTP_PORT') ? SMTP_PORT : 465;
  $user = defined('SMTP_USER') ? SMTP_USER : 'info@hamarastaff.com';
  $pass = defined('SMTP_PASS') ? SMTP_PASS : '';
  if ($pass === '') return null;                      /* not configured */
  $fp = @stream_socket_client("ssl://$host:$port", $errno, $errstr, 15);
  if (!$fp) return false;
  $read = function() use ($fp) { $d=''; while ($l = fgets($fp, 515)) { $d .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; } return $d; };
  $cmd  = function($c) use ($fp, $read) { fwrite($fp, $c . "\r\n"); return $read(); };
  $read();
  $cmd('EHLO hamarastaff.com');
  $cmd('AUTH LOGIN'); $cmd(base64_encode($user));
  $r = $cmd(base64_encode($pass));
  if (strpos($r, '235') !== 0) { fclose($fp); return false; }
  $cmd("MAIL FROM:<$user>");
  $r = $cmd("RCPT TO:<$to>");
  if (strpos($r, '250') !== 0 && strpos($r, '251') !== 0) { fclose($fp); return false; }
  $cmd('DATA');
  $hdr = "From: HamaraStaff <$user>\r\n"
    . "Reply-To: $user\r\n"
    . "To: <$to>\r\n"
    . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
    . "MIME-Version: 1.0\r\n"
    . "Content-Type: text/html; charset=utf-8\r\n"
    . "Date: " . date('r') . "\r\n"
    . "Message-ID: <" . uniqid('hs', true) . "@hamarastaff.com>\r\n";
  fwrite($fp, $hdr . "\r\n" . $html . "\r\n.\r\n");
  $r = $read();
  $cmd('QUIT'); fclose($fp);
  return strpos($r, '250') === 0;
}

function hs_send_mail($to, $subject, $innerHtml, $ctaText = '', $ctaUrl = '') {
  $html = hs_wrap_email($innerHtml, $ctaText, $ctaUrl);
  $smtp = hs_smtp_send($to, $subject, $html);
  if ($smtp !== null) return $smtp;                   /* SMTP configured: its result is final */
  $headers = "MIME-Version: 1.0\r\n"
    . "Content-Type: text/html; charset=utf-8\r\n"
    . "From: HamaraStaff <info@hamarastaff.com>\r\n"
    . "Reply-To: info@hamarastaff.com\r\n";
  return @mail($to, $subject, $html, $headers);       /* fallback until SMTP is configured */
}

/* ---- registration email templates (used by trial.php and mailtest.php) ---- */
function hs_welcome_email($name, $code, $apass, $endsNice) {
  $CU = strtoupper($code);
  $portal = "https://hamarastaff.com/$code/";
  $inner = "<p>Welcome to HamaraStaff, <b>" . htmlspecialchars($name) . "</b>! &#127881;</p>"
    . "<p>Your <b>7-day FREE trial</b> is ready &mdash; full access, no payment needed.</p>"
    . "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background:#F0F7F6;border-radius:14px'><tr><td style='padding:18px 20px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:2'>"
    . "<b>Your portal:</b> <a href='$portal' style='color:#0E6B63;font-weight:700'>hamarastaff.com/$code/</a><br>"
    . "<b>Management login:</b> admin &nbsp;/&nbsp; <b>$apass</b><br>"
    . "<b>Sample staff (mobile app):</b> $CU-1001, $CU-1002, $CU-1003<br>"
    . "<b>Staff password:</b> $code@123"
    . "</td></tr></table>"
    . "<p>You can rename the sample staff or add your own (up to 10 in trial) from the management panel &rarr; <b>Employees</b>.</p>"
    . "<p>Your trial ends on <b>$endsNice</b>. To continue after that, choose a plan:<br>"
    . "&#8377;150 per employee/month &mdash; Mobile app &middot; &#8377;250 per employee/month &mdash; Mobile + Management panel</p>"
    . "<p>Need help? Just reply to this email.</p>";
  return ["Your HamaraStaff free trial is ready — $name", $inner, "Open My Portal", $portal];
}

function hs_lead_email($name, $code, $email, $phone, $endsNice) {
  $CU = strtoupper($code);
  $portal = "https://hamarastaff.com/$code/";
  $inner = "<p><b>&#127881; New trial signup!</b></p>"
    . "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background:#F0F7F6;border-radius:14px'><tr><td style='padding:18px 20px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:2'>"
    . "<b>Company:</b> " . htmlspecialchars($name) . "<br>"
    . "<b>Portal:</b> <a href='$portal' style='color:#0E6B63;font-weight:700'>hamarastaff.com/$code/</a><br>"
    . "<b>Email:</b> <a href='mailto:$email' style='color:#0E6B63'>$email</a><br>"
    . "<b>Phone:</b> " . ($phone !== '' ? "<a href='tel:$phone' style='color:#0E6B63'>" . htmlspecialchars($phone) . "</a> &middot; <a href='https://wa.me/" . preg_replace('/[^0-9]/', '', $phone) . "' style='color:#1E7B34;font-weight:700'>WhatsApp</a>" : "<i>not given</i>") . "<br>"
    . "<b>Trial ends:</b> $endsNice<br>"
    . "<b>Signed up:</b> " . date('d M Y, h:i A') . " IST"
    . "</td></tr></table>"
    . "<p style='font-size:13px;color:#5B6E6B'>Reach out within the first day &mdash; fresh trials convert best. Activate them from the owner panel once they pay.</p>";
  return ["🔔 New trial: $name ($CU) — $email", $inner, "Open Owner Panel", "https://hamarastaff.com/admin.html"];
}
