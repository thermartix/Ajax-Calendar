<?php
function envGet(string $key): ?string {
    $val = getenv($key);
    if ($val !== false) {
        return (string)$val;
    }
    if (isset($_ENV[$key])) {
        return (string)$_ENV[$key];
    }
    if (isset($_SERVER[$key])) {
        return (string)$_SERVER[$key];
    }
    return null;
}

function loadEnvFile(string $path): void {
    if (!is_file($path) || !is_readable($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($key === '') {
            continue;
        }
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        if (envGet($key) === null) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

$projectRoot = dirname(__DIR__);
loadEnvFile($projectRoot . '/.env');
loadEnvFile($projectRoot . '/.env.local');

$requiredEnvKeys = ['CAL_DB_HOST', 'CAL_DB_USER', 'CAL_DB_PASS', 'CAL_DB_NAME'];
$missingEnvKeys = [];
foreach ($requiredEnvKeys as $envKey) {
    $envVal = envGet($envKey);
    if ($envVal === null || trim($envVal) === '') {
        $missingEnvKeys[] = $envKey;
    }
}

if (!empty($missingEnvKeys)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database configuration is missing. Check your .env file.',
        'missing' => $missingEnvKeys
    ]);
    exit;
}

$host = (string)envGet('CAL_DB_HOST');
$user = (string)envGet('CAL_DB_USER');
$pass = (string)envGet('CAL_DB_PASS');
$db = (string)envGet('CAL_DB_NAME');
$portRaw = envGet('CAL_DB_PORT');
$port = ($portRaw !== null && trim($portRaw) !== '') ? (int)$portRaw : 3306;

$mysqliConn = mysqli_connect($host, $user, $pass, $db, $port);
if (!$mysqliConn) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
mysqli_set_charset($mysqliConn, 'utf8mb4');
?>
