<?php
require __DIR__ . '/../boot.php';
require __DIR__ . '/schema.php';
header('Content-Type: text/plain; charset=utf-8');
try {
  hs_create_tables();
  if (defined('SEED_DEMO') && SEED_DEMO && hs_seed_demo()) {
    $cb = strtoupper(CODE);
    echo "Seeded 3 demo employees ({$cb}-1001..1003, password " . CODE . "@123).\n";
  }
  echo "OK " . COMPANY_NAME . " — all tables ready (prefix: " . TP . ").\n";
} catch (Exception $e) {
  http_response_code(500);
  echo "INSTALL ERROR: " . $e->getMessage() . "\n";
}
