<?php
require_once __DIR__ . '/api/bootstrap.php';
requireLogin();

$user = currentUser($mysqliConn);
$json = jsonInput();
$eventId = (int)($json['event_id'] ?? 0);

if ($eventId <= 0) {
    respond(['success' => false, 'message' => 'Invalid event id'], 422);
}

$q = mysqli_prepare($mysqliConn, 'SELECT id, user_id, country_id FROM events WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($q, 'i', $eventId);
mysqli_stmt_execute($q);
$event = stmtFetchOneAssoc($q);
mysqli_stmt_close($q);

if (!$event) {
    respond(['success' => false, 'message' => 'Event not found'], 404);
}
if (!canEditEvent($user, ['user_id' => (int)$event['user_id'], 'country_id' => (int)$event['country_id']])) {
    respond(['success' => false, 'message' => 'Not allowed'], 403);
}

$stmt = mysqli_prepare($mysqliConn, 'DELETE FROM events WHERE id = ?');
if (!$stmt) {
    respond(['success' => false, 'message' => 'SQL error'], 500);
}
mysqli_stmt_bind_param($stmt, 'i', $eventId);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    respond(['success' => false, 'message' => 'SQL error'], 500);
}

respond(['success' => true]);
?>
