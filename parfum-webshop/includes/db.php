<?php
declare(strict_types=1);

$DB_HOST = 'localhost';
$DB_NAME = 'parfum_webshop';
$DB_USER = 'root';
$DB_PASS = ''; // állítsd be

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
  $pdo = new PDO(
    "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    $options
  );
} catch (PDOException $e) {
  die("DB kapcsolat hiba: " . htmlspecialchars($e->getMessage()));
}
