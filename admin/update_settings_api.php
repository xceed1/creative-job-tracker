<?php
require_once "../auth/guard.php";
requireRole(['ADMIN']);
require_once "../config/db.php";

header('Content-Type: application/json; charset=utf-8');

$settings = $_POST['settings'] ?? null;

if (!is_array($settings)) {
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit;
}

/* ---------------- FETCH SETTING TYPES ---------------- */
$stmt = $pdo->query("
    SELECT setting_key, setting_type
    FROM system_settings
");
$types = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

/* ---------------- VALIDATION ---------------- */
foreach ($settings as $key => $value) {

    if (!isset($types[$key])) {
        echo json_encode(['success' => false, 'error' => "Unknown setting: $key"]);
        exit;
    }

    $type = $types[$key];
    $value = trim($value);

    switch ($type) {

        case 'string':
            if (strlen($value) > 255) {
                fail("$key exceeds maximum length");
            }
            break;

        case 'int':
            if (!ctype_digit($value)) {
                fail("$key must be a number");
            }
            break;

        case 'boolean':
            if (!in_array($value, ['0', '1'], true)) {
                fail("$key must be 0 or 1");
            }
            break;

        case 'email':
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                fail("$key must be a valid email");
            }
            break;

        case 'url':
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                fail("$key must be a valid URL");
            }
            break;

        default:
            fail("Invalid type for $key");
    }
}

/* ---------------- SAVE SETTINGS ---------------- */
$stmt = $pdo->prepare("
    UPDATE system_settings
    SET setting_value = ?
    WHERE setting_key = ?
");

foreach ($settings as $key => $value) {
    $stmt->execute([trim($value), $key]);
}

echo json_encode(['success' => true]);
exit;

/* ---------------- HELPER ---------------- */
function fail(string $msg): void {
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}
