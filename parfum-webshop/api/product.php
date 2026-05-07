<?php

declare(strict_types=1);

require_once __DIR__ . "/_bootstrap.php";

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id <= 0) {
    json_response([
        "success" => false,
        "message" => "Ervenytelen termek ID"
    ], 400);
}

$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.name,
        p.brand,
        p.description,
        p.price,
        p.stock,
        p.category_id,
        p.image_url,
        p.created_at,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.id = ?
    LIMIT 1
");

$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    json_response([
        "success" => false,
        "message" => "Termek nem talalhato"
    ], 404);
}

$variantStmt = $pdo->prepare("
    SELECT id, product_id, size_ml, price, stock
    FROM product_variants
    WHERE product_id = ?
    ORDER BY size_ml ASC
");

$variantStmt->execute([$id]);
$variants = $variantStmt->fetchAll();

$product["variants"] = $variants;

json_response([
    "success" => true,
    "product" => $product
]);