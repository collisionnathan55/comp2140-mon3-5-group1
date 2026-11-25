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

function orderCancelConfirmationRequest(int $id) {
    $html = <<<EOT
    <div>
        <h1>Confirmation Request</h1>
        <p>Please confirm the cancellation of order #%d for %s.</p>
        <p>This action will release the reserved stock of related products.</p>
    </div>
    <form method="dialog">
        <button onclick="confirmRemovalSubmission()">Confirm</button>
        <button>Cancel</button>
    </form>
    EOT;

    $order = getOrderById($id);
    $place = array_search($order, getOrdersByPhone($order["customer_phone"]));
    if (!$place) {
        $place = 1;
    }

    return sprintf($html, $place, $order["customer_name"]);
}
function orderCompleteConfirmationRequest(int $id) {
    $html = <<<EOT
    <div>
        <h1>Confirmation Request</h1>
        <p>Please confirm the completion of order #%d for %s.</p>
        <p>This action will permanently reduce the stock of related products.</p>
    </div>
    <form method="dialog">
        <button onclick="confirmRemovalSubmission()">Confirm</button>
        <button>Cancel</button>
    </form>
    EOT;

    $order = getOrderById($id);
    $place = array_search($order, getOrdersByPhone($order["customer_phone"]));
    if (!$place) {
        $place = 1;
    }

    return sprintf($html, $place, $order["customer_name"]);
}

?>