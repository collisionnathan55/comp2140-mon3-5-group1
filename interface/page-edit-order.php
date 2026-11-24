<?php
require "../authentication/action-authorization.php";

if (!authorizePage(basename(__FILE__))) {
    exit();
}

require_once "../database/database-config.php";

if (empty($_SESSION["edit_order_csrf_token"])) {
    $_SESSION["edit_order_csrf_token"] = bin2hex(random_bytes(32));
}

function editOrderEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$message = "";
$messageType = "";
$order = null;
$orderItems = [];
$products = [];
$orderID = 0;

if (isset($_POST["order_id"]) && ctype_digit((string)$_POST["order_id"])) {
    $orderID = (int)$_POST["order_id"];
} elseif (isset($_GET["order_id"]) && ctype_digit((string)$_GET["order_id"])) {
    $orderID = (int)$_GET["order_id"];
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_order_changes"])) {
    try {
        if (
            !isset($_POST["csrf_token"]) ||
            !hash_equals($_SESSION["edit_order_csrf_token"], $_POST["csrf_token"])
        ) {
            throw new RuntimeException("Invalid form submission. Please refresh the page.");
        }

        if ($orderID < 1) {
            throw new RuntimeException("Please provide a valid order ID.");
        }

        $customerName = trim($_POST["customer_name"] ?? "");
        $customerPhone = trim($_POST["customer_phone"] ?? "");
        $quantities = $_POST["quantities"] ?? [];

        if ($customerName === "" || strlen($customerName) > 150) {
            throw new RuntimeException("Please enter a valid customer name.");
        }
        if ($customerPhone === "" || strlen($customerPhone) > 20) {
            throw new RuntimeException("Please enter a valid customer phone number.");
        }
        if (!is_array($quantities)) {
            throw new RuntimeException("Invalid product quantities were submitted.");
        }

        $newQuantities = [];
        foreach ($quantities as $productID => $quantity) {
            if (!ctype_digit((string)$productID)) {
                throw new RuntimeException("An invalid product was selected.");
            }
            if ($quantity === "" || $quantity === null || (string)$quantity === "0") {
                continue;
            }
            if (!filter_var($quantity, FILTER_VALIDATE_INT) || (int)$quantity < 1) {
                throw new RuntimeException("Order quantities must be whole numbers greater than zero, or zero to remove a product.");
            }
            $newQuantities[(int)$productID] = (int)$quantity;
        }

        if (count($newQuantities) === 0) {
            throw new RuntimeException("An order must contain at least one product.");
        }

        $db = pdo();
        $db->beginTransaction();

        $orderLock = $db->prepare("SELECT order_id, order_status FROM orders WHERE order_id = :order_id FOR UPDATE");
        $orderLock->execute([":order_id" => $orderID]);
        $lockedOrder = $orderLock->fetch();

        if (!$lockedOrder) {
            throw new RuntimeException("The requested order could not be found.");
        }
        if (strtolower($lockedOrder["order_status"]) !== "pending") {
            throw new RuntimeException("Only pending orders can be edited. This order's status is " . $lockedOrder["order_status"] . ".");
        }

        $oldItemsQuery = $db->prepare(
            "SELECT oi.product_id, oi.quantity, oi.stored_unit_price,
                    p.display_name, p.sell_quantity
             FROM order_items oi
             INNER JOIN products p ON p.product_id = oi.product_id
             WHERE oi.order_id = :order_id
             FOR UPDATE"
        );
        $oldItemsQuery->execute([":order_id" => $orderID]);
        $oldRows = $oldItemsQuery->fetchAll();

        $oldItems = [];
        foreach ($oldRows as $row) {
            $oldItems[(int)$row["product_id"]] = $row;
        }

        $allProductIDs = array_unique(array_merge(array_keys($oldItems), array_keys($newQuantities)));
        $productLock = $db->prepare(
            "SELECT product_id, display_name, original_price, sell_quantity,
                    stock_quantity, reserved_quantity, is_archived, expiration_date
             FROM products WHERE product_id = :product_id FOR UPDATE"
        );

        $newItems = [];
        $totalCost = 0.00;

        foreach ($allProductIDs as $productID) {
            $productLock->execute([":product_id" => $productID]);
            $product = $productLock->fetch();
            if (!$product) {
                throw new RuntimeException("A product in this order no longer exists.");
            }

            $oldQuantity = isset($oldItems[$productID]) ? (int)$oldItems[$productID]["quantity"] : 0;
            $newQuantity = $newQuantities[$productID] ?? 0;
            $sellQuantity = (float)$product["sell_quantity"];

            if ($sellQuantity <= 0) {
                throw new RuntimeException("Invalid sale quantity for " . $product["display_name"] . ".");
            }

            if ($newQuantity > 0) {
                $isUnavailable = (int)$product["is_archived"] === 1 || $product["expiration_date"] < date("Y-m-d");
                if ($isUnavailable && $newQuantity > $oldQuantity) {
                    throw new RuntimeException($product["display_name"] . " is archived or expired, so its quantity cannot be increased.");
                }

                // The current order's existing reservation is added back before checking the revised quantity.
                $oldReservedForThisOrder = $oldQuantity * $sellQuantity;
                $availableAfterReleasingOld = (float)$product["stock_quantity"]
                    - (float)$product["reserved_quantity"]
                    + $oldReservedForThisOrder;
                $newPhysicalQuantity = $newQuantity * $sellQuantity;

                if ($newPhysicalQuantity > $availableAfterReleasingOld + 0.000001) {
                    throw new RuntimeException("Not enough stock is available for " . $product["display_name"] . ".");
                }

                // Keep the original unit price for existing order lines; use the current price for newly added products.
                $unitPrice = isset($oldItems[$productID])
                    ? (float)$oldItems[$productID]["stored_unit_price"]
                    : (float)$product["original_price"];

                $subtotal = $newQuantity * $unitPrice;
                $totalCost += $subtotal;

                $newItems[] = [
                    "product_id" => $productID,
                    "quantity" => $newQuantity,
                    "unit_price" => $unitPrice,
                    "new_physical_quantity" => $newPhysicalQuantity,
                    "old_physical_quantity" => $oldReservedForThisOrder
                ];
            } else {
                // Removing a product releases its previous reservation.
                if ($oldQuantity > 0) {
                    $newItems[] = [
                        "product_id" => $productID,
                        "quantity" => 0,
                        "unit_price" => 0,
                        "new_physical_quantity" => 0,
                        "old_physical_quantity" => $oldQuantity * $sellQuantity
                    ];
                }
            }
        }

        $reservationUpdate = $db->prepare(
            "UPDATE products
             SET reserved_quantity = reserved_quantity - :old_quantity + :new_quantity
             WHERE product_id = :product_id"
        );
        foreach ($newItems as $item) {
            $reservationUpdate->execute([
                ":old_quantity" => $item["old_physical_quantity"],
                ":new_quantity" => $item["new_physical_quantity"],
                ":product_id" => $item["product_id"]
            ]);
        }

        $deleteItems = $db->prepare("DELETE FROM order_items WHERE order_id = :order_id");
        $deleteItems->execute([":order_id" => $orderID]);

        $insertItem = $db->prepare(
            "INSERT INTO order_items (order_id, product_id, quantity, stored_unit_price)
             VALUES (:order_id, :product_id, :quantity, :unit_price)"
        );
        foreach ($newItems as $item) {
            if ($item["quantity"] < 1) {
                continue;
            }
            $insertItem->execute([
                ":order_id" => $orderID,
                ":product_id" => $item["product_id"],
                ":quantity" => $item["quantity"],
                ":unit_price" => $item["unit_price"]
            ]);
        }

        $updateOrder = $db->prepare(
            "UPDATE orders
             SET customer_name = :customer_name,
                 customer_phone = :customer_phone,
                 total_cost = :total_cost
             WHERE order_id = :order_id"
        );
        $updateOrder->execute([
            ":customer_name" => $customerName,
            ":customer_phone" => $customerPhone,
            ":total_cost" => round($totalCost, 2),
            ":order_id" => $orderID
        ]);

        $log = $db->prepare(
            "INSERT INTO action_logs (action, description)
             VALUES (:action, :description)"
        );
        $log->execute([
            ":action" => "edit_order",
            ":description" => "Order #" . $orderID . " was updated for customer " . $customerName . "."
        ]);

        $db->commit();
        header("Location: page-edit-order.php?order_id=" . $orderID . "&updated=1");
        exit();

    } catch (Throwable $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log($e->getMessage());
        $message = $e instanceof RuntimeException
            ? $e->getMessage()
            : "The order could not be updated. Please check the database and try again.";
        $messageType = "error";
    }
}

