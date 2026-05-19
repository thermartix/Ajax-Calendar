<?php
require_once __DIR__ . '/bootstrap.php';

$rows = [];
$check = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'speakers'");
if ($check && mysqli_num_rows($check) > 0) {
    $res = mysqli_query($mysqliConn, 'SELECT id, name, slug, bio, profile_image_path FROM speakers ORDER BY name ASC');
    $rows = resultFetchAllAssoc($res);
}

$speakers = [];
foreach ($rows as $r) {
    $speakers[] = [
        'id' => (int)$r['id'],
        'name' => (string)$r['name'],
        'slug' => (string)$r['slug'],
        'bio' => (string)($r['bio'] ?? ''),
        'profile_image_path' => (string)($r['profile_image_path'] ?? '')
    ];
}

respond(['success' => true, 'speakers' => $speakers]);
?>
