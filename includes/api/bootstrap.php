<?php
session_start();
require_once __DIR__ . '/../databaseHandler.php';
header('Content-Type: application/json');

function jsonInput(): array {
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?: []) : [];
}

function respond(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function ensureAppSettingsTable(mysqli $db): void {
    mysqli_query($db, 'CREATE TABLE IF NOT EXISTS app_settings (
        setting_key VARCHAR(120) PRIMARY KEY,
        setting_value TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

function appSettingGet(mysqli $db, string $key, ?string $defaultValue = null): ?string {
    ensureAppSettingsTable($db);
    $stmt = mysqli_prepare($db, 'SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $key);
    mysqli_stmt_execute($stmt);
    $row = stmtFetchOneAssoc($stmt);
    mysqli_stmt_close($stmt);
    if (!$row) {
        return $defaultValue;
    }
    return (string)$row['setting_value'];
}

function appSettingSet(mysqli $db, string $key, string $value): void {
    ensureAppSettingsTable($db);
    $stmt = mysqli_prepare($db, 'INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    mysqli_stmt_bind_param($stmt, 'ss', $key, $value);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
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
    if ($user['role'] === 'category_editor') {
        if ((int)$user['country_id'] === (int)$event['country_id']) {
            return true;
        }
        if (!empty($user['allowed_country_ids'])) {
            return in_array((int)$event['country_id'], $user['allowed_country_ids'], true);
        }
    }
    return false;
}
?>
