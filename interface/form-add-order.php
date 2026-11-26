<?php
require "../authentication/action-authorization.php";
if (!authorizePage(basename(__FILE__))) {
    exit();
}
require_once "../database/database-config.php";
require_once "../products/get-products.php";

if (empty($_SESSION["order_csrf_token"])) {
    $_SESSION["order_csrf_token"] = bin2hex(random_bytes(32));
}
function addOrderEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$message = "";
$messageType = "";
$showConfirmation = false;
$draft = $_SESSION["pending_new_order"] ?? null;
$products = [];

try {
    $db = pdo();
    $productQuery = $db->query(
        "SELECT product_id, display_name, display_category, original_price, sell_quantity,
                stock_quantity, reserved_quantity, unit_of_measure, expiration_date,
                stale_price,
            (stock_quantity - reserved_quantity) AS available_stock
         FROM products
         WHERE is_archived = 0 AND expiration_date >= CURDATE() AND sell_quantity > 0
           AND (stock_quantity - reserved_quantity) >= sell_quantity
         ORDER BY display_name ASC"
    );
    $products = $productQuery->fetchAll();
} catch (Throwable $e) {
    error_log($e->getMessage());
    $products = [];
    $message = "Products could not be loaded. Please check the database connection.";
    $messageType = "error";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (!isset($_POST["csrf_token"]) || !hash_equals($_SESSION["order_csrf_token"], $_POST["csrf_token"])) {
            throw new RuntimeException("Invalid form submission. Please refresh the page.");
        }
        $action = $_POST["order_action"] ?? "preview";

        if ($action === "deny") {
            // Keep the draft in the session and return to the editable Make Order form.
            $draft = $_SESSION["pending_new_order"] ?? null;
            $showConfirmation = false;
            $message = "You can revise the order details below. No order has been created.";
            $messageType = "error";
        } elseif ($action === "confirm") {
            $draft = $_SESSION["pending_new_order"] ?? null;
            if (!$draft) {
                throw new RuntimeException("The order draft has expired. Please enter the order again.");
            }

            $db = pdo();
            $db->beginTransaction();
            $lockedItems = [];
            $totalCost = 0.0;
            $lockProduct = $db->prepare(
                "SELECT product_id, display_name, original_price, sell_quantity, stock_quantity,
                        reserved_quantity, is_archived, expiration_date, stale_price
                 FROM products WHERE product_id = :id FOR UPDATE"
            );
            foreach ($draft["items"] as $item) {
                $lockProduct->execute([":id" => $item["product_id"]]);
                $product = $lockProduct->fetch();
                if (!$product || (int)$product["is_archived"] === 1 || $product["expiration_date"] < date("Y-m-d")) {
                    throw new RuntimeException(($item["name"] ?? "A selected product") . " is no longer available. Please revise the order.");
                }
                $sellQuantity = (float)$product["sell_quantity"];
                if ($sellQuantity <= 0 || abs((float)getTimelyPrice($product) - (float)$item["unit_price"]) > 0.00001) {
                    throw new RuntimeException("A product's price or selling quantity changed. Please review the order again.");
                }
                $physical = (int)$item["quantity"] * $sellQuantity;
                $available = (float)$product["stock_quantity"] - (float)$product["reserved_quantity"];
                if ($physical > $available + 0.000001) {
                    throw new RuntimeException("Not enough stock is available for " . $product["display_name"] . ". Please revise the order.");
                }
                $lockedItems[] = ["product_id" => (int)$product["product_id"], "quantity" => (int)$item["quantity"], "price" => (float)$item["unit_price"], "physical" => $physical];
                $totalCost += (int)$item["quantity"] * (float)$item["unit_price"];
            }

            $insertOrder = $db->prepare("INSERT INTO orders (customer_name, customer_phone, total_cost, order_status) VALUES (:name, :phone, :total, 'pending')");
            $insertOrder->execute([":name" => $draft["customer_name"], ":phone" => $draft["customer_phone"], ":total" => round($totalCost, 2)]);
            $newOrderID = (int)$db->lastInsertId();
            $insertItem = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, stored_unit_price) VALUES (:order_id, :product_id, :quantity, :price)");
            $reserve = $db->prepare("UPDATE products SET reserved_quantity = reserved_quantity + :quantity WHERE product_id = :product_id");
            foreach ($lockedItems as $item) {
                $insertItem->execute([":order_id" => $newOrderID, ":product_id" => $item["product_id"], ":quantity" => $item["quantity"], ":price" => $item["price"]]);
                $reserve->execute([":quantity" => $item["physical"], ":product_id" => $item["product_id"]]);
            }

            $db->commit();
            unset($_SESSION["pending_new_order"]);
            header("Location: form-add-order.php?created=" . $newOrderID);
            exit();
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

            $selected = [];
            foreach ($quantities as $productID => $quantity) {
                if (!ctype_digit((string)$productID)) {
                    throw new RuntimeException("Invalid product selected.");
                }
                if ($quantity === "" || $quantity === null || (float)$quantity == 0) {
                    continue;
                }
                if (!filter_var($quantity, FILTER_VALIDATE_INT) || (int)$quantity < 1) {
                    throw new RuntimeException("Product quantities must be whole numbers greater than zero.");
                }
                $selected[(int)$productID] = (int)$quantity;
            }
            if (!$selected) {
                throw new RuntimeException("Please select at least one product.");
            }

            $db = pdo();
            $lookup = $db->prepare("SELECT product_id, display_name, display_category, original_price, sell_quantity, stock_quantity, reserved_quantity, unit_of_measure, is_archived, expiration_date, stale_price FROM products WHERE product_id = :id");
            $draftItems = [];
            $total = 0.0;
            foreach ($selected as $productID => $quantity) {
                $lookup->execute([":id" => $productID]);
                $p = $lookup->fetch();
                if (!$p || (int)$p["is_archived"] === 1 || $p["expiration_date"] < date("Y-m-d")) {
                    throw new RuntimeException("A selected product is no longer available.");
                }
                $price = (float)getTimelyPrice($p);
                $sellQuantity = (float)$p["sell_quantity"];
                $available = (float)$p["stock_quantity"] - (float)$p["reserved_quantity"];
                if ($sellQuantity <= 0 || $quantity * $sellQuantity > $available + 0.000001) {
                    throw new RuntimeException("Not enough stock is available for " . $p["display_name"] . ".");
                }
                $subtotal = $quantity * $price;
                $total += $subtotal;
                $draftItems[] = ["product_id" => $productID, "name" => $p["display_name"], "category" => $p["display_category"], "quantity" => $quantity, "unit_price" => $price, "subtotal" => $subtotal, "unit" => $p["unit_of_measure"]];
            }
            $draft = ["customer_name" => $customerName, "customer_phone" => $customerPhone, "items" => $draftItems, "total" => round($total, 2)];
            $_SESSION["pending_new_order"] = $draft;
            $showConfirmation = true;
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
        $draft = $_SESSION["pending_new_order"] ?? $draft;
        $showConfirmation = false;
    }
}

