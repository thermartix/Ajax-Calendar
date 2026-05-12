<?php
require_once __DIR__ . '/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    respond(['success' => false, 'message' => 'Invalid event id'], 422);
}

$hasEventCountries = false;
$check = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_countries'");
if ($check && mysqli_num_rows($check) > 0) {
    $hasEventCountries = true;
}

$hasRecurringColumns = false;
$hasRecurWeeksColumn = false;
$recCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'recurrence_type'");
if ($recCheck && mysqli_num_rows($recCheck) > 0) {
    $hasRecurringColumns = true;
    $recurWeeksCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'recur_weeks'");
    if ($recurWeeksCheck && mysqli_num_rows($recurWeeksCheck) > 0) {
        $hasRecurWeeksColumn = true;
    }
}
$hasEventLanguageColumn = false;
$langCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'event_language_country_id'");
if ($langCheck && mysqli_num_rows($langCheck) > 0) {
    $hasEventLanguageColumn = true;
}
$hasInterpCountries = false;
$interpCheck = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_interpretation_countries'");
if ($interpCheck && mysqli_num_rows($interpCheck) > 0) {
    $hasInterpCountries = true;
}

$sql = 'SELECT e.id, e.user_id, e.country_id, c.code AS country_code, c.name AS country_name, e.title, e.description, e.event_link, e.image_path, e.attachment_path, ';
if ($hasRecurringColumns) {
    if ($hasRecurWeeksColumn) {
        $sql .= 'e.recurrence_type, e.recur_week, e.recur_weeks, e.recur_weekday, ';
    } else {
        $sql .= 'e.recurrence_type, e.recur_week, NULL AS recur_weeks, e.recur_weekday, ';
    }
} else {
    $sql .= '"none" AS recurrence_type, NULL AS recur_week, NULL AS recur_weeks, NULL AS recur_weekday, ';
}
if ($hasEventLanguageColumn) {
    $sql .= 'elc.code AS event_language_country_code, elc.name AS event_language_country_name, ';
} else {
    $sql .= 'NULL AS event_language_country_code, NULL AS event_language_country_name, ';
}
$sql .= 'e.start_at, e.end_at, u.username, u.first_name, u.last_name FROM events e JOIN countries c ON c.id = e.country_id JOIN users u ON u.user_id = e.user_id ';
if ($hasEventLanguageColumn) {
    $sql .= 'LEFT JOIN countries elc ON elc.id = e.event_language_country_id ';
}
$sql .= 'WHERE e.id = ? LIMIT 1';

$stmt = mysqli_prepare($mysqliConn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$event = stmtFetchOneAssoc($stmt);
mysqli_stmt_close($stmt);

if (!$event) {
    respond(['success' => false, 'message' => 'Event not found'], 404);
}

$event['id'] = (int)$event['id'];
$event['country_id'] = (int)$event['country_id'];
$event['user_id'] = (int)$event['user_id'];
$weeks = [];
if (!empty($event['recur_weeks'])) {
    foreach (explode(',', (string)$event['recur_weeks']) as $w) {
        $n = (int)trim($w);
        if ($n >= 1 && $n <= 5) $weeks[] = $n;
    }
}
if (empty($weeks) && !empty($event['recur_week'])) {
    $n = (int)$event['recur_week'];
    if ($n >= 1 && $n <= 5) $weeks[] = $n;
}
$event['recur_weeks'] = $weeks;

if ($hasEventCountries) {
    $cStmt = mysqli_prepare($mysqliConn, 'SELECT ec.country_id, c.code, c.name FROM event_countries ec JOIN countries c ON c.id = ec.country_id WHERE ec.event_id = ? ORDER BY c.name');
    mysqli_stmt_bind_param($cStmt, 'i', $id);
    mysqli_stmt_execute($cStmt);
    $countryRows = stmtFetchAllAssoc($cStmt);
    mysqli_stmt_close($cStmt);
    $event['country_ids'] = [];
    $event['country_codes'] = [];
    $event['country_names'] = [];
    foreach ($countryRows as $row) {
        $event['country_ids'][] = (int)$row['country_id'];
        $event['country_codes'][] = $row['code'];
        $event['country_names'][] = $row['name'];
    }
}
if (empty($event['country_ids'])) {
    $event['country_ids'] = [(int)$event['country_id']];
}
if (empty($event['country_codes'])) {
    $event['country_codes'] = [(string)$event['country_code']];
}
if (empty($event['country_names'])) {
    $event['country_names'] = [$event['country_name']];
}
if ($hasInterpCountries) {
    $iStmt = mysqli_prepare($mysqliConn, 'SELECT c.code FROM event_interpretation_countries eic JOIN countries c ON c.id = eic.country_id WHERE eic.event_id = ? ORDER BY c.name');
    mysqli_stmt_bind_param($iStmt, 'i', $id);
    mysqli_stmt_execute($iStmt);
    $event['interpretation_country_codes'] = [];
    foreach (stmtFetchAllAssoc($iStmt) as $row) {
        $event['interpretation_country_codes'][] = $row['code'];
    }
    mysqli_stmt_close($iStmt);
} else {
    $event['interpretation_country_codes'] = [];
}

$event['creator_name'] = trim(($event['first_name'] ?? '') . ' ' . ($event['last_name'] ?? ''));
if ($event['creator_name'] === '') {
    $event['creator_name'] = $event['username'];
}

$event['can_edit'] = false;
$user = currentUser($mysqliConn);
if ($user) {
    if ($user['role'] === 'admin') {
        $event['can_edit'] = true;
    } else {
        $allowed = array_unique(array_merge([(int)$user['country_id']], userAllowedCountryIds($mysqliConn, (int)$user['user_id'])));
        $missing = array_diff($event['country_ids'], $allowed);
        $event['can_edit'] = count($missing) === 0;
    }
}

respond(['success' => true, 'event' => $event]);
?>
