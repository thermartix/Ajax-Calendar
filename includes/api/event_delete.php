<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
$data = jsonInput();
$id = (int)($data['id'] ?? 0);
if ($id <= 0) {
    respond(['success' => false, 'message' => 'Invalid event id'], 422);
}

$stmt = mysqli_prepare($mysqliConn, 'SELECT id, country_id FROM events WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$existing = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$existing) {
    respond(['success' => false, 'message' => 'Event not found'], 404);
}
if ($user['role'] !== 'admin' && (int)$existing['country_id'] !== (int)$user['country_id']) {
    respond(['success' => false, 'message' => 'Not allowed to delete this event'], 403);
}

$stmt = mysqli_prepare($mysqliConn, 'DELETE FROM events WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
respond(['success' => true]);
?>