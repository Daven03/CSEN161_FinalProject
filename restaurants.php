<?php
// restaurants.php
// Loads the restaurants template and adds one card for each restaurant.

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

function getPreviewText(string $htmlContent, int $maxLength = 160): string
{
    $plainText = trim(strip_tags($htmlContent));

    if (strlen($plainText) <= $maxLength) {
        return $plainText;
    }

    return substr($plainText, 0, $maxLength) . "...";
}

try {
    $pdo = new PDO("sqlite:" . __DIR__ . "/restaurants.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT id, name, tags, content FROM restaurants ORDER BY id ASC";
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

        $tagArray = array_map("trim", explode(",", $restaurant["tags"]));

        $tagList = $document->createElement("ul");
        $tagList->setAttribute("class", "tags");

        foreach ($tagArray as $tag) {
            $tagItem = $document->createElement("li", $tag);
            $tagItem->setAttribute("class", "tag");
            $tagList->appendChild($tagItem);
        }

        $card->appendChild($tagList);

        $preview = $document->createElement("p", getPreviewText($restaurant["content"]));
        $card->appendChild($preview);

        $link = $document->createElement("a", "Read Full Review");
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
