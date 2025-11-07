<?php
include "session_config.php";

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: ../login.html");
    exit;
}

if (isset($_SESSION["last_activity"]) && (time() - $_SESSION["last_activity"] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: ../login.html");
    exit;
}

$_SESSION["last_activity"] = time();
?>
