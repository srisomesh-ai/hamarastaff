<?php
/* Push notification diagnostics — owner use only.
   status : /api/pushcheck.php?key=hs-push-8k2m9x-2026
   test   : /api/pushcheck.php?key=hs-push-8k2m9x-2026&send=CODE          */
require __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
if (($_GET['key'] ?? '') !== 'hs-push-8k2m9x-2026') { http_response_code(403); exit('forbidden'); }

$keyFile = __DIR__ . '/fcm-key.json';
echo "== HamaraStaff push diagnostics ==\n\n";
echo "1) Server key (api/fcm-key.json): " . (file_exists($keyFile) ? "PRESENT ✓" : "MISSING ✗  <-- upload it via hPanel File Manager") . "\n";

$project = null;
if (file_exists($keyFile)) {
  $k = json_decode(file_get_contents($keyFile), true);
  $project = $k['project_id'] ?? null;
  echo "   project_id: " . ($project ?: 'INVALID JSON ✗') . "\n";
  echo "   client_email: " . (isset($k['client_email']) ? 'ok' : 'missing ✗') . "\n";
  echo "   private_key: " . (isset($k['private_key']) ? 'ok' : 'missing ✗') . "\n";
}

$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
  [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

echo "\n2) Registered device tokens per client:\n";
$clients = [];
foreach (glob(dirname(__DIR__) . '/clients/*.php') as $f) $clients[] = basename($f, '.php');
foreach ($clients as $code) {
  try {
    $rows = $pdo->query("SELECT t.role, t.emp_id, t.updated_at, e.name, e.emp_code
      FROM `{$code}_push_tokens` t LEFT JOIN `{$code}_employees` e ON e.id = t.emp_id
      ORDER BY t.role, t.updated_at DESC")->fetchAll();
    if (!$rows) { echo "   $code - no devices registered\n"; continue; }
    $emps = 0;
    try { $emps = (int)$pdo->query("SELECT COUNT(*) FROM `{$code}_employees`")->fetchColumn(); } catch (Exception $e) {}
    echo "   $code (total employees in account: $emps):\n";
    foreach ($rows as $r) {
      $who = $r['role'] === 'admin' ? 'ADMIN device' : (($r['name'] ?: 'deleted employee') . ($r['emp_code'] ? " ({$r['emp_code']})" : ''));
      echo "      * $who - registered {$r['updated_at']}\n";
    }
  } catch (Exception $e) {
    echo "   $code - no devices registered yet\n";
  }
}
echo "\n   (No tokens = the app on that phone hasn't registered. Requires the v3.7 app build,\n    notification permission allowed, and a login/refresh inside the app.)\n";

/* ---- optional test send ---- */
$send = strtolower(trim($_GET['send'] ?? ''));
if ($send !== '') {
  echo "\n3) TEST SEND to all '$send' tokens:\n";
  if (!file_exists($keyFile) || !$project) exit("   cannot send — fcm-key.json missing/invalid\n");
  try {
    $tokens = $pdo->query("SELECT token, role FROM `{$send}_push_tokens`")->fetchAll();
  } catch (Exception $e) { exit("   no token table for '$send'\n"); }
  if (!$tokens) exit("   zero tokens registered for '$send'\n");

  $k = json_decode(file_get_contents($keyFile), true);
  $now = time();
  $hdr = rtrim(strtr(base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
  $clm = rtrim(strtr(base64_encode(json_encode(['iss' => $k['client_email'], 'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
    'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600])), '+/', '-_'), '=');
  openssl_sign("$hdr.$clm", $sig, $k['private_key'], 'sha256WithRSAEncryption');
  $jwt = "$hdr.$clm." . rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
  $ch = curl_init('https://oauth2.googleapis.com/token');
  curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
    CURLOPT_POSTFIELDS => http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt])]);
  $tok = json_decode(curl_exec($ch), true); curl_close($ch);
  if (empty($tok['access_token'])) exit("   OAuth FAILED ✗ — key file may be from the wrong Firebase project\n   " . json_encode($tok) . "\n");
  echo "   OAuth token: ok ✓\n";

  foreach ($tokens as $t) {
    $msg = ['message' => ['token' => $t['token'],
      'notification' => ['title' => '🔔 HamaraStaff test', 'body' => 'Push is working! (' . strtoupper($send) . ' · ' . $t['role'] . ' · ' . date('h:i A') . ')'],
      'android' => ['priority' => 'HIGH', 'notification' => ['channel_id' => 'hamarastaff', 'icon' => 'ic_stat_hs', 'color' => '#0E6B63', 'default_sound' => true, 'default_vibrate_timings' => true]]]];
    $ch = curl_init("https://fcm.googleapis.com/v1/projects/$project/messages:send");
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
      CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $tok['access_token'], 'Content-Type: application/json'],
      CURLOPT_POSTFIELDS => json_encode($msg)]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "   {$t['role']} token …" . substr($t['token'], -8) . " → HTTP $code " . ($code == 200 ? '✓ sent' : '✗ ' . substr((string)$resp, 0, 120)) . "\n";
  }
}
echo "\nDone.\n";
