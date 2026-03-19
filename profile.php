<?php

declare(strict_types=1);

require __DIR__ . "/auth.php";

header("Content-Type: text/html; charset=UTF-8");

$username = $_SESSION["username"] ?? "Reviewer";
$favoriteRestaurants = [];
$userReviews = [];

// Connect to the database and fetch the user's profile information, including their favorite restaurants and reviews
try {
    $pdo = new PDO("sqlite:" . __DIR__ . "/restaurants.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $userSql = "SELECT id, username FROM users WHERE username = :username LIMIT 1";
    $userStatement = $pdo->prepare($userSql);
    $userStatement->bindParam(":username", $username, PDO::PARAM_STR);
    $userStatement->execute();
    $user = $userStatement->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User profile was not found.");
    }

    $favoriteSql = "
        SELECT
            restaurants.id AS restaurant_id,
            restaurants.name AS restaurant_name,
            restaurants.tags AS restaurant_tags,
            restaurants.image AS restaurant_image,
            MAX(reviews.rating) AS top_rating,
            MAX(datetime(reviews.created_at)) AS latest_review_at
        FROM reviews
        INNER JOIN restaurants ON restaurants.id = reviews.restaurant_id
        WHERE reviews.user_id = :user_id
        GROUP BY restaurants.id, restaurants.name, restaurants.tags, restaurants.image
        ORDER BY top_rating DESC, latest_review_at DESC
        LIMIT 3
    ";
    $favoriteStatement = $pdo->prepare($favoriteSql);
    $favoriteStatement->bindParam(":user_id", $user["id"], PDO::PARAM_INT);
    $favoriteStatement->execute();
    $favoriteRestaurants = $favoriteStatement->fetchAll(PDO::FETCH_ASSOC);

    $reviewSql = "
        SELECT
            reviews.id,
            reviews.rating,
            reviews.body,
            reviews.created_at,
            restaurants.id AS restaurant_id,
            restaurants.name AS restaurant_name,
            restaurants.tags AS restaurant_tags
        FROM reviews
        INNER JOIN restaurants ON restaurants.id = reviews.restaurant_id
        WHERE reviews.user_id = :user_id
        ORDER BY datetime(reviews.created_at) DESC, reviews.id DESC
    ";
    $reviewStatement = $pdo->prepare($reviewSql);
    $reviewStatement->bindParam(":user_id", $user["id"], PDO::PARAM_INT);
    $reviewStatement->execute();
    $userReviews = $reviewStatement->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $error) {
    echo "<h1>Profile Error</h1>";
    echo "<p>" . htmlspecialchars($error->getMessage(), ENT_QUOTES, "UTF-8") . "</p>";
    exit;
}

function formatRatingStars(int $rating): string
{
    return str_repeat("*", $rating) . str_repeat(".", 5 - $rating);
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="styles/base.css">
    <link rel="stylesheet" href="styles/profile.css">
  </head>
  <body>
    <nav>
      <ul id="nav">
        <li class="navLi"><a class="navA" href="home.php">Home</a></li>
        <li class="navLi"><a class="navA" href="restaurants.php">Find Restaurants</a></li>
        <li class="navLi"><a class="navA" href="Add.html">Add a Restaurant</a></li>
        <li class="navLi"><a class="navA active" href="profile.php">Profile</a></li>
        <li class="navLi"><a class="navA" href="logout.php">Logout</a></li>
      </ul>
    </nav>

    <main class="profile-page">
      <section class="profile-card">
        <p class="eyebrow">Reviewer Profile</p>
        <h1><?php echo htmlspecialchars($username, ENT_QUOTES, "UTF-8"); ?></h1>
        <p class="summary">
          Your review history lives here. Use the restaurant pages to add new reviews and build out your personal list over time.
        </p>

        <section class="favorites-section">
          <div class="profile-heading-row">
            <h2>Favorite Restaurants</h2>
            <p>Up to 3 highest-rated places</p>
          </div>

          <?php if (count($favoriteRestaurants) === 0): ?>
            <p class="empty-state">Once you rate restaurants, your top picks will appear here.</p>
          <?php else: ?>
            <div class="favorite-cards">
              <?php foreach ($favoriteRestaurants as $restaurant): ?>
                <article class="favorite-card">
                  <h3><?php echo htmlspecialchars($restaurant["restaurant_name"], ENT_QUOTES, "UTF-8"); ?></h3>
                  <img
                    class="favorite-image"
                    src="images/<?php echo htmlspecialchars($restaurant["restaurant_image"], ENT_QUOTES, "UTF-8"); ?>"
                    alt="<?php echo htmlspecialchars($restaurant["restaurant_name"], ENT_QUOTES, "UTF-8"); ?>"
                  >
                  <ul class="tags">
                    <?php foreach (array_map("trim", explode(",", $restaurant["restaurant_tags"])) as $tag): ?>
                      <li class="tag"><?php echo htmlspecialchars($tag, ENT_QUOTES, "UTF-8"); ?></li>
                    <?php endforeach; ?>
                  </ul>
                  <p class="favorite-rating"><?php echo htmlspecialchars(formatRatingStars((int) $restaurant["top_rating"]), ENT_QUOTES, "UTF-8"); ?></p>
                  <a href="restaurant.php?id=<?php echo (int) $restaurant["restaurant_id"]; ?>">View</a>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <section class="recent-reviews-section">
          <div class="profile-heading-row">
            <h2>Your Reviews</h2>
            <p><?php echo count($userReviews); ?> total review<?php echo count($userReviews) === 1 ? "" : "s"; ?></p>
          </div>

          <?php if (count($userReviews) === 0): ?>
            <p class="empty-state">You have not written any reviews yet. Visit a restaurant page to submit your first one.</p>
          <?php else: ?>
            <div class="profile-review-list">
              <?php foreach ($userReviews as $review): ?>
                <article class="profile-review-card">
                  <div class="profile-review-header">
                    <div>
                      <p class="profile-review-restaurant">
                        <a href="restaurant.php?id=<?php echo (int) $review["restaurant_id"]; ?>">
                          <?php echo htmlspecialchars($review["restaurant_name"], ENT_QUOTES, "UTF-8"); ?>
                        </a>
                      </p>
                    </div>
                    <p class="profile-review-rating"><?php echo htmlspecialchars(formatRatingStars((int) $review["rating"]), ENT_QUOTES, "UTF-8"); ?></p>
                  </div>

                  <p class="profile-review-date"><?php echo htmlspecialchars($review["created_at"], ENT_QUOTES, "UTF-8"); ?></p>
                  <p class="profile-review-body"><?php echo nl2br(htmlspecialchars($review["body"], ENT_QUOTES, "UTF-8")); ?></p>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      </section>
    </main>

    <footer>
      <p>&copy; 2026</p>
    </footer>
  </body>
</html>
