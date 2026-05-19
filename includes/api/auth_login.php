<?php
require_once __DIR__ . '/bootstrap.php';
$data = jsonInput();
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') {
    respond(['success' => false, 'message' => 'Username and password are required'], 422);
}

$lookup = strtolower($username);
$subject = $lookup . '|' . clientIpAddress();
$limit = rateLimitCheck($mysqliConn, 'auth_login', $subject, 8, 300, 600);
if (!$limit['allowed']) {
    respond(['success' => false, 'message' => 'Too many login attempts. Please try again later.', 'retry_after' => $limit['retry_after']], 429);
}
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
            $stmt = mysqli_prepare($mysqliConn, 'SELECT user_id, username, password, first_name, last_name, role, country_id, is_approved FROM users WHERE user_id = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'i', $uid);
            mysqli_stmt_execute($stmt);
            $row = stmtFetchOneAssoc($stmt);
            mysqli_stmt_close($stmt);
        }
    }
} else {
    $stmt = mysqli_prepare($mysqliConn, 'SELECT user_id, username, password, first_name, last_name, role, country_id, is_approved FROM users WHERE username = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $lookup);
    mysqli_stmt_execute($stmt);
    $row = stmtFetchOneAssoc($stmt);
    mysqli_stmt_close($stmt);
}

if (!$row || !password_verify($password, $row['password'])) {
    rateLimitFailure($mysqliConn, 'auth_login', $subject, 300);
    respond(['success' => false, 'message' => 'Invalid credentials'], 401);
}
if ((int)$row['is_approved'] !== 1) {
    rateLimitFailure($mysqliConn, 'auth_login', $subject, 300);
    respond(['success' => false, 'message' => 'Account is not active yet. Please confirm your email first.'], 403);
}

rateLimitReset($mysqliConn, 'auth_login', $subject);
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$row['user_id'];
$_SESSION['username'] = $row['username'];

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
