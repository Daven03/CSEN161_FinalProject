<?php

declare(strict_types=1);

session_start();

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true ||
    !isset($_SESSION["username"]) ||
    trim((string) $_SESSION["username"]) === ""
) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
