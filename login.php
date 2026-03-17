<?php

declare(strict_types=1);

session_start();

if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
    header("Location: home.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    try {
        $pdo = new PDO("sqlite:" . __DIR__ . "/restaurants.db");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT id, username, password FROM users WHERE username = :username";
        $statement = $pdo->prepare($sql);
        $statement->bindParam(":username", $username, PDO::PARAM_STR);
        $statement->execute();
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {
            $_SESSION["logged_in"] = true;
            $_SESSION["username"] = $user["username"];
            header("Location: home.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles/base.css">
    <link rel="stylesheet" href="styles/login.css">
  </head>
  <body>
    <nav>
      <ul id="nav">
        <li class="navLi"><a class="navA active" href="login.php">Login</a></li>
        <li class="navLi"><a class="navA" href="signup.php">Sign Up</a></li>
      </ul>
    </nav>

    <main>
      <h1>Login</h1>
      <form method="POST" action="login.php" class="login-form">
        <?php if ($error): ?>
          <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Log In</button>
      </form>
      <p class="auth-link">Don't have an account? <a href="signup.php">Sign up</a></p>
    </main>

    <footer>
      <p>&copy; 2026</p>
    </footer>
  </body>
</html>
