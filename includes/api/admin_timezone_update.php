<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
requireAdmin($user);

$data = jsonInput();
$tz = trim((string)($data['calendar_timezone'] ?? ''));
$showEventAuthor = isset($data['show_event_author']) ? (int)$data['show_event_author'] : 1;
if ($tz === '') {
    respond(['success' => false, 'message' => 'Timezone is required'], 422);
}

if (!in_array($tz, timezone_identifiers_list(), true)) {
    respond(['success' => false, 'message' => 'Invalid timezone'], 422);
}
if ($showEventAuthor !== 0 && $showEventAuthor !== 1) {
    respond(['success' => false, 'message' => 'Invalid author visibility value'], 422);
}

$stmt = mysqli_prepare($mysqliConn, 'INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
$key = 'calendar_timezone';
mysqli_stmt_bind_param($stmt, 'ss', $key, $tz);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$stmt2 = mysqli_prepare($mysqliConn, 'INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
$key2 = 'show_event_author';
$val2 = (string)$showEventAuthor;
mysqli_stmt_bind_param($stmt2, 'ss', $key2, $val2);
mysqli_stmt_execute($stmt2);
mysqli_stmt_close($stmt2);

respond(['success' => true]);
?>
