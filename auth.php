<?php
/**
 * Authentication System — Nournia Shop
 * Session-based auth with role management
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Check if user is logged in
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Check if current user is admin
 */
function is_admin(): bool {
    return is_logged_in() && ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * Get current user data from session
 */
function current_user(): ?array {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
    ];
}

/**
 * Require authentication — redirect to login if not logged in
 */
function require_auth(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require admin role — redirect with error if not admin
 */
function require_admin(): void {
    require_auth();
    if (!is_admin()) {
        header('Location: index.php?error=unauthorized');
        exit;
    }
}

/**
 * Attempt login with email and password
 */
function attempt_login(PDO $pdo, string $email, string $password): array {
    $errors = [];

    if (empty($email)) {
        $errors[] = 'กรุณากรอกอีเมล';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }

    if (empty($password)) {
        $errors[] = 'กรุณากรอกรหัสผ่าน';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'errors' => ['อีเมลหรือรหัสผ่านไม่ถูกต้อง']];
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];

    return ['success' => true, 'user' => $user];
}

/**
 * Register a new user
 */
function register_user(PDO $pdo, string $username, string $email, string $password, string $confirmPassword): array {
    $errors = [];

    // Validate username
    if (empty($username)) {
        $errors[] = 'กรุณากรอกชื่อผู้ใช้';
    } elseif (strlen($username) < 3) {
        $errors[] = 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร';
    } elseif (strlen($username) > 50) {
        $errors[] = 'ชื่อผู้ใช้ต้องไม่เกิน 50 ตัวอักษร';
    }

    // Validate email
    if (empty($email)) {
        $errors[] = 'กรุณากรอกอีเมล';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }

    // Validate password
    if (empty($password)) {
        $errors[] = 'กรุณากรอกรหัสผ่าน';
    } elseif (strlen($password) < 8) {
        $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    }

    // Validate confirm password
    if ($password !== $confirmPassword) {
        $errors[] = 'รหัสผ่านไม่ตรงกัน';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Check for duplicate email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'errors' => ['อีเมลนี้ถูกใช้งานแล้ว']];
    }

    // Check for duplicate username
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return ['success' => false, 'errors' => ['ชื่อผู้ใช้นี้ถูกใช้งานแล้ว']];
    }

    // Insert new user
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')");
    $stmt->execute([$username, $email, $hash]);

    return ['success' => true];
}

/**
 * Logout — destroy session
 */
function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
