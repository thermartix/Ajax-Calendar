<?php
require_once __DIR__ . '/api/bootstrap.php';
requireLogin();

$user = currentUser($mysqliConn);
$json = jsonInput();
$requestedUserId = (int)($json['user_id'] ?? 0);
$userId = (int)($user['user_id'] ?? 0);

if ($requestedUserId > 0 && (string)($user['role'] ?? '') === 'admin') {
    $userId = $requestedUserId;
}
if ($userId <= 0) {
    respond(['success' => false, 'message' => 'Invalid user id'], 422);
}

$stmt = mysqli_prepare($mysqliConn, 'SELECT * FROM events WHERE user_id = ? ORDER BY time ASC');
if (!$stmt) {
    respond(['success' => false, 'message' => 'SQL Error'], 500);
}
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$events = stmtFetchAllAssoc($stmt);
mysqli_stmt_close($stmt);

respond([
    'success' => true,
    'events' => $events
]);
?>
