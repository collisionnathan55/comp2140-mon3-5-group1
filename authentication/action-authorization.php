<?php require "session-handler.php" ?>

<?php 
    // Logic Layer interfaces
    include "../products/add-product.php" 
?>

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
        if (!isAdmin()) {
            session_write_close();
            header("Location: ../interface/page-login.php");
            return false;
        }
    }

    return true;
}
?>

<?php

if (!isset($_POST["attempt"])) {
    return;
}

$attempt = $_POST["attempt"];
$response = [
    "status" => "ok",
    "alert" => null,
    "html" => ""
];

$response["status"] = "ok";

$admin_actions = ["add-product", "edit-product"];
if (in_array($attempt, $admin_actions) && !isAdmin()) {
    $response["alert"] = "You don't have the permissions to attempt this.";
    echo json_encode($response);
    return;
} 

switch ($attempt) {
    case "add-product":
        $messages = validateProduct($_POST);

        if (!isset($_POST["submission-confirmation"])) {
            storeFormRequest($_POST);
            $response["status"] = "confirmation";
            $response["html"] = productConfirmationRequest($_POST);
            break;
        }

        break;
}

echo json_encode($response);
?>