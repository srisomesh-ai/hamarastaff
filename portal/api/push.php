<?php
/* ============ HamaraStaff push notifications (FCM HTTP v1) ============
   Requires api/fcm-key.json (Firebase service-account key, NOT in git).
   Tokens live per-tenant in {code}_push_tokens (lazily created).
====================================================================== */

function push_available() {
  return file_exists(__DIR__ . '/../../api/fcm-key.json') || file_exists(__DIR__ . '/fcm-key.json');
}

function push_keyfile() {
  if (file_exists(__DIR__ . '/fcm-key.json')) return __DIR__ . '/fcm-key.json';
  return __DIR__ . '/../../api/fcm-key.json';
}

function push_ensure_table() {
  db()->exec("CREATE TABLE IF NOT EXISTS hs_push_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NULL,
    role ENUM('emp','admin') NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function push_register_token($role, $empId, $token) {
  if (!preg_match('/^[A-Za-z0-9_\-:]{20,4096}$/', $token)) return false;
  push_ensure_table();
  $st = db()->prepare("INSERT INTO hs_push_tokens (emp_id, role, token) VALUES (?,?,?)
    ON DUPLICATE KEY UPDATE emp_id=VALUES(emp_id), role=VALUES(role), updated_at=NOW()");
  $st->execute([$empId, $role, $token]);
  return true;
}

function push_access_token() {
  $cache = sys_get_temp_dir() . '/hs_fcm_token.json';
  if (file_exists($cache)) {
    $c = json_decode(file_get_contents($cache), true);
    if ($c && $c['exp'] > time() + 60) return $c['tok'];
  }
  $key = json_decode(file_get_contents(push_keyfile()), true);
  if (!$key) return null;
  $now = time();
  $hdr = rtrim(strtr(base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
  $clm = rtrim(strtr(base64_encode(json_encode([
    'iss' => $key['client_email'],
    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
    'aud' => 'https://oauth2.googleapis.com/token',
    'iat' => $now, 'exp' => $now + 3600
  ])), '+/', '-_'), '=');
  openssl_sign("$hdr.$clm", $sig, $key['private_key'], 'sha256WithRSAEncryption');
  $jwt = "$hdr.$clm." . rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
  $ch = curl_init('https://oauth2.googleapis.com/token');
  curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
    CURLOPT_POSTFIELDS => http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt])]);
  $res = json_decode(curl_exec($ch), true);
  curl_close($ch);
  if (empty($res['access_token'])) return null;
  file_put_contents($cache, json_encode(['tok' => $res['access_token'], 'exp' => $now + (int)($res['expires_in'] ?? 3600)]));
  return $res['access_token'];
}

/* send to a set of rows [{token,...}]; deletes tokens FCM reports invalid */
function push_send_to($rows, $title, $body) {
  if (!push_available() || !$rows) return;
  $key = json_decode(file_get_contents(push_keyfile()), true);
  $project = $key['project_id'] ?? null;
  $access = push_access_token();
  if (!$project || !$access) return;
  $url = "https://fcm.googleapis.com/v1/projects/$project/messages:send";
  foreach ($rows as $r) {
    $msg = ['message' => [
      'token' => $r['token'],
      'notification' => ['title' => $title, 'body' => $body],
      'android' => ['priority' => 'HIGH', 'notification' => ['channel_id' => 'hamarastaff']]
    ]];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8,
      CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $access, 'Content-Type: application/json'],
      CURLOPT_POSTFIELDS => json_encode($msg)]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 404 || strpos((string)$resp, 'UNREGISTERED') !== false) {
      try { db()->prepare("DELETE FROM hs_push_tokens WHERE token=?")->execute([$r['token']]); } catch (Exception $e) {}
    }
  }
}

function push_to_admins($title, $body) {
  if (!push_available()) return;
  try {
    push_ensure_table();
    $rows = db()->query("SELECT token FROM hs_push_tokens WHERE role='admin'")->fetchAll();
    push_send_to($rows, $title, $body);
  } catch (Exception $e) {}
}

function push_to_emp($empId, $title, $body) {
  if (!push_available()) return;
  try {
    push_ensure_table();
    $st = db()->prepare("SELECT token FROM hs_push_tokens WHERE role='emp' AND emp_id=?");
    $st->execute([$empId]);
    push_send_to($st->fetchAll(), $title, $body);
  } catch (Exception $e) {}
}
