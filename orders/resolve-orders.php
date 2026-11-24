<?php require_once "get-orders.php" ?>
<?php require_once "../products/add-product.php" ?>

<?php

function cancelOrder(int $order_id) {
    $order_items = getOrderItemsByOrder($order_id);

    foreach ($order_items as $item) {
        $product = getProductbyId($item["product_id"]);
        if ($product == null) {
            continue;
        }

        $totalStock = $product["sell_quantity"] * $item["quantity"];
        reduceProductStock($item["product_id"], 0, $totalStock);
    }
    db_update_order_status($order_id, "cancelled");
}
function completeOrder(int $order_id) {
    $order_items = getOrderItemsByOrder($order_id);

    foreach ($order_items as $item) {
        $product = getProductbyId($item["product_id"]);
        if ($product == null) {
            continue;
        }

        $totalStock = $product["sell_quantity"] * $item["quantity"];
        reduceProductStock($item["product_id"], $totalStock, $totalStock);
    }
    db_update_order_status($order_id, "completed");
}

function deleteOrder(int $order_id) {
    $order_items = getOrderItemsByOrder($order_id);
    foreach ($order_items as $item) {
        db_delete_orderItem($item["order_item_id"]);
    }
    db_delete_order($order_id);
}

?>