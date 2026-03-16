<?php
// setup-db.php
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
            content TEXT NOT NULL
        )
    ";
    $pdo->exec($createTableSql);

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

    $insertSql = "
        INSERT INTO restaurants (id, name, tags, content)
        VALUES (:id, :name, :tags, :content)
    ";
    $statement = $pdo->prepare($insertSql);

    foreach ($restaurants as $restaurant) {
        $id = (int) $restaurant["id"];
        $name = $restaurant["name"];
        $tags = implode(", ", $restaurant["tags"]);
        $content = $restaurant["content"];

        $statement->bindParam(":id", $id, PDO::PARAM_INT);
        $statement->bindParam(":name", $name, PDO::PARAM_STR);
        $statement->bindParam(":tags", $tags, PDO::PARAM_STR);
        $statement->bindParam(":content", $content, PDO::PARAM_STR);
        $statement->execute();
    }

    echo "<h1>Database setup complete.</h1>";
    echo "<p>The restaurants table was created and seeded from restaurants.json.</p>";
    echo "<p><a href='home.php'>Go to Home Page</a></p>";
} catch (PDOException $error) {
    echo "<h1>Database Error</h1>";
    echo "<p>" . htmlspecialchars($error->getMessage(), ENT_QUOTES, "UTF-8") . "</p>";
} catch (Exception $error) {
    echo "<h1>Setup Error</h1>";
    echo "<p>" . htmlspecialchars($error->getMessage(), ENT_QUOTES, "UTF-8") . "</p>";
}
?>
