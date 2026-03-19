<?php
// Creates the SQLite database and loads starter data from restaurants.json.

declare(strict_types=1);

header("Content-Type: text/html; charset=UTF-8");

$databaseFile = __DIR__ . "/restaurants.db";
$jsonFile = __DIR__ . "/restaurants.json";

try {
    $pdo = new PDO("sqlite:" . $databaseFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $createTableSql = "
        CREATE TABLE IF NOT EXISTS restaurants (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            tags TEXT NOT NULL,
            image TEXT NOT NULL
        )
    ";
    $pdo->exec($createTableSql);

    $columnNames = [];
    foreach ($pdo->query("PRAGMA table_info(restaurants)") as $column) {
        $columnNames[] = $column["name"];
    }

    if (!in_array("image", $columnNames, true)) {
        $pdo->exec("ALTER TABLE restaurants ADD COLUMN image TEXT NOT NULL DEFAULT ''");
    }

    $createUsersTableSql = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL
        )
    ";
    $pdo->exec($createUsersTableSql);

    $createReviewsTableSql = "
        CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            restaurant_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
            body TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    $pdo->exec($createReviewsTableSql);

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_reviews_restaurant_id ON reviews(restaurant_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_reviews_user_id ON reviews(user_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_reviews_created_at ON reviews(created_at)");

    if (!file_exists($jsonFile)) {
        throw new Exception("restaurants.json was not found.");
    }

    $jsonText = file_get_contents($jsonFile);
    $restaurants = json_decode($jsonText, true);

    if (!is_array($restaurants)) {
        throw new Exception("restaurants.json does not contain valid JSON data.");
    }

    // Clear old seed data so the setup script can be run more than once.
    $pdo->exec("DELETE FROM restaurants");

    $hasLegacyContentColumn = in_array("content", $columnNames, true);

    if ($hasLegacyContentColumn) {
        $insertSql = "
            INSERT INTO restaurants (id, name, tags, content, image)
            VALUES (:id, :name, :tags, :content, :image)
        ";
    } else {
        $insertSql = "
            INSERT INTO restaurants (id, name, tags, image)
            VALUES (:id, :name, :tags, :image)
        ";
    }
    $statement = $pdo->prepare($insertSql);

    foreach ($restaurants as $restaurant) {
        $id = (int) $restaurant["id"];
        $name = $restaurant["name"];
        $tags = implode(", ", $restaurant["tags"]);
        $image = $restaurant["image"] ?? "";
        $content = $image;

        $statement->bindParam(":id", $id, PDO::PARAM_INT);
        $statement->bindParam(":name", $name, PDO::PARAM_STR);
        $statement->bindParam(":tags", $tags, PDO::PARAM_STR);
        if ($hasLegacyContentColumn) {
            $statement->bindParam(":content", $content, PDO::PARAM_STR);
        }
        $statement->bindParam(":image", $image, PDO::PARAM_STR);
        $statement->execute();
    }

    echo "<h1>Database setup complete.</h1>";
    echo "<p>The restaurants, users, and reviews tables were created. Restaurants seeded from restaurants.json.</p>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
} catch (PDOException $error) {
    echo "<h1>Database Error</h1>";
    echo "<p>" . htmlspecialchars($error->getMessage(), ENT_QUOTES, "UTF-8") . "</p>";
} catch (Exception $error) {
    echo "<h1>Setup Error</h1>";
    echo "<p>" . htmlspecialchars($error->getMessage(), ENT_QUOTES, "UTF-8") . "</p>";
}
?>
