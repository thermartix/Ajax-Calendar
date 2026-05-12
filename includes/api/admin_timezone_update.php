<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
requireAdmin($user);

$data = jsonInput();
$tz = trim((string)($data['calendar_timezone'] ?? ''));
if ($tz === '') {
    respond(['success' => false, 'message' => 'Timezone is required'], 422);
}

if (!in_array($tz, timezone_identifiers_list(), true)) {
    respond(['success' => false, 'message' => 'Invalid timezone'], 422);
}

$stmt = mysqli_prepare($mysqliConn, 'INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
$key = 'calendar_timezone';
mysqli_stmt_bind_param($stmt, 'ss', $key, $tz);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

respond(['success' => true]);
?>