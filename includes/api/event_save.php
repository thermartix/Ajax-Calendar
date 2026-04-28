<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
$data = jsonInput();

$id = isset($data['id']) && $data['id'] !== '' ? (int)$data['id'] : null;
$title = trim((string)($data['title'] ?? ''));
$description = trim((string)($data['description'] ?? ''));
$link = trim((string)($data['event_link'] ?? ''));
$countryId = (int)($data['country_id'] ?? 0);
$startAt = (string)($data['start_at'] ?? '');
$endAt = (string)($data['end_at'] ?? '');

if ($title === '' || $countryId <= 0 || $startAt === '' || $endAt === '') {
    respond(['success' => false, 'message' => 'Missing required fields'], 422);
}
if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL)) {
    respond(['success' => false, 'message' => 'Invalid event link URL'], 422);
}
if ($endAt < $startAt) {
    respond(['success' => false, 'message' => 'End must be after start'], 422);
}
if ($user['role'] === 'category_editor' && (int)$user['country_id'] !== $countryId) {
    respond(['success' => false, 'message' => 'Not allowed outside your category'], 403);
}

if ($id) {
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
        respond(['success' => false, 'message' => 'Not allowed to edit this event'], 403);
    }

    $stmt = mysqli_prepare($mysqliConn, 'UPDATE events SET country_id = ?, title = ?, description = ?, event_link = ?, start_at = ?, end_at = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'isssssi', $countryId, $title, $description, $link, $startAt, $endAt, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    respond(['success' => true, 'id' => $id]);
}

$userId = (int)$user['user_id'];
$stmt = mysqli_prepare($mysqliConn, 'INSERT INTO events (user_id, country_id, title, description, event_link, start_at, end_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'iisssss', $userId, $countryId, $title, $description, $link, $startAt, $endAt);
mysqli_stmt_execute($stmt);
$newId = mysqli_insert_id($mysqliConn);
mysqli_stmt_close($stmt);

respond(['success' => true, 'id' => $newId]);
?>