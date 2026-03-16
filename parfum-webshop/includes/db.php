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
    "mysql:host=127.0.0.1;dbname=parfum_webshop;charset=utf8",
    "root",
    ""
);
} catch (PDOException $e) {
  die("DB kapcsolat hiba: " . htmlspecialchars($e->getMessage()));
}
