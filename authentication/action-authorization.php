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
        "page-actions-admin.php",

        "form-add-product.php",
        "form-edit-order.php"
    ];

    if (in_array($pageName, $adminPages)) {
        if (!isAdmin()) {
            session_write_close();
            header("Location: ../interface/page-login.php");
            return false;
        }
    }

    $validLastPages = [
        "home.php",

        "page-home-admin.php",
        "page-products-admin.php",
        "page-orders-admin.php",
        "page-actions-admin.php",
    ];

    if (in_array($pageName, $validLastPages)) {
        $page_link = $pageName;
        $numq = 0;
        foreach($_GET as $key => $value) {
            $numq++;
            $page_link .= ($numq == 1 ? "?" : "&") . $key . "=" . $value;
        }
        $_SESSION["lastAuthenticatedPage"] = $page_link;
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
    "get-products-all",
    "archive-products", "delete-products", "confirm-product-removal",
    "get-orders-all",
    "get-actions"
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

        if ($product["product-id"] != -1) {
            updateProduct($product);

            $response["status"] = "form-leave";
            $response["alert"] = "Successfully saved product.";
        }
        else {
            createProduct($product);

            $response["status"] = "form-reset";
            $response["alert"] = "Successfully created product.";
        }

        discardFormRequest();
        break;
    //
    
    case "get-products-all":
        include "../products/get-products.php";
        $products = getAllProducts();

        for ($i = 0; $i < count($products); $i++) {
            $availability = "";

            if (isAvailable($products[$i])) {
                $availability = "Yes";
            }
            else {
                if ($products[$i]["is_archived"]) {
                    $availability .= "Archived<br>";
                }
                if (!isInStock($products[$i])) {
                    $availability .= "Out of Stock<br>";
                }
                if (daysToExpiration($products[$i]) <= 0) {
                    $availability .= "Expired<br>";
                }
            }

            $products[$i]["is_available"] = $availability;
        }
        
        $response["dataList"] = $products;
        break;
    case "get-products-categories":
        include "../products/get-products.php";
        $response["dataList"] = getProductCategories();
        break;
    case "get-products":
        include "../products/get-products.php";
        if (isset($_GET["id"])) {
            $product = getProductbyId((int) $_GET["id"]);
            if ($product == null) {
                http_response_code(500);
                exit;
            }
            $response["dataList"] = $product;
            break;
        }
        if (isset($_GET["name"])) {
            $response["dataList"] = getProductsbyName($_GET["name"]);
            break;
        }

        http_response_code(500);
        exit;
    //
    
    case "archive-products":
        include "../products/add-product.php";

        if (!isset($_GET["ids"]) || !isset($_GET["set"])) {
            $response["status"] = "error";
            break;
        }

        if (!is_array($_GET["ids"])) {
            $_GET["ids"] = [$_GET["ids"]];
        }

        storeFormRequest($_GET);
        $response["status"] = "confirmation";
        $response["html"] = productRemovalConfirmationRequest($_GET["ids"], $_GET["set"] == "true" ? "archiving" : "unarchiving");
        break;
    case "delete-products":
        include "../products/add-product.php";
        include "../orders/get-orders.php";

        if (!isset($_GET["ids"])) {
            $response["status"] = "error";
            break;
        }

        if (!is_array($_GET["ids"])) {
            $_GET["ids"] = [$_GET["ids"]];
        }

        $orders_affected = 0;
        foreach ($_GET["ids"] as $id) {
            $orders_affected += count(getOrdersByProduct($id));
        }

        storeFormRequest($_GET);
        $response["status"] = "confirmation";
        $response["html"] = productRemovalConfirmationRequest(
            $_GET["ids"], "permanent removal",
            "<p>This will permanently delete (and cancel) " . $orders_affected . " order(s) using this product.</p>"
        );
        break;
    case "confirm-product-removal":
        include "../products/add-product.php";
        $query = getFormRequest();

        switch ($query["attempt"]) {
            case "archive-products":
                foreach ($query["ids"] as $id) {
                    toggleArchiveProduct($id, $query["set"] == "true");
                }
                $response["alert"] = "Products successfully " . ($query["set"] == "true" ? "archived" : "unarchived") . ".";
            break;
            case "delete-products":
                foreach ($query["ids"] as $id) {
                    deleteProduct((int) $id);
                }
                $response["alert"] = "Products successfully removed.";
            break;
        }

        discardFormRequest();
        break;
    //

    case "get-orders-all":
        include_once "../orders/get-orders.php";
        include_once "../products/get-products.php";
        $orders = getAllOrders();

        for ($i = 0; $i < count($orders); $i++) {
            $orders[$i]["related_products"] = "";
            $order_items = getOrderItemsByOrder($orders[$i]["order_id"]);
            
            foreach ($order_items as $item) {
                $product = getProductbyId($item["product_id"]);
                if ($product != null) {
                    $orders[$i]["related_products"] .= $product["display_name"] . ", ";
                }
            }

            if (strlen($orders[$i]["related_products"]) > 1) {
                $orders[$i]["related_products"] = substr($orders[$i]["related_products"], 0, strlen($orders[$i]["related_products"]) - 2);
            }
        }
        
        $response["dataList"] = $orders;
        break;
    case "get-orders-phone":
        include_once "../orders/get-orders.php";

        if (!isset($_POST["order-phone-number"]) || !preg_match('/^\d{3}-\d{3}-\d{4}$/', $_POST["order-phone-number"])) {
            $response["status"] = "error";
            break;
        }

        $orders = getOrdersByPhone($_POST["order-phone-number"]);
        $html = "<p>No orders with this phone number available.</p>";

        if (count($orders) > 0) {
            $html = "";

            foreach($orders as $order) {
                $customer = $order["customer_name"];
                $date = date_create($order["order_date"], timezone_open("EST"));

                $html .= "<div><p>" . $customer . "</p><p>" . $date->format("Y/m/d") . "</p><p>" . $order["order_status"] . "</p><button onclick='openOrderDetail(" . (int)$order["order_id"] . ", \"" . $_POST["order-phone-number"] . "\")'>Enter</button></div>";
            }
        }

        $response["status"] = "select";
        $response["html"] = $html;
        break;
    //

    case "cancel-order":
        include "../orders/resolve-orders.php";

        if (!isset($_GET["id"])) {
            $response["status"] = "error";
            break;
        }

        if (!isLiveOrder($_GET["id"])) {
            $response["status"] = "error";
            $response["alert"] = "Please choose a live order.";
            break;
        }

        storeFormRequest($_GET);
        $response["status"] = "confirmation";
        $response["html"] = orderCancelConfirmationRequest($_GET["id"]);
        break;
    case "complete-order":
        include "../orders/resolve-orders.php";

        if (!isset($_GET["id"])) {
            $response["status"] = "error";
            break;
        }

        if (!isLiveOrder($_GET["id"])) {
            $response["status"] = "error";
            $response["alert"] = "Please choose a live order.";
            break;
        }

        storeFormRequest($_GET);
        $response["status"] = "confirmation";
        $response["html"] = orderCompleteConfirmationRequest($_GET["id"]);
        break;
    case "confirm-order-removal":
        include "../orders/resolve-orders.php";
        $query = getFormRequest();

        switch ($query["attempt"]) {
            case "cancel-order":
                cancelOrder($query["id"]);
                $response["alert"] = "Successfully cancelled order.";
            break;
            case "complete-order":
                completeOrder($query["id"]);
                $response["alert"] = "Successfully completed order.";
            break;
        }

        discardFormRequest();
        break;
    //
    
    case "get-actions":
        include_once "../system-actions/add-action.php";
        deleteAgedActions();
        $actions = getAllActions();

        $response["dataList"] = $actions;
        break;
    //
}

echo json_encode($response);
?>