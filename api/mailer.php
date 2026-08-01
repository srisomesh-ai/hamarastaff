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
