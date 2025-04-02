<?php
// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        set_flash_message('Invalid request', 'error');
        header('Location: login.php');
        exit();
    }

    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        set_flash_message('Please enter your email address', 'error');
        header('Location: ./login.php');
        exit();
    }

    $conn = db_connect();
    $stmt = $conn->prepare("SELECT user_id, username, email, first_name, verification_token FROM users WHERE email = ? AND email_verified = FALSE");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Generate new token if none exists or it's expired
        if (empty($user['verification_token'])) {
            $verification_token = bin2hex(random_bytes(32));
            $verification_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $update_stmt = $conn->prepare("UPDATE users SET verification_token = ?, verification_token_expiry = ? WHERE user_id = ?");
            $update_stmt->bind_param("ssi", $verification_token, $verification_expiry, $user['id']);
            $update_stmt->execute();
        } else {
            $verification_token = $user['verification_token'];
        }

        // Send verification email
        $email_sent = send_verification_email($user['email'], $user['first_name'], $verification_token);

        if ($email_sent) {
            set_flash_message('Verification email resent. Please check your inbox.', 'success');
        } else {
            set_flash_message('Failed to resend verification email. Please try again later.', 'error');
        }
    } else {
        set_flash_message('No unverified account found with that email or the account is already verified.', 'error');
    }

    header('Location: ./login.php');
    exit();
}

header('Location: /../index.php');
exit();
