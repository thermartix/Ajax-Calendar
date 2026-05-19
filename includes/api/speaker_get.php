<?php
require_once __DIR__ . '/bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = trim((string)($_GET['slug'] ?? ''));

$check = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'speakers'");
if (!$check || mysqli_num_rows($check) === 0) {
    respond(['success' => false, 'message' => 'Speakers table is missing'], 404);
}

if ($id > 0) {
    $stmt = mysqli_prepare($mysqliConn, 'SELECT id, name, slug, bio, profile_image_path FROM speakers WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
} else {
    $stmt = mysqli_prepare($mysqliConn, 'SELECT id, name, slug, bio, profile_image_path FROM speakers WHERE slug = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $slug);
}
mysqli_stmt_execute($stmt);
$s = stmtFetchOneAssoc($stmt);
mysqli_stmt_close($stmt);

if (!$s) {
    respond(['success' => false, 'message' => 'Speaker not found'], 404);
}

$events = [];
$esCheck = mysqli_query($mysqliConn, "SHOW TABLES LIKE 'event_speakers'");
if ($esCheck && mysqli_num_rows($esCheck) > 0) {
    $eStmt = mysqli_prepare($mysqliConn, 'SELECT e.id, e.title, e.start_at, e.end_at FROM event_speakers es JOIN events e ON e.id = es.event_id WHERE es.speaker_id = ? ORDER BY e.start_at DESC LIMIT 20');
    $sid = (int)$s['id'];
    mysqli_stmt_bind_param($eStmt, 'i', $sid);
    mysqli_stmt_execute($eStmt);
    foreach (stmtFetchAllAssoc($eStmt) as $row) {
        $events[] = [
            'id' => (int)$row['id'],
            'title' => (string)$row['title'],
            'start_at' => (string)$row['start_at'],
            'end_at' => (string)$row['end_at']
        ];
    }
    mysqli_stmt_close($eStmt);
}

respond(['success' => true, 'speaker' => [
    'id' => (int)$s['id'],
    'name' => (string)$s['name'],
    'slug' => (string)$s['slug'],
    'bio' => (string)($s['bio'] ?? ''),
    'profile_image_path' => (string)($s['profile_image_path'] ?? ''),
    'events' => $events
]]);
?>
