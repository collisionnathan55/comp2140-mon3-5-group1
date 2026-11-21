<?php
require "user-auth-check.php";

$env = parse_ini_file(".env");
$login = false;
if (isset($env["LOGIN_KEY"]) && isset($_POST["login-password"]) 
    && $_POST["login-password"] == $env["LOGIN_KEY"]) {
    $_POST["login-password"] = "";
    $login = true;
}

if ($login) {
    $_SESSION["admin-auth"] = 1;
    session_regenerate_id(true);
    
    session_write_close();
    header("Location: index.php?dest=page-home-admin.php");
    exit();
}

session_write_close();
header("Location: index.php?dest=page-login.php");
exit();
?>