<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$admin = currentUser($mysqliConn);
requireAdmin($admin);
$data = jsonInput();
$userId = (int)($data['user_id'] ?? 0);
$isApproved = isset($data['is_approved']) ? (int)$data['is_approved'] : null;
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