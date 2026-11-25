<?php
/* 
    This file standardizes the site navigation between pages
    to start from the main folder. Proper authentication of
    whether a user is allowed to be on a page is handled in
    user-authentication.php.
*/

function getNavigationQuery() {
    if (!isset($_GET["dest"]) || !preg_match('/^[A-Za-z_\-.]+$/', $_GET["dest"])) {
        return "";
    }
    return $_GET["dest"];
}

function getQuery() {
    if (!isset($_GET["nav-query"]) || !preg_match('/^[A-Za-z0-9 ]+$/', $_GET["nav-query"])) {
        return "";
    }
    return "?val=" . $_GET["nav-query"];
}

?>

<?php
const root = "comp2140-mon3-5-group1/";
$rootWindow = substr($_SERVER["PHP_SELF"], 0, strpos($_SERVER["PHP_SELF"], root) + strlen(root));

$location = getNavigationQuery();
if ($location == "last") {
    include_once "session-handler.php";
    $location = isset($_SESSION["lastAuthenticatedPage"]) ? $_SESSION["lastAuthenticatedPage"] : "";
}

$query = "";
if ($location != "") {
    $query = getQuery();
}

header("Location: " . $rootWindow . "interface/" . $location . $query);
?>