<?php
// helpers/auth_guard.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Make sure user already login.
 * If not yet, it will go to login page.
 */
function requireAuth(): void {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php?error=unauthorized");
        exit;
    }
}

/**
 * Limit access for another role.
 * Ex:checkRole(['admin', 'agent']);
 */
function checkRole(array $allowedRoles): void {
    requireAuth();

    if (!in_array($_SESSION['role'], $allowedRoles, true)) {
        header("Location: ../views/unauthorized.php");
        exit;
    }
}

/**
 * Avoid user that already login to go to login page.
 */
function requireGuest(): void {
    if (isset($_SESSION['user_id'])) {
        header("Location: ../views/dashboard.php");
        exit;
    }
}