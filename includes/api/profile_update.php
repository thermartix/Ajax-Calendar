<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
$uid = (int)$user['user_id'];
$data = jsonInput();
$first = trim((string)($data['first_name'] ?? ''));
$last = trim((string)($data['last_name'] ?? ''));
$datetimeFormat = (string)($data['datetime_format'] ?? '');
$stmt = mysqli_prepare($mysqliConn, 'UPDATE users SET first_name = ?, last_name = ? WHERE user_id = ?');
mysqli_stmt_bind_param($stmt, 'ssi', $first, $last, $uid);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
if ($datetimeFormat !== '') {
    $fmt = $datetimeFormat === 'eu' ? 'eu' : 'us';
    appSettingSet($mysqliConn, 'user_datetime_format_' . $uid, $fmt);
}
respond(['success' => true]);
?>
