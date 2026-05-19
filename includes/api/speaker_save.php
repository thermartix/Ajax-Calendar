<?php
require_once __DIR__ . '/bootstrap.php';

function slugifySpeaker(string $name): string {
    $s = strtolower(trim($name));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim((string)$s, '-');
    return $s !== '' ? $s : 'speaker';
}

requireLogin();
$user = currentUser($mysqliConn);
if (!$user || !in_array((string)$user['role'], ['admin', 'editor'], true)) {
    respond(['success' => false, 'message' => 'Admin or editor required'], 403);
}

$in = jsonInput();
$id = isset($in['id']) ? (int)$in['id'] : 0;
$name = trim((string)($in['name'] ?? ''));
$bio = trim((string)($in['bio'] ?? ''));
$profileImagePath = trim((string)($in['profile_image_path'] ?? ''));

if ($name === '') {
    respond(['success' => false, 'message' => 'Speaker name is required'], 422);
}

$check = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'speakers'");
if (!$check || mysqli_num_rows($check) === 0) {
    respond(['success' => false, 'message' => 'Speakers table is missing. Run migration_2026_05_speakers.sql first.'], 500);
}

$base = slugifySpeaker($name);
$slug = $base;
$idx = 2;
while (true) {
    if ($id > 0) {
        $stmt = mysqli_prepare($mysqliConn, 'SELECT id FROM speakers WHERE slug = ? AND id <> ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'si', $slug, $id);
    } else {
        $stmt = mysqli_prepare($mysqliConn, 'SELECT id FROM speakers WHERE slug = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $slug);
    }
    mysqli_stmt_execute($stmt);
    $dup = stmtFetchOneAssoc($stmt);
    mysqli_stmt_close($stmt);
    if (!$dup) break;
    $slug = $base . '-' . $idx;
    $idx++;
}

if ($id > 0) {
    $stmt = mysqli_prepare($mysqliConn, 'UPDATE speakers SET name = ?, slug = ?, bio = ?, profile_image_path = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'ssssi', $name, $slug, $bio, $profileImagePath, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    respond(['success' => true, 'id' => $id, 'slug' => $slug]);
}

$stmt = mysqli_prepare($mysqliConn, 'INSERT INTO speakers (name, slug, bio, profile_image_path) VALUES (?, ?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'ssss', $name, $slug, $bio, $profileImagePath);
mysqli_stmt_execute($stmt);
$newId = (int)mysqli_insert_id($mysqliConn);
mysqli_stmt_close($stmt);
respond(['success' => true, 'id' => $newId, 'slug' => $slug]);
?>
