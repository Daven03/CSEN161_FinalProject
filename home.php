<?php
// home.php
// Loads the home template and shows the newest restaurant review.

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

function getPreviewText(string $htmlContent, int $maxLength = 140): string
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

    $sql = "SELECT id, name, tags, content FROM restaurants ORDER BY id DESC LIMIT 1";
    $statement = $pdo->prepare($sql);
    $statement->execute();
    $latestRestaurant = $statement->fetch(PDO::FETCH_ASSOC);

    $templatePath = loadTemplateFile(["home.html", "Home.html"]);
    $html = file_get_contents($templatePath);

    $document = new DOMDocument();
    libxml_use_internal_errors(true);
    $document->loadHTML($html);
    libxml_clear_errors();

    if ($latestRestaurant) {
        $titleElement = $document->getElementById("home-latest-title");
        $tagsElement = $document->getElementById("home-latest-tags");
        $timeElement = $document->getElementById("home-latest-time");
        $previewElement = $document->getElementById("home-latest-preview");
        $linkElement = $document->getElementById("home-continue-link");

        $tagArray = array_map("trim", explode(",", $latestRestaurant["tags"]));
        $previewText = getPreviewText($latestRestaurant["content"]);

        if ($titleElement) {
            $titleElement->nodeValue = $latestRestaurant["name"];
        }

        if ($tagsElement) {
            while ($tagsElement->firstChild) {
                $tagsElement->removeChild($tagsElement->firstChild);
            }

            foreach ($tagArray as $tag) {
                $tagItem = $document->createElement("li", $tag);
                $tagItem->setAttribute("class", "tag");
                $tagsElement->appendChild($tagItem);
            }
        }

        if ($timeElement) {
            $timeElement->nodeValue = "Latest review in the database";
        }

        if ($previewElement) {
            $previewElement->nodeValue = $previewText;
        }

        if ($linkElement) {
            $linkElement->setAttribute("href", "restaurant.php?id=" . $latestRestaurant["id"]);
            $linkElement->nodeValue = "Read Full Review";
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
