<?php
require_once __DIR__ . '/bootstrap.php';
$data = jsonInput();
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') {
    respond(['success' => false, 'message' => 'Username and password are required'], 422);
}

$stmt = mysqli_prepare($mysqliConn, 'SELECT user_id, username, password, role, country_id FROM users WHERE username = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$row || !password_verify($password, $row['password'])) {
    respond(['success' => false, 'message' => 'Invalid credentials'], 401);
}

$_SESSION['user_id'] = (int)$row['user_id'];
$_SESSION['username'] = $row['username'];

respond([
    'success' => true,
    'user' => [
        'user_id' => (int)$row['user_id'],
        'username' => $row['username'],
        'role' => $row['role'],
        'country_id' => $row['country_id'] !== null ? (int)$row['country_id'] : null
    ]
]);
?>