<?php
// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

require_once __DIR__ . '/../../includes/functions.php';

// Log received tokens for debugging
error_log("Verification attempt - Token: " . ($_GET['token'] ?? 'NULL'));
error_log("Login token: " . ($_GET['login_token'] ?? 'NULL'));

if (!isset($_GET['token'], $_GET['login_token'])) {
    error_log("Missing verification tokens");
    set_flash_message('Invalid verification link', 'error');
    header('Location: login.php');
    exit();
}

$conn = db_connect();
if (!$conn) {
    error_log("Database connection failed");
    set_flash_message('System error. Please try again.', 'error');
    header('Location: login.php');
    exit();
}

// Begin transaction for atomic verification
$conn->begin_transaction();

try {
    // 1. Find unverified user with matching token
    $stmt = $conn->prepare("SELECT user_id, username, login_token 
                          FROM users 
                          WHERE verification_token = ? 
                          AND email_verified = 0
                          LIMIT 1");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $_GET['token']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        error_log("No user found with verification token: " . $_GET['token']);
        throw new Exception("Invalid or expired verification link");
    }

    $user = $result->fetch_assoc();
    error_log("Found user for verification: " . print_r($user, true));

    // 2. Verify login token
    if (!password_verify($_GET['login_token'], $user['login_token'])) {
        error_log("Login token verification failed");
        throw new Exception("Invalid verification link");
    }

    // 3. Mark as verified
    $update = $conn->prepare("UPDATE users 
                            SET email_verified = 1,
                                verification_token = NULL,
                                login_token = NULL,
                                login_token_expiry = NULL
                            WHERE user_id = ?");
    if (!$update) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $update->bind_param("i", $user['user_id']);
    if (!$update->execute()) {
        throw new Exception("Update failed: " . $update->error);
    }

    // Commit all changes
    $conn->commit();
    error_log("Successfully verified user ID: " . $user['user_id']);

    // 4. Log the user in
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email_verified'] = true;
    session_regenerate_id(true);

    set_flash_message('Email verified successfully!', 'success');
    header('Location: login.php');
    exit();
} catch (Exception $e) {
    $conn->rollback();
    error_log("Verification ERROR: " . $e->getMessage());
    set_flash_message($e->getMessage(), 'error');
    header('Location: login.php');
    exit();
}
