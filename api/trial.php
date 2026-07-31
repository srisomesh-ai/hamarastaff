<?php
/* ============ HamaraStaff FREE TRIAL SIGNUP API ============ */
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
function out($d){ echo json_encode(['ok'=>true,'data'=>$d]); exit; }
function fail($e,$c=400){ http_response_code($c); echo json_encode(['ok'=>false,'error'=>$e]); exit; }

$ROOT = dirname(__DIR__);
$CLIENTS = $ROOT . '/clients';
if (!is_dir($CLIENTS)) mkdir($CLIENTS, 0755, true);

$in = json_decode(file_get_contents('php://input'), true) ?: [];
if (($in['action'] ?? '') !== 'signup') fail('unknown_action');

/* honeypot: real users never fill this hidden field */
if (!empty($in['website'])) out(['code'=>'ok']);

$name  = trim($in['company'] ?? '');
$code  = strtolower(trim($in['code'] ?? ''));
$email = strtolower(trim($in['email'] ?? ''));
$phone = trim($in['phone'] ?? '');

if (strlen($name) < 3) fail('Please enter your company / institute name');
if (!preg_match('/^[a-z0-9][a-z0-9-]{1,19}$/', $code)) fail('Portal code must be 2–20 letters or numbers');
if (in_array($code, ['api','portal','clients','assets','admin','login','pricing','index','trial','www','mail'])) fail('That portal code is reserved — please choose another');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email address — your login details will be sent there');
if (file_exists("$CLIENTS/$code.php")) fail('That portal code is already taken — please choose another');

/* one trial per email */
foreach (glob("$CLIENTS/*.php") as $f) {
  if (strpos(file_get_contents($f), "TRIAL_EMAIL', '" . addslashes($email)) !== false) {
    fail('A trial already exists for this email. Contact info@hamarastaff.com if you need help.');
  }
}

/* generate admin password */
$apass = 'HS' . substr(str_shuffle('23456789abcdefghjkmnpqrstuvwxyz'), 0, 6) . '@' . rand(10, 99);
$ends  = date('Y-m-d', strtotime('+7 days'));

/* create tables + seed 3 demo employees (shared DB, prefixed) */
define('TP', $code . '_');
class TPDO extends PDO {
  #[\ReturnTypeWillChange]
  public function prepare($sql, $options = []) { return parent::prepare(str_replace('hs_', TP, $sql), $options); }
  #[\ReturnTypeWillChange]
  public function query($sql, ...$rest) { return parent::query(str_replace('hs_', TP, $sql), ...$rest); }
  #[\ReturnTypeWillChange]
  public function exec($sql) { return parent::exec(str_replace('hs_', TP, $sql)); }
}
function db() {
  static $pdo = null;
  if ($pdo) return $pdo;
  $pdo = new TPDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
  return $pdo;
}
try {
  require $ROOT . '/portal/api/schema.php';
  hs_create_tables();
  hs_seed_demo();
} catch (Exception $e) { fail('Could not set up your trial right now. Please try again or email info@hamarastaff.com', 500); }

/* the client file */
$cfg = "<?php\n"
  . "define('COMPANY_NAME', '" . addslashes($name) . "');\n"
  . "define('ADMIN_USER', 'admin');\n"
  . "define('ADMIN_PASS', '" . addslashes($apass) . "');\n"
  . "define('SEED_DEMO', true);\n"
  . "define('PLAN', 'trial');\n"
  . "define('TRIAL_ENDS', '$ends');\n"
  . "define('TRIAL_EMAIL', '" . addslashes($email) . "');\n"
  . "define('TRIAL_PHONE', '" . addslashes($phone) . "');\n";
file_put_contents("$CLIENTS/$code.php", $cfg);

$CU = strtoupper($code);
$portal = "https://hamarastaff.com/$code/";
$endsNice = date('d M Y', strtotime($ends));

/* email the credentials */
$body = "Welcome to HamaraStaff, $name!\n\n"
  . "Your 7-day FREE trial is ready — full access, no payment needed.\n\n"
  . "YOUR PORTAL\n$portal\n\n"
  . "MANAGEMENT LOGIN (Desktop panel)\nUsername: admin\nPassword: $apass\n\n"
  . "SAMPLE FIELD STAFF LOGINS (Mobile app)\n"
  . "$CU-1001, $CU-1002, $CU-1003\nPassword: $code@123\n"
  . "(You can rename these or add your own staff from the management panel → Employees)\n\n"
  . "Your trial ends on $endsNice. To continue after that, simply reply to this email and choose a plan:\n"
  . "• ₹150 per employee/month — Mobile app\n"
  . "• ₹250 per employee/month — Mobile app + Desktop management panel\n\n"
  . "Need help? Just reply to this email.\n\n"
  . "Regards,\nTeam HamaraStaff\nhttps://hamarastaff.com";
$headers = "From: HamaraStaff <info@hamarastaff.com>\r\nReply-To: info@hamarastaff.com\r\nContent-Type: text/plain; charset=utf-8";
$mailed = @mail($email, "Your HamaraStaff free trial is ready — $name", $body, $headers);

/* notify the owner */
@mail('info@hamarastaff.com', "New trial signup: $name ($CU)",
  "Company: $name\nCode: $code\nPortal: $portal\nEmail: $email\nPhone: $phone\nTrial ends: $endsNice", $headers);

out([
  'code' => $code,
  'url' => "/$code/",
  'admin_user' => 'admin',
  'admin_pass' => $apass,
  'staff_ids' => "$CU-1001 · $CU-1002 · $CU-1003",
  'staff_pass' => "$code@123",
  'ends' => $endsNice,
  'mailed' => (bool)$mailed
]);
