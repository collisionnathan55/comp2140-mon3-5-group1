<?php require_once "../database/query-presets.php" ?>

<?php

function getAllOrders() {
    return db_query_orders_all();
}

function getOrderById(int $id) {
    return count(db_query_order_id($id)) > 0 ? db_query_order_id($id)[0] : null;
}
function getOrdersByName(string $customerName) {
    $alpha = '/^[A-Za-z \']+$/';
    if (preg_match($alpha, $customerName)) {
        return db_query_orders_name($customerName);
    }
    return [];
}
function getOrdersByPhone(int $phoneNumber) {
    $alpha = '/^\d{3}-\d{3}-\d{4}$/';
    if (preg_match($alpha, $phoneNumber)) {
        return db_query_orders_phone($phoneNumber);
    }

    return [];
}

function getOrdersByProduct(int $product_id) {
    $orderItems = getOrderItemsByProduct($product_id);
    $orders = [];

    foreach ($orderItems as $item) {
        $order = getOrderById($item["order_id"]);
        if ($order != null) {
            array_push($orders, $order);
        }
    }

    return $orders;
}

// order items

function getOrderItemsByOrder(int $order_id) {
    return db_query_orderItems_order($order_id);
}
function getOrderItemsByProduct(int $product_id) {
    return db_query_orderItems_product($product_id);
}

// order functions

function isLiveOrder(int $order_id) {
    $order = getOrderById($order_id);
    if ($order == null || !isset($order["order_status"])) {
        return false;
    }

    if (in_array($order["order_status"], ["cancelled", "completed"])) {
        return false;
    }

    return true;
}

?>