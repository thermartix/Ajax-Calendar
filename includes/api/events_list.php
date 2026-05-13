<?php
require_once __DIR__ . '/bootstrap.php';
$countryFilter = isset($_GET['country_id']) && $_GET['country_id'] !== '' ? (int)$_GET['country_id'] : null;
$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;
$hasEventCountries = false;
$hasRecurringColumns = false;
$hasRecurrenceUntilColumn = false;
$hasRecurWeeksColumn = false;
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
    $recurWeeksCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'recur_weeks'");
    if ($recurWeeksCheck && mysqli_num_rows($recurWeeksCheck) > 0) {
        $hasRecurWeeksColumn = true;
    }
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
$hasEventModeColumn = false;
$modeCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'event_mode'");
if ($modeCheck && mysqli_num_rows($modeCheck) > 0) {
    $hasEventModeColumn = true;
}
$hasVenueAddressColumn = false;
$venueAddressCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'venue_address'");
if ($venueAddressCheck && mysqli_num_rows($venueAddressCheck) > 0) {
    $hasVenueAddressColumn = true;
}
$hasTicketUrlColumn = false;
$ticketUrlCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'ticket_url'");
if ($ticketUrlCheck && mysqli_num_rows($ticketUrlCheck) > 0) {
    $hasTicketUrlColumn = true;
}
$hasVenueImagePathColumn = false;
$venueImageCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'venue_image_path'");
if ($venueImageCheck && mysqli_num_rows($venueImageCheck) > 0) {
    $hasVenueImagePathColumn = true;
}
$hasAudienceTypeColumn = false;
$audienceTypeCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'audience_type'");
if ($audienceTypeCheck && mysqli_num_rows($audienceTypeCheck) > 0) {
    $hasAudienceTypeColumn = true;
}
$hasSoldOutColumn = false;
$soldOutCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'sold_out'");
if ($soldOutCheck && mysqli_num_rows($soldOutCheck) > 0) {
    $hasSoldOutColumn = true;
}
$hasOccurrenceExceptions = false;
$excCheck = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_occurrence_exceptions'");
if ($excCheck && mysqli_num_rows($excCheck) > 0) {
    $hasOccurrenceExceptions = true;
}

$sql = 'SELECT e.id, e.user_id, e.country_id, c.code AS country_code, c.name AS country_name, e.title, e.description, e.event_link, e.image_path, e.attachment_path, ';
if ($hasRecurringColumns) {
    if ($hasRecurrenceUntilColumn) {
        if ($hasRecurWeeksColumn) {
            $sql .= 'e.recurrence_type, e.recur_week, e.recur_weeks, e.recur_weekday, e.recurrence_until, ';
        } else {
            $sql .= 'e.recurrence_type, e.recur_week, NULL AS recur_weeks, e.recur_weekday, e.recurrence_until, ';
        }
    } else {
        if ($hasRecurWeeksColumn) {
            $sql .= 'e.recurrence_type, e.recur_week, e.recur_weeks, e.recur_weekday, NULL AS recurrence_until, ';
        } else {
            $sql .= 'e.recurrence_type, e.recur_week, NULL AS recur_weeks, e.recur_weekday, NULL AS recurrence_until, ';
        }
    }
} else {
    $sql .= '"none" AS recurrence_type, NULL AS recur_week, NULL AS recur_weeks, NULL AS recur_weekday, NULL AS recurrence_until, ';
}
if ($hasEventLanguageColumn) {
    $sql .= 'elc.code AS event_language_country_code, elc.name AS event_language_country_name, ';
} else {
    $sql .= 'NULL AS event_language_country_code, NULL AS event_language_country_name, ';
}
if ($hasEventModeColumn) {
    $sql .= 'e.event_mode, ';
} else {
    $sql .= '"online" AS event_mode, ';
}
if ($hasVenueAddressColumn) {
    $sql .= 'e.venue_address, ';
} else {
    $sql .= 'NULL AS venue_address, ';
}
if ($hasTicketUrlColumn) {
    $sql .= 'e.ticket_url, ';
} else {
    $sql .= 'NULL AS ticket_url, ';
}
if ($hasVenueImagePathColumn) {
    $sql .= 'e.venue_image_path, ';
} else {
    $sql .= 'NULL AS venue_image_path, ';
}
if ($hasAudienceTypeColumn) {
    $sql .= 'e.audience_type, ';
} else {
    $sql .= '"customers_guests" AS audience_type, ';
}
if ($hasSoldOutColumn) {
    $sql .= 'e.sold_out, ';
} else {
    $sql .= '0 AS sold_out, ';
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

function deletedOccurrenceStartSet(mysqli $db, int $eventId, bool $hasOccurrenceExceptions): array {
    if (!$hasOccurrenceExceptions) {
        return [];
    }
    $stmt = mysqli_prepare($db, 'SELECT occurrence_start_at FROM event_occurrence_exceptions WHERE event_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $eventId);
    mysqli_stmt_execute($stmt);
    $rows = stmtFetchAllAssoc($stmt);
    mysqli_stmt_close($stmt);
    $set = [];
    foreach ($rows as $r) {
        $set[(string)$r['occurrence_start_at']] = true;
    }
    return $set;
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
    $deletedStarts = deletedOccurrenceStartSet($mysqliConn, $ev['id'], $hasOccurrenceExceptions);
    $durationSeconds = max(0, $baseEnd->getTimestamp() - $baseStart->getTimestamp());
    $nths = [];
    if (!empty($ev['recur_weeks'])) {
        foreach (explode(',', (string)$ev['recur_weeks']) as $w) {
            $n = (int)trim($w);
            if ($n >= 1 && $n <= 5) $nths[] = $n;
        }
    }
    if (empty($nths)) {
        $n = (int)$ev['recur_week'];
        if ($n >= 1 && $n <= 5) $nths[] = $n;
    }
    $ev['recur_weeks'] = $nths;
    $weekday = (int)$ev['recur_weekday'];
    if (empty($nths) || $weekday < 0 || $weekday > 6 || !$rangeStart || !$rangeEnd) {
        continue;
    }

    $scan = new DateTime($rangeStart->format('Y-m-01 00:00:00'));
    $limit = new DateTime($rangeEnd->format('Y-m-01 00:00:00'));

    while ($scan <= $limit) {
        $y = (int)$scan->format('Y');
        $m = (int)$scan->format('m');
        foreach ($nths as $nth) {
            $occDate = nthWeekdayOfMonth($y, $m, $weekday, $nth);
            if ($occDate) {
                $occStart = clone $occDate;
                $occStart->setTime((int)$baseStart->format('H'), (int)$baseStart->format('i'), (int)$baseStart->format('s'));
                if ($occStart < $baseStart) {
                    continue;
                }
                if ($recurrenceUntil && $occStart > $recurrenceUntil) {
                    continue;
                }
                $occEnd = clone $occStart;
                if ($durationSeconds > 0) {
                    $occEnd->modify('+' . $durationSeconds . ' seconds');
                }

                if ($occEnd >= $rangeStart && $occStart <= $rangeEnd) {
                    if (isset($deletedStarts[$occStart->format('Y-m-d H:i:s')])) {
                        continue;
                    }
                    $inst = $ev;
                    $inst['start_at'] = $occStart->format('Y-m-d H:i:s');
                    $inst['end_at'] = $occEnd->format('Y-m-d H:i:s');
                    $events[] = $inst;
                }
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
