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
function hs_seed_demo_visits() {
  $db = db();
  if ($db->query("SELECT COUNT(*) c FROM hs_tasks")->fetch()['c'] > 0) return false;
  $emps = $db->query("SELECT id, emp_code FROM hs_employees ORDER BY id LIMIT 3")->fetchAll();
  if (count($emps) < 2) return false;
  $e1 = $emps[0]['id']; $e2 = $emps[1]['id'];
  $D = date('Y-m-d');
  /* today's attendance for two staff */
  $a = $db->prepare("INSERT IGNORE INTO hs_attendance (emp_id,att_date,start_time,start_lat,start_lng,start_area) VALUES (?,?,?,?,?,?)");
  $a->execute([$e1, $D, "$D 09:05:00", '17.72861', '83.30502', 'Dwaraka Nagar']);
  $a->execute([$e2, $D, "$D 09:20:00", '17.74195', '83.33501', 'MVP Colony']);
  /* one closed visit with full report */
  $db->prepare("INSERT INTO hs_tasks (emp_id,doctor,hospital,area,purpose,planned_time,client_email,status,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?)")
     ->execute([$e1,'Dr. K. Prasad','Apollo Clinic','Waltair Uplands','Product Demo','10:00','','closed','Self',"$D 09:06:00"]);
  $t1 = $db->lastInsertId();
  $ev = $db->prepare("INSERT INTO hs_task_events (task_id,type,event_time,lat,lng,area) VALUES (?,?,?,?,?,?)");
  $ev->execute([$t1,'reach',"$D 10:05:00",'17.72154','83.32291','Waltair Uplands']);
  $ev->execute([$t1,'close',"$D 10:45:00",'17.72149','83.32304','Waltair Uplands']);
  $db->prepare("INSERT INTO hs_visit_reports (task_id,met,products,demo_given,samples,outcome,remarks,loc_attached,sent_via,closed_at) VALUES (?,?,?,?,?,?,?,?,?,?)")
     ->execute([$t1,'Dr. K. Prasad','["Product A","Product B"]','Yes',6,'Interested — wants pricing','Liked the demo. Asked for institutional pricing before next month.',1,'Email',"$D 10:45:00"]);
  /* one visit in progress */
  $db->prepare("INSERT INTO hs_tasks (emp_id,doctor,hospital,area,purpose,planned_time,status,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?)")
     ->execute([$e2,'Dr. Meena Rao','Care Hospital','Gajuwaka','Follow-up Visit','11:30','reached','Manager',"$D 09:21:00"]);
  $t2 = $db->lastInsertId();
  $ev->execute([$t2,'reach',"$D 11:20:00",'17.68702','83.21876','Gajuwaka']);
  /* one open visit */
  $db->prepare("INSERT INTO hs_tasks (emp_id,doctor,hospital,area,purpose,planned_time,status,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?)")
     ->execute([$e1,'Sri Sai Medicals','Pharmacy','MVP Colony','Order Collection','15:00','open','Self',"$D 09:07:00"]);
  return true;
}
