<?php

declare(strict_types=1);

require_once __DIR__ . "/_bootstrap.php";

$stmt = $pdo->query("
    SELECT id, name
    FROM categories
    ORDER BY id ASC
");

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_response([
    "success" => true,
    "categories" => $categories
]);