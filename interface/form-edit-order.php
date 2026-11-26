<?php
require "../authentication/action-authorization.php";
if (!authorizePage(basename(__FILE__))) {
    exit();
}

require_once "../database/database-config.php";
require_once "../products/get-products.php";

if (empty($_SESSION["edit_order_csrf_token"])) {
    $_SESSION["edit_order_csrf_token"] = bin2hex(random_bytes(32));
}
function editOrderEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$orderID = 0;
if (isset($_POST["val"]) && ctype_digit((string)$_POST["val"])) {
    $orderID = (int)$_POST["val"];
} elseif (isset($_GET["val"]) && ctype_digit((string)$_GET["val"])) {
    $orderID = (int)$_GET["val"];
} elseif (isset($_GET["order_id"]) && ctype_digit((string)$_GET["order_id"])) {
    $orderID = (int)$_GET["order_id"];
}

$message = "";
$messageType = "";
$order = null;
$orderItems = [];
$products = [];
$showConfirmation = false;
$draft = ($orderID > 0) ? ($_SESSION["pending_edit_orders"][$orderID] ?? null) : null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["order_action"])) {
    try {
        if (!isset($_POST["csrf_token"]) || !hash_equals($_SESSION["edit_order_csrf_token"], $_POST["csrf_token"])) {
            throw new RuntimeException("Invalid form submission. Please refresh the page.");
        }
        $action = $_POST["order_action"];
        if ($orderID < 1) {
            throw new RuntimeException("Please provide a valid order ID.");
        }

        if ($action === "deny") {
            $draft = $_SESSION["pending_edit_orders"][$orderID] ?? null;
            $showConfirmation = false;
            $message = "Your proposed changes were not saved. You can continue editing this order below.";
            $messageType = "error";
        } elseif ($action === "preview") {
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
                throw new RuntimeException("Invalid product selection.");
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
            if (!$newQuantities) {
                throw new RuntimeException("An order must contain at least one product.");
            }

            $db = pdo();
            $orderCheck = $db->prepare("SELECT order_status FROM orders WHERE order_id = :id");
            $orderCheck->execute([":id" => $orderID]);
            $status = $orderCheck->fetchColumn();
            if ($status === false) {
                throw new RuntimeException("The requested order could not be found.");
            }
            if (strtolower($status) !== "pending") {
                throw new RuntimeException("Only pending orders can be edited. This order's status is " . $status . ".");
            }

            $oldQuery = $db->prepare("SELECT product_id, quantity, stored_unit_price FROM order_items WHERE order_id = :id");
            $oldQuery->execute([":id" => $orderID]);
            $oldItems = [];
            foreach ($oldQuery->fetchAll() as $row) {
                $oldItems[(int)$row["product_id"]] = $row;
            }
            $allIDs = array_unique(array_merge(array_keys($oldItems), array_keys($newQuantities)));
            $lookup = $db->prepare("SELECT * FROM products WHERE product_id = :id");
            $draftItems = [];
            $total = 0.0;
            foreach ($allIDs as $pid) {
                $lookup->execute([":id" => $pid]);
                $p = $lookup->fetch();
                if (!$p) {
                    throw new RuntimeException("A product in this order no longer exists.");
                }
                $oldQty = isset($oldItems[$pid]) ? (int)$oldItems[$pid]["quantity"] : 0;
                $qty = $newQuantities[$pid] ?? 0;
                if ($qty < 1) {
                    continue;
                }
                $sell = (float)$p["sell_quantity"];
                if ($sell <= 0) {
                    throw new RuntimeException("Invalid sale quantity for " . $p["display_name"] . ".");
                }
                $unavailable = (int)$p["is_archived"] === 1 || $p["expiration_date"] < date("Y-m-d");
                if ($unavailable && $qty > $oldQty) {
                    throw new RuntimeException($p["display_name"] . " is archived or expired, so its quantity cannot be increased.");
                }
                $available = (float)$p["stock_quantity"] - (float)$p["reserved_quantity"] + ($oldQty * $sell);
                if ($qty * $sell > $available + 0.000001) {
                    throw new RuntimeException("Not enough stock is available for " . $p["display_name"] . ".");
                }
                $price = isset($oldItems[$pid]) ? (float)$oldItems[$pid]["stored_unit_price"] : getTimelyPrice($p);
                $subtotal = $qty * $price;
                $total += $subtotal;
                $draftItems[] = ["product_id" => (int)$pid, "name" => $p["display_name"], "quantity" => $qty, "unit_price" => $price, "subtotal" => $subtotal];
            }
            if (!$draftItems) {
                throw new RuntimeException("An order must contain at least one product.");
            }
            $draft = ["customer_name" => $customerName, "customer_phone" => $customerPhone, "items" => $draftItems, "total" => round($total, 2)];
            $_SESSION["pending_edit_orders"][$orderID] = $draft;
            $showConfirmation = true;
        } elseif ($action === "confirm") {
            $draft = $_SESSION["pending_edit_orders"][$orderID] ?? null;
            if (!$draft) {
                throw new RuntimeException("The proposed changes have expired. Please review the order again.");
            }
            $db = pdo();
            $db->beginTransaction();
            $orderLock = $db->prepare("SELECT order_id, order_status FROM orders WHERE order_id = :id FOR UPDATE");
            $orderLock->execute([":id" => $orderID]);
            $lockedOrder = $orderLock->fetch();
            if (!$lockedOrder) {
                throw new RuntimeException("The requested order could not be found.");
            }
            if (strtolower($lockedOrder["order_status"]) !== "pending") {
                throw new RuntimeException("Only pending orders can be edited.");
            }

            $oldQuery = $db->prepare("SELECT oi.product_id, oi.quantity, oi.stored_unit_price, p.sell_quantity FROM order_items oi INNER JOIN products p ON p.product_id = oi.product_id WHERE oi.order_id = :id FOR UPDATE");
            $oldQuery->execute([":id" => $orderID]);
            $oldItems = [];
            foreach ($oldQuery->fetchAll() as $row) {
                $oldItems[(int)$row["product_id"]] = $row;
            }
            $newMap = [];
            foreach ($draft["items"] as $item) {
                $newMap[(int)$item["product_id"]] = $item;
            }
            $allIDs = array_unique(array_merge(array_keys($oldItems), array_keys($newMap)));
            $lockProduct = $db->prepare("SELECT * FROM products WHERE product_id = :id FOR UPDATE");
            $reservationUpdate = $db->prepare("UPDATE products SET reserved_quantity = reserved_quantity - :old_quantity + :new_quantity WHERE product_id = :product_id");
            $finalItems = [];
            $finalTotal = 0.0;
            foreach ($allIDs as $pid) {
                $lockProduct->execute([":id" => $pid]);
                $p = $lockProduct->fetch();
                if (!$p) {
                    throw new RuntimeException("A product in this order no longer exists.");
                }
                $oldQty = isset($oldItems[$pid]) ? (int)$oldItems[$pid]["quantity"] : 0;
                $newItem = $newMap[$pid] ?? null;
                $newQty = $newItem ? (int)$newItem["quantity"] : 0;
                $sell = (float)$p["sell_quantity"];
                if ($sell <= 0) {
                    throw new RuntimeException("Invalid sale quantity for " . $p["display_name"] . ".");
                }
                $oldPhysical = $oldQty * $sell;
                $newPhysical = $newQty * $sell;
                if ($newQty > 0) {
                    $unavailable = (int)$p["is_archived"] === 1 || $p["expiration_date"] < date("Y-m-d");
                    if ($unavailable && $newQty > $oldQty) {
                        throw new RuntimeException($p["display_name"] . " is no longer available for an increased quantity.");
                    }
                    $available = (float)$p["stock_quantity"] - (float)$p["reserved_quantity"] + $oldPhysical;
                    if ($newPhysical > $available + 0.000001) {
                        throw new RuntimeException("Not enough stock is available for " . $p["display_name"] . ".");
                    }
                    if (!$newItem) {
                        throw new RuntimeException("The proposed order details are invalid. Please review again.");
                    }
                    // Existing lines retain their saved price; newly added lines must still match current price.
                    if (!isset($oldItems[$pid]) && abs((float)getTimelyPrice($p) - (float)$newItem["unit_price"]) > 0.00001) {
                        throw new RuntimeException("The price of " . $p["display_name"] . " has changed. Please review the order again.");
                    }
                    $finalItems[] = ["product_id" => (int)$pid, "quantity" => $newQty, "price" => (float)$newItem["unit_price"]];
                    $finalTotal += $newQty * (float)$newItem["unit_price"];
                }
                $reservationUpdate->execute([":old_quantity" => $oldPhysical, ":new_quantity" => $newPhysical, ":product_id" => $pid]);
            }
            if (!$finalItems) {
                throw new RuntimeException("An order must contain at least one product.");
            }
            $delete = $db->prepare("DELETE FROM order_items WHERE order_id = :id");
            $delete->execute([":id" => $orderID]);
            $insert = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, stored_unit_price) VALUES (:order_id, :product_id, :quantity, :price)");
            foreach ($finalItems as $item) {
                $insert->execute([":order_id" => $orderID, ":product_id" => $item["product_id"], ":quantity" => $item["quantity"], ":price" => $item["price"]]);
            }
            $update = $db->prepare("UPDATE orders SET customer_name = :name, customer_phone = :phone, total_cost = :total WHERE order_id = :id");
            $update->execute([":name" => $draft["customer_name"], ":phone" => $draft["customer_phone"], ":total" => round($finalTotal, 2), ":id" => $orderID]);
            $db->commit();

            include_once "../system-actions/add-action.php";
            createAction_editOrder($orderID, $draft["customer_name"]);

            unset($_SESSION["pending_edit_orders"][$orderID]);
            header("Location: form-edit-order.php?val=" . $orderID . "&updated=1");
            exit();
        } else {
            throw new RuntimeException("Unknown order action.");
        }
    } catch (Throwable $e) {
        if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log($e->getMessage());
        $message = $e instanceof RuntimeException ? $e->getMessage() : "The order could not be processed. Please try again.";
        $messageType = "error";
        $draft = $_SESSION["pending_edit_orders"][$orderID] ?? $draft;
        $showConfirmation = false;
    }
}

