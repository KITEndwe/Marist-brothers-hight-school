<?php
/**
 * Session bootstrap + role guard.
 * Include at the very top of any protected page:
 *   require_once __DIR__ . '/../includes/auth.php';
 *   require_role('student');   // or 'teacher' / 'admin'
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    if (!current_user()) {
        header('Location: /login.php');
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    if (current_user()['role'] !== $role) {
        header('Location: /login.php?error=wrong_portal');
        exit;
    }
}

function logout_user(): void {
    $_SESSION = [];
    session_destroy();
}