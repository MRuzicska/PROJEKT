<?php

declare(strict_types=1);

require_once __DIR__ . "/_auth.php";

$token = get_bearer_token();

if (!$token) {
    json_response([
        "success" => false,
        "message" => "Hianyzik a token"
    ], 401);
}

$stmt = $pdo->prepare("DELETE FROM api_tokens WHERE token = ?");
$stmt->execute([$token]);

json_response([
    "success" => true,
    "message" => "Sikeres kijelentkezes"
]);