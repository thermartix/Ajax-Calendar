<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
requireAdmin($user);
$data = jsonInput();
$items = isset($data['countries']) && is_array($data['countries']) ? $data['countries'] : [];
$normalized = [];
foreach ($items as $item) {
    $code = strtoupper(trim((string)($item['code'] ?? '')));
    $name = trim((string)($item['name'] ?? ''));
    if ($code === '' || $name === '') continue;
    $normalized[$code] = $name; // last wins
}

// Keep baseline options always available.
if (!isset($normalized['EU'])) $normalized['EU'] = 'European Union';
if (!isset($normalized['CH'])) $normalized['CH'] = 'Switzerland';

$upStmt = mysqli_prepare($mysqliConn, 'INSERT INTO countries (code, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)');
foreach ($normalized as $code => $name) {
    mysqli_stmt_bind_param($upStmt, 'ss', $code, $name);
    mysqli_stmt_execute($upStmt);
}
mysqli_stmt_close($upStmt);

// Delete removed countries (true sync), but skip any country still referenced.
$res = mysqli_query($mysqliConn, 'SELECT id, code FROM countries');
$existing = resultFetchAllAssoc($res);
$keepCodes = array_keys($normalized);
$deleted = [];
$skipped = [];

$chkUsers = mysqli_prepare($mysqliConn, 'SELECT 1 FROM users WHERE country_id = ? LIMIT 1');
$chkPerms = mysqli_prepare($mysqliConn, 'SELECT 1 FROM user_country_permissions WHERE country_id = ? LIMIT 1');
$chkEvents = mysqli_prepare($mysqliConn, 'SELECT 1 FROM events WHERE country_id = ? LIMIT 1');
$chkEventCountries = mysqli_prepare($mysqliConn, 'SELECT 1 FROM event_countries WHERE country_id = ? LIMIT 1');
$chkInterp = mysqli_prepare($mysqliConn, 'SELECT 1 FROM event_interpretation_countries WHERE country_id = ? LIMIT 1');
$chkEventLang = mysqli_prepare($mysqliConn, "SELECT 1 FROM events WHERE event_language_country_id = ? LIMIT 1");
$delStmt = mysqli_prepare($mysqliConn, 'DELETE FROM countries WHERE id = ? LIMIT 1');

foreach ($existing as $row) {
    $id = (int)$row['id'];
    $code = strtoupper((string)$row['code']);
    if (in_array($code, $keepCodes, true)) continue;

    $hasRefs = false;
    foreach ([$chkUsers, $chkPerms, $chkEvents, $chkEventCountries, $chkInterp, $chkEventLang] as $stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        if (stmtFetchOneAssoc($stmt)) {
            $hasRefs = true;
            break;
        }
    }
    if ($hasRefs) {
        $skipped[] = $code;
        continue;
    }

    mysqli_stmt_bind_param($delStmt, 'i', $id);
    mysqli_stmt_execute($delStmt);
    if (mysqli_stmt_affected_rows($delStmt) > 0) $deleted[] = $code;
}

foreach ([$chkUsers, $chkPerms, $chkEvents, $chkEventCountries, $chkInterp, $chkEventLang, $delStmt] as $stmt) {
    if ($stmt) mysqli_stmt_close($stmt);
}

respond(['success' => true, 'deleted_codes' => $deleted, 'skipped_in_use_codes' => $skipped]);
?>
