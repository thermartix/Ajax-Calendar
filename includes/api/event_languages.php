<?php
require_once __DIR__ . '/bootstrap.php';

function deriveLanguageCode(string $countryCode): string {
    $code = strtolower(trim($countryCode));
    $map = [
        'dach' => 'de',
        'de' => 'de',
        'at' => 'de',
        'ch' => 'de',
        'fr' => 'fr',
        'it' => 'it',
        'es' => 'es',
        'pt' => 'pt',
        'ro' => 'ro',
        'hu' => 'hu',
        'sk' => 'sk',
        'gb' => 'en',
        'uk' => 'en',
        'us' => 'en'
    ];
    return $map[$code] ?? '';
}

$hasEventCountries = false;
$check = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_countries'");
if ($check && mysqli_num_rows($check) > 0) {
    $hasEventCountries = true;
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

$sql = 'SELECT e.id, c.code AS country_code';
if ($hasEventLanguageColumn) {
    $sql .= ', elc.code AS event_language_country_code';
}
$sql .= ' FROM events e JOIN countries c ON c.id = e.country_id';
if ($hasEventLanguageColumn) {
    $sql .= ' LEFT JOIN countries elc ON elc.id = e.event_language_country_id';
}
$sql .= ' ORDER BY e.id ASC';

$stmt = mysqli_prepare($mysqliConn, $sql);
mysqli_stmt_execute($stmt);
$rows = stmtFetchAllAssoc($stmt);
mysqli_stmt_close($stmt);

$languages = [];
foreach ($rows as $row) {
    $eventId = (int)$row['id'];
    $found = [];

    $main = strtolower(trim((string)($row['event_language_country_code'] ?? '')));
    if ($main !== '') {
        $found[$main] = true;
    }

    if ($hasInterpCountries) {
        $iStmt = mysqli_prepare($mysqliConn, 'SELECT c.code FROM event_interpretation_countries eic JOIN countries c ON c.id = eic.country_id WHERE eic.event_id = ?');
        mysqli_stmt_bind_param($iStmt, 'i', $eventId);
        mysqli_stmt_execute($iStmt);
        foreach (stmtFetchAllAssoc($iStmt) as $ir) {
            $code = strtolower(trim((string)($ir['code'] ?? '')));
            if ($code !== '') {
                $found[$code] = true;
            }
        }
        mysqli_stmt_close($iStmt);
    }

    if (count($found) === 0) {
        $countryCodes = [];
        if ($hasEventCountries) {
            $cStmt = mysqli_prepare($mysqliConn, 'SELECT c.code FROM event_countries ec JOIN countries c ON c.id = ec.country_id WHERE ec.event_id = ?');
            mysqli_stmt_bind_param($cStmt, 'i', $eventId);
            mysqli_stmt_execute($cStmt);
            foreach (stmtFetchAllAssoc($cStmt) as $cr) {
                $cc = strtolower(trim((string)($cr['code'] ?? '')));
                if ($cc !== '') {
                    $countryCodes[] = $cc;
                }
            }
            mysqli_stmt_close($cStmt);
        }
        if (count($countryCodes) === 0) {
            $countryCodes[] = strtolower(trim((string)$row['country_code']));
        }
        foreach ($countryCodes as $cc) {
            $derived = deriveLanguageCode($cc);
            if ($derived !== '') {
                $found[$derived] = true;
            }
        }
    }

    foreach (array_keys($found) as $code) {
        $languages[$code] = true;
    }
}

$codes = array_keys($languages);
sort($codes);
respond(['success' => true, 'codes' => $codes]);
?>
