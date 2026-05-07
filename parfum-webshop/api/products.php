<?php

declare(strict_types=1);

require_once __DIR__ . "/_bootstrap.php";

$categoryId = isset($_GET["category_id"]) ? (int) $_GET["category_id"] : 0;
$search = $_GET["search"] ?? "";

$sql = "
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
    WHERE 1 = 1
";

$params = [];

if ($categoryId > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryId;
}

if ($search !== "") {
    $sql .= " AND (p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
}

$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

json_response([
    "success" => true,
    "products" => $products
]);