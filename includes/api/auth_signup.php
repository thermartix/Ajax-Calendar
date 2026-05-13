<?php
require_once __DIR__ . '/bootstrap.php';
$data = jsonInput();
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');
$password2 = (string)($data['passwordRepeat'] ?? '');
$email = trim((string)($data['email'] ?? ''));
$firstName = trim((string)($data['first_name'] ?? ''));
$lastName = trim((string)($data['last_name'] ?? ''));
$countryId = isset($data['country_id']) && $data['country_id'] !== '' ? (int)$data['country_id'] : null;

if ($username === '' || $password === '' || $password2 === '' || $email === '') {
    respond(['success' => false, 'message' => 'All required fields must be filled'], 422);
}
if ($password !== $password2) {
    respond(['success' => false, 'message' => 'Passwords do not match'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(['success' => false, 'message' => 'Valid email is required'], 422);
}

$res = mysqli_query($mysqliConn, "SELECT COUNT(*) AS c FROM users WHERE role='admin'");
$adminExists = ((int)mysqli_fetch_assoc($res)['c']) > 0;
$role = $adminExists ? 'editor' : 'admin';
$isApproved = $adminExists ? 0 : 1;

if ($role === 'editor' && !$countryId) {
    respond(['success' => false, 'message' => 'Country is required'], 422);
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
$stmt = mysqli_prepare($mysqliConn, 'INSERT INTO users (username, password, first_name, last_name, role, country_id, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'sssssii', $username, $hash, $firstName, $lastName, $role, $countryId, $isApproved);
if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    respond(['success' => false, 'message' => 'Could not create user'], 500);
}
$userId = mysqli_insert_id($mysqliConn);
mysqli_stmt_close($stmt);

appSettingSet($mysqliConn, 'user_email_' . $userId, $email);

if ($isApproved === 1) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
}

respond([
    'success' => true,
    'approved' => $isApproved === 1,
    'message' => $isApproved === 1 ? 'Account created' : 'Registered. Waiting for admin approval and role assignment.'
]);
?>
