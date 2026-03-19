<?php
// Loads one restaurant's info and all user reviews for that restaurant.

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
function createReviewCard(DOMDocument $document, array $review): DOMElement
{
    $card = $document->createElement("article");
    $card->setAttribute("class", "review-card");

    $header = $document->createElement("div");
    $header->setAttribute("class", "review-card-header");

    $titleGroup = $document->createElement("div");
    $author = $document->createElement("p", "By " . $review["username"]);
    $author->setAttribute("class", "review-author");
    $titleGroup->appendChild($author);
    $header->appendChild($titleGroup);

    $rating = $document->createElement("p", str_repeat("*", (int) $review["rating"]) . str_repeat(".", 5 - (int) $review["rating"]));
    $rating->setAttribute("class", "review-rating");
    $header->appendChild($rating);

    $card->appendChild($header);

    $date = $document->createElement("p", $review["created_at"]);
    $date->setAttribute("class", "review-date");
    $card->appendChild($date);

    $body = $document->createElement("p", $review["body"]);
    $body->setAttribute("class", "review-body");
    $card->appendChild($body);

    return $card;
}

// Main logic to load the restaurant and reviews, and render the page
try {
    if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
        throw new Exception("A valid restaurant id is required in the URL.");
    }

    $restaurantId = (int) $_GET["id"];
    $currentUsername = $_SESSION["username"] ?? "Reviewer";
    $reviewSuccess = trim($_GET["review_success"] ?? "");
    $reviewError = trim($_GET["review_error"] ?? "");

    $pdo = new PDO("sqlite:" . __DIR__ . "/restaurants.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $restaurantSql = "SELECT id, name, tags, image FROM restaurants WHERE id = :id";
    $restaurantStatement = $pdo->prepare($restaurantSql);
    $restaurantStatement->bindParam(":id", $restaurantId, PDO::PARAM_INT);
    $restaurantStatement->execute();
    $restaurant = $restaurantStatement->fetch(PDO::FETCH_ASSOC);

    if (!$restaurant) {
        throw new Exception("Restaurant not found.");
    }

    $reviewSql = "
        SELECT
            reviews.id,
            reviews.rating,
            reviews.body,
            reviews.created_at,
            users.username
        FROM reviews
        INNER JOIN users ON users.id = reviews.user_id
        WHERE reviews.restaurant_id = :restaurant_id
        ORDER BY datetime(reviews.created_at) DESC, reviews.id DESC
    ";
    $reviewStatement = $pdo->prepare($reviewSql);
    $reviewStatement->bindParam(":restaurant_id", $restaurantId, PDO::PARAM_INT);
    $reviewStatement->execute();
    $reviews = $reviewStatement->fetchAll(PDO::FETCH_ASSOC);

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
    $imageElement = $document->getElementById("restaurant-image");
    $reviewCountElement = $document->getElementById("restaurant-review-count");
    $reviewsListElement = $document->getElementById("restaurant-reviews-list");
    $reviewUserNoteElement = $document->getElementById("review-form-user-note");
    $reviewMessageElement = $document->getElementById("review-form-message");
    $reviewRestaurantIdElement = $document->getElementById("review-restaurant-id");
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
            $tagItem->setAttribute("class", "tag");
            $tagsElement->appendChild($tagItem);
        }
    }

    if ($imageElement) {
        $imageElement->setAttribute("src", "images/" . $restaurant["image"]);
        $imageElement->setAttribute("alt", $restaurant["name"]);
    }

    if ($reviewCountElement) {
        $reviewCountElement->nodeValue = count($reviews) . " review" . (count($reviews) === 1 ? "" : "s");
    }

    if ($reviewsListElement) {
        while ($reviewsListElement->firstChild) {
            $reviewsListElement->removeChild($reviewsListElement->firstChild);
        }

        if (count($reviews) === 0) {
            $emptyState = $document->createElement("p", "No user reviews yet. Be the first to add one.");
            $emptyState->setAttribute("class", "empty-review-state");
            $reviewsListElement->appendChild($emptyState);
        } else {
            foreach ($reviews as $review) {
                $reviewsListElement->appendChild(createReviewCard($document, $review));
            }
        }
    }

    if ($reviewUserNoteElement) {
        $reviewUserNoteElement->nodeValue = "Posting as " . $currentUsername;
    }

    if ($reviewMessageElement) {
        if ($reviewSuccess !== "") {
            $reviewMessageElement->nodeValue = $reviewSuccess;
            $reviewMessageElement->setAttribute("class", "form-message success-message");
        } elseif ($reviewError !== "") {
            $reviewMessageElement->nodeValue = $reviewError;
            $reviewMessageElement->setAttribute("class", "form-message error-message");
        } else {
            $reviewMessageElement->nodeValue = "";
            $reviewMessageElement->setAttribute("class", "form-message");
        }
    }

    if ($reviewRestaurantIdElement) {
        $reviewRestaurantIdElement->setAttribute("value", (string) $restaurantId);
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
