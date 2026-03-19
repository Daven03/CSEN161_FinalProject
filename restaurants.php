<?php
// Loads the restaurants template and adds one card for each restaurant.

declare(strict_types=1);

require __DIR__ . "/auth.php";

header("Content-Type: text/html; charset=UTF-8");

// Helper function to find the template file
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

// Helper function to create a review card element from a review array
try {
    $pdo = new PDO("sqlite:" . __DIR__ . "/restaurants.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT id, name, tags, image FROM restaurants ORDER BY id ASC";
    $statement = $pdo->prepare($sql);
    $statement->execute();
    $restaurants = $statement->fetchAll(PDO::FETCH_ASSOC);

    $templatePath = loadTemplateFile(["restaurants.html", "Restaurants.html"]);
    $html = file_get_contents($templatePath);

    $document = new DOMDocument();
    libxml_use_internal_errors(true);
    $document->loadHTML($html);
    libxml_clear_errors();

    $mainElement = $document->getElementById("restaurants-main");

    if (!$mainElement) {
        throw new Exception("The restaurants-main container was not found in the template.");
    }

    foreach ($restaurants as $restaurant) {
        $card = $document->createElement("article");
        $card->setAttribute("class", "restaurant-card");

        $title = $document->createElement("h2", $restaurant["name"]);
        $card->appendChild($title);

        $image = $document->createElement("img");
        $image->setAttribute("class", "restaurant-card-image");
        $image->setAttribute("src", "images/" . $restaurant["image"]);
        $image->setAttribute("alt", $restaurant["name"]);
        $card->appendChild($image);

        $tagArray = array_map("trim", explode(",", $restaurant["tags"]));

        $tagList = $document->createElement("ul");
        $tagList->setAttribute("class", "tags");

        foreach ($tagArray as $tag) {
            $tagItem = $document->createElement("li", $tag);
            $tagItem->setAttribute("class", "tag");
            $tagList->appendChild($tagItem);
        }

        $card->appendChild($tagList);

        $link = $document->createElement("a", "View");
        $link->setAttribute("href", "restaurant.php?id=" . $restaurant["id"]);
        $card->appendChild($link);

        $mainElement->appendChild($card);
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
