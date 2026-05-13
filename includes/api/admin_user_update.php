<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$admin = currentUser($mysqliConn);
requireAdmin($admin);
$data = jsonInput();
$userId = (int)($data['user_id'] ?? 0);
$isApproved = isset($data['is_approved']) ? (int)$data['is_approved'] : null;
$role = isset($data['role']) ? trim((string)$data['role']) : null;
$email = isset($data['email']) ? trim((string)$data['email']) : null;
$primaryCountry = isset($data['country_id']) && $data['country_id'] !== '' ? (int)$data['country_id'] : null;
$allowed = isset($data['allowed_country_ids']) && is_array($data['allowed_country_ids']) ? array_map('intval', $data['allowed_country_ids']) : [];

if ($userId <= 0) {
    respond(['success' => false, 'message' => 'Invalid user id'], 422);
}

if ($isApproved !== null) {
    $stmt = mysqli_prepare($mysqliConn, 'UPDATE users SET is_approved = ? WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $isApproved, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

if ($role !== null && in_array($role, ['editor', 'admin', 'category_editor'], true)) {
    $stmt = mysqli_prepare($mysqliConn, 'UPDATE users SET role = ? WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'si', $role, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

if ($email !== null) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(['success' => false, 'message' => 'Invalid email format'], 422);
    }
    $emailLower = strtolower($email);
    $chk = mysqli_prepare($mysqliConn, 'SELECT user_id FROM users WHERE username = ? AND user_id <> ? LIMIT 1');
    mysqli_stmt_bind_param($chk, 'si', $emailLower, $userId);
    mysqli_stmt_execute($chk);
    $dup = stmtFetchOneAssoc($chk);
    mysqli_stmt_close($chk);
    if ($dup) {
        respond(['success' => false, 'message' => 'Email already in use'], 409);
    }
    $uStmt = mysqli_prepare($mysqliConn, 'UPDATE users SET username = ? WHERE user_id = ?');
    mysqli_stmt_bind_param($uStmt, 'si', $emailLower, $userId);
    mysqli_stmt_execute($uStmt);
    mysqli_stmt_close($uStmt);
    appSettingSet($mysqliConn, 'user_email_' . $userId, $emailLower);
}

if ($primaryCountry !== null) {
    $stmt = mysqli_prepare($mysqliConn, 'UPDATE users SET country_id = ? WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $primaryCountry, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

$stmt = mysqli_prepare($mysqliConn, 'DELETE FROM user_country_permissions WHERE user_id = ?');
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!empty($allowed)) {
    $stmt = mysqli_prepare($mysqliConn, 'INSERT INTO user_country_permissions (user_id, country_id) VALUES (?, ?)');
    foreach ($allowed as $cid) {
        mysqli_stmt_bind_param($stmt, 'ii', $userId, $cid);
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);
}

respond(['success' => true]);
?>
