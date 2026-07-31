<?php
/* Shared table schema — requires db() + TP to be defined (via portal/boot.php or provision.php) */
function hs_create_tables() {
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
}
function hs_seed_demo() {
  $db = db();
  $n = $db->query("SELECT COUNT(*) c FROM hs_employees")->fetch()['c'];
  if ($n > 0) return false;
  $codebase = strtoupper(rtrim(TP, '_'));
  $pw = strtolower($codebase) . '@123';
  $st = $db->prepare("INSERT INTO hs_employees (emp_code,name,password,area) VALUES (?,?,?,?)");
  $st->execute([$codebase.'-1001', 'Ravi Kumar',   $pw, 'Dwaraka Nagar']);
  $st->execute([$codebase.'-1002', 'Priya Sharma', $pw, 'MVP Colony']);
  $st->execute([$codebase.'-1003', 'Suresh Babu',  $pw, 'Gajuwaka']);
  return true;
}
