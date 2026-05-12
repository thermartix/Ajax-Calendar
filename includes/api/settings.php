<?php
require_once __DIR__ . '/bootstrap.php';

$res = mysqli_query($mysqliConn, "SELECT COUNT(*) AS c FROM users WHERE role='admin'");
$row = mysqli_fetch_assoc($res);

$tz = 'Europe/Prague';
$tzStmt = mysqli_prepare($mysqliConn, 'SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1');
$key = 'calendar_timezone';
mysqli_stmt_bind_param($tzStmt, 's', $key);
mysqli_stmt_execute($tzStmt);
$tzRow = stmtFetchOneAssoc($tzStmt);
if ($tzRow) {
    $tz = $tzRow['setting_value'];
}
mysqli_stmt_close($tzStmt);

respond([
    'success' => true,
    'adminExists' => ((int)$row['c']) > 0,
    'calendarTimezone' => $tz
]);
?>
