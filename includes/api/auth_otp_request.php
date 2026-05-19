<?php
require_once __DIR__ . '/bootstrap.php';
$data = jsonInput();
$username = trim((string)($data['username'] ?? ''));

if ($username === '') {
    respond(['success' => false, 'message' => 'Username is required'], 422);
}

$lookup = strtolower($username);
$rateSubject = $lookup . '|' . clientIpAddress();
$limit = rateLimitCheck($mysqliConn, 'auth_otp_request', $rateSubject, 5, 900, 900);
if (!$limit['allowed']) {
    respond(['success' => false, 'message' => 'Too many OTP requests. Please try again later.', 'retry_after' => $limit['retry_after']], 429);
}
rateLimitFailure($mysqliConn, 'auth_otp_request', $rateSubject, 900);
$row = null;
if (preg_match('/^\d{7}$/', $lookup)) {
    $sStmt = mysqli_prepare($mysqliConn, "SELECT setting_key FROM app_settings WHERE setting_key LIKE 'user_member_id_%' AND setting_value = ? LIMIT 1");
    mysqli_stmt_bind_param($sStmt, 's', $lookup);
    mysqli_stmt_execute($sStmt);
    $mRow = stmtFetchOneAssoc($sStmt);
    mysqli_stmt_close($sStmt);
    if ($mRow && !empty($mRow['setting_key'])) {
        $uid = (int)str_replace('user_member_id_', '', (string)$mRow['setting_key']);
        if ($uid > 0) {
            $stmt = mysqli_prepare($mysqliConn, 'SELECT user_id, username, is_approved FROM users WHERE user_id = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'i', $uid);
            mysqli_stmt_execute($stmt);
            $row = stmtFetchOneAssoc($stmt);
            mysqli_stmt_close($stmt);
        }
    }
} else {
    $stmt = mysqli_prepare($mysqliConn, 'SELECT user_id, username, is_approved FROM users WHERE username = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $lookup);
    mysqli_stmt_execute($stmt);
    $row = stmtFetchOneAssoc($stmt);
    mysqli_stmt_close($stmt);
}

if (!$row) {
    rateLimitFailure($mysqliConn, 'auth_otp_request', $rateSubject, 900);
    respond(['success' => false, 'message' => 'User not found'], 404);
}
if ((int)$row['is_approved'] !== 1) {
    rateLimitFailure($mysqliConn, 'auth_otp_request', $rateSubject, 900);
    respond(['success' => false, 'message' => 'Account is not active yet. Please confirm your email first.'], 403);
}

$email = appSettingGet($mysqliConn, 'user_email_' . (int)$row['user_id'], '');
if (!filter_var((string)$email, FILTER_VALIDATE_EMAIL)) {
    rateLimitFailure($mysqliConn, 'auth_otp_request', $rateSubject, 900);
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

$mailSubject = 'Your one-time login code';
$body = "Your login code is: {$code}\n\nThis code expires in 10 minutes.";
$headers = 'From: no-reply@immeet.ing' . "\r\n" . 'Reply-To: no-reply@immeet.ing' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
$sent = mail((string)$email, $mailSubject, $body, $headers);
if (!$sent) {
    rateLimitFailure($mysqliConn, 'auth_otp_request', $rateSubject, 900);
    unset($_SESSION['otp_login']);
    respond(['success' => false, 'message' => 'Could not send OTP email from this server.'], 500);
}

respond(['success' => true, 'message' => 'OTP sent to your email address.']);
?>
