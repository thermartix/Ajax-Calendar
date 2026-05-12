<?php
require_once __DIR__ . '/bootstrap.php';
$res = mysqli_query($mysqliConn, 'SELECT id, code, name FROM countries ORDER BY name');
$countries = resultFetchAllAssoc($res);
respond(['success' => true, 'countries' => $countries]);
?>