try {
    if ($orderID > 0) {
        $db = pdo();
        $orderQuery = $db->prepare("SELECT * FROM orders WHERE order_id = :id");
        $orderQuery->execute([":id" => $orderID]);
        $order = $orderQuery->fetch();
        if (!$order) {
            $message = "Order #" . $orderID . " could not be found.";
            $messageType = "error";
        } else {
            $itemsQuery = $db->prepare("SELECT oi.product_id, oi.quantity, oi.stored_unit_price, p.display_name, p.display_category, p.sell_quantity, p.unit_of_measure, p.stock_quantity, p.reserved_quantity, p.is_archived, p.expiration_date FROM order_items oi INNER JOIN products p ON p.product_id = oi.product_id WHERE oi.order_id = :id ORDER BY p.display_name");
            $itemsQuery->execute([":id" => $orderID]);
            $orderItems = $itemsQuery->fetchAll();
            $existingIDs = array_map(fn($item) => (int)$item["product_id"], $orderItems);
            $where = "(is_archived = 0 AND expiration_date >= CURDATE() AND sell_quantity > 0)";
            if ($existingIDs) {
                $where .= " OR product_id IN (" . implode(",", array_map("intval", $existingIDs)) . ")";
            }
            $products = $db->query("SELECT * FROM products WHERE " . $where . " ORDER BY display_name")->fetchAll();
        }
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    $message = "The order details could not be loaded. Please check the database connection.";
    $messageType = "error";
}

$oldItemMap = [];
foreach ($orderItems as $item) {
    $oldItemMap[(int)$item["product_id"]] = $item;
}
$draftItemMap = [];
if ($draft && isset($draft["items"])) {
    foreach ($draft["items"] as $item) {
        $draftItemMap[(int)$item["product_id"]] = $item;
    }
}
$displayName = $draft["customer_name"] ?? ($order["customer_name"] ?? "");
$displayPhone = $draft["customer_phone"] ?? ($order["customer_phone"] ?? "");
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
        .edit-order-container {
            width: 100%;
            max-width: 1100px;
            padding: 0 1.5rem
        }

        .edit-order-message {
            padding: .9rem;
            margin: 1rem 0;
            border-radius: 5px
        }

        .edit-order-success {
            background: #e5f5e9;
            color: #176b2c
        }

        .edit-order-error {
            background: #fdeaea;
            color: #9b1c1c
        }

        .edit-order-section {
            margin: 1.5rem 0
        }

        .edit-order-section label {
            display: block;
            margin: .8rem 0 .3rem
        }

        .edit-order-section input {
            max-width: 500px;
            width: 100%;
            padding: .65rem
        }

        .edit-order-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem
        }

        .edit-order-table th,
        .edit-order-table td {
            border: 1px solid #ccc;
            padding: .65rem;
            text-align: left
        }

        .edit-order-table th {
            background: #f2f2f2
        }

        .edit-order-table input {
            width: 85px;
            padding: .45rem
        }

        .edit-order-total {
            text-align: right;
            font-size: 1.2rem;
            font-weight: bold;
            margin: 1.5rem 0
        }

        .edit-order-buttons {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
            margin-top: 1.5rem
        }

        .confirmation-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .65);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 9999
        }

        .confirmation-box {
            background: #fff;
            color: #222;
            width: 100%;
            max-width: 650px;
            max-height: 90vh;
            overflow: auto;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, .3)
        }

        .confirmation-box table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0
        }

        .confirmation-box th,
        .confirmation-box td {
            border: 1px solid #ccc;
            padding: .55rem;
            text-align: left
        }

        .confirmation-actions {
            display: flex;
            gap: .75rem;
            justify-content: flex-end;
            margin-top: 1.25rem
        }

        .confirmation-actions button {
            padding: .7rem 1.3rem;
            cursor: pointer
        }

        .confirm-button {
            background: #087443;
            color: white;
            border: 0
        }

        .deny-button {
            background: #8b1e1e;
            color: white;
            border: 0
        }

        @media(max-width:700px) {
            .edit-order-container {
                padding: .75rem
            }

            .edit-order-table {
                font-size: .85rem
            }

            .edit-order-table th,
            .edit-order-table td {
                padding: .35rem
            }
        }
    </style>
