<?php
require_once __DIR__ . '/bootstrap.php';
$data = jsonInput();
$code = strtoupper(trim((string)($data['code'] ?? '')));

if ($code === '') {
    respond(['success' => false, 'message' => 'OTP code is required'], 422);
}

$otp = $_SESSION['otp_login'] ?? null;
if (!is_array($otp)) {
    respond(['success' => false, 'message' => 'No OTP request found. Request a new code.'], 400);
}

if ((int)($otp['expires_at'] ?? 0) < time()) {
    unset($_SESSION['otp_login']);
    respond(['success' => false, 'message' => 'OTP code expired. Request a new code.'], 400);
}

if (!password_verify($code, (string)($otp['code_hash'] ?? ''))) {
    respond(['success' => false, 'message' => 'Invalid OTP code'], 401);
}

$uid = (int)($otp['user_id'] ?? 0);
$stmt = mysqli_prepare($mysqliConn, 'SELECT user_id, username, first_name, last_name, role, country_id, is_approved FROM users WHERE user_id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $uid);
mysqli_stmt_execute($stmt);
$row = stmtFetchOneAssoc($stmt);
mysqli_stmt_close($stmt);

if (!$row || (int)$row['is_approved'] !== 1) {
    unset($_SESSION['otp_login']);
    respond(['success' => false, 'message' => 'Account is not active'], 403);
}

$_SESSION['user_id'] = (int)$row['user_id'];
$_SESSION['username'] = $row['username'];
unset($_SESSION['otp_login']);

respond([
    'success' => true,
    'user' => [
        'user_id' => (int)$row['user_id'],
        'username' => $row['username'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'role' => $row['role'],
        'country_id' => $row['country_id'] !== null ? (int)$row['country_id'] : null
    ]
]);
?>
