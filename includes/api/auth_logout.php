<?php
require_once __DIR__ . '/bootstrap.php';
session_destroy();
respond(['success' => true]);
?>