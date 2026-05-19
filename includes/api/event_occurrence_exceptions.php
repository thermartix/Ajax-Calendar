<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
$user['allowed_country_ids'] = userAllowedCountryIds($mysqliConn, (int)$user['user_id']);

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$input = ($method === 'POST') ? jsonInput() : [];
$id = (int)(($method === 'POST' ? ($input['id'] ?? 0) : ($_GET['id'] ?? 0)));
$occurrenceStartAt = trim((string)($input['occurrence_start_at'] ?? ''));

if ($id <= 0) {
    respond(['success' => false, 'message' => 'Invalid event id'], 422);
}

$recCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'recurrence_type'");
$hasRecurringColumns = $recCheck && mysqli_num_rows($recCheck) > 0;
if (!$hasRecurringColumns) {
    respond(['success' => false, 'message' => 'Recurring events are not available'], 422);
}

$excCheck = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_occurrence_exceptions'");
$hasExceptionsTable = $excCheck && mysqli_num_rows($excCheck) > 0;
if (!$hasExceptionsTable) {
    respond(['success' => true, 'occurrences' => []]);
}

$stmt = mysqli_prepare($mysqliConn, 'SELECT id, user_id, country_id, recurrence_type FROM events WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$event = stmtFetchOneAssoc($stmt);
mysqli_stmt_close($stmt);

if (!$event) {
    respond(['success' => false, 'message' => 'Event not found'], 404);
}
if (!canEditEvent($user, ['user_id' => (int)$event['user_id'], 'country_id' => (int)$event['country_id']])) {
    respond(['success' => false, 'message' => 'Not allowed to edit this event'], 403);
}
if (($event['recurrence_type'] ?? 'none') !== 'monthly_nth_weekday') {
    respond(['success' => false, 'message' => 'Hidden occurrences are only available for recurring events'], 422);
}

if ($method === 'POST') {
    if ($occurrenceStartAt === '') {
        respond(['success' => false, 'message' => 'Missing occurrence start date'], 422);
    }
    $dStmt = mysqli_prepare($mysqliConn, 'DELETE FROM event_occurrence_exceptions WHERE event_id = ? AND occurrence_start_at = ?');
    mysqli_stmt_bind_param($dStmt, 'is', $id, $occurrenceStartAt);
    mysqli_stmt_execute($dStmt);
    mysqli_stmt_close($dStmt);
    respond(['success' => true]);
}

$lStmt = mysqli_prepare($mysqliConn, 'SELECT occurrence_start_at FROM event_occurrence_exceptions WHERE event_id = ? ORDER BY occurrence_start_at ASC');
mysqli_stmt_bind_param($lStmt, 'i', $id);
mysqli_stmt_execute($lStmt);
$rows = stmtFetchAllAssoc($lStmt);
mysqli_stmt_close($lStmt);

$occurrences = [];
foreach ($rows as $row) {
    $occurrences[] = (string)$row['occurrence_start_at'];
}

respond(['success' => true, 'occurrences' => $occurrences]);
?>
