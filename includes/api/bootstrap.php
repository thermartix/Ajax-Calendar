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

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        respond(['success' => false, 'message' => 'Login required'], 401);
    }
}

function currentUser(mysqli $db): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = mysqli_prepare($db, 'SELECT user_id, username, role, country_id FROM users WHERE user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res) ?: null;
    mysqli_stmt_close($stmt);
    return $user;
}

function canEditEvent(array $user, array $event): bool {
    if ($user['role'] === 'admin') {
        return true;
    }
    if ($user['role'] === 'category_editor') {
        return (int)$user['country_id'] === (int)$event['country_id'];
    }
    return false;
}
?>