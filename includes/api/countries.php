<?php
require_once __DIR__ . '/bootstrap.php';

// Ensure baseline assignable options are always available.
// Best-effort insert to support existing deployments without manual country seeding.
function ensureCountry(mysqli $db, string $code, string $name): void {
    $q = mysqli_prepare($db, 'SELECT id FROM countries WHERE LOWER(code) = ? LIMIT 1');
    if (!$q) return;
    $codeLower = strtolower($code);
    mysqli_stmt_bind_param($q, 's', $codeLower);
    mysqli_stmt_execute($q);
    $row = stmtFetchOneAssoc($q);
    mysqli_stmt_close($q);
    if ($row) return;

    $ins = mysqli_prepare($db, 'INSERT INTO countries (code, name) VALUES (?, ?)');
    if (!$ins) return;
    mysqli_stmt_bind_param($ins, 'ss', $code, $name);
    mysqli_stmt_execute($ins);
    mysqli_stmt_close($ins);
}

ensureCountry($mysqliConn, 'EU', 'European Union');
ensureCountry($mysqliConn, 'CH', 'Switzerland');
ensureCountry($mysqliConn, 'AT', 'Austria');

$res = mysqli_query($mysqliConn, 'SELECT id, code, name FROM countries ORDER BY name');
$countries = resultFetchAllAssoc($res);
respond(['success' => true, 'countries' => $countries]);
?>
