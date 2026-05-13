<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
requireAdmin($user);

$sql = 'SELECT u.user_id, u.username, u.first_name, u.last_name, u.role, u.country_id, u.is_approved, c.name AS country_name
        FROM users u
        LEFT JOIN countries c ON c.id = u.country_id
        ORDER BY u.created_at DESC';
$res = mysqli_query($mysqliConn, $sql);
$users = mysqli_fetch_all($res, MYSQLI_ASSOC);

foreach ($users as &$u) {
    $u['user_id'] = (int)$u['user_id'];
    $u['country_id'] = $u['country_id'] !== null ? (int)$u['country_id'] : null;
    $u['is_approved'] = (int)$u['is_approved'];
    $u['allowed_country_ids'] = userAllowedCountryIds($mysqliConn, (int)$u['user_id']);
    $u['email'] = appSettingGet($mysqliConn, 'user_email_' . (int)$u['user_id'], $u['username'] ?? '');
    $u['email_verified'] = appSettingGet($mysqliConn, 'user_email_verified_' . (int)$u['user_id'], '0') === '1' ? 1 : 0;
}

respond(['success' => true, 'users' => $users]);
?>
