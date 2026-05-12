<?php
require_once __DIR__ . '/bootstrap.php';
$user = currentUser($mysqliConn);
if ($user) {
    $user['user_id'] = (int)$user['user_id'];
    $user['country_id'] = $user['country_id'] !== null ? (int)$user['country_id'] : null;
    $user['is_approved'] = (int)$user['is_approved'];
    $user['allowed_country_ids'] = userAllowedCountryIds($mysqliConn, (int)$user['user_id']);
    $fmt = appSettingGet($mysqliConn, 'user_datetime_format_' . (int)$user['user_id'], 'eu');
    $user['datetime_format'] = $fmt === 'eu' ? 'eu' : 'us';
}
respond([
    'success' => true,
    'loggedIn' => $user !== null,
    'user' => $user
]);
?>
