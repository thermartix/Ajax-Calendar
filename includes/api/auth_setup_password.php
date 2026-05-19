<?php
require_once __DIR__ . '/bootstrap.php';
$data = jsonInput();
$uid = (int)($data['uid'] ?? 0);
$token = trim((string)($data['token'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($uid <= 0 || $token === '' || $password === '') {
    respond(['success' => false, 'message' => 'Missing required fields'], 422);
}
if (strlen($password) < 8) {
    respond(['success' => false, 'message' => 'Password must have at least 8 characters'], 422);
}

$savedHash = appSettingGet($mysqliConn, 'user_setup_token_' . $uid, '');
$savedExp = (int)appSettingGet($mysqliConn, 'user_setup_expires_' . $uid, '0');
if ($savedHash === '' || $savedExp <= time()) {
    respond(['success' => false, 'message' => 'Setup link expired'], 400);
}
if (!hash_equals($savedHash, hash('sha256', $token))) {
    respond(['success' => false, 'message' => 'Invalid setup token'], 400);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$approved = 1;
$stmt = mysqli_prepare($mysqliConn, 'UPDATE users SET password = ?, is_approved = ? WHERE user_id = ?');
mysqli_stmt_bind_param($stmt, 'sii', $hash, $approved, $uid);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
appSettingSet($mysqliConn, 'user_setup_token_' . $uid, '');
appSettingSet($mysqliConn, 'user_setup_expires_' . $uid, '0');
appSettingSet($mysqliConn, 'user_email_verified_' . $uid, '1');

respond(['success' => true]);
?>
