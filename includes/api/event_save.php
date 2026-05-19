<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
if ((int)$user['is_approved'] !== 1) {
    respond(['success' => false, 'message' => 'Account pending approval'], 403);
}
if (!in_array((string)$user['role'], ['admin', 'editor', 'category_editor'], true)) {
    respond(['success' => false, 'message' => 'Your role cannot create or edit events'], 403);
}
$user['allowed_country_ids'] = userAllowedCountryIds($mysqliConn, (int)$user['user_id']);
if (!function_exists('ensureEventMetaSchema')) {
    function ensureEventMetaSchema(mysqli $db): void {
        try {
            $rec = mysqli_query($db, "SHOW COLUMNS FROM events LIKE 'recurrence_type'");
            if (!$rec || mysqli_num_rows($rec) === 0) {
                @mysqli_query($db, "ALTER TABLE events ADD COLUMN recurrence_type VARCHAR(32) NOT NULL DEFAULT 'none' AFTER attachment_path");
            }
            $rw = mysqli_query($db, "SHOW COLUMNS FROM events LIKE 'recur_week'");
            if (!$rw || mysqli_num_rows($rw) === 0) {
                @mysqli_query($db, "ALTER TABLE events ADD COLUMN recur_week TINYINT NULL AFTER recurrence_type");
            }
            $rwd = mysqli_query($db, "SHOW COLUMNS FROM events LIKE 'recur_weekday'");
            if (!$rwd || mysqli_num_rows($rwd) === 0) {
                @mysqli_query($db, "ALTER TABLE events ADD COLUMN recur_weekday TINYINT NULL AFTER recur_week");
            }
            $col = mysqli_query($db, "SHOW COLUMNS FROM events LIKE 'event_language_country_id'");
            if (!$col || mysqli_num_rows($col) === 0) {
                // Best-effort only; deployments without ALTER privileges must run migrations manually.
                @mysqli_query($db, "ALTER TABLE events ADD COLUMN event_language_country_id INT NULL");
            }
            $modeCol = mysqli_query($db, "SHOW COLUMNS FROM events LIKE 'event_mode'");
            if (!$modeCol || mysqli_num_rows($modeCol) === 0) {
                @mysqli_query($db, "ALTER TABLE events ADD COLUMN event_mode VARCHAR(16) NOT NULL DEFAULT 'online'");
            }
            $venueAddrCol = mysqli_query($db, "SHOW COLUMNS FROM events LIKE 'venue_address'");
            if (!$venueAddrCol || mysqli_num_rows($venueAddrCol) === 0) {
                @mysqli_query($db, "ALTER TABLE events ADD COLUMN venue_address TEXT NULL");
            }
            $ticketUrlCol = mysqli_query($db, "SHOW COLUMNS FROM events LIKE 'ticket_url'");
            if (!$ticketUrlCol || mysqli_num_rows($ticketUrlCol) === 0) {
                @mysqli_query($db, "ALTER TABLE events ADD COLUMN ticket_url VARCHAR(500) NULL");
            }
            $venueImgCol = mysqli_query($db, "SHOW COLUMNS FROM events LIKE 'venue_image_path'");
            if (!$venueImgCol || mysqli_num_rows($venueImgCol) === 0) {
                @mysqli_query($db, "ALTER TABLE events ADD COLUMN venue_image_path VARCHAR(255) NULL");
            }
            $audCol = mysqli_query($db, "SHOW COLUMNS FROM events LIKE 'audience_type'");
            if (!$audCol || mysqli_num_rows($audCol) === 0) {
                @mysqli_query($db, "ALTER TABLE events ADD COLUMN audience_type VARCHAR(32) NOT NULL DEFAULT 'customers_guests'");
            }
            $soldOutCol = mysqli_query($db, "SHOW COLUMNS FROM events LIKE 'sold_out'");
            if (!$soldOutCol || mysqli_num_rows($soldOutCol) === 0) {
                @mysqli_query($db, "ALTER TABLE events ADD COLUMN sold_out TINYINT(1) NOT NULL DEFAULT 0");
            }
            $tbl = mysqli_query($db, "SHOW TABLES LIKE 'event_interpretation_countries'");
            if (!$tbl || mysqli_num_rows($tbl) === 0) {
                @mysqli_query($db, "CREATE TABLE event_interpretation_countries (
                  event_id INT NOT NULL,
                  country_id INT NOT NULL,
                  PRIMARY KEY (event_id, country_id),
                  CONSTRAINT fk_event_interp_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
                  CONSTRAINT fk_event_interp_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
        } catch (Throwable $e) {
            return;
        }
    }
}
ensureEventMetaSchema($mysqliConn);
$hasEventLanguageColumn = false;
$langColCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'event_language_country_id'");
if ($langColCheck && mysqli_num_rows($langColCheck) > 0) {
    $hasEventLanguageColumn = true;
}
$hasRecurringColumns = false;
$hasRecurWeeksColumn = false;
$recColCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'recurrence_type'");
if ($recColCheck && mysqli_num_rows($recColCheck) > 0) {
    $hasRecurringColumns = true;
    $recurWeeksCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'recur_weeks'");
    if ($recurWeeksCheck && mysqli_num_rows($recurWeeksCheck) > 0) {
        $hasRecurWeeksColumn = true;
    } else {
        @mysqli_query($mysqliConn, "ALTER TABLE events ADD COLUMN recur_weeks VARCHAR(32) NULL AFTER recur_week");
        $recurWeeksCheck2 = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'recur_weeks'");
        if ($recurWeeksCheck2 && mysqli_num_rows($recurWeeksCheck2) > 0) {
            $hasRecurWeeksColumn = true;
        }
    }
}
$hasRecurrenceUntilColumn = false;
$recUntilCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'recurrence_until'");
if ($recUntilCheck && mysqli_num_rows($recUntilCheck) > 0) {
    $hasRecurrenceUntilColumn = true;
} elseif ($hasRecurringColumns) {
    @mysqli_query($mysqliConn, "ALTER TABLE events ADD COLUMN recurrence_until DATETIME NULL AFTER recur_weekday");
    $recUntilCheck2 = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'recurrence_until'");
    if ($recUntilCheck2 && mysqli_num_rows($recUntilCheck2) > 0) {
        $hasRecurrenceUntilColumn = true;
    }
}
$hasInterpTable = false;
$interpTblCheck = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_interpretation_countries'");
if ($interpTblCheck && mysqli_num_rows($interpTblCheck) > 0) {
    $hasInterpTable = true;
}
$hasEventModeColumn = false;
$modeColCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'event_mode'");
if ($modeColCheck && mysqli_num_rows($modeColCheck) > 0) {
    $hasEventModeColumn = true;
}
$hasVenueAddressColumn = false;
$venueAddrCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'venue_address'");
if ($venueAddrCheck && mysqli_num_rows($venueAddrCheck) > 0) {
    $hasVenueAddressColumn = true;
}
$hasTicketUrlColumn = false;
$ticketUrlCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'ticket_url'");
if ($ticketUrlCheck && mysqli_num_rows($ticketUrlCheck) > 0) {
    $hasTicketUrlColumn = true;
}
$hasVenueImagePathColumn = false;
$venueImgCheck = mysqli_query($mysqliConn, "SHOW COLUMNS FROM events LIKE 'venue_image_path'");
if ($venueImgCheck && mysqli_num_rows($venueImgCheck) > 0) {
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
$hasEventSpeakers = false;
$speakerTblCheck = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_speakers'");
if ($speakerTblCheck && mysqli_num_rows($speakerTblCheck) > 0) {
    $hasEventSpeakers = true;
}

$id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
$copyFromId = isset($_POST['copy_from_id']) && $_POST['copy_from_id'] !== '' ? (int)$_POST['copy_from_id'] : null;
$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$link = trim((string)($_POST['event_link'] ?? ''));
$eventMode = trim((string)($_POST['event_mode'] ?? 'online'));
$venueAddress = trim((string)($_POST['venue_address'] ?? ''));
$ticketUrl = trim((string)($_POST['ticket_url'] ?? ''));
$audienceType = trim((string)($_POST['audience_type'] ?? 'customers_guests'));
$soldOut = ((string)($_POST['sold_out'] ?? '0') === '1') ? 1 : 0;
$countryIdsRaw = isset($_POST['country_ids']) ? json_decode((string)$_POST['country_ids'], true) : [];
$countryIds = is_array($countryIdsRaw) ? array_values(array_unique(array_map('intval', $countryIdsRaw))) : [];
$countryIdPrimary = (int)($countryIds[0] ?? 0);
$eventLanguageCountryId = isset($_POST['event_language_country_id']) && $_POST['event_language_country_id'] !== '' ? (int)$_POST['event_language_country_id'] : null;
$interpRaw = isset($_POST['interpretation_country_ids']) ? json_decode((string)$_POST['interpretation_country_ids'], true) : [];
$interpretationCountryIds = is_array($interpRaw) ? array_values(array_unique(array_map('intval', $interpRaw))) : [];
$speakerIdsRaw = isset($_POST['speaker_ids']) ? json_decode((string)$_POST['speaker_ids'], true) : [];
$speakerIds = is_array($speakerIdsRaw) ? array_values(array_unique(array_map('intval', $speakerIdsRaw))) : [];
$startAt = (string)($_POST['start_at'] ?? '');
$endAt = (string)($_POST['end_at'] ?? '');
$recurrenceType = (string)($_POST['recurrence_type'] ?? 'none');
$recurWeek = isset($_POST['recur_week']) && $_POST['recur_week'] !== '' ? (int)$_POST['recur_week'] : null;
$recurWeeksRaw = isset($_POST['recur_weeks']) ? json_decode((string)$_POST['recur_weeks'], true) : [];
$recurWeeks = is_array($recurWeeksRaw) ? array_values(array_unique(array_map('intval', $recurWeeksRaw))) : [];
$recurWeekday = isset($_POST['recur_weekday']) && $_POST['recur_weekday'] !== '' ? (int)$_POST['recur_weekday'] : null;
$recurrenceUntil = isset($_POST['recurrence_until']) && trim((string)$_POST['recurrence_until']) !== '' ? trim((string)$_POST['recurrence_until']) : null;

if ($title === '' || $startAt === '' || $endAt === '') {
    respond(['success' => false, 'message' => 'Missing required fields'], 422);
}
if ($eventMode !== 'online' && $eventMode !== 'offline') {
    $eventMode = 'online';
}
if (!in_array($audienceType, ['customers_guests', 'consultant_meeting', 'consultant_training', 'consultants'], true)) {
    $audienceType = 'customers_guests';
}
if ($eventMode === 'offline') {
    $link = '';
    $ticketUrl = substr($ticketUrl, 0, 500);
} else {
    $ticketUrl = '';
}
if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL)) {
    respond(['success' => false, 'message' => 'Invalid event link URL'], 422);
}
if ($ticketUrl !== '' && !filter_var($ticketUrl, FILTER_VALIDATE_URL)) {
    respond(['success' => false, 'message' => 'Invalid ticket URL'], 422);
}
if ($endAt < $startAt) {
    respond(['success' => false, 'message' => 'End must be after start'], 422);
}
if ($hasRecurringColumns && $recurrenceType !== 'none' && $recurrenceType !== 'monthly_nth_weekday') {
    respond(['success' => false, 'message' => 'Invalid recurrence type'], 422);
}
if ($hasRecurringColumns && $recurrenceType === 'monthly_nth_weekday') {
    if (empty($recurWeeks) && $recurWeek !== null) {
        $recurWeeks = [$recurWeek];
    }
    $recurWeeks = array_values(array_filter($recurWeeks, function($n) { return $n >= 1 && $n <= 5; }));
    if (empty($recurWeeks) || $recurWeekday === null || $recurWeekday < 0 || $recurWeekday > 6) {
        respond(['success' => false, 'message' => 'Invalid monthly recurrence settings'], 422);
    }
    $recurWeek = (int)$recurWeeks[0];
}
if (!$hasRecurringColumns) {
    $recurrenceType = 'none';
    $recurWeek = null;
    $recurWeeks = [];
    $recurWeekday = null;
    $recurrenceUntil = null;
}
if ($recurrenceType !== 'monthly_nth_weekday') {
    $recurWeeks = [];
}
$recurWeeksCsv = !empty($recurWeeks) ? implode(',', $recurWeeks) : null;
if ($recurrenceUntil !== null && $recurrenceUntil < $startAt) {
    respond(['success' => false, 'message' => 'Recurrence end must be after event start'], 422);
}
if ($recurrenceType === 'monthly_nth_weekday' && !$hasRecurringColumns) {
    respond([
        'success' => false,
        'message' => 'Recurring events are not available because recurrence columns are missing and DB permissions prevented auto-migration. Please run recurrence migration on the server.'
    ], 500);
}

if ($eventLanguageCountryId !== null && $eventLanguageCountryId <= 0) {
    $eventLanguageCountryId = null;
}
if ($eventLanguageCountryId !== null && !$hasEventLanguageColumn) {
    respond([
        'success' => false,
        'message' => 'Event language is not available because events.event_language_country_id is missing and DB permissions prevented auto-migration.'
    ], 500);
}
if (!empty($interpretationCountryIds) && !$hasInterpTable) {
    respond([
        'success' => false,
        'message' => 'Interpretation is not available because event_interpretation_countries table is missing and DB permissions prevented auto-migration.'
    ], 500);
}
if ($audienceType !== 'customers_guests' && !$hasAudienceTypeColumn) {
    respond([
        'success' => false,
        'message' => 'Audience type is not available because events.audience_type is missing and DB permissions prevented auto-migration.'
    ], 500);
}
if ($eventMode !== 'online' && !$hasEventModeColumn) {
    respond([
        'success' => false,
        'message' => 'Event mode is not available because events.event_mode is missing and DB permissions prevented auto-migration.'
    ], 500);
}
if (($venueAddress !== '' || (isset($_FILES['venue_image']) && (int)($_FILES['venue_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)) && (!$hasVenueAddressColumn || !$hasVenueImagePathColumn)) {
    respond([
        'success' => false,
        'message' => 'Offline venue details are not available because required events venue columns are missing and DB permissions prevented auto-migration.'
    ], 500);
}
if ($eventMode === 'offline' && $ticketUrl !== '' && !$hasTicketUrlColumn) {
    respond([
        'success' => false,
        'message' => 'Ticket URL is not available because events.ticket_url is missing and DB permissions prevented auto-migration.'
    ], 500);
}
if ($soldOut === 1 && !$hasSoldOutColumn) {
    respond([
        'success' => false,
        'message' => 'Sold out status is not available because events.sold_out is missing and DB permissions prevented auto-migration.'
    ], 500);
}

$uploadBase = __DIR__ . '/../../assets/uploads';
$webBase = 'assets/uploads';
if (!is_dir($uploadBase)) {
    if (!mkdir($uploadBase, 0775, true) && !is_dir($uploadBase)) {
        respond(['success' => false, 'message' => 'Upload directory is not available on server'], 500);
    }
}

function detectMimeType(string $path): string {
    if (function_exists('finfo_open')) {
        $f = @finfo_open(FILEINFO_MIME_TYPE);
        if ($f) {
            $m = @finfo_file($f, $path);
            @finfo_close($f);
            if (is_string($m) && $m !== '') return strtolower($m);
        }
    }
    if (function_exists('mime_content_type')) {
        $m = @mime_content_type($path);
        if (is_string($m) && $m !== '') return strtolower($m);
    }
    return '';
}

function hasPdfSignature(string $path): bool {
    $h = @fopen($path, 'rb');
    if (!$h) return false;
    $head = @fread($h, 5);
    @fclose($h);
    return $head === '%PDF-';
}

function saveFileUpload(array $file, string $targetDir, string $webDir, array $allowedExts, string $prefix, string $startAt, ?int $maxWidth = null, ?int $maxHeight = null): ?string {
    if (!isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if (!is_uploaded_file($file['tmp_name'])) return null;
    if (((int)($file['size'] ?? 0)) <= 0) return null;
    $name = $file['name'] ?? 'file';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) return null;
    $tmpPath = (string)$file['tmp_name'];

    $imageExts = ['jpg', 'jpeg', 'png', 'webp'];
    if (in_array($ext, $imageExts, true)) {
        $info = function_exists('getimagesize') ? @getimagesize($tmpPath) : false;
        if (!is_array($info) || empty($info[0]) || empty($info[1]) || empty($info[2])) return null;
        $imgType = (int)$info[2];
        $validByExt = ($ext === 'jpg' || $ext === 'jpeg') ? ($imgType === IMAGETYPE_JPEG)
            : ($ext === 'png' ? ($imgType === IMAGETYPE_PNG) : ($imgType === IMAGETYPE_WEBP));
        if (!$validByExt) return null;
        $mime = detectMimeType($tmpPath);
        $allowedMimes = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp']
        ];
        if ($mime !== '' && !in_array($mime, $allowedMimes[$ext] ?? [], true)) return null;
    } elseif ($ext === 'pdf') {
        if (!hasPdfSignature($tmpPath)) return null;
        $mime = detectMimeType($tmpPath);
        if ($mime !== '' && !in_array($mime, ['application/pdf', 'application/x-pdf'], true)) return null;
    } else {
        return null;
    }

    $dateSeed = preg_replace('/[^0-9]/', '', substr($startAt, 0, 10));
    if ($dateSeed === '') $dateSeed = date('Ymd');
    $base = strtolower($prefix . '_' . $dateSeed);
    $safe = $base . '.' . $ext;
    $i = 1;
    while (file_exists(rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $safe)) {
        $safe = $base . '_' . $i . '.' . $ext;
        $i += 1;
    }
    $dest = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $safe;
    $shouldResize = $maxWidth !== null && $maxHeight !== null && in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
    if ($shouldResize && function_exists('getimagesize')) {
        $info = @getimagesize($tmpPath);
        if (is_array($info) && !empty($info[0]) && !empty($info[1]) && !empty($info[2])) {
            $srcW = (int)$info[0];
            $srcH = (int)$info[1];
            $scale = min(1.0, $maxWidth / $srcW, $maxHeight / $srcH);
            $dstW = max(1, (int)floor($srcW * $scale));
            $dstH = max(1, (int)floor($srcH * $scale));

            $srcImg = null;
            if ($info[2] === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) $srcImg = @imagecreatefromjpeg($tmpPath);
            if ($info[2] === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) $srcImg = @imagecreatefrompng($tmpPath);
            if ($info[2] === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) $srcImg = @imagecreatefromwebp($tmpPath);

            if ($srcImg) {
                $outImg = $srcImg;
                if ($scale < 1.0) {
                    $outImg = imagecreatetruecolor($dstW, $dstH);
                    if (!$outImg) {
                        imagedestroy($srcImg);
                        return null;
                    }
                    if ($info[2] === IMAGETYPE_PNG || $info[2] === IMAGETYPE_WEBP) {
                        imagealphablending($outImg, false);
                        imagesavealpha($outImg, true);
                    }
                    imagecopyresampled($outImg, $srcImg, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
                }

                $written = false;
                if ($ext === 'jpg' || $ext === 'jpeg') $written = @imagejpeg($outImg, $dest, 82);
                if ($ext === 'png') $written = @imagepng($outImg, $dest, 6);
                if ($ext === 'webp' && function_exists('imagewebp')) $written = @imagewebp($outImg, $dest, 80);

                if ($outImg !== $srcImg) imagedestroy($outImg);
                imagedestroy($srcImg);

                if (!$written) return null;
                return rtrim($webDir, '/\\') . '/' . $safe;
            }
        }
    }

    if (!move_uploaded_file($tmpPath, $dest)) return null;
    return rtrim($webDir, '/\\') . '/' . $safe;
}

function executeStmtOrEmojiError(mysqli_stmt $stmt): void {
    try {
        $ok = mysqli_stmt_execute($stmt);
        if ($ok !== true) {
            $err = mysqli_stmt_error($stmt);
            respond([
                'success' => false,
                'message' => 'Database save failed: ' . ($err !== '' ? $err : 'unknown statement error')
            ], 500);
        }
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'Incorrect string value') !== false) {
            respond([
                'success' => false,
                'message' => 'Emoji/text character set not supported by current DB table collation. Please convert events.title and events.description to utf8mb4.'
            ], 500);
        }
        throw $e;
    }
}

$newImagePath = isset($_FILES['event_image']) ? saveFileUpload($_FILES['event_image'], $uploadBase, $webBase, ['jpg', 'jpeg', 'png', 'webp'], 'banner', $startAt) : null;
$newAttachmentPath = isset($_FILES['event_attachment']) ? saveFileUpload($_FILES['event_attachment'], $uploadBase, $webBase, ['pdf'], 'attachment', $startAt) : null;
$newVenueImagePath = isset($_FILES['venue_image']) ? saveFileUpload($_FILES['venue_image'], $uploadBase, $webBase, ['jpg', 'jpeg', 'png', 'webp'], 'venue', $startAt, 1200, 800) : null;

if ($id) {
    $existingSql = 'SELECT id, user_id, country_id, image_path, attachment_path';
    if ($hasVenueImagePathColumn) {
        $existingSql .= ', venue_image_path';
    }
    $existingSql .= ' FROM events WHERE id = ? LIMIT 1';
    $stmt = mysqli_prepare($mysqliConn, $existingSql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $existing = stmtFetchOneAssoc($stmt);
    mysqli_stmt_close($stmt);

    if (!$existing) respond(['success' => false, 'message' => 'Event not found'], 404);

    // Safety: preserve existing countries if an edit submits no valid country ids.
    // This avoids events disappearing from filtered listings due to an accidental empty payload.
    if (empty($countryIds) || $countryIdPrimary <= 0) {
        $existingCountryIds = [];
        $ecCheck = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_countries'");
        if ($ecCheck && mysqli_num_rows($ecCheck) > 0) {
            $ecStmt = mysqli_prepare($mysqliConn, 'SELECT country_id FROM event_countries WHERE event_id = ? ORDER BY country_id');
            mysqli_stmt_bind_param($ecStmt, 'i', $id);
            mysqli_stmt_execute($ecStmt);
            foreach (stmtFetchAllAssoc($ecStmt) as $r) {
                $existingCountryIds[] = (int)$r['country_id'];
            }
            mysqli_stmt_close($ecStmt);
        }
        if (empty($existingCountryIds)) {
            $cStmt = mysqli_prepare($mysqliConn, 'SELECT country_id FROM events WHERE id = ? LIMIT 1');
            mysqli_stmt_bind_param($cStmt, 'i', $id);
            mysqli_stmt_execute($cStmt);
            $crow = stmtFetchOneAssoc($cStmt);
            mysqli_stmt_close($cStmt);
            if ($crow && (int)$crow['country_id'] > 0) {
                $existingCountryIds[] = (int)$crow['country_id'];
            }
        }
        $countryIds = array_values(array_unique(array_filter(array_map('intval', $existingCountryIds), function($n) { return $n > 0; })));
        $countryIdPrimary = (int)($countryIds[0] ?? 0);
    }
    if ($countryIdPrimary <= 0) {
        respond(['success' => false, 'message' => 'Select at least one country'], 422);
    }

    if ($user['role'] !== 'admin') {
      $eStmt = mysqli_prepare($mysqliConn, 'SELECT country_id FROM event_countries WHERE event_id = ?');
      mysqli_stmt_bind_param($eStmt, 'i', $id);
      mysqli_stmt_execute($eStmt);
      $evCountries = [];
      foreach (stmtFetchAllAssoc($eStmt) as $r) $evCountries[] = (int)$r['country_id'];
      mysqli_stmt_close($eStmt);
      if (!canEditEvent($user, ['user_id' => (int)$existing['user_id'], 'country_id' => (int)$existing['country_id'], 'country_ids' => $evCountries])) {
          respond(['success' => false, 'message' => 'Not allowed to edit this event'], 403);
      }
    }

    $finalImagePath = $newImagePath ?: $existing['image_path'];
    $finalAttachmentPath = $newAttachmentPath ?: $existing['attachment_path'];
    $finalVenueImagePath = $hasVenueImagePathColumn ? ($newVenueImagePath ?: ($existing['venue_image_path'] ?? null)) : null;

    if ($hasEventLanguageColumn && $hasRecurringColumns && $hasRecurrenceUntilColumn && $hasRecurWeeksColumn) {
        $stmt = mysqli_prepare($mysqliConn, 'UPDATE events SET country_id = ?, title = ?, description = ?, event_link = ?, image_path = ?, attachment_path = ?, recurrence_type = ?, recur_week = ?, recur_weeks = ?, recur_weekday = ?, recurrence_until = ?, event_language_country_id = ?, start_at = ?, end_at = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'issssssisisissi', $countryIdPrimary, $title, $description, $link, $finalImagePath, $finalAttachmentPath, $recurrenceType, $recurWeek, $recurWeeksCsv, $recurWeekday, $recurrenceUntil, $eventLanguageCountryId, $startAt, $endAt, $id);
    } elseif ($hasEventLanguageColumn && $hasRecurringColumns && $hasRecurrenceUntilColumn && !$hasRecurWeeksColumn) {
        $stmt = mysqli_prepare($mysqliConn, 'UPDATE events SET country_id = ?, title = ?, description = ?, event_link = ?, image_path = ?, attachment_path = ?, recurrence_type = ?, recur_week = ?, recur_weekday = ?, recurrence_until = ?, event_language_country_id = ?, start_at = ?, end_at = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'issssssiisissi', $countryIdPrimary, $title, $description, $link, $finalImagePath, $finalAttachmentPath, $recurrenceType, $recurWeek, $recurWeekday, $recurrenceUntil, $eventLanguageCountryId, $startAt, $endAt, $id);
    } elseif ($hasEventLanguageColumn && $hasRecurringColumns && !$hasRecurrenceUntilColumn) {
        if ($hasRecurWeeksColumn) {
            $stmt = mysqli_prepare($mysqliConn, 'UPDATE events SET country_id = ?, title = ?, description = ?, event_link = ?, image_path = ?, attachment_path = ?, recurrence_type = ?, recur_week = ?, recur_weeks = ?, recur_weekday = ?, event_language_country_id = ?, start_at = ?, end_at = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'issssssisiissi', $countryIdPrimary, $title, $description, $link, $finalImagePath, $finalAttachmentPath, $recurrenceType, $recurWeek, $recurWeeksCsv, $recurWeekday, $eventLanguageCountryId, $startAt, $endAt, $id);
        } else {
            $stmt = mysqli_prepare($mysqliConn, 'UPDATE events SET country_id = ?, title = ?, description = ?, event_link = ?, image_path = ?, attachment_path = ?, recurrence_type = ?, recur_week = ?, recur_weekday = ?, event_language_country_id = ?, start_at = ?, end_at = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'issssssiiissi', $countryIdPrimary, $title, $description, $link, $finalImagePath, $finalAttachmentPath, $recurrenceType, $recurWeek, $recurWeekday, $eventLanguageCountryId, $startAt, $endAt, $id);
        }
    } elseif ($hasEventLanguageColumn && !$hasRecurringColumns) {
        $stmt = mysqli_prepare($mysqliConn, 'UPDATE events SET country_id = ?, title = ?, description = ?, event_link = ?, image_path = ?, attachment_path = ?, event_language_country_id = ?, start_at = ?, end_at = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'isssssissi', $countryIdPrimary, $title, $description, $link, $finalImagePath, $finalAttachmentPath, $eventLanguageCountryId, $startAt, $endAt, $id);
    } elseif (!$hasEventLanguageColumn && $hasRecurringColumns && $hasRecurrenceUntilColumn) {
        if ($hasRecurWeeksColumn) {
            $stmt = mysqli_prepare($mysqliConn, 'UPDATE events SET country_id = ?, title = ?, description = ?, event_link = ?, image_path = ?, attachment_path = ?, recurrence_type = ?, recur_week = ?, recur_weeks = ?, recur_weekday = ?, recurrence_until = ?, start_at = ?, end_at = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'issssssisissssi', $countryIdPrimary, $title, $description, $link, $finalImagePath, $finalAttachmentPath, $recurrenceType, $recurWeek, $recurWeeksCsv, $recurWeekday, $recurrenceUntil, $startAt, $endAt, $id);
        } else {
            $stmt = mysqli_prepare($mysqliConn, 'UPDATE events SET country_id = ?, title = ?, description = ?, event_link = ?, image_path = ?, attachment_path = ?, recurrence_type = ?, recur_week = ?, recur_weekday = ?, recurrence_until = ?, start_at = ?, end_at = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'issssssiisssi', $countryIdPrimary, $title, $description, $link, $finalImagePath, $finalAttachmentPath, $recurrenceType, $recurWeek, $recurWeekday, $recurrenceUntil, $startAt, $endAt, $id);
        }
    } elseif (!$hasEventLanguageColumn && $hasRecurringColumns && !$hasRecurrenceUntilColumn) {
        if ($hasRecurWeeksColumn) {
            $stmt = mysqli_prepare($mysqliConn, 'UPDATE events SET country_id = ?, title = ?, description = ?, event_link = ?, image_path = ?, attachment_path = ?, recurrence_type = ?, recur_week = ?, recur_weeks = ?, recur_weekday = ?, start_at = ?, end_at = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'issssssisisssi', $countryIdPrimary, $title, $description, $link, $finalImagePath, $finalAttachmentPath, $recurrenceType, $recurWeek, $recurWeeksCsv, $recurWeekday, $startAt, $endAt, $id);
        } else {
            $stmt = mysqli_prepare($mysqliConn, 'UPDATE events SET country_id = ?, title = ?, description = ?, event_link = ?, image_path = ?, attachment_path = ?, recurrence_type = ?, recur_week = ?, recur_weekday = ?, start_at = ?, end_at = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'issssssiiisi', $countryIdPrimary, $title, $description, $link, $finalImagePath, $finalAttachmentPath, $recurrenceType, $recurWeek, $recurWeekday, $startAt, $endAt, $id);
        }
    } else {
        $stmt = mysqli_prepare($mysqliConn, 'UPDATE events SET country_id = ?, title = ?, description = ?, event_link = ?, image_path = ?, attachment_path = ?, start_at = ?, end_at = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'isssssssi', $countryIdPrimary, $title, $description, $link, $finalImagePath, $finalAttachmentPath, $startAt, $endAt, $id);
    }
    executeStmtOrEmojiError($stmt);
    mysqli_stmt_close($stmt);

    if ($hasEventModeColumn || $hasVenueAddressColumn || $hasTicketUrlColumn || $hasVenueImagePathColumn || $hasAudienceTypeColumn || $hasSoldOutColumn) {
        $setParts = [];
        $bindTypes = '';
        $bindVals = [];
        if ($hasEventModeColumn) {
            $setParts[] = 'event_mode = ?';
            $bindTypes .= 's';
            $bindVals[] = $eventMode;
        }
        if ($hasVenueAddressColumn) {
            $setParts[] = 'venue_address = ?';
            $bindTypes .= 's';
            $bindVals[] = ($eventMode === 'offline' ? $venueAddress : '');
        }
        if ($hasTicketUrlColumn) {
            $setParts[] = 'ticket_url = ?';
            $bindTypes .= 's';
            $bindVals[] = ($eventMode === 'offline' ? $ticketUrl : '');
        }
        if ($hasVenueImagePathColumn) {
            $setParts[] = 'venue_image_path = ?';
            $bindTypes .= 's';
            $bindVals[] = ($eventMode === 'offline' ? $finalVenueImagePath : null);
        }
        if ($hasAudienceTypeColumn) {
            $setParts[] = 'audience_type = ?';
            $bindTypes .= 's';
            $bindVals[] = $audienceType;
        }
        if ($hasSoldOutColumn) {
            $setParts[] = 'sold_out = ?';
            $bindTypes .= 'i';
            $bindVals[] = $soldOut;
        }
        if (!empty($setParts)) {
            $sqlMeta = 'UPDATE events SET ' . implode(', ', $setParts) . ' WHERE id = ?';
            $mStmt = mysqli_prepare($mysqliConn, $sqlMeta);
            $bindTypes .= 'i';
            $bindVals[] = $id;
            mysqli_stmt_bind_param($mStmt, $bindTypes, ...$bindVals);
            executeStmtOrEmojiError($mStmt);
            mysqli_stmt_close($mStmt);
        }
    }

    $dStmt = mysqli_prepare($mysqliConn, 'DELETE FROM event_countries WHERE event_id = ?');
    mysqli_stmt_bind_param($dStmt, 'i', $id);
    mysqli_stmt_execute($dStmt);
    mysqli_stmt_close($dStmt);

    $iStmt = mysqli_prepare($mysqliConn, 'INSERT INTO event_countries (event_id, country_id) VALUES (?, ?)');
    foreach ($countryIds as $cid) {
      mysqli_stmt_bind_param($iStmt, 'ii', $id, $cid);
      mysqli_stmt_execute($iStmt);
    }
    mysqli_stmt_close($iStmt);

    if ($hasInterpTable) {
        $diStmt = mysqli_prepare($mysqliConn, 'DELETE FROM event_interpretation_countries WHERE event_id = ?');
        mysqli_stmt_bind_param($diStmt, 'i', $id);
        mysqli_stmt_execute($diStmt);
        mysqli_stmt_close($diStmt);

        if (!empty($interpretationCountryIds)) {
            $iiStmt = mysqli_prepare($mysqliConn, 'INSERT INTO event_interpretation_countries (event_id, country_id) VALUES (?, ?)');
            foreach ($interpretationCountryIds as $cid) {
                mysqli_stmt_bind_param($iiStmt, 'ii', $id, $cid);
                mysqli_stmt_execute($iiStmt);
            }
            mysqli_stmt_close($iiStmt);
        }
    }
    if ($hasEventSpeakers) {
        $dsStmt = mysqli_prepare($mysqliConn, 'DELETE FROM event_speakers WHERE event_id = ?');
        mysqli_stmt_bind_param($dsStmt, 'i', $id);
        mysqli_stmt_execute($dsStmt);
        mysqli_stmt_close($dsStmt);
        if (!empty($speakerIds)) {
            $isStmt = mysqli_prepare($mysqliConn, 'INSERT INTO event_speakers (event_id, speaker_id, sort_order) VALUES (?, ?, ?)');
            foreach ($speakerIds as $idx => $sid) {
                $sort = (int)$idx;
                mysqli_stmt_bind_param($isStmt, 'iii', $id, $sid, $sort);
                mysqli_stmt_execute($isStmt);
            }
            mysqli_stmt_close($isStmt);
        }
    }

    respond(['success' => true, 'id' => $id]);
}

if ($countryIdPrimary <= 0) {
    respond(['success' => false, 'message' => 'Select at least one country'], 422);
}

$copiedImagePath = null;
$copiedAttachmentPath = null;
$copiedVenueImagePath = null;
if (!$id && $copyFromId && $copyFromId > 0) {
    $srcSql = 'SELECT id, user_id, country_id, image_path, attachment_path';
    if ($hasVenueImagePathColumn) {
        $srcSql .= ', venue_image_path';
    }
    $srcSql .= ' FROM events WHERE id = ? LIMIT 1';
    $srcStmt = mysqli_prepare($mysqliConn, $srcSql);
    mysqli_stmt_bind_param($srcStmt, 'i', $copyFromId);
    mysqli_stmt_execute($srcStmt);
    $src = stmtFetchOneAssoc($srcStmt);
    mysqli_stmt_close($srcStmt);
    if (!$src) {
        respond(['success' => false, 'message' => 'Source event for copy not found'], 404);
    }
    if ($user['role'] !== 'admin') {
        $csStmt = mysqli_prepare($mysqliConn, 'SELECT country_id FROM event_countries WHERE event_id = ?');
        mysqli_stmt_bind_param($csStmt, 'i', $copyFromId);
        mysqli_stmt_execute($csStmt);
        $srcCountries = [];
        foreach (stmtFetchAllAssoc($csStmt) as $r) $srcCountries[] = (int)$r['country_id'];
        mysqli_stmt_close($csStmt);
        if (!canEditEvent($user, ['user_id' => (int)$src['user_id'], 'country_id' => (int)($srcCountries[0] ?? 0), 'country_ids' => $srcCountries])) {
            respond(['success' => false, 'message' => 'Not allowed to copy this event'], 403);
        }
    }
    $copiedImagePath = $src['image_path'] ?? null;
    $copiedAttachmentPath = $src['attachment_path'] ?? null;
    $copiedVenueImagePath = $src['venue_image_path'] ?? null;
}

$userId = (int)$user['user_id'];
$insertImagePath = $newImagePath ?: $copiedImagePath;
$insertAttachmentPath = $newAttachmentPath ?: $copiedAttachmentPath;
$insertVenueImagePath = $newVenueImagePath ?: $copiedVenueImagePath;
if ($hasEventLanguageColumn && $hasRecurringColumns && $hasRecurrenceUntilColumn) {
    if ($hasRecurWeeksColumn) {
        $stmt = mysqli_prepare($mysqliConn, 'INSERT INTO events (user_id, country_id, title, description, event_link, image_path, attachment_path, recurrence_type, recur_week, recur_weeks, recur_weekday, recurrence_until, event_language_country_id, start_at, end_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iissssssisisiss', $userId, $countryIdPrimary, $title, $description, $link, $insertImagePath, $insertAttachmentPath, $recurrenceType, $recurWeek, $recurWeeksCsv, $recurWeekday, $recurrenceUntil, $eventLanguageCountryId, $startAt, $endAt);
    } else {
        $stmt = mysqli_prepare($mysqliConn, 'INSERT INTO events (user_id, country_id, title, description, event_link, image_path, attachment_path, recurrence_type, recur_week, recur_weekday, recurrence_until, event_language_country_id, start_at, end_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iissssssiisiss', $userId, $countryIdPrimary, $title, $description, $link, $insertImagePath, $insertAttachmentPath, $recurrenceType, $recurWeek, $recurWeekday, $recurrenceUntil, $eventLanguageCountryId, $startAt, $endAt);
    }
} elseif ($hasEventLanguageColumn && $hasRecurringColumns && !$hasRecurrenceUntilColumn) {
    if ($hasRecurWeeksColumn) {
        $stmt = mysqli_prepare($mysqliConn, 'INSERT INTO events (user_id, country_id, title, description, event_link, image_path, attachment_path, recurrence_type, recur_week, recur_weeks, recur_weekday, event_language_country_id, start_at, end_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iissssssisiiss', $userId, $countryIdPrimary, $title, $description, $link, $insertImagePath, $insertAttachmentPath, $recurrenceType, $recurWeek, $recurWeeksCsv, $recurWeekday, $eventLanguageCountryId, $startAt, $endAt);
    } else {
        $stmt = mysqli_prepare($mysqliConn, 'INSERT INTO events (user_id, country_id, title, description, event_link, image_path, attachment_path, recurrence_type, recur_week, recur_weekday, event_language_country_id, start_at, end_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iissssssiiiss', $userId, $countryIdPrimary, $title, $description, $link, $insertImagePath, $insertAttachmentPath, $recurrenceType, $recurWeek, $recurWeekday, $eventLanguageCountryId, $startAt, $endAt);
    }
} elseif ($hasEventLanguageColumn && !$hasRecurringColumns) {
    $stmt = mysqli_prepare($mysqliConn, 'INSERT INTO events (user_id, country_id, title, description, event_link, image_path, attachment_path, event_language_country_id, start_at, end_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iisssssiss', $userId, $countryIdPrimary, $title, $description, $link, $insertImagePath, $insertAttachmentPath, $eventLanguageCountryId, $startAt, $endAt);
} elseif (!$hasEventLanguageColumn && $hasRecurringColumns && $hasRecurrenceUntilColumn) {
    if ($hasRecurWeeksColumn) {
        $stmt = mysqli_prepare($mysqliConn, 'INSERT INTO events (user_id, country_id, title, description, event_link, image_path, attachment_path, recurrence_type, recur_week, recur_weeks, recur_weekday, recurrence_until, start_at, end_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iissssssisisss', $userId, $countryIdPrimary, $title, $description, $link, $insertImagePath, $insertAttachmentPath, $recurrenceType, $recurWeek, $recurWeeksCsv, $recurWeekday, $recurrenceUntil, $startAt, $endAt);
    } else {
        $stmt = mysqli_prepare($mysqliConn, 'INSERT INTO events (user_id, country_id, title, description, event_link, image_path, attachment_path, recurrence_type, recur_week, recur_weekday, recurrence_until, start_at, end_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iissssssiisss', $userId, $countryIdPrimary, $title, $description, $link, $insertImagePath, $insertAttachmentPath, $recurrenceType, $recurWeek, $recurWeekday, $recurrenceUntil, $startAt, $endAt);
    }
} elseif (!$hasEventLanguageColumn && $hasRecurringColumns && !$hasRecurrenceUntilColumn) {
    if ($hasRecurWeeksColumn) {
        $stmt = mysqli_prepare($mysqliConn, 'INSERT INTO events (user_id, country_id, title, description, event_link, image_path, attachment_path, recurrence_type, recur_week, recur_weeks, recur_weekday, start_at, end_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iissssssisiss', $userId, $countryIdPrimary, $title, $description, $link, $insertImagePath, $insertAttachmentPath, $recurrenceType, $recurWeek, $recurWeeksCsv, $recurWeekday, $startAt, $endAt);
    } else {
        $stmt = mysqli_prepare($mysqliConn, 'INSERT INTO events (user_id, country_id, title, description, event_link, image_path, attachment_path, recurrence_type, recur_week, recur_weekday, start_at, end_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iisssssiiiss', $userId, $countryIdPrimary, $title, $description, $link, $insertImagePath, $insertAttachmentPath, $recurrenceType, $recurWeek, $recurWeekday, $startAt, $endAt);
    }
} else {
    $stmt = mysqli_prepare($mysqliConn, 'INSERT INTO events (user_id, country_id, title, description, event_link, image_path, attachment_path, start_at, end_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iisssssss', $userId, $countryIdPrimary, $title, $description, $link, $insertImagePath, $insertAttachmentPath, $startAt, $endAt);
}
executeStmtOrEmojiError($stmt);
$newId = mysqli_insert_id($mysqliConn);
mysqli_stmt_close($stmt);

if ($hasEventModeColumn || $hasVenueAddressColumn || $hasTicketUrlColumn || $hasVenueImagePathColumn || $hasAudienceTypeColumn || $hasSoldOutColumn) {
    $setParts = [];
    $bindTypes = '';
    $bindVals = [];
    if ($hasEventModeColumn) {
        $setParts[] = 'event_mode = ?';
        $bindTypes .= 's';
        $bindVals[] = $eventMode;
    }
    if ($hasVenueAddressColumn) {
        $setParts[] = 'venue_address = ?';
        $bindTypes .= 's';
        $bindVals[] = ($eventMode === 'offline' ? $venueAddress : '');
    }
    if ($hasTicketUrlColumn) {
        $setParts[] = 'ticket_url = ?';
        $bindTypes .= 's';
        $bindVals[] = ($eventMode === 'offline' ? $ticketUrl : '');
    }
    if ($hasVenueImagePathColumn) {
        $setParts[] = 'venue_image_path = ?';
        $bindTypes .= 's';
        $bindVals[] = ($eventMode === 'offline' ? $insertVenueImagePath : null);
    }
    if ($hasAudienceTypeColumn) {
        $setParts[] = 'audience_type = ?';
        $bindTypes .= 's';
        $bindVals[] = $audienceType;
    }
    if ($hasSoldOutColumn) {
        $setParts[] = 'sold_out = ?';
        $bindTypes .= 'i';
        $bindVals[] = $soldOut;
    }
    if (!empty($setParts)) {
        $sqlMeta = 'UPDATE events SET ' . implode(', ', $setParts) . ' WHERE id = ?';
        $mStmt = mysqli_prepare($mysqliConn, $sqlMeta);
        $bindTypes .= 'i';
        $bindVals[] = $newId;
        mysqli_stmt_bind_param($mStmt, $bindTypes, ...$bindVals);
        executeStmtOrEmojiError($mStmt);
        mysqli_stmt_close($mStmt);
    }
}

$iStmt = mysqli_prepare($mysqliConn, 'INSERT INTO event_countries (event_id, country_id) VALUES (?, ?)');
foreach ($countryIds as $cid) {
  mysqli_stmt_bind_param($iStmt, 'ii', $newId, $cid);
  mysqli_stmt_execute($iStmt);
}
mysqli_stmt_close($iStmt);

if ($hasInterpTable && !empty($interpretationCountryIds)) {
    $iiStmt = mysqli_prepare($mysqliConn, 'INSERT INTO event_interpretation_countries (event_id, country_id) VALUES (?, ?)');
    foreach ($interpretationCountryIds as $cid) {
        mysqli_stmt_bind_param($iiStmt, 'ii', $newId, $cid);
        mysqli_stmt_execute($iiStmt);
    }
    mysqli_stmt_close($iiStmt);
}
if ($hasEventSpeakers && !empty($speakerIds)) {
    $isStmt = mysqli_prepare($mysqliConn, 'INSERT INTO event_speakers (event_id, speaker_id, sort_order) VALUES (?, ?, ?)');
    foreach ($speakerIds as $idx => $sid) {
        $sort = (int)$idx;
        mysqli_stmt_bind_param($isStmt, 'iii', $newId, $sid, $sort);
        mysqli_stmt_execute($isStmt);
    }
    mysqli_stmt_close($isStmt);
}

respond(['success' => true, 'id' => $newId]);
?>
