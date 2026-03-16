<?php
// restaurant.php
// Loads one restaurant review based on the id in the URL.

declare(strict_types=1);

header("Content-Type: text/html; charset=UTF-8");

function loadTemplateFile(array $possibleFiles): string
{
    foreach ($possibleFiles as $fileName) {
        $fullPath = __DIR__ . "/" . $fileName;
        if (file_exists($fullPath)) {
            return $fullPath;
        }
    }

    throw new Exception("Template file was not found.");
}

try {
    if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
        throw new Exception("A valid restaurant id is required in the URL.");
    }

    $restaurantId = (int) $_GET["id"];

    $pdo = new PDO("sqlite:" . __DIR__ . "/restaurants.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT id, name, tags, content FROM restaurants WHERE id = :id";
    $statement = $pdo->prepare($sql);
    $statement->bindParam(":id", $restaurantId, PDO::PARAM_INT);
    $statement->execute();
    $restaurant = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$restaurant) {
        throw new Exception("Restaurant not found.");
    }

    $previousSql = "
        SELECT id FROM restaurants
        WHERE id < :id
        ORDER BY id DESC
        LIMIT 1
    ";
    $previousStatement = $pdo->prepare($previousSql);
    $previousStatement->bindParam(":id", $restaurantId, PDO::PARAM_INT);
    $previousStatement->execute();
    $previousRestaurant = $previousStatement->fetch(PDO::FETCH_ASSOC);

    $nextSql = "
        SELECT id FROM restaurants
        WHERE id > :id
        ORDER BY id ASC
        LIMIT 1
    ";
    $nextStatement = $pdo->prepare($nextSql);
    $nextStatement->bindParam(":id", $restaurantId, PDO::PARAM_INT);
    $nextStatement->execute();
    $nextRestaurant = $nextStatement->fetch(PDO::FETCH_ASSOC);

    $templatePath = loadTemplateFile(["restaurant.html", "Restaurant.html"]);
    $html = file_get_contents($templatePath);

    $document = new DOMDocument();
    libxml_use_internal_errors(true);
    $document->loadHTML($html);
    libxml_clear_errors();

    $titleElement = $document->getElementById("restaurant-title");
    $tagsElement = $document->getElementById("restaurant-tags");
    $contentElement = $document->getElementById("restaurant-content");
    $previousLink = $document->getElementById("prev-link");
    $nextLink = $document->getElementById("next-link");

    if ($titleElement) {
        $titleElement->nodeValue = $restaurant["name"];
    }

    if ($tagsElement) {
        while ($tagsElement->firstChild) {
            $tagsElement->removeChild($tagsElement->firstChild);
        }

        $tagArray = array_map("trim", explode(",", $restaurant["tags"]));

        foreach ($tagArray as $tag) {
            $tagItem = $document->createElement("li", $tag);
            $tagsElement->appendChild($tagItem);
        }
    }

    if ($contentElement) {
        while ($contentElement->firstChild) {
            $contentElement->removeChild($contentElement->firstChild);
        }

        // Append the saved HTML review content without removing paragraph tags.
        $fragment = $document->createDocumentFragment();
        $fragment->appendXML($restaurant["content"]);
        $contentElement->appendChild($fragment);
    }

    if ($previousLink) {
        if ($previousRestaurant) {
            $previousLink->setAttribute("href", "restaurant.php?id=" . $previousRestaurant["id"]);
        } else {
            $previousLink->setAttribute("href", "#");
            $previousLink->nodeValue = "No Previous Restaurant";
        }
    }

    if ($nextLink) {
        if ($nextRestaurant) {
            $nextLink->setAttribute("href", "restaurant.php?id=" . $nextRestaurant["id"]);
        } else {
            $nextLink->setAttribute("href", "#");
            $nextLink->nodeValue = "No Next Restaurant";
        }
    }

    echo $document->saveHTML();
} catch (PDOException $error) {
    echo "<h1>Database Error</h1>";
    echo "<p>" . htmlspecialchars($error->getMessage(), ENT_QUOTES, "UTF-8") . "</p>";
} catch (Exception $error) {
    echo "<h1>Page Error</h1>";
    echo "<p>" . htmlspecialchars($error->getMessage(), ENT_QUOTES, "UTF-8") . "</p>";
}
?>
