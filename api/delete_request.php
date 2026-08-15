<?php
require __DIR__ . '/config.php';
require __DIR__ . '/mailer.php';
header('Content-Type: application/json; charset=utf-8');
$in = json_decode(file_get_contents('php://input'), true) ?: [];
if (!empty($in['website'])) { echo json_encode(['ok'=>true]); exit; } /* honeypot */
$code = strtolower(trim($in['code'] ?? ''));
$email = strtolower(trim($in['email'] ?? ''));
$scope = ($in['scope'] ?? '') === 'employee' ? 'Single employee' : 'Entire company account';
$details = substr(trim($in['details'] ?? ''), 0, 800);
if (!preg_match('/^[a-z0-9][a-z0-9-]{1,19}$/', $code) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Please check the company code and email']); exit;
}
$body = "<p><b>&#128465; Account deletion request</b></p>"
 . "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background:#FBE9E7;border-radius:14px'><tr><td style='padding:18px 20px;font-family:Arial,sans-serif;font-size:14px;line-height:2'>"
 . "<b>Portal code:</b> " . htmlspecialchars(strtoupper($code)) . "<br>"
 . "<b>Requester email:</b> " . htmlspecialchars($email) . "<br>"
 . "<b>Scope:</b> $scope<br>"
 . "<b>Details:</b> " . nl2br(htmlspecialchars($details ?: '—')) . "<br>"
 . "<b>Received:</b> " . date('d M Y, h:i A') . " IST"
 . "</td></tr></table>"
 . "<p style='font-size:13px;color:#5B6E6B'>Verify with the registered account email, then delete via the owner panel (and DB tables if full deletion) within 30 days.</p>";
foreach (['someswararao.pyle@gmail.com', 'info@hamarastaff.com'] as $to) {
  hs_send_mail($to, "🗑 Deletion request: " . strtoupper($code) . " — $scope", $body, "Open Owner Panel", "https://hamarastaff.com/admin.html");
}
hs_send_mail($email, "We received your HamaraStaff deletion request",
 "<p>We have received your request to delete <b>$scope</b> for portal <b>" . htmlspecialchars(strtoupper($code)) . "</b>.</p>"
 . "<p>We will verify this request with the registered account owner and complete the deletion within <b>30 days</b>. You will receive a confirmation email when it is done.</p>"
 . "<p>If you did not make this request, reply to this email immediately.</p>");
echo json_encode(['ok'=>true]);
