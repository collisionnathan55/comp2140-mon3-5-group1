<?php
/* 
    This file forces php sessions to be started on every page
    and handles protecting logged in users from XSS and CSRF attacks,
    as well as disallows users from accessing admin protected pages.
*/

function checkInvalidSession() 
{
    if (isset($_SESSION["current-user-agent"])) {
        if ($_SESSION["current-user-agent"] != $_SERVER['HTTP_USER_AGENT']) {
            return false;
        }
    }

    if (isset($_SESSION["current-user-ip"])) {
        if ($_SESSION["current-user-ip"] != $_SERVER['REMOTE_ADDR']) {
            return false;
        }
    }

    return true;
}

function forceSessionData()
{
    session_name("yaad-sesh");
    $sessionFlags = [
        "cookie_samesite" => "Strict",
        "cookie_lifetime" => 1800,   
        // 30 minutes of inactivity?
        "cookie_secure" => 1,
        "cookie_httponly" => 1
    ];

    if (!session_start($sessionFlags)) {
        http_response_code(500);
        exit();
    }

    if (!checkInvalidSession()) {
        session_unset();
        session_regenerate_id(true);
    }

    $_SESSION["current-user-agent"] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION["current-user-ip"] = $_SERVER['REMOTE_ADDR'];

    if (!isset($_SESSION["admin-auth"])) {
        $_SESSION["admin-auth"] = 0;
    }
}

function logSession()
{
    echo session_id();
    foreach ($_SESSION as $key => $value) {
        echo "<br>" . $key . " -> " . $value;
    };
}

forceSessionData();
?>