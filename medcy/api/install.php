<?php
require __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
try {
  $db = db();
  $db->exec("CREATE TABLE IF NOT EXISTS hs_employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emp_code VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL,
    area VARCHAR(100) DEFAULT '',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $db->exec("CREATE TABLE IF NOT EXISTS hs_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    att_date DATE NOT NULL,
    start_time DATETIME NOT NULL,
    start_lat VARCHAR(20), start_lng VARCHAR(20), start_area VARCHAR(120),
    end_time DATETIME NULL,
    UNIQUE KEY uq_emp_date (emp_id, att_date),
    INDEX idx_date (att_date)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $db->exec("CREATE TABLE IF NOT EXISTS hs_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    doctor VARCHAR(150) NOT NULL,
    hospital VARCHAR(150) DEFAULT '',
    area VARCHAR(120) DEFAULT '',
    purpose VARCHAR(100) DEFAULT '',
    planned_time VARCHAR(10) DEFAULT '',
    client_email VARCHAR(150) DEFAULT '',
    client_phone VARCHAR(30) DEFAULT '',
    status ENUM('open','reached','closed') NOT NULL DEFAULT 'open',
    created_by VARCHAR(20) NOT NULL DEFAULT 'Self',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp (emp_id), INDEX idx_created (created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $db->exec("CREATE TABLE IF NOT EXISTS hs_task_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    type ENUM('reach','close') NOT NULL,
    event_time DATETIME NOT NULL,
    lat VARCHAR(20), lng VARCHAR(20), area VARCHAR(120),
    INDEX idx_task (task_id), INDEX idx_time (event_time)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $db->exec("CREATE TABLE IF NOT EXISTS hs_visit_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL UNIQUE,
    met VARCHAR(150) DEFAULT '',
    products TEXT,
    demo_given VARCHAR(5) DEFAULT 'No',
    samples INT DEFAULT 0,
    outcome VARCHAR(120) DEFAULT '',
    remarks TEXT,
    next_visit DATE NULL,
    loc_attached TINYINT(1) DEFAULT 0,
    sent_via VARCHAR(50) DEFAULT '',
    closed_at DATETIME NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $db->exec("CREATE TABLE IF NOT EXISTS hs_approvals (
    ym CHAR(7) PRIMARY KEY,
    approved_at DATETIME NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $db->exec("CREATE TABLE IF NOT EXISTS hs_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actor VARCHAR(100) NOT NULL,
    action VARCHAR(60) NOT NULL,
    details TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $n = $db->query("SELECT COUNT(*) c FROM hs_employees")->fetch()['c'];
  if ($n == 0) {
    $st = $db->prepare("INSERT INTO hs_employees (emp_code,name,password,area) VALUES (?,?,?,?)");
    $st->execute(['MEDCY-1001', 'Ravi Kumar',   'medcy@123', 'Dwaraka Nagar']);
    $st->execute(['MEDCY-1002', 'Priya Sharma', 'medcy@123', 'MVP Colony']);
    $st->execute(['MEDCY-1003', 'Suresh Babu',  'medcy@123', 'Gajuwaka']);
    echo "Seeded 3 default employees.\n";
  }
  echo "✓ MEDCY database installed successfully. All tables ready.\n";
  echo "Data retention: " . RETENTION_DAYS . " days (audit purpose).\n";
  echo "You can now use the app. You may delete this file if you wish.\n";
} catch (Exception $e) {
  http_response_code(500);
  echo "INSTALL ERROR: " . $e->getMessage() . "\n";
  echo "→ Check that config.php has the correct database name, user and password.\n";
}