if (isset($_GET["created"]) && ctype_digit((string)$_GET["created"])) {
    $createdID = (int)$_GET["created"];
    $message = "Order #" . $createdID . " was successfully created.";
    $messageType = "success";
}

$draftQuantities = [];
if ($draft && isset($draft["items"])) {
    foreach ($draft["items"] as $item) {
        $draftQuantities[(int)$item["product_id"]] = (int)$item["quantity"];
    }
}
$draftName = $draft["customer_name"] ?? "";
$draftPhone = $draft["customer_phone"] ?? "";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yaad Goods - Make Order</title>
    <link rel="icon" href="images/business-logo.png" type="image/png">
    <link rel="stylesheet" href="master.css">
    <style>
        .order-container {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto
        }

        .order-form-section {
            background: #fff;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #ccc
        }

        .order-form-section label {
            display: block;
            margin-bottom: .4rem;
            font-weight: bold
        }

        .order-form-section input {
            width: 100%;
            padding: .7rem;
            margin-bottom: 1rem;
            box-sizing: border-box
        }

        .order-product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem
        }

        .order-product-table th,
        .order-product-table td {
            border: 1px solid #ccc;
            padding: .7rem;
            text-align: left
        }

        .order-product-table th {
            background: #005C32;
            color: #fff
        }

        .order-quantity {
            width: 85px;
            padding: .4rem
        }

        .order-total {
            font-size: 1.4rem;
            font-weight: bold;
            text-align: right;
            margin-top: 1.5rem
        }

        .order-message {
            padding: 1rem;
            margin-bottom: 1rem
        }

        .order-success {
            background: #d8f3dc;
            border: 1px solid #2d6a4f;
            color: #1b4332
        }

        .order-error {
            background: #ffe0e0;
            border: 1px solid #b00020;
            color: #800000
        }

        .order-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin: 1.5rem 0
        }

        .order-buttons button,
        .order-buttons a {
            padding: .8rem 1.2rem;
            cursor: pointer
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
            .order-product-table {
                font-size: .85rem
            }

            .order-product-table th,
            .order-product-table td {
                padding: .4rem
            }
        }
    </style>
</head>

