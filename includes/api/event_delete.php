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

$hasExceptionsTable = false;
$excCheck = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_occurrence_exceptions'");
if ($excCheck && mysqli_num_rows($excCheck) > 0) {
    $hasExceptionsTable = true;
}

if (!$hasExceptionsTable) {
    @mysqli_query($mysqliConn, "CREATE TABLE event_occurrence_exceptions (
      event_id INT NOT NULL,
      occurrence_start_at DATETIME NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (event_id, occurrence_start_at),
      CONSTRAINT fk_event_occ_exc_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $excCheck2 = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_occurrence_exceptions'");
    if ($excCheck2 && mysqli_num_rows($excCheck2) > 0) {
        $hasExceptionsTable = true;
    }
}

if ($scope !== 'series' && $scope !== 'occurrence') {
    respond(['success' => false, 'message' => 'Invalid delete scope'], 422);
}

$sql = 'SELECT id, country_id';
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
if (!canEditEvent($user, ['country_id' => (int)$existing['country_id']])) {
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
        respond(['success' => false, 'message' => 'Occurrence delete is not available because event_occurrence_exceptions table is missing'], 500);
    }
    $iStmt = mysqli_prepare($mysqliConn, 'INSERT IGNORE INTO event_occurrence_exceptions (event_id, occurrence_start_at) VALUES (?, ?)');
    mysqli_stmt_bind_param($iStmt, 'is', $id, $occurrenceStartAt);
    mysqli_stmt_execute($iStmt);
    mysqli_stmt_close($iStmt);
    respond(['success' => true]);
}

$stmt = mysqli_prepare($mysqliConn, 'DELETE FROM events WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
respond(['success' => true]);
?>
