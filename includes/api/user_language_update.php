<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$user = currentUser($mysqliConn);
$uid = (int)$user['user_id'];
$data = jsonInput();
$lang = strtolower(trim((string)($data['language'] ?? '')));
$allowed = ['en', 'de', 'it', 'es', 'fr', 'hu', 'pt', 'ro', 'sk'];
if (!in_array($lang, $allowed, true)) {
    respond(['success' => false, 'message' => 'Invalid language'], 422);
}
appSettingSet($mysqliConn, 'user_lang_' . $uid, $lang);
respond(['success' => true, 'language' => $lang]);
?>