<body>
    <header><?php require "instance/navigation-bar.html"; ?></header>
    <main class="admin">
        <div class="order-container">
            <h2>Make Order</h2>
            <?php if ($message !== ""): ?><div class="order-message <?php echo $messageType === "success" ? "order-success" : "order-error"; ?>"><?php echo addOrderEscape($message); ?></div><?php endif; ?>
            <form method="POST" action="form-add-order.php" id="make-order-form">
                <input type="hidden" name="csrf_token" value="<?php echo addOrderEscape($_SESSION["order_csrf_token"]); ?>"><input type="hidden" name="order_action" value="preview">
                <section class="order-form-section">
                    <h2>Customer Information</h2>
                    <label for="customer-name">Customer Name</label><input type="text" id="customer-name" name="customer_name" maxlength="150" required placeholder="Enter customer name" value="<?php echo addOrderEscape($draftName); ?>">
                    <label for="customer-phone">Customer Phone Number</label><input type="tel" id="customer-phone" name="customer_phone" maxlength="20" required placeholder="Enter customer phone number" value="<?php echo addOrderEscape($draftPhone); ?>">
                </section>
                <section class="order-form-section">
                    <h2>Select Products</h2>
                    <p>Enter the number of sale units required for each product. Available stock excludes existing reservations.</p>
                    <?php if ($products): ?><div style="overflow-x:auto">
                            <table class="order-product-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Price (JMD)</th>
                                        <th>Quantity per Sale</th>
                                        <th>Available Stock</th>
                                        <th>Order Quantity</th>
                                        <th>Subtotal (JMD)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $p): $max = (int)floor(((float)$p["available_stock"] / (float)$p["sell_quantity"]) + 0.0000001);
                                        $qty = $draftQuantities[(int)$p["product_id"]] ?? 0; ?>
                                        <tr>
                                            <td><?php echo addOrderEscape($p["display_name"]); ?></td>
                                            <td><?php echo addOrderEscape($p["display_category"]); ?></td>
                                            <td>J$<?php echo number_format((float)getTimelyPrice($p), 2); ?></td>
                                            <td><?php echo addOrderEscape($p["sell_quantity"] . " " . $p["unit_of_measure"]); ?></td>
                                            <td><?php echo addOrderEscape($p["available_stock"] . " " . $p["unit_of_measure"]); ?></td>
                                            <td><input class="order-quantity" type="number" name="quantities[<?php echo (int)$p["product_id"]; ?>]" min="0" max="<?php echo $max; ?>" step="1" value="<?php echo $qty; ?>" data-price="<?php echo addOrderEscape(getTimelyPrice($p)); ?>"></td>
                                            <td class="order-subtotal">J$<?php echo number_format($qty * (float)getTimelyPrice($p), 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div><?php else: ?><p>No products are currently available to order.</p><?php endif; ?>
                    <div class="order-total">Total: J$<span id="order-total">0.00</span></div>
                </section>
                <div class="order-buttons"><button type="submit" <?php echo !$products ? "disabled" : ""; ?>>Review Order</button><button type="reset" onclick="setTimeout(updateOrderTotal,0)">Clear Form</button><button type="button" onclick="navigationReturn()">Return</button></div>
            </form>
        </div>
    </main><?php require "instance/copyright.html"; ?>
    <?php if ($showConfirmation && $draft): ?>
        <div class="confirmation-overlay" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
            <div class="confirmation-box">
                <h2 id="confirm-title">Confirm New Order</h2>
                <p>Please review the order details. The order will not be created until you select <strong>Confirm Order</strong>.</p>
                <p><strong>Customer:</strong> <?php echo addOrderEscape($draft["customer_name"]); ?><br><strong>Phone:</strong> <?php echo addOrderEscape($draft["customer_phone"]); ?></p>
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
                                <td><?php echo addOrderEscape($item["name"]); ?></td>
                                <td><?php echo (int)$item["quantity"]; ?></td>
                                <td>J$<?php echo number_format((float)$item["unit_price"], 2); ?></td>
                                <td>J$<?php echo number_format((float)$item["subtotal"], 2); ?></td>
                            </tr><?php endforeach; ?></tbody>
                </table>
                <h3 style="text-align:right">Total: J$<?php echo number_format((float)$draft["total"], 2); ?></h3>
                <form method="POST" action="form-add-order.php" class="confirmation-actions"><input type="hidden" name="csrf_token" value="<?php echo addOrderEscape($_SESSION["order_csrf_token"]); ?>"><button class="deny-button" type="submit" name="order_action" value="deny">Deny / Edit Order</button><button class="confirm-button" type="submit" name="order_action" value="confirm">Confirm Order</button></form>
            </div>
        </div>
    <?php endif; ?>
    <script>
        function updateOrderTotal() {
            let total = 0;
            document.querySelectorAll('.order-quantity').forEach(input => {
                const qty = Number(input.value) || 0;
                const price = Number(input.dataset.price) || 0;
                const subtotal = qty * price;
                input.closest('tr').querySelector('.order-subtotal').textContent = 'J$' + subtotal.toFixed(2);
                total += subtotal;
            });
            const el = document.getElementById('order-total');
            if (el) el.textContent = total.toFixed(2);
        }
        document.querySelectorAll('.order-quantity').forEach(input => input.addEventListener('input', updateOrderTotal));
        updateOrderTotal();
    </script>
</body>

</html>