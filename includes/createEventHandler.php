<?php
require_once __DIR__ . '/api/bootstrap.php';
requireLogin();

$user = currentUser($mysqliConn);
$json = jsonInput();

$title = trim((string)($json['title'] ?? ''));
$date = trim((string)($json['date'] ?? ''));
$time = trim((string)($json['time'] ?? ''));
$userId = (int)($user['user_id'] ?? 0);

if ($title === '' || $date === '' || $time === '' || $userId <= 0) {
    respond([
        'success' => false,
        'message' => 'Empty fields'
    ], 422);
}

$stmt = mysqli_prepare($mysqliConn, 'INSERT INTO events (user_id, date, time, title) VALUES (?, ?, ?, ?)');
if (!$stmt) {
    respond(['success' => false, 'message' => 'SQL error'], 500);
}
mysqli_stmt_bind_param($stmt, 'isss', $userId, $date, $time, $title);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    respond(['success' => false, 'message' => 'SQL error'], 500);
}

respond(['success' => true]);
