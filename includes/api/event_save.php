<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
if ((int)$user['is_approved'] !== 1) {
    respond(['success' => false, 'message' => 'Account pending approval'], 403);
}
$user['allowed_country_ids'] = userAllowedCountryIds($mysqliConn, (int)$user['user_id']);
if (!function_exists('ensureEventMetaSchema')) {
    function ensureEventMetaSchema(mysqli $db): void {
        try {
            $col = mysqli_query($db, "SHOW COLUMNS FROM events LIKE 'event_language_country_id'");
            if (!$col || mysqli_num_rows($col) === 0) {
                // Best-effort only; deployments without ALTER privileges must run migrations manually.
                @mysqli_query($db, "ALTER TABLE events ADD COLUMN event_language_country_id INT NULL");
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
$hasInterpTable = false;
$interpTblCheck = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_interpretation_countries'");
if ($interpTblCheck && mysqli_num_rows($interpTblCheck) > 0) {
    $hasInterpTable = true;
}

$id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$link = trim((string)($_POST['event_link'] ?? ''));
$countryIdsRaw = isset($_POST['country_ids']) ? json_decode((string)$_POST['country_ids'], true) : [];
$countryIds = is_array($countryIdsRaw) ? array_values(array_unique(array_map('intval', $countryIdsRaw))) : [];
$countryIdPrimary = (int)($countryIds[0] ?? 0);
$eventLanguageCountryId = isset($_POST['event_language_country_id']) && $_POST['event_language_country_id'] !== '' ? (int)$_POST['event_language_country_id'] : null;
$interpRaw = isset($_POST['interpretation_country_ids']) ? json_decode((string)$_POST['interpretation_country_ids'], true) : [];
$interpretationCountryIds = is_array($interpRaw) ? array_values(array_unique(array_map('intval', $interpRaw))) : [];
$startAt = (string)($_POST['start_at'] ?? '');
$endAt = (string)($_POST['end_at'] ?? '');
$recurrenceType = (string)($_POST['recurrence_type'] ?? 'none');
$recurWeek = isset($_POST['recur_week']) && $_POST['recur_week'] !== '' ? (int)$_POST['recur_week'] : null;
$recurWeekday = isset($_POST['recur_weekday']) && $_POST['recur_weekday'] !== '' ? (int)$_POST['recur_weekday'] : null;

if ($title === '' || $countryIdPrimary <= 0 || $startAt === '' || $endAt === '') {
    respond(['success' => false, 'message' => 'Missing required fields'], 422);
}
if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL)) {
    respond(['success' => false, 'message' => 'Invalid event link URL'], 422);
}
if ($endAt < $startAt) {
    respond(['success' => false, 'message' => 'End must be after start'], 422);
}
if ($recurrenceType !== 'none' && $recurrenceType !== 'monthly_nth_weekday') {
    respond(['success' => false, 'message' => 'Invalid recurrence type'], 422);
}
if ($recurrenceType === 'monthly_nth_weekday') {
    if ($recurWeek === null || $recurWeek < 1 || $recurWeek > 5 || $recurWeekday === null || $recurWeekday < 0 || $recurWeekday > 6) {
        respond(['success' => false, 'message' => 'Invalid monthly recurrence settings'], 422);
    }
}

if ($user['role'] === 'category_editor') {
    $allowed = array_unique(array_merge([(int)$user['country_id']], $user['allowed_country_ids']));
    $missing = array_diff($countryIds, $allowed);
    if (!empty($missing)) {
        respond(['success' => false, 'message' => 'Not allowed outside your categories'], 403);
    }
}
if ($eventLanguageCountryId !== null && $eventLanguageCountryId <= 0) {
    $eventLanguageCountryId = null;
}

$uploadBase = __DIR__ . '/../../assets/uploads';
$webBase = 'assets/uploads';
if (!is_dir($uploadBase)) {
    @mkdir($uploadBase, 0775, true);
}

function saveFileUpload(array $file, string $targetDir, string $webDir, array $allowedExts): ?string {
    if (!isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $name = $file['name'] ?? 'file';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) return null;
    $safe = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $safe;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    return rtrim($webDir, '/\\') . '/' . $safe;
}

$newImagePath = isset($_FILES['event_image']) ? saveFileUpload($_FILES['event_image'], $uploadBase, $webBase, ['jpg', 'jpeg', 'png', 'webp']) : null;
$newAttachmentPath = isset($_FILES['event_attachment']) ? saveFileUpload($_FILES['event_attachment'], $uploadBase, $webBase, ['pdf']) : null;

if ($id) {
    $stmt = mysqli_prepare($mysqliConn, 'SELECT id, image_path, attachment_path FROM events WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $existing = stmtFetchOneAssoc($stmt);
    mysqli_stmt_close($stmt);

    if (!$existing) respond(['success' => false, 'message' => 'Event not found'], 404);

    if ($user['role'] !== 'admin') {
      $eStmt = mysqli_prepare($mysqliConn, 'SELECT country_id FROM event_countries WHERE event_id = ?');
      mysqli_stmt_bind_param($eStmt, 'i', $id);
      mysqli_stmt_execute($eStmt);
      $evCountries = [];
      foreach (stmtFetchAllAssoc($eStmt) as $r) $evCountries[] = (int)$r['country_id'];
      mysqli_stmt_close($eStmt);
      $allowed = array_unique(array_merge([(int)$user['country_id']], $user['allowed_country_ids']));
      $missingCurrent = array_diff($evCountries, $allowed);
      if (!empty($missingCurrent)) respond(['success' => false, 'message' => 'Not allowed to edit this event'], 403);
    }

    $finalImagePath = $newImagePath ?: $existing['image_path'];
    $finalAttachmentPath = $newAttachmentPath ?: $existing['attachment_path'];

    if ($hasEventLanguageColumn) {
        $stmt = mysqli_prepare($mysqliConn, 'UPDATE events SET country_id = ?, title = ?, description = ?, event_link = ?, image_path = ?, attachment_path = ?, recurrence_type = ?, recur_week = ?, recur_weekday = ?, event_language_country_id = ?, start_at = ?, end_at = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'issssssiiissi', $countryIdPrimary, $title, $description, $link, $finalImagePath, $finalAttachmentPath, $recurrenceType, $recurWeek, $recurWeekday, $eventLanguageCountryId, $startAt, $endAt, $id);
    } else {
        $stmt = mysqli_prepare($mysqliConn, 'UPDATE events SET country_id = ?, title = ?, description = ?, event_link = ?, image_path = ?, attachment_path = ?, recurrence_type = ?, recur_week = ?, recur_weekday = ?, start_at = ?, end_at = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'issssssiiisi', $countryIdPrimary, $title, $description, $link, $finalImagePath, $finalAttachmentPath, $recurrenceType, $recurWeek, $recurWeekday, $startAt, $endAt, $id);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

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

    respond(['success' => true, 'id' => $id]);
}

$userId = (int)$user['user_id'];
if ($hasEventLanguageColumn) {
    $stmt = mysqli_prepare($mysqliConn, 'INSERT INTO events (user_id, country_id, title, description, event_link, image_path, attachment_path, recurrence_type, recur_week, recur_weekday, event_language_country_id, start_at, end_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iissssssiiiss', $userId, $countryIdPrimary, $title, $description, $link, $newImagePath, $newAttachmentPath, $recurrenceType, $recurWeek, $recurWeekday, $eventLanguageCountryId, $startAt, $endAt);
} else {
    $stmt = mysqli_prepare($mysqliConn, 'INSERT INTO events (user_id, country_id, title, description, event_link, image_path, attachment_path, recurrence_type, recur_week, recur_weekday, start_at, end_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iisssssiiiss', $userId, $countryIdPrimary, $title, $description, $link, $newImagePath, $newAttachmentPath, $recurrenceType, $recurWeek, $recurWeekday, $startAt, $endAt);
}
mysqli_stmt_execute($stmt);
$newId = mysqli_insert_id($mysqliConn);
mysqli_stmt_close($stmt);

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

respond(['success' => true, 'id' => $newId]);
?>
