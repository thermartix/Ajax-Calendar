<?php
require_once __DIR__ . '/bootstrap.php';
$user = currentUser($mysqliConn);
respond([
    'success' => true,
    'loggedIn' => $user !== null,
    'user' => $user
]);
?>