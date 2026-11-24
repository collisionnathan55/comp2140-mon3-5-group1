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

if (!isset($_GET["attempt"])) {
    return;
}

$attempt = $_GET["attempt"];
$response = [
    "status" => "ok",
    "alert" => null,
    "html" => "",
    "dataList" => []
];

$response["status"] = "ok";

$admin_actions = [
    "add-product", "confirm-product", "edit-product",
    "get-products-all"
];
if (in_array($attempt, $admin_actions) && !isAdmin()) {
    $response["alert"] = "You don't have the permissions to attempt this.";
    echo json_encode($response);
    return;
} 

switch ($attempt) {
    case "add-product":
    case "edit-product":
        include "../products/add-product.php";

        $product = packProductQuery($_POST);
        $messages = validateProduct($product, $attempt == "edit-product");

        if (count($messages) > 0) {
            $response["status"] = "invalid-input";
            foreach ($messages as $m) {
                $response["alert"] .= "\n" . $m;
            }
            break;
        }

        storeFormRequest($_POST);
        $response["status"] = "confirmation";
        $response["html"] = productConfirmationRequest($product);
        break;

    case "confirm-product":
        include "../products/add-product.php";
        
        $query = getFormRequest();
        $product = packProductQuery($query);
        $messages = validateProduct($product, true);

        if (count($messages) > 0) {
            $response["status"] = "error";
            $response["alert"] = "There was an issue with saving the product";
            break;
        }

        $response["status"] = "form-ok";
        $response["alert"] = "Successfully saved product.";

        createProduct($product);
        break;

    case "get-products-all":
        include "../products/get-products.php";
        $products = getAllProducts();

        for ($i = 0; $i < count($products); $i++) {
            $products[$i]["is_available"] = "No";
            if (isAvailable($products[$i])) {
                $products[$i]["is_available"] = "Yes";
            }
        }
        
        $response["dataList"] = $products;
        break;
}

echo json_encode($response);
?>