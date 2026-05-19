<?php
session_start();
require_once __DIR__ . '/../databaseHandler.php';
header('Content-Type: application/json');

function csrfToken(): string {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function isMutationRequest(): bool {
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    return in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
}

function requestCsrfToken(): string {
    $hdr = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($hdr !== '') {
        return $hdr;
    }
    $post = (string)($_POST['csrf_token'] ?? '');
    if ($post !== '') {
        return $post;
    }
    $raw = requestRawBody();
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && !empty($decoded['csrf_token']) && is_string($decoded['csrf_token'])) {
            return $decoded['csrf_token'];
        }
    }
    return '';
}

function enforceCsrfIfNeeded(): void {
    if (!isMutationRequest()) {
        return;
    }
    $sent = requestCsrfToken();
    $token = csrfToken();
    if ($sent === '' || !hash_equals($token, $sent)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid CSRF token',
            'csrf_token' => $token
        ]);
        exit;
    }
}

function requestRawBody(): string {
    static $cachedRaw = null;
    if ($cachedRaw !== null) {
        return $cachedRaw;
    }
    $raw = file_get_contents('php://input');
    $cachedRaw = is_string($raw) ? $raw : '';
    return $cachedRaw;
}

function jsonInput(): array {
    $raw = requestRawBody();
    return $raw ? (json_decode($raw, true) ?: []) : [];
}

function respond(array $payload, int $status = 200): void {
    if (!array_key_exists('csrf_token', $payload)) {
        $payload['csrf_token'] = csrfToken();
    }
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function ensureAppSettingsTable(mysqli $db): void {
    // Intentionally no-op: production DB users may not have CREATE privileges.
    // Schema changes should be applied via migrations, not at request runtime.
}

function appSettingGet(mysqli $db, string $key, ?string $defaultValue = null): ?string {
    try {
        $check = mysqli_query($db, "SHOW TABLES LIKE 'app_settings'");
        if (!$check || mysqli_num_rows($check) === 0) {
            return $defaultValue;
        }
        $stmt = mysqli_prepare($db, 'SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $key);
        mysqli_stmt_execute($stmt);
        $row = stmtFetchOneAssoc($stmt);
        mysqli_stmt_close($stmt);
        if (!$row) {
            return $defaultValue;
        }
        return (string)$row['setting_value'];
    } catch (Throwable $e) {
        return $defaultValue;
    }
}

function appSettingSet(mysqli $db, string $key, string $value): void {
    try {
        $check = mysqli_query($db, "SHOW TABLES LIKE 'app_settings'");
        if (!$check || mysqli_num_rows($check) === 0) {
            return;
        }
        $stmt = mysqli_prepare($db, 'INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        mysqli_stmt_bind_param($stmt, 'ss', $key, $value);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } catch (Throwable $e) {
        // No-op when DB user lacks permissions; app should remain functional.
        return;
    }
}

function resultFetchAllAssoc($result): array {
    $rows = [];
    if (!$result) {
        return $rows;
    }
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function stmtFetchAllAssoc(mysqli_stmt $stmt): array {
    $rows = [];
    $meta = mysqli_stmt_result_metadata($stmt);
    if (!$meta) {
        return $rows;
    }

    $fields = mysqli_fetch_fields($meta);
    $data = [];
    $bind = [];
    foreach ($fields as $field) {
        $data[$field->name] = null;
        $bind[] = &$data[$field->name];
    }
    call_user_func_array('mysqli_stmt_bind_result', array_merge([$stmt], $bind));

    while (mysqli_stmt_fetch($stmt)) {
        $row = [];
        foreach ($data as $key => $value) {
            $row[$key] = $value;
        }
        $rows[] = $row;
    }

    mysqli_free_result($meta);
    return $rows;
}

function stmtFetchOneAssoc(mysqli_stmt $stmt): ?array {
    $rows = stmtFetchAllAssoc($stmt);
    return $rows[0] ?? null;
}

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        respond(['success' => false, 'message' => 'Login required'], 401);
    }
}

function currentUser(mysqli $db): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = mysqli_prepare($db, 'SELECT user_id, username, first_name, last_name, role, country_id, is_approved FROM users WHERE user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $user = stmtFetchOneAssoc($stmt);
    mysqli_stmt_close($stmt);
    return $user;
}

function requireAdmin(array $user): void {
    if ($user['role'] !== 'admin') {
        respond(['success' => false, 'message' => 'Admin required'], 403);
    }
}

function userAllowedCountryIds(mysqli $db, int $userId): array {
    $stmt = mysqli_prepare($db, 'SELECT country_id FROM user_country_permissions WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $rows = stmtFetchAllAssoc($stmt);
    $ids = [];
    foreach ($rows as $row) {
        $ids[] = (int)$row['country_id'];
    }
    mysqli_stmt_close($stmt);
    return $ids;
}

function canEditEvent(array $user, array $event): bool {
    if ($user['role'] === 'admin') {
        return true;
    }
    if ($user['role'] === 'editor' || $user['role'] === 'category_editor') {
        if (isset($event['user_id']) && (int)$event['user_id'] === (int)$user['user_id']) {
            return true;
        }
        $ownCountry = (int)($user['country_id'] ?? 0);
        if ($ownCountry <= 0) {
            return false;
        }
        if (!empty($event['country_ids']) && is_array($event['country_ids'])) {
            return in_array($ownCountry, array_map('intval', $event['country_ids']), true);
        }
        if (isset($event['country_id'])) {
            return (int)$event['country_id'] === $ownCountry;
        }
    }
    return false;
}

function randomPasswordString(int $len = 24): string {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $out;
}

function makeAbsoluteUrl(string $path): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $appBasePath = dirname(dirname(dirname($scriptName)));
    if ($appBasePath === '\\' || $appBasePath === '/' || $appBasePath === '.') {
        $appBasePath = '';
    }
    return $scheme . '://' . $host . $appBasePath . $path;
}

function clientIpAddress(): string {
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    return $ip !== '' ? $ip : 'unknown';
}

function rateLimitStorageKey(string $bucket, string $subject): string {
    $safeBucket = preg_replace('/[^a-z0-9_]/i', '_', strtolower($bucket));
    return 'rate_' . $safeBucket . '_' . hash('sha256', $subject);
}

function rateLimitReadState(mysqli $db, string $bucket, string $subject): array {
    $raw = appSettingGet($db, rateLimitStorageKey($bucket, $subject), '');
    if (!is_string($raw) || $raw === '') {
        return ['count' => 0, 'window_start' => time(), 'blocked_until' => 0];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['count' => 0, 'window_start' => time(), 'blocked_until' => 0];
    }
    return [
        'count' => (int)($decoded['count'] ?? 0),
        'window_start' => (int)($decoded['window_start'] ?? time()),
        'blocked_until' => (int)($decoded['blocked_until'] ?? 0)
    ];
}

function rateLimitWriteState(mysqli $db, string $bucket, string $subject, array $state): void {
    appSettingSet($db, rateLimitStorageKey($bucket, $subject), json_encode([
        'count' => (int)($state['count'] ?? 0),
        'window_start' => (int)($state['window_start'] ?? time()),
        'blocked_until' => (int)($state['blocked_until'] ?? 0)
    ]));
}

function rateLimitCheck(mysqli $db, string $bucket, string $subject, int $maxAttempts, int $windowSeconds, int $blockSeconds): array {
    $now = time();
    $state = rateLimitReadState($db, $bucket, $subject);
    if ($state['blocked_until'] > $now) {
        return ['allowed' => false, 'retry_after' => $state['blocked_until'] - $now];
    }
    if (($now - $state['window_start']) >= $windowSeconds) {
        $state = ['count' => 0, 'window_start' => $now, 'blocked_until' => 0];
        rateLimitWriteState($db, $bucket, $subject, $state);
    }
    if ($state['count'] >= $maxAttempts) {
        $state['blocked_until'] = $now + $blockSeconds;
        rateLimitWriteState($db, $bucket, $subject, $state);
        return ['allowed' => false, 'retry_after' => $blockSeconds];
    }
    return ['allowed' => true, 'retry_after' => 0];
}

function rateLimitFailure(mysqli $db, string $bucket, string $subject, int $windowSeconds): void {
    $now = time();
    $state = rateLimitReadState($db, $bucket, $subject);
    if (($now - $state['window_start']) >= $windowSeconds) {
        $state = ['count' => 0, 'window_start' => $now, 'blocked_until' => 0];
    }
    $state['count'] += 1;
    rateLimitWriteState($db, $bucket, $subject, $state);
}

function rateLimitReset(mysqli $db, string $bucket, string $subject): void {
    rateLimitWriteState($db, $bucket, $subject, ['count' => 0, 'window_start' => time(), 'blocked_until' => 0]);
}

csrfToken();
enforceCsrfIfNeeded();
?>
