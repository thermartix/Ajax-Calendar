<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();
$admin = currentUser($mysqliConn);
requireAdmin($admin);
$data = jsonInput();
$rows = isset($data['users']) && is_array($data['users']) ? $data['users'] : [];
if (!$rows) respond(['success' => false, 'message' => 'No users provided'], 422);

$created = 0;
$skipped = 0;
$invited = 0;

foreach ($rows as $r) {
    $first = trim((string)($r['first_name'] ?? ''));
    $last = trim((string)($r['last_name'] ?? ''));
    $email = strtolower(trim((string)($r['email'] ?? '')));
    $memberId = trim((string)($r['member_id'] ?? ''));
    $role = trim((string)($r['role'] ?? 'visitor'));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['visitor', 'editor', 'admin'], true)) {
        $skipped++;
        continue;
    }
    $dup = mysqli_prepare($mysqliConn, 'SELECT user_id FROM users WHERE username = ? LIMIT 1');
    mysqli_stmt_bind_param($dup, 's', $email);
    mysqli_stmt_execute($dup);
    $existing = stmtFetchOneAssoc($dup);
    mysqli_stmt_close($dup);
    if ($existing) { $skipped++; continue; }

    $plainPassword = randomPasswordString(24);
    $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $approved = $role === 'visitor' ? 1 : 0;
    $country = null;
    $stmt = mysqli_prepare($mysqliConn, 'INSERT INTO users (username, password, first_name, last_name, role, country_id, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'sssssii', $email, $passwordHash, $first, $last, $role, $country, $approved);
    mysqli_stmt_execute($stmt);
    $uid = mysqli_insert_id($mysqliConn);
    mysqli_stmt_close($stmt);
    appSettingSet($mysqliConn, 'user_email_' . $uid, $email);
    appSettingSet($mysqliConn, 'user_member_id_' . $uid, $memberId);
    if ($role === 'visitor') {
        appSettingSet($mysqliConn, 'user_email_verified_' . $uid, '1');
        $created++;
        continue;
    }
    $tokenPlain = bin2hex(random_bytes(24));
    appSettingSet($mysqliConn, 'user_setup_token_' . $uid, hash('sha256', $tokenPlain));
    appSettingSet($mysqliConn, 'user_setup_expires_' . $uid, (string)(time() + 86400));
    appSettingSet($mysqliConn, 'user_email_verified_' . $uid, '0');
    $setupUrl = makeAbsoluteUrl('/login/?setup=1&uid=' . urlencode((string)$uid) . '&token=' . urlencode($tokenPlain));
    $subject = 'Your new account setup';
    $body = "Hello,\n\nA new account was created for you.\nPlease set your password here:\n{$setupUrl}\n\nThis link expires in 24 hours.";
    $headers = 'From: no-reply@immeet.ing' . "\r\n" . 'Reply-To: no-reply@immeet.ing' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
    if (mail($email, $subject, $body, $headers)) $invited++;
    $created++;
}

respond(['success' => true, 'created' => $created, 'skipped' => $skipped, 'invited' => $invited]);
?>
