<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$admin = currentUser($mysqliConn);
requireAdmin($admin);

$data = jsonInput();
$userId = (int)($data['user_id'] ?? 0);
if ($userId <= 0) {
    respond(['success' => false, 'message' => 'Invalid user id'], 422);
}
if ($userId === (int)$admin['user_id']) {
    respond(['success' => false, 'message' => 'You cannot delete your own account'], 422);
}

$checkStmt = mysqli_prepare($mysqliConn, 'SELECT user_id FROM users WHERE user_id = ? LIMIT 1');
mysqli_stmt_bind_param($checkStmt, 'i', $userId);
mysqli_stmt_execute($checkStmt);
$exists = stmtFetchOneAssoc($checkStmt);
mysqli_stmt_close($checkStmt);
if (!$exists) {
    respond(['success' => false, 'message' => 'User not found'], 404);
}

$stmt = mysqli_prepare($mysqliConn, 'DELETE FROM users WHERE user_id = ?');
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Best-effort cleanup for user-specific app_settings entries.
try {
    $keys = [
        'user_email_' . $userId,
        'user_datetime_format_' . $userId,
        'user_email_verify_token_' . $userId,
        'user_email_verify_expires_' . $userId,
        'user_email_verified_' . $userId
    ];
    $dStmt = mysqli_prepare($mysqliConn, 'DELETE FROM app_settings WHERE setting_key = ?');
    foreach ($keys as $k) {
        mysqli_stmt_bind_param($dStmt, 's', $k);
        mysqli_stmt_execute($dStmt);
    }
    mysqli_stmt_close($dStmt);
} catch (Throwable $e) {
    // Ignore settings cleanup errors.
}

respond(['success' => true]);
?>
