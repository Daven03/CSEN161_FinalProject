<?php

declare(strict_types=1);

require __DIR__ . "/auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: restaurants.php");
    exit;
}

$restaurantId = isset($_POST["restaurant_id"]) ? (int) $_POST["restaurant_id"] : 0;
$rating = isset($_POST["rating"]) ? (int) $_POST["rating"] : 0;
$body = trim($_POST["body"] ?? "");
$username = $_SESSION["username"] ?? "";

if ($restaurantId <= 0) {
    header("Location: restaurants.php");
    exit;
}

if ($body === "" || $rating < 1 || $rating > 5) {
    header("Location: restaurant.php?id=" . $restaurantId . "&review_error=Please+complete+the+rating+and+review+text.");
    exit;
}

try {
    $pdo = new PDO("sqlite:" . __DIR__ . "/restaurants.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $userSql = "SELECT id FROM users WHERE username = :username LIMIT 1";
    $userStatement = $pdo->prepare($userSql);
    $userStatement->bindParam(":username", $username, PDO::PARAM_STR);
    $userStatement->execute();
    $user = $userStatement->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Logged-in user was not found.");
    }

    $restaurantSql = "SELECT id FROM restaurants WHERE id = :restaurant_id LIMIT 1";
    $restaurantStatement = $pdo->prepare($restaurantSql);
    $restaurantStatement->bindParam(":restaurant_id", $restaurantId, PDO::PARAM_INT);
    $restaurantStatement->execute();

    if (!$restaurantStatement->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception("Restaurant was not found.");
    }

    $insertSql = "
        INSERT INTO reviews (restaurant_id, user_id, rating, body)
        VALUES (:restaurant_id, :user_id, :rating, :body)
    ";
    $insertStatement = $pdo->prepare($insertSql);
    $insertStatement->bindParam(":restaurant_id", $restaurantId, PDO::PARAM_INT);
    $insertStatement->bindParam(":user_id", $user["id"], PDO::PARAM_INT);
    $insertStatement->bindParam(":rating", $rating, PDO::PARAM_INT);
    $insertStatement->bindParam(":body", $body, PDO::PARAM_STR);
    $insertStatement->execute();

    header("Location: restaurant.php?id=" . $restaurantId . "&review_success=Review+submitted+successfully.");
    exit;
} catch (Throwable $error) {
    header("Location: restaurant.php?id=" . $restaurantId . "&review_error=" . rawurlencode($error->getMessage()));
    exit;
}
