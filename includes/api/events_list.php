<?php
require_once __DIR__ . '/bootstrap.php';
$countryFilter = isset($_GET['country_id']) && $_GET['country_id'] !== '' ? (int)$_GET['country_id'] : null;
$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;
$hasEventCountries = false;
$hasRecurringColumns = false;
$hasRecurrenceUntilColumn = false;
$hasEventLanguageColumn = false;
$hasInterpCountries = false;
$check = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_countries'");
if ($check && mysqli_num_rows($check) > 0) {
    $hasEventCountries = true;
}
// Support older schemas where recurrence columns are not migrated yet.
$recCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'recurrence_type'");
if ($recCheck && mysqli_num_rows($recCheck) > 0) {
    $hasRecurringColumns = true;
    $recUntilCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'recurrence_until'");
    if ($recUntilCheck && mysqli_num_rows($recUntilCheck) > 0) {
        $hasRecurrenceUntilColumn = true;
    }
}
$langCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'event_language_country_id'");
if ($langCheck && mysqli_num_rows($langCheck) > 0) {
    $hasEventLanguageColumn = true;
}
$interpCheck = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_interpretation_countries'");
if ($interpCheck && mysqli_num_rows($interpCheck) > 0) {
    $hasInterpCountries = true;
}

$sql = 'SELECT e.id, e.user_id, e.country_id, c.code AS country_code, c.name AS country_name, e.title, e.description, e.event_link, e.image_path, e.attachment_path, ';
if ($hasRecurringColumns) {
    if ($hasRecurrenceUntilColumn) {
        $sql .= 'e.recurrence_type, e.recur_week, e.recur_weekday, e.recurrence_until, ';
    } else {
        $sql .= 'e.recurrence_type, e.recur_week, e.recur_weekday, NULL AS recurrence_until, ';
    }
} else {
    $sql .= '"none" AS recurrence_type, NULL AS recur_week, NULL AS recur_weekday, NULL AS recurrence_until, ';
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
$sql .= 'WHERE 1=1';
$params = [];
$types = '';

if ($countryFilter && $hasEventCountries) {
    $sql .= ' AND EXISTS (SELECT 1 FROM event_countries ecf WHERE ecf.event_id = e.id AND ecf.country_id = ?)';
    $types .= 'i';
    $params[] = $countryFilter;
} elseif ($countryFilter) {
    $sql .= ' AND e.country_id = ?';
    $types .= 'i';
    $params[] = $countryFilter;
}
if ($start && $hasRecurringColumns) {
    $sql .= ' AND (e.recurrence_type <> "none" OR e.end_at >= ?)';
    $types .= 's';
    $params[] = $start;
} elseif ($start) {
    $sql .= ' AND e.end_at >= ?';
    $types .= 's';
    $params[] = $start;
}
if ($end && $hasRecurringColumns) {
    $sql .= ' AND (e.recurrence_type <> "none" OR e.start_at <= ?)';
    $types .= 's';
    $params[] = $end;
} elseif ($end) {
    $sql .= ' AND e.start_at <= ?';
    $types .= 's';
    $params[] = $end;
}

$sql .= ' ORDER BY e.start_at ASC';
$stmt = mysqli_prepare($mysqliConn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$rows = stmtFetchAllAssoc($stmt);
mysqli_stmt_close($stmt);

$user = currentUser($mysqliConn);
if ($user) {
    $user['allowed_country_ids'] = userAllowedCountryIds($mysqliConn, (int)$user['user_id']);
}

function eventCountries(mysqli $db, int $eventId, int $fallbackId, string $fallbackCode, string $fallbackName, bool $hasEventCountries): array {
    if (!$hasEventCountries) {
        return ['ids' => [$fallbackId], 'codes' => [$fallbackCode], 'names' => [$fallbackName]];
    }
    $cStmt = mysqli_prepare($db, 'SELECT ec.country_id, c.code, c.name FROM event_countries ec JOIN countries c ON c.id = ec.country_id WHERE ec.event_id = ? ORDER BY c.name');
    mysqli_stmt_bind_param($cStmt, 'i', $eventId);
    mysqli_stmt_execute($cStmt);
    $countryRows = stmtFetchAllAssoc($cStmt);
    mysqli_stmt_close($cStmt);
    if (!$countryRows) {
        return ['ids' => [$fallbackId], 'codes' => [$fallbackCode], 'names' => [$fallbackName]];
    }
    $ids = [];
    $codes = [];
    $names = [];
    foreach ($countryRows as $r) {
        $ids[] = (int)$r['country_id'];
        $codes[] = $r['code'];
        $names[] = $r['name'];
    }
    return ['ids' => $ids, 'codes' => $codes, 'names' => $names];
}

function nthWeekdayOfMonth(int $year, int $month, int $weekday, int $nth): ?DateTime {
    $first = new DateTime(sprintf('%04d-%02d-01 00:00:00', $year, $month));
    $firstWeekday = (int)$first->format('w');
    $delta = ($weekday - $firstWeekday + 7) % 7;
    $day = 1 + $delta + (($nth - 1) * 7);
    $candidate = new DateTime(sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day));
    if ((int)$candidate->format('m') !== $month) {
        return null;
    }
    return $candidate;
}

$events = [];
$rangeStart = $start ? new DateTime($start . ' 00:00:00') : null;
$rangeEnd = $end ? new DateTime($end . ' 23:59:59') : null;

foreach ($rows as $ev) {
    $ev['id'] = (int)$ev['id'];
    $ev['country_id'] = (int)$ev['country_id'];
    $ev['user_id'] = (int)$ev['user_id'];

    $cset = eventCountries($mysqliConn, $ev['id'], $ev['country_id'], $ev['country_code'], $ev['country_name'], $hasEventCountries);
    $ev['country_ids'] = $cset['ids'];
    $ev['country_codes'] = $cset['codes'];
    $ev['country_names'] = $cset['names'];
    $ev['interpretation_country_codes'] = [];
    if ($hasInterpCountries) {
        $iStmt = mysqli_prepare($mysqliConn, 'SELECT c.code FROM event_interpretation_countries eic JOIN countries c ON c.id = eic.country_id WHERE eic.event_id = ? ORDER BY c.name');
        mysqli_stmt_bind_param($iStmt, 'i', $ev['id']);
        mysqli_stmt_execute($iStmt);
        foreach (stmtFetchAllAssoc($iStmt) as $row) {
            $ev['interpretation_country_codes'][] = $row['code'];
        }
        mysqli_stmt_close($iStmt);
    }

    $canEdit = false;
    if ($user) {
        if ($user['role'] === 'admin') {
            $canEdit = true;
        } else {
            $allowed = array_unique(array_merge([(int)$user['country_id']], $user['allowed_country_ids']));
            $missing = array_diff($ev['country_ids'], $allowed);
            $canEdit = count($missing) === 0;
        }
    }
    $ev['can_edit'] = $canEdit;
    $ev['creator_name'] = trim(($ev['first_name'] ?? '') . ' ' . ($ev['last_name'] ?? ''));
    if ($ev['creator_name'] === '') {
        $ev['creator_name'] = $ev['username'];
    }

    $isRecurring = isset($ev['recurrence_type']) && $ev['recurrence_type'] === 'monthly_nth_weekday';
    if (!$isRecurring) {
        $events[] = $ev;
        continue;
    }

    $baseStart = new DateTime($ev['start_at']);
    $baseEnd = new DateTime($ev['end_at']);
    $recurrenceUntil = !empty($ev['recurrence_until']) ? new DateTime($ev['recurrence_until']) : null;
    $durationSeconds = max(0, $baseEnd->getTimestamp() - $baseStart->getTimestamp());
    $nth = (int)$ev['recur_week'];
    $weekday = (int)$ev['recur_weekday'];
    if ($nth < 1 || $nth > 5 || $weekday < 0 || $weekday > 6 || !$rangeStart || !$rangeEnd) {
        continue;
    }

    $scan = new DateTime($rangeStart->format('Y-m-01 00:00:00'));
    $limit = new DateTime($rangeEnd->format('Y-m-01 00:00:00'));

    while ($scan <= $limit) {
        $y = (int)$scan->format('Y');
        $m = (int)$scan->format('m');
        $occDate = nthWeekdayOfMonth($y, $m, $weekday, $nth);
        if ($occDate) {
            $occStart = clone $occDate;
            $occStart->setTime((int)$baseStart->format('H'), (int)$baseStart->format('i'), (int)$baseStart->format('s'));
            if ($occStart < $baseStart) {
                $scan->modify('+1 month');
                continue;
            }
            if ($recurrenceUntil && $occStart > $recurrenceUntil) {
                break;
            }
            $occEnd = clone $occStart;
            if ($durationSeconds > 0) {
                $occEnd->modify('+' . $durationSeconds . ' seconds');
            }

            if ($occEnd >= $rangeStart && $occStart <= $rangeEnd) {
                $inst = $ev;
                $inst['start_at'] = $occStart->format('Y-m-d H:i:s');
                $inst['end_at'] = $occEnd->format('Y-m-d H:i:s');
                $events[] = $inst;
            }
        }
        $scan->modify('+1 month');
    }
}

usort($events, function($a, $b) {
    return strcmp($a['start_at'], $b['start_at']);
});

respond(['success' => true, 'events' => $events]);
?>
