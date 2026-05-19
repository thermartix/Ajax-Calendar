<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
$uid = (int)$user['user_id'];
$data = jsonInput();
$first = trim((string)($data['first_name'] ?? ''));
$last = trim((string)($data['last_name'] ?? ''));
$countryId = isset($data['country_id']) && $data['country_id'] !== '' ? (int)$data['country_id'] : null;
$datetimeFormat = (string)($data['datetime_format'] ?? '');
$currentPassword = (string)($data['current_password'] ?? '');
$newPassword = (string)($data['new_password'] ?? '');
$email = isset($data['email']) ? strtolower(trim((string)$data['email'])) : '';
$memberId = isset($data['member_id']) ? trim((string)$data['member_id']) : null;
$stmt = mysqli_prepare($mysqliConn, 'UPDATE users SET first_name = ?, last_name = ?, country_id = ? WHERE user_id = ?');
$countryIdForBind = $countryId !== null ? (int)$countryId : null;
mysqli_stmt_bind_param($stmt, 'sssi', $first, $last, $countryIdForBind, $uid);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
if ($newPassword !== '') {
    if ($currentPassword === '') {
        respond(['success' => false, 'message' => 'Current password is required'], 422);
    }
    if (strlen($newPassword) < 8) {
        respond(['success' => false, 'message' => 'New password must have at least 8 characters'], 422);
    }
    $pCheck = mysqli_prepare($mysqliConn, 'SELECT password FROM users WHERE user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($pCheck, 'i', $uid);
    mysqli_stmt_execute($pCheck);
    $pwdRow = stmtFetchOneAssoc($pCheck);
    mysqli_stmt_close($pCheck);
    $currentHash = (string)($pwdRow['password'] ?? '');
    if ($currentHash === '' || !password_verify($currentPassword, $currentHash)) {
        respond(['success' => false, 'message' => 'Current password is incorrect'], 403);
    }
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $pstmt = mysqli_prepare($mysqliConn, 'UPDATE users SET password = ? WHERE user_id = ?');
    mysqli_stmt_bind_param($pstmt, 'si', $hash, $uid);
    mysqli_stmt_execute($pstmt);
    mysqli_stmt_close($pstmt);
}
if ($email !== '' && (string)$user['role'] === 'admin') {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(['success' => false, 'message' => 'Invalid email format'], 422);
    }
    $check = mysqli_prepare($mysqliConn, 'SELECT user_id FROM users WHERE username = ? AND user_id <> ? LIMIT 1');
    mysqli_stmt_bind_param($check, 'si', $email, $uid);
    mysqli_stmt_execute($check);
    $dup = stmtFetchOneAssoc($check);
    mysqli_stmt_close($check);
    if ($dup) {
        respond(['success' => false, 'message' => 'Email already in use'], 409);
    }
    $uStmt = mysqli_prepare($mysqliConn, 'UPDATE users SET username = ? WHERE user_id = ?');
    mysqli_stmt_bind_param($uStmt, 'si', $email, $uid);
    mysqli_stmt_execute($uStmt);
    mysqli_stmt_close($uStmt);
    appSettingSet($mysqliConn, 'user_email_' . $uid, $email);
    $_SESSION['username'] = $email;
}
if ($datetimeFormat !== '') {
    $fmt = $datetimeFormat === 'eu' ? 'eu' : 'us';
    appSettingSet($mysqliConn, 'user_datetime_format_' . $uid, $fmt);
}
if ($memberId !== null) {
    appSettingSet($mysqliConn, 'user_member_id_' . $uid, $memberId);
}
respond(['success' => true]);
?>
