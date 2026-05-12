<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
requireAdmin($user);
$data = jsonInput();
$items = isset($data['countries']) && is_array($data['countries']) ? $data['countries'] : [];
$stmt = mysqli_prepare($mysqliConn, 'INSERT INTO countries (code, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)');
foreach ($items as $item) {
    $code = trim((string)($item['code'] ?? ''));
    $name = trim((string)($item['name'] ?? ''));
    if ($code === '' || $name === '') {
        continue;
    }
    mysqli_stmt_bind_param($stmt, 'ss', $code, $name);
    mysqli_stmt_execute($stmt);
}
mysqli_stmt_close($stmt);
respond(['success' => true]);
?>
