<?php
require_once __DIR__ . '/bootstrap.php';
$data = jsonInput();
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');
$password2 = (string)($data['passwordRepeat'] ?? '');
$role = (string)($data['role'] ?? 'category_editor');
$countryId = isset($data['country_id']) && $data['country_id'] !== '' ? (int)$data['country_id'] : null;

if ($username === '' || $password === '' || $password2 === '') {
    respond(['success' => false, 'message' => 'All required fields must be filled'], 422);
}
if ($password !== $password2) {
    respond(['success' => false, 'message' => 'Passwords do not match'], 422);
}
if (!in_array($role, ['admin', 'category_editor'], true)) {
    respond(['success' => false, 'message' => 'Invalid role'], 422);
}
if ($role === 'category_editor' && !$countryId) {
    respond(['success' => false, 'message' => 'Category editor requires a country'], 422);
}

$stmt = mysqli_prepare($mysqliConn, 'SELECT user_id FROM users WHERE username = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (mysqli_fetch_assoc($res)) {
    mysqli_stmt_close($stmt);
    respond(['success' => false, 'message' => 'Username already exists'], 409);
}
mysqli_stmt_close($stmt);

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($mysqliConn, 'INSERT INTO users (username, password, role, country_id) VALUES (?, ?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'sssi', $username, $hash, $role, $countryId);
if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    respond(['success' => false, 'message' => 'Could not create user'], 500);
}
$userId = mysqli_insert_id($mysqliConn);
mysqli_stmt_close($stmt);

$_SESSION['user_id'] = $userId;
$_SESSION['username'] = $username;
respond([
    'success' => true,
    'user' => [
        'user_id' => $userId,
        'username' => $username,
        'role' => $role,
        'country_id' => $countryId
    ]
]);
?>