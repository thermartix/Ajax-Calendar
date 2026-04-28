<?php
require_once __DIR__ . '/bootstrap.php';
$countryFilter = isset($_GET['country_id']) && $_GET['country_id'] !== '' ? (int)$_GET['country_id'] : null;
$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;

$sql = 'SELECT e.id, e.user_id, e.country_id, c.name AS country_name, e.title, e.description, e.event_link, e.start_at, e.end_at, u.username FROM events e JOIN countries c ON c.id = e.country_id JOIN users u ON u.user_id = e.user_id WHERE 1=1';
$params = [];
$types = '';

if ($countryFilter) {
    $sql .= ' AND e.country_id = ?';
    $types .= 'i';
    $params[] = $countryFilter;
}
if ($start) {
    $sql .= ' AND e.end_at >= ?';
    $types .= 's';
    $params[] = $start;
}
if ($end) {
    $sql .= ' AND e.start_at <= ?';
    $types .= 's';
    $params[] = $end;
}

$sql .= ' ORDER BY e.start_at ASC';
$stmt = mysqli_prepare($mysqliConn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$events = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$user = currentUser($mysqliConn);
foreach ($events as &$ev) {
    $ev['id'] = (int)$ev['id'];
    $ev['country_id'] = (int)$ev['country_id'];
    $ev['user_id'] = (int)$ev['user_id'];
    $ev['can_edit'] = $user ? canEditEvent($user, $ev) : false;
}

respond(['success' => true, 'events' => $events]);
?>