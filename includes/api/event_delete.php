<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
$user['allowed_country_ids'] = userAllowedCountryIds($mysqliConn, (int)$user['user_id']);
$data = jsonInput();
$id = (int)($data['id'] ?? 0);
$scope = (string)($data['scope'] ?? 'series');
$occurrenceStartAt = trim((string)($data['occurrence_start_at'] ?? ''));
if ($id <= 0) {
    respond(['success' => false, 'message' => 'Invalid event id'], 422);
}

$hasRecurringColumns = false;
$recCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'recurrence_type'");
if ($recCheck && mysqli_num_rows($recCheck) > 0) {
    $hasRecurringColumns = true;
}
$hasRecurrenceUntilColumn = false;
$recUntilCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'recurrence_until'");
if ($recUntilCheck && mysqli_num_rows($recUntilCheck) > 0) {
    $hasRecurrenceUntilColumn = true;
}

$hasExceptionsTable = false;
$excCheck = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_occurrence_exceptions'");
if ($excCheck && mysqli_num_rows($excCheck) > 0) {
    $hasExceptionsTable = true;
}

if ($scope !== 'series' && $scope !== 'occurrence' && $scope !== 'from_here') {
    respond(['success' => false, 'message' => 'Invalid delete scope'], 422);
}

$sql = 'SELECT id, user_id, country_id';
if ($hasRecurringColumns) {
    $sql .= ', recurrence_type';
}
$sql .= ' FROM events WHERE id = ? LIMIT 1';
$stmt = mysqli_prepare($mysqliConn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$existing = stmtFetchOneAssoc($stmt);
mysqli_stmt_close($stmt);

if (!$existing) {
    respond(['success' => false, 'message' => 'Event not found'], 404);
}
if (!canEditEvent($user, ['user_id' => (int)$existing['user_id'], 'country_id' => (int)$existing['country_id']])) {
    respond(['success' => false, 'message' => 'Not allowed to delete this event'], 403);
}

if ($scope === 'occurrence') {
    if (!$hasRecurringColumns || (($existing['recurrence_type'] ?? 'none') !== 'monthly_nth_weekday')) {
        respond(['success' => false, 'message' => 'Occurrence delete is only available for recurring events'], 422);
    }
    if ($occurrenceStartAt === '') {
        respond(['success' => false, 'message' => 'Missing occurrence start date'], 422);
    }
    if (!$hasExceptionsTable) {
        respond([
            'success' => false,
            'message' => 'Occurrence delete is not available because event_occurrence_exceptions table is missing. Please run recurrence migration on the server.'
        ], 422);
    }
    $iStmt = mysqli_prepare($mysqliConn, 'INSERT IGNORE INTO event_occurrence_exceptions (event_id, occurrence_start_at) VALUES (?, ?)');
    mysqli_stmt_bind_param($iStmt, 'is', $id, $occurrenceStartAt);
    mysqli_stmt_execute($iStmt);
    mysqli_stmt_close($iStmt);
    respond(['success' => true]);
}

if ($scope === 'from_here') {
    if (!$hasRecurringColumns || (($existing['recurrence_type'] ?? 'none') !== 'monthly_nth_weekday')) {
        respond(['success' => false, 'message' => 'Delete from here is only available for recurring events'], 422);
    }
    if (!$hasRecurrenceUntilColumn) {
        respond(['success' => false, 'message' => 'Delete from here is not available because recurrence_until column is missing. Please run recurrence migration on the server.'], 422);
    }
    if ($occurrenceStartAt === '') {
        respond(['success' => false, 'message' => 'Missing occurrence start date'], 422);
    }
    $ts = strtotime($occurrenceStartAt);
    if ($ts === false) {
        respond(['success' => false, 'message' => 'Invalid occurrence start date'], 422);
    }
    $until = date('Y-m-d H:i:s', $ts - 1);
    $uStmt = mysqli_prepare($mysqliConn, 'UPDATE events SET recurrence_until = ? WHERE id = ?');
    mysqli_stmt_bind_param($uStmt, 'si', $until, $id);
    mysqli_stmt_execute($uStmt);
    mysqli_stmt_close($uStmt);
    respond(['success' => true]);
}

$stmt = mysqli_prepare($mysqliConn, 'DELETE FROM events WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
respond(['success' => true]);
?>
