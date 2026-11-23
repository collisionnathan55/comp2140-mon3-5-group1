<?php require "session-handler.php" ?>

<?php
/* 
    This file handles the requests for lower layer actions
    such as creating products or orders.

    It also handles the page request authorization for every page.
*/

function authorizePage(string $pageName) {
    $adminPages = [
        "page-home-admin.php",
        "page-products-admin.php",
        "page-orders-admin.php",

        "form-add-product.php"
    ];

    if (in_array($pageName, $adminPages)) {
        if (!isset($_SESSION["admin-auth"]) || $_SESSION["admin-auth"] != 1) {
            session_write_close();
            header("Location: ../interface/page-login.php");
            return false;
        }
    }

    return true;
}

?>