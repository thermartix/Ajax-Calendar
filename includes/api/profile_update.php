<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
$uid = (int)$user['user_id'];
$data = jsonInput();
$first = trim((string)($data['first_name'] ?? ''));
$last = trim((string)($data['last_name'] ?? ''));
$countryId = isset($data['country_id']) && $data['country_id'] !== '' ? (int)$data['country_id'] : null;
$datetimeFormat = (string)($data['datetime_format'] ?? '');
$stmt = mysqli_prepare($mysqliConn, 'UPDATE users SET first_name = ?, last_name = ?, country_id = ? WHERE user_id = ?');
mysqli_stmt_bind_param($stmt, 'ssii', $first, $last, $countryId, $uid);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
if ($datetimeFormat !== '') {
    $fmt = $datetimeFormat === 'eu' ? 'eu' : 'us';
    appSettingSet($mysqliConn, 'user_datetime_format_' . $uid, $fmt);
}
respond(['success' => true]);
?>
