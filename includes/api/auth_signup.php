<?php
require_once __DIR__ . '/bootstrap.php';
$data = jsonInput();
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');
$email = trim((string)($data['email'] ?? ''));
$firstName = trim((string)($data['first_name'] ?? ''));
$lastName = trim((string)($data['last_name'] ?? ''));
$memberId = trim((string)($data['member_id'] ?? ''));
$countryId = isset($data['country_id']) && $data['country_id'] !== '' ? (int)$data['country_id'] : null;

if ($username === '' || $password === '' || $email === '') {
    respond(['success' => false, 'message' => 'All required fields must be filled'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(['success' => false, 'message' => 'Valid email is required'], 422);
}
if (strtolower($username) !== strtolower($email)) {
    respond(['success' => false, 'message' => 'Username must match email'], 422);
}

$stmt = mysqli_prepare($mysqliConn, 'SELECT user_id FROM users WHERE username = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$existing = stmtFetchOneAssoc($stmt);
if ($existing) {
    mysqli_stmt_close($stmt);
    respond(['success' => false, 'message' => 'Username already exists'], 409);
}
mysqli_stmt_close($stmt);

$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'visitor';
$isApproved = 0;
$countryForInsert = null;
$stmt = mysqli_prepare($mysqliConn, 'INSERT INTO users (username, password, first_name, last_name, role, country_id, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'sssssii', $username, $hash, $firstName, $lastName, $role, $countryForInsert, $isApproved);
if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    respond(['success' => false, 'message' => 'Could not create user'], 500);
}
$userId = mysqli_insert_id($mysqliConn);
mysqli_stmt_close($stmt);

appSettingSet($mysqliConn, 'user_email_' . $userId, $email);
appSettingSet($mysqliConn, 'user_member_id_' . $userId, $memberId);
$tokenPlain = '';
try {
    $tokenPlain = bin2hex(random_bytes(24));
} catch (Throwable $e) {
    respond(['success' => false, 'message' => 'Could not initialize email verification token.'], 500);
}
$tokenHash = hash('sha256', $tokenPlain);
appSettingSet($mysqliConn, 'user_email_verify_token_' . $userId, $tokenHash);
appSettingSet($mysqliConn, 'user_email_verify_expires_' . $userId, (string)(time() + 86400));
appSettingSet($mysqliConn, 'user_email_verified_' . $userId, '0');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
$appBasePath = dirname(dirname(dirname($scriptName)));
if ($appBasePath === '\\' || $appBasePath === '/' || $appBasePath === '.') {
    $appBasePath = '';
}
$confirmUrl = $scheme . '://' . $host . $appBasePath . '/includes/api/auth_verify_email.php?uid=' . urlencode((string)$userId) . '&token=' . urlencode($tokenPlain);
$subject = 'Confirm your account email';
$body = "Hello,\n\nPlease confirm your account by clicking this link:\n{$confirmUrl}\n\nThis link expires in 24 hours.";
$headers = 'From: no-reply@immeet.ing' . "\r\n" . 'Reply-To: no-reply@immeet.ing' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
$sent = @mail((string)$email, $subject, $body, $headers);
if (!$sent) {
    respond(['success' => false, 'message' => 'Could not send confirmation email from this server.'], 500);
}

respond([
    'success' => true,
    'approved' => false,
    'message' => 'Signup created. Please confirm your email.'
]);
?>