if (isset($_GET["updated"]) && $_GET["updated"] === "1") {
    $message = "Order #" . $orderID . " was successfully updated.";
    $messageType = "success";
}

if ($orderID > 0) {
    try {
        $db = pdo();
        $orderQuery = $db->prepare("SELECT * FROM orders WHERE order_id = :order_id");
        $orderQuery->execute([":order_id" => $orderID]);
        $order = $orderQuery->fetch();

        if (!$order) {
            $message = "Order #" . $orderID . " could not be found.";
            $messageType = "error";
        } else {
            $itemsQuery = $db->prepare(
                "SELECT oi.product_id, oi.quantity, oi.stored_unit_price,
                        p.display_name, p.display_category, p.sell_quantity,
                        p.unit_of_measure, p.stock_quantity, p.reserved_quantity,
                        p.is_archived, p.expiration_date
                 FROM order_items oi
                 INNER JOIN products p ON p.product_id = oi.product_id
                 WHERE oi.order_id = :order_id
                 ORDER BY p.display_name"
            );
            $itemsQuery->execute([":order_id" => $orderID]);
            $orderItems = $itemsQuery->fetchAll();

            $existingIDs = array_map(function ($item) {
                return (int)$item["product_id"];
            }, $orderItems);

            $where = "(is_archived = 0 AND expiration_date >= CURDATE() AND sell_quantity > 0)";
            if (count($existingIDs) > 0) {
                $where .= " OR product_id IN (" . implode(",", array_map("intval", $existingIDs)) . ")";
            }

            $productsQuery = $db->query(
                "SELECT product_id, display_name, display_category, original_price,
                        sell_quantity, stock_quantity, reserved_quantity,
                        unit_of_measure, expiration_date, is_archived
                 FROM products WHERE " . $where . " ORDER BY display_name"
            );
            $products = $productsQuery->fetchAll();
        }
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $message = "The order details could not be loaded. Please check the database connection.";
        $messageType = "error";
    }
}

