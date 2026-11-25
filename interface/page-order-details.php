<?php
    require_once "../authentication/action-authorization.php";
    if (!authorizePage(basename(__FILE__))) {
        exit();
    }
    require_once "../products/get-orders.php";
    require_once "../products/get-products.php";

    $orderID = (isset($_GET["val"]) ? (int)$_GET["val"] : null);
    $order = null;
    if ($orderID != null) {
        $product = getProductbyId($orderID);
    }

    if ($product == null) {
        header("Location: ../authentication/index.php?dest=last");
    }
?>