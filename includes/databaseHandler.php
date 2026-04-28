<?php
$config = [];
$configFile = __DIR__ . '/database.config.php';
if (file_exists($configFile)) {
    $loaded = require $configFile;
    if (is_array($loaded)) {
        $config = $loaded;
    }
}

$host = $config['host'] ?? getenv('CAL_DB_HOST') ?: 'localhost';
$user = $config['user'] ?? getenv('CAL_DB_USER') ?: 'root';
$pass = $config['pass'] ?? getenv('CAL_DB_PASS') ?: '';
$db = $config['name'] ?? getenv('CAL_DB_NAME') ?: 'd130770_jxcal';
$port = (int)($config['port'] ?? getenv('CAL_DB_PORT') ?: 3306);

$mysqliConn = mysqli_connect($host, $user, $pass, $db, $port);
if (!$mysqliConn) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
mysqli_set_charset($mysqliConn, 'utf8mb4');
?>
