<?php
require_once "../config/db_context.php";
setDbContext($pdo, 'TRAFFIC');

http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
  'success' => false,
  'error' => 'Deprecated API. Use assignment_approve_api.php'
]);
exit;
