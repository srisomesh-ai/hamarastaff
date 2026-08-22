<?php
/* Push notifications to the OWNER's devices (tokens in hs_owner_push). */
function owner_push_pdo() {
  return new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}
function owner_push_register($token) {
  if (!preg_match('/^[A-Za-z0-9_\-:]{20,4096}$/', $token)) return false;
  $pdo = owner_push_pdo();
  $pdo->exec("CREATE TABLE IF NOT EXISTS hs_owner_push (token VARCHAR(255) PRIMARY KEY, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB");
  $pdo->prepare("INSERT INTO hs_owner_push (token) VALUES (?) ON DUPLICATE KEY UPDATE updated_at=NOW()")->execute([$token]);
  return true;
}
function owner_push_send($title, $body) {
  $keyFile = __DIR__ . '/fcm-key.json';
  if (!file_exists($keyFile)) return;
  $k = json_decode(file_get_contents($keyFile), true);
  if (!$k || empty($k['project_id'])) return;
  try { $pdo = owner_push_pdo(); $tokens = $pdo->query("SELECT token FROM hs_owner_push")->fetchAll(PDO::FETCH_COLUMN); }
  catch (Throwable $e) { return; }
  if (!$tokens) return;
  /* oauth (cached) */
  $cache = __DIR__ . '/fcm_token_cache.json'; $access = null;
  if (@file_exists($cache)) { $c = json_decode((string)@file_get_contents($cache), true); if ($c && ($c['exp'] ?? 0) > time() + 60) $access = $c['tok']; }
  if (!$access) {
    $now = time();
    $hdr = rtrim(strtr(base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
    $clm = rtrim(strtr(base64_encode(json_encode(['iss' => $k['client_email'], 'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
      'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600])), '+/', '-_'), '=');
    openssl_sign("$hdr.$clm", $sig, $k['private_key'], 'sha256WithRSAEncryption');
    $jwt = "$hdr.$clm." . rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
      CURLOPT_POSTFIELDS => http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt])]);
    $res = json_decode(curl_exec($ch), true); curl_close($ch);
    if (empty($res['access_token'])) return;
    $access = $res['access_token'];
    @file_put_contents($cache, json_encode(['tok' => $access, 'exp' => $now + (int)($res['expires_in'] ?? 3600)]));
  }
  foreach ($tokens as $t) {
    $msg = ['message' => ['token' => $t,
      'notification' => ['title' => $title, 'body' => $body],
      'android' => ['priority' => 'HIGH', 'notification' => ['channel_id' => 'hamarastaff', 'icon' => 'ic_stat_hs', 'color' => '#0E6B63', 'default_sound' => true, 'default_vibrate_timings' => true]]]];
    $ch = curl_init('https://fcm.googleapis.com/v1/projects/' . $k['project_id'] . '/messages:send');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8,
      CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $access, 'Content-Type: application/json'],
      CURLOPT_POSTFIELDS => json_encode($msg)]);
    $resp = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($code === 404 || strpos((string)$resp, 'UNREGISTERED') !== false) {
      try { owner_push_pdo()->prepare("DELETE FROM hs_owner_push WHERE token=?")->execute([$t]); } catch (Throwable $e) {}
    }
  }
}