</head>

<body>
    <header><?php require "instance/navigation-bar.html"; ?></header>
    <main class="admin"><?php include "instance/admin-header.html"; ?><div class="edit-order-container">
            <h2>Edit Order</h2>
            <?php if ($message !== ""): ?><div class="edit-order-message <?php echo $messageType === "success" ? "edit-order-success" : "edit-order-error"; ?>"><?php echo editOrderEscape($message); ?></div><?php endif; ?>
            <?php if ($orderID < 1): ?><form method="GET" action="form-edit-order.php"><label for="order-id">Enter Order ID</label><input type="number" id="order-id" name="val" min="1" required><button type="submit">Find Order</button></form>
            <?php elseif ($order): ?><p><strong>Order #:</strong> <?php echo (int)$order["order_id"]; ?></p>
                <p><strong>Status:</strong> <?php echo editOrderEscape($order["order_status"]); ?></p>
                <p><strong>Order Date:</strong> <?php echo editOrderEscape($order["order_date"]); ?></p>
                <?php if (strtolower($order["order_status"]) !== "pending"): ?><p>This order cannot be edited because only pending orders can be changed.</p>
                <?php else: ?><form method="POST" action="form-edit-order.php"><input type="hidden" name="val" value="<?php echo (int)$order["order_id"]; ?>"><input type="hidden" name="csrf_token" value="<?php echo editOrderEscape($_SESSION["edit_order_csrf_token"]); ?>">
                        <section class="edit-order-section">
                            <h3>Customer Information</h3><label for="customer-name">Customer Name</label><input type="text" id="customer-name" name="customer_name" maxlength="150" required value="<?php echo editOrderEscape($displayName); ?>"><label for="customer-phone">Customer Phone Number</label><input type="tel" id="customer-phone" name="customer_phone" maxlength="20" pattern="^\d{3}-\d{3}-\d{4}$" placeholder="eg. 876-123-1234" required value="<?php echo editOrderEscape($displayPhone); ?>">
                        </section>
                        <section class="edit-order-section">
                            <h3>Order Products</h3>
                            <p>Change quantities to update the order. Enter 0 to remove a product. Adding products is subject to current stock availability. Existing unit prices are preserved.</p>
                            <div style="overflow-x:auto">
                                <table class="edit-order-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th>Unit Price (JMD)</th>
                                            <th>Quantity per Sale</th>
                                            <th>Available Stock</th>
                                            <th>Order Quantity</th>
                                            <th>Subtotal (JMD)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $p): $pid = (int)$p["product_id"];
                                            $old = $oldItemMap[$pid] ?? null;
                                            $draftItem = $draftItemMap[$pid] ?? null;
                                            $currentQty = $draft ? ($draftItem ? (int)$draftItem["quantity"] : 0) : ($old ? (int)$old["quantity"] : 0);
                                            $price = $draftItem ? (float)$draftItem["unit_price"] : ($old ? (float)$old["stored_unit_price"] : (float)getTimelyPrice($p));
                                            $sell = (float)$p["sell_quantity"];
                                            $available = (float)$p["stock_quantity"] - (float)$p["reserved_quantity"] + (($old ? (int)$old["quantity"] : 0) * $sell);
                                            $max = $sell > 0 ? (int)floor(($available / $sell) + 0.0000001) : 0;
                                            $unavailable = (int)$p["is_archived"] === 1 || $p["expiration_date"] < date("Y-m-d");
                                            if ($unavailable) {
                                                $max = $old ? (int)$old["quantity"] : 0;
                                            } ?>
                                            <tr>
                                                <td><?php echo editOrderEscape($p["display_name"]); ?><?php if ($unavailable): ?> (Unavailable for new orders)<?php endif; ?></td>
                                                <td><?php echo editOrderEscape($p["display_category"]); ?></td>
                                                <td>J$<?php echo number_format($price, 2); ?></td>
                                                <td><?php echo editOrderEscape($p["sell_quantity"] . " " . $p["unit_of_measure"]); ?></td>
                                                <td><?php echo number_format(max(0, $available), 3) . " " . editOrderEscape($p["unit_of_measure"]); ?></td>
                                                <td><input class="edit-order-quantity" type="number" name="quantities[<?php echo $pid; ?>]" min="0" max="<?php echo max($max, $currentQty); ?>" step="1" value="<?php echo $currentQty; ?>" data-price="<?php echo editOrderEscape($price); ?>"></td>
                                                <td class="edit-order-subtotal">J$<?php echo number_format($currentQty * $price, 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                        <div class="edit-order-total">Proposed Total: J$<span id="edit-order-total">0.00</span></div>
                        <div class="edit-order-buttons"><button type="submit" name="order_action" value="preview">Review Changes</button><button type="button" onclick="navigation('page-orders-admin.php')">Cancel</button></div>
                    </form><?php endif; ?><?php endif; ?>
        </div>
    </main><?php require "instance/copyright.html"; ?>
    <?php if ($showConfirmation && $draft): ?><div class="confirmation-overlay" role="dialog" aria-modal="true" aria-labelledby="edit-confirm-title">
            <div class="confirmation-box">
                <h2 id="edit-confirm-title">Confirm Order Changes</h2>
                <p>Review the proposed changes. Nothing will be saved until you select <strong>Confirm Changes</strong>.</p>
                <p><strong>Order:</strong> #<?php echo $orderID; ?><br><strong>Customer:</strong> <?php echo editOrderEscape($draft["customer_name"]); ?><br><strong>Phone:</strong> <?php echo editOrderEscape($draft["customer_phone"]); ?></p>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody><?php foreach ($draft["items"] as $item): ?><tr>
                                <td><?php echo editOrderEscape($item["name"]); ?></td>
                                <td><?php echo (int)$item["quantity"]; ?></td>
                                <td>J$<?php echo number_format((float)$item["unit_price"], 2); ?></td>
                                <td>J$<?php echo number_format((float)$item["subtotal"], 2); ?></td>
                            </tr><?php endforeach; ?></tbody>
                </table>
                <h3 style="text-align:right">New Total: J$<?php echo number_format((float)$draft["total"], 2); ?></h3>
                <form method="POST" action="form-edit-order.php" class="confirmation-actions"><input type="hidden" name="val" value="<?php echo $orderID; ?>"><input type="hidden" name="csrf_token" value="<?php echo editOrderEscape($_SESSION["edit_order_csrf_token"]); ?>"><button class="deny-button" type="submit" name="order_action" value="deny">Deny / Continue Editing</button><button class="confirm-button" type="submit" name="order_action" value="confirm">Confirm Changes</button></form>
            </div>
        </div><?php endif; ?>
    <script>
        function updateEditOrderTotal() {
            let total = 0;
            document.querySelectorAll('.edit-order-quantity').forEach(input => {
                const qty = Number(input.value) || 0;
                const price = Number(input.dataset.price) || 0;
                const subtotal = qty * price;
                input.closest('tr').querySelector('.edit-order-subtotal').textContent = 'J$' + subtotal.toFixed(2);
                total += subtotal;
            });
            const el = document.getElementById('edit-order-total');
            if (el) el.textContent = total.toFixed(2);
        }
        document.querySelectorAll('.edit-order-quantity').forEach(input => input.addEventListener('input', updateEditOrderTotal));
        updateEditOrderTotal();
    </script>
</body>

</html>