<?php
require_once __DIR__ . '/bootstrap.php';
$data = jsonInput();
$username = trim((string)($data['username'] ?? ''));

if ($username === '') {
    respond(['success' => false, 'message' => 'Username is required'], 422);
}

$stmt = mysqli_prepare($mysqliConn, 'SELECT user_id, username, is_approved FROM users WHERE username = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$row = stmtFetchOneAssoc($stmt);
mysqli_stmt_close($stmt);

if (!$row) {
    respond(['success' => false, 'message' => 'User not found'], 404);
}
if ((int)$row['is_approved'] !== 1) {
    respond(['success' => false, 'message' => 'Account pending admin approval'], 403);
}

$email = appSettingGet($mysqliConn, 'user_email_' . (int)$row['user_id'], '');
if (!filter_var((string)$email, FILTER_VALIDATE_EMAIL)) {
    respond(['success' => false, 'message' => 'No valid email is configured for OTP login. Use password login or contact admin.'], 422);
}

$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 8; $i++) {
    $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
}

$_SESSION['otp_login'] = [
    'user_id' => (int)$row['user_id'],
    'username' => $row['username'],
    'code_hash' => password_hash($code, PASSWORD_DEFAULT),
    'expires_at' => time() + 600
];

$subject = 'Your one-time login code';
$body = "Your login code is: {$code}\n\nThis code expires in 10 minutes.";
$headers = 'From: no-reply@localhost' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
$sent = @mail((string)$email, $subject, $body, $headers);
if (!$sent) {
    unset($_SESSION['otp_login']);
    respond(['success' => false, 'message' => 'Could not send OTP email from this server.'], 500);
}

respond(['success' => true, 'message' => 'OTP sent to your email address.']);
?>
