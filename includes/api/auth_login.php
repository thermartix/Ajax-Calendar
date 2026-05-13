<?php
require_once __DIR__ . '/bootstrap.php';
$data = jsonInput();
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') {
    respond(['success' => false, 'message' => 'Username and password are required'], 422);
}

$stmt = mysqli_prepare($mysqliConn, 'SELECT user_id, username, password, first_name, last_name, role, country_id, is_approved FROM users WHERE username = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$row = stmtFetchOneAssoc($stmt);
mysqli_stmt_close($stmt);

if (!$row || !password_verify($password, $row['password'])) {
    respond(['success' => false, 'message' => 'Invalid credentials'], 401);
}
if ((int)$row['is_approved'] !== 1) {
    respond(['success' => false, 'message' => 'Account is not active yet. Please confirm your email first.'], 403);
}

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
