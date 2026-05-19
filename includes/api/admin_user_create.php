<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$admin = currentUser($mysqliConn);
requireAdmin($admin);
$data = jsonInput();

$first = trim((string)($data['first_name'] ?? ''));
$last = trim((string)($data['last_name'] ?? ''));
$email = strtolower(trim((string)($data['email'] ?? '')));
$memberId = trim((string)($data['member_id'] ?? ''));
$role = trim((string)($data['role'] ?? 'visitor'));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(['success' => false, 'message' => 'Valid email is required'], 422);
}
if (!in_array($role, ['visitor', 'editor', 'admin'], true)) {
    respond(['success' => false, 'message' => 'Invalid user level'], 422);
}

$dup = mysqli_prepare($mysqliConn, 'SELECT user_id FROM users WHERE username = ? LIMIT 1');
mysqli_stmt_bind_param($dup, 's', $email);
mysqli_stmt_execute($dup);
$existing = stmtFetchOneAssoc($dup);
mysqli_stmt_close($dup);
if ($existing) respond(['success' => false, 'message' => 'Email already in use'], 409);

$plainPassword = randomPasswordString(24);
$passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
$approved = $role === 'visitor' ? 1 : 0;
$country = null;
$stmt = mysqli_prepare($mysqliConn, 'INSERT INTO users (username, password, first_name, last_name, role, country_id, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'sssssii', $email, $passwordHash, $first, $last, $role, $country, $approved);
mysqli_stmt_execute($stmt);
$uid = mysqli_insert_id($mysqliConn);
mysqli_stmt_close($stmt);

appSettingSet($mysqliConn, 'user_email_' . $uid, $email);
appSettingSet($mysqliConn, 'user_member_id_' . $uid, $memberId);

if ($role === 'visitor') {
    appSettingSet($mysqliConn, 'user_email_verified_' . $uid, '1');
    respond(['success' => true, 'created' => 1, 'invited' => 0]);
}

$tokenPlain = bin2hex(random_bytes(24));
$tokenHash = hash('sha256', $tokenPlain);
appSettingSet($mysqliConn, 'user_setup_token_' . $uid, $tokenHash);
appSettingSet($mysqliConn, 'user_setup_expires_' . $uid, (string)(time() + 86400));
appSettingSet($mysqliConn, 'user_email_verified_' . $uid, '0');
$setupUrl = makeAbsoluteUrl('/login/?setup=1&uid=' . urlencode((string)$uid) . '&token=' . urlencode($tokenPlain));

$subject = 'Your new account setup';
$body = "Hello,\n\nA new account was created for you.\nPlease set your password here:\n{$setupUrl}\n\nThis link expires in 24 hours.";
$headers = 'From: no-reply@immeet.ing' . "\r\n" . 'Reply-To: no-reply@immeet.ing' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
$sent = mail($email, $subject, $body, $headers);
if (!$sent) {
    respond(['success' => false, 'message' => 'User created but invitation email could not be sent'], 500);
}

respond(['success' => true, 'created' => 1, 'invited' => 1]);
?>
