<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function is_logged_in(): bool {
  return isset($_SESSION['user_id']);
}

function require_login(): void {
  if (!is_logged_in()) {
    header('Location: /login.php');
    exit;
  }
}

function is_admin(): bool {
  return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_admin(): void {
  if (!is_admin()) {
    http_response_code(403);
    echo "403 - Nincs jogosultság.";
    exit;
  }
}
