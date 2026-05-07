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

$name = trim($data["name"] ?? "");
$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";

if ($name === "" || $email === "" || $password === "") {
    json_response([
        "success" => false,
        "message" => "Minden mezo kitoltese kotelezo"
    ], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response([
        "success" => false,
        "message" => "Ervenytelen email cim"
    ], 400);
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    json_response([
        "success" => false,
        "message" => "Ez az email mar foglalt"
    ], 409);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users (name, email, password_hash, role)
    VALUES (?, ?, ?, 'user')
");

$stmt->execute([$name, $email, $passwordHash]);

json_response([
    "success" => true,
    "message" => "Sikeres regisztracio"
], 201);