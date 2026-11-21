<?php

function getNavigationQuery() {
    if (!isset($_GET["dest"]) || !preg_match('/^[A-Za-z_\-.]+$/', $_GET["dest"])) {
        return "";
    }
    return $_GET["dest"];
}

?>

<?php
const root = "comp2140-mon3-5-group1/";
$rootWindow = substr($_SERVER["PHP_SELF"], 0, strpos($_SERVER["PHP_SELF"], root) + strlen(root));
header("Location: " . $rootWindow . "interface/" . getNavigationQuery());
?>