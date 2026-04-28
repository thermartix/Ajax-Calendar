<?php
require_once __DIR__ . '/bootstrap.php';
$res = mysqli_query($mysqliConn, 'SELECT id, code, name FROM countries ORDER BY name');
$countries = mysqli_fetch_all($res, MYSQLI_ASSOC);
respond(['success' => true, 'countries' => $countries]);
?>