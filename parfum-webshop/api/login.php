<?php

declare(strict_types=1);

require_once __DIR__ . "/_bootstrap.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response([
        "success" => false,
        "message" => "Csak POST keres engedelyezett"
    ], 405);
}

$data = get_json_input();

$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";

if ($email === "" || $password === "") {
    json_response([
        "success" => false,
        "message" => "Email es jelszo kotelezo"
    ], 400);
}

$stmt = $pdo->prepare("
    SELECT id, name, email, password_hash, role
    FROM users
    WHERE email = ?
    LIMIT 1
");

$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user["password_hash"])) {
    json_response([
        "success" => false,
        "message" => "Hibas email vagy jelszo"
    ], 401);
}

$token = bin2hex(random_bytes(32));

$stmt = $pdo->prepare("
    INSERT INTO api_tokens (user_id, token)
    VALUES (?, ?)
");

$stmt->execute([$user["id"], $token]);

json_response([
    "success" => true,
    "message" => "Sikeres bejelentkezes",
    "token" => $token,
    "user" => [
        "id" => $user["id"],
        "name" => $user["name"],
        "email" => $user["email"],
        "role" => $user["role"]
    ]
]);