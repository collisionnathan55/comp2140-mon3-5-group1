<?php
/* 
    This file handles the login page requests and grants or
    disallows users from accessing admin authentication.

    It also allows admin users to log out with the correct query.
*/

require "user-auth-check.php";

function requestLogin() {
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
}
?>

<?php 

if (isset($_POST["logout"])) {
    $_POST["logout"] = null;
    $_SESSION["admin-auth"] = 0;
    session_regenerate_id(true);
}
else {
    requestLogin();
}

session_write_close();
header("Location: index.php?dest=page-login.php");
exit();
?>