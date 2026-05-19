<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
$user['allowed_country_ids'] = userAllowedCountryIds($mysqliConn, (int)$user['user_id']);

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$data = $method === 'POST' ? jsonInput() : [];
$mode = (string)($data['mode'] ?? 'list');
$id = (int)(($method === 'POST' ? ($data['id'] ?? 0) : ($_GET['id'] ?? 0)));

if ($id <= 0) {
    respond(['success' => false, 'message' => 'Invalid event id'], 422);
}

$hasOverrides = false;
$ovrCheck = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_occurrence_overrides'");
if ($ovrCheck && mysqli_num_rows($ovrCheck) > 0) {
    $hasOverrides = true;
}
if (!$hasOverrides) {
    respond(['success' => false, 'message' => 'Occurrence overrides table is missing. Please run migration on the server.'], 422);
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
    respond(['success' => false, 'message' => 'Occurrence overrides are only available for recurring events'], 422);
}

if ($method === 'GET' || $mode === 'list') {
    $lStmt = mysqli_prepare($mysqliConn, 'SELECT occurrence_start_at FROM event_occurrence_overrides WHERE event_id = ? ORDER BY occurrence_start_at ASC');
    mysqli_stmt_bind_param($lStmt, 'i', $id);
    mysqli_stmt_execute($lStmt);
    $rows = stmtFetchAllAssoc($lStmt);
    mysqli_stmt_close($lStmt);
    $dates = [];
    foreach ($rows as $r) $dates[] = (string)$r['occurrence_start_at'];
    respond(['success' => true, 'occurrences' => $dates]);
}

if ($mode === 'save_occurrence') {
    $occurrenceStartAt = trim((string)($data['occurrence_start_at'] ?? ''));
    $payload = $data['payload'] ?? null;
    if ($occurrenceStartAt === '') {
        respond(['success' => false, 'message' => 'Missing occurrence start date'], 422);
    }
    if (!is_array($payload)) {
        respond(['success' => false, 'message' => 'Invalid override payload'], 422);
    }
    $json = json_encode($payload);
    $sStmt = mysqli_prepare($mysqliConn, 'INSERT INTO event_occurrence_overrides (event_id, occurrence_start_at, override_json) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE override_json = VALUES(override_json), updated_at = CURRENT_TIMESTAMP');
    mysqli_stmt_bind_param($sStmt, 'iss', $id, $occurrenceStartAt, $json);
    mysqli_stmt_execute($sStmt);
    mysqli_stmt_close($sStmt);
    respond(['success' => true]);
}

if ($mode === 'clear_all') {
    $cStmt = mysqli_prepare($mysqliConn, 'DELETE FROM event_occurrence_overrides WHERE event_id = ?');
    mysqli_stmt_bind_param($cStmt, 'i', $id);
    mysqli_stmt_execute($cStmt);
    mysqli_stmt_close($cStmt);
    respond(['success' => true]);
}

respond(['success' => false, 'message' => 'Invalid mode'], 422);
?>
