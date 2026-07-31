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

function hs_send_mail($to, $subject, $innerHtml, $ctaText = '', $ctaUrl = '') {
  $html = hs_wrap_email($innerHtml, $ctaText, $ctaUrl);
  $headers = "MIME-Version: 1.0\r\n"
    . "Content-Type: text/html; charset=utf-8\r\n"
    . "From: HamaraStaff <info@hamarastaff.com>\r\n"
    . "Reply-To: info@hamarastaff.com\r\n";
  return @mail($to, $subject, $html, $headers);
}
