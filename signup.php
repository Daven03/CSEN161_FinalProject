<?php

declare(strict_types=1);

session_start();

if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
    header("Location: home.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Username and password are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        try {
            $pdo = new PDO("sqlite:" . __DIR__ . "/restaurants.db");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $checkSql = "SELECT id FROM users WHERE username = :username";
            $checkStatement = $pdo->prepare($checkSql);
            $checkStatement->bindParam(":username", $username, PDO::PARAM_STR);
            $checkStatement->execute();

            if ($checkStatement->fetch()) {
                $error = "That username is already taken.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $insertSql = "INSERT INTO users (username, password) VALUES (:username, :password)";
                $insertStatement = $pdo->prepare($insertSql);
                $insertStatement->bindParam(":username", $username, PDO::PARAM_STR);
                $insertStatement->bindParam(":password", $hashedPassword, PDO::PARAM_STR);
                $insertStatement->execute();

                $success = "Account created! You can now log in.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="styles/base.css">
    <link rel="stylesheet" href="styles/login.css">
  </head>
  <body>
    <nav>
      <ul id="nav">
        <li class="navLi"><a class="navA" href="login.php">Login</a></li>
        <li class="navLi"><a class="navA active" href="signup.php">Sign Up</a></li>
      </ul>
    </nav>

    <main>
      <h1>Sign Up</h1>
      <form method="POST" action="signup.php" class="login-form">
        <?php if ($error): ?>
          <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
          <p class="success"><?php echo htmlspecialchars($success, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <button type="submit">Create Account</button>
      </form>
      <p class="auth-link">Already have an account? <a href="login.php">Log in</a></p>
    </main>

    <footer>
      <p>&copy; 2026</p>
    </footer>
  </body>
</html>