$oldItemMap = [];
foreach ($orderItems as $item) {
    $oldItemMap[(int)$item["product_id"]] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yaad Goods - Edit Order</title>
    <link rel="icon" href="images/business-logo.png" type="image/png">
    <link rel="stylesheet" href="master.css">
    <link rel="stylesheet" href="admin-master.css">
    <style>
        .edit-order-container { width: 100%; max-width: 1100px; margin: 2rem auto; padding: 1.5rem; }
        .edit-order-container h2 { margin-top: 1rem; }
        .edit-order-message { padding: 0.9rem; margin: 1rem 0; border-radius: 5px; }
        .edit-order-success { background: #e5f5e9; color: #176b2c; }
        .edit-order-error { background: #fdeaea; color: #9b1c1c; }
        .edit-order-section { margin: 1.5rem 0; }
        .edit-order-section label { display: block; margin: 0.8rem 0 0.3rem; }
        .edit-order-section input { max-width: 500px; width: 100%; padding: 0.65rem; }
        .edit-order-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .edit-order-table th, .edit-order-table td { border: 1px solid #ccc; padding: 0.65rem; text-align: left; }
        .edit-order-table th { background: #f2f2f2; }
        .edit-order-table input { width: 85px; padding: 0.45rem; }
        .edit-order-total { text-align: right; font-size: 1.2rem; font-weight: bold; margin: 1.5rem 0; }
        .edit-order-buttons { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1.5rem; }
        .edit-order-back { display: inline-block; margin-bottom: 1rem; }
        @media (max-width: 700px) { .edit-order-container { padding: 0.75rem; } .edit-order-table { font-size: 0.85rem; } .edit-order-table th, .edit-order-table td { padding: 0.35rem; } }
    </style>
</head>
<body>
<header><?php require "instance/navigation-bar.html"; ?></header>
<main class="admin">
    <?php include "instance/admin-header.html"; ?>
    <div class="edit-order-container">
        <a class="edit-order-back" href="page-orders-admin.php">Back to Make Order</a>
        <h2>Edit Order</h2>

        <?php if ($message !== ""): ?>
            <div class="edit-order-message <?php echo $messageType === "success" ? "edit-order-success" : "edit-order-error"; ?>">
                <?php echo editOrderEscape($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($orderID < 1): ?>
            <form method="GET" action="page-edit-order.php">
                <label for="order-id">Enter Order ID</label>
                <input type="number" id="order-id" name="order_id" min="1" required>
                <button type="submit">Find Order</button>
            </form>
        <?php elseif ($order): ?>
            <p><strong>Order #:</strong> <?php echo (int)$order["order_id"]; ?></p>
            <p><strong>Status:</strong> <?php echo editOrderEscape($order["order_status"]); ?></p>
            <p><strong>Order Date:</strong> <?php echo editOrderEscape($order["order_date"]); ?></p>

            <?php if (strtolower($order["order_status"]) !== "pending"): ?>
                <p>This order cannot be edited because only pending orders can be changed.</p>
            <?php else: ?>
                <form method="POST" action="page-edit-order.php">
                    <input type="hidden" name="order_id" value="<?php echo (int)$order["order_id"]; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo editOrderEscape($_SESSION["edit_order_csrf_token"]); ?>">

                    <section class="edit-order-section">
                        <h3>Customer Information</h3>
                        <label for="customer-name">Customer Name</label>
                        <input type="text" id="customer-name" name="customer_name" maxlength="150" required value="<?php echo editOrderEscape($order["customer_name"]); ?>">
                        <label for="customer-phone">Customer Phone Number</label>
                        <input type="tel" id="customer-phone" name="customer_phone" maxlength="20" required value="<?php echo editOrderEscape($order["customer_phone"]); ?>">
                    </section>

                    <section class="edit-order-section">
                        <h3>Order Products</h3>
                        <p>Change quantities to update the order. Enter 0 to remove a product. Adding products is subject to current stock availability. Existing unit prices are preserved.</p>
                        <div style="overflow-x:auto;">
                            <table class="edit-order-table">
                                <thead><tr><th>Product</th><th>Category</th><th>Unit Price (JMD)</th><th>Quantity per Sale</th><th>Available Stock</th><th>Order Quantity</th><th>Subtotal (JMD)</th></tr></thead>
                                <tbody>
                                <?php foreach ($products as $product): ?>
                                    <?php
                                        $productID = (int)$product["product_id"];
                                        $oldItem = $oldItemMap[$productID] ?? null;
                                        $currentQuantity = $oldItem ? (int)$oldItem["quantity"] : 0;
                                        $unitPrice = $oldItem ? (float)$oldItem["stored_unit_price"] : (float)$product["original_price"];
                                        $sellQuantity = (float)$product["sell_quantity"];
                                        $availableForThisOrder = (float)$product["stock_quantity"] - (float)$product["reserved_quantity"] + ($currentQuantity * $sellQuantity);
                                        $maxQuantity = $sellQuantity > 0 ? (int)floor(($availableForThisOrder / $sellQuantity) + 0.0000001) : 0;
                                        $unavailable = (int)$product["is_archived"] === 1 || $product["expiration_date"] < date("Y-m-d");
                                        if ($unavailable) { $maxQuantity = $currentQuantity; }
                                    ?>
                                    <tr>
                                        <td><?php echo editOrderEscape($product["display_name"]); ?><?php if ($unavailable): ?> (Unavailable for new orders)<?php endif; ?></td>
                                        <td><?php echo editOrderEscape($product["display_category"]); ?></td>
                                        <td><?php echo number_format($unitPrice, 2); ?></td>
                                        <td><?php echo editOrderEscape($product["sell_quantity"] . " " . $product["unit_of_measure"]); ?></td>
                                        <td><?php echo number_format(max(0, $availableForThisOrder), 3); ?> <?php echo editOrderEscape($product["unit_of_measure"]); ?></td>
                                        <td><input class="edit-order-quantity" type="number" name="quantities[<?php echo $productID; ?>]" min="0" max="<?php echo max($maxQuantity, $currentQuantity); ?>" step="1" value="<?php echo $currentQuantity; ?>" data-price="<?php echo editOrderEscape($unitPrice); ?>"></td>
                                        <td class="edit-order-subtotal">J$<?php echo number_format($currentQuantity * $unitPrice, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <div class="edit-order-total">New Total: J$<span id="edit-order-total">0.00</span></div>
                    <div class="edit-order-buttons">
                        <button type="submit" name="save_order_changes" value="1">Save Changes</button>
                        <button type="button" onclick="window.location.href='page-orders-admin.php'">Cancel</button>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
<?php require "instance/copyright.html"; ?>
<script>
function updateEditOrderTotal() {
    let total = 0;
    document.querySelectorAll(".edit-order-quantity").forEach(input => {
        const quantity = Number(input.value) || 0;
        const price = Number(input.dataset.price) || 0;
        const subtotal = quantity * price;
        input.closest("tr").querySelector(".edit-order-subtotal").textContent = "J$" + subtotal.toFixed(2);
        total += subtotal;
    });
    const totalElement = document.getElementById("edit-order-total");
    if (totalElement) totalElement.textContent = total.toFixed(2);
}
document.querySelectorAll(".edit-order-quantity").forEach(input => input.addEventListener("input", updateEditOrderTotal));
updateEditOrderTotal();
</script>
</body>
</html>
