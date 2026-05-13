<?php
require_once __DIR__ . '/bootstrap.php';

$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$token = trim((string)($_GET['token'] ?? ''));

if ($uid <= 0 || $token === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid verification link.';
    exit;
}

$savedHash = appSettingGet($mysqliConn, 'user_email_verify_token_' . $uid, '');
$savedExp = (int)appSettingGet($mysqliConn, 'user_email_verify_expires_' . $uid, '0');
if ($savedHash === '' || $savedExp <= time()) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Verification link expired. Please sign up again.';
    exit;
}

$tokenHash = hash('sha256', $token);
if (!hash_equals($savedHash, $tokenHash)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid verification token.';
    exit;
}

$role = 'visitor';
$approved = 1;
$stmt = mysqli_prepare($mysqliConn, 'UPDATE users SET is_approved = ?, role = ? WHERE user_id = ?');
mysqli_stmt_bind_param($stmt, 'isi', $approved, $role, $uid);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

appSettingSet($mysqliConn, 'user_email_verified_' . $uid, '1');
appSettingSet($mysqliConn, 'user_email_verify_token_' . $uid, '');
appSettingSet($mysqliConn, 'user_email_verify_expires_' . $uid, '0');

header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Email Confirmed</title></head><body style="font-family:Arial,sans-serif;padding:24px;"><h2>Email confirmed</h2><p>Your account is now active as a visitor.</p><p><a href="/login/">Go to login</a></p></body></html>';
?>
