
<?php

require "../authentication/action-authorization.php";

if (!authorizePage(basename(__FILE__))) {
    exit();
}

require_once "../database/database-config.php";

// Generate a CSRF token for this session.
if (empty($_SESSION["order_csrf_token"])) {
    $_SESSION["order_csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "";
$createdOrderID = null;


// Handle order submission.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        // Check the CSRF token.
        if (
            !isset($_POST["csrf_token"]) ||
            !hash_equals(
                $_SESSION["order_csrf_token"],
                $_POST["csrf_token"]
            )
        ) {
            throw new RuntimeException(
                "Invalid form submission. Please refresh the page."
            );
        }

        $customerName = trim($_POST["customer_name"] ?? "");
        $customerPhone = trim($_POST["customer_phone"] ?? "");
        $quantities = $_POST["quantities"] ?? [];

        // Validate customer information.
        if ($customerName === "" || strlen($customerName) > 150) {
            throw new RuntimeException(
                "Please enter a valid customer name."
            );
        }

        if (
            $customerPhone === "" ||
            strlen($customerPhone) > 20
        ) {
            throw new RuntimeException(
                "Please enter a valid customer phone number."
            );
        }

        if (!is_array($quantities)) {
            throw new RuntimeException(
                "Invalid product selection."
            );
        }

        // Remove products with no quantity selected.
        $selectedProducts = [];

        foreach ($quantities as $productID => $quantity) {

            if (!ctype_digit((string)$productID)) {
                throw new RuntimeException(
                    "Invalid product selected."
                );
            }

            if (
                $quantity === "" ||
                $quantity === null ||
                (float)$quantity == 0
            ) {
                continue;
            }

            if (
                !filter_var(
                    $quantity,
                    FILTER_VALIDATE_INT
                ) ||
                (int)$quantity < 1
            ) {
                throw new RuntimeException(
                    "Product quantities must be whole numbers greater than zero."
                );
            }

            $selectedProducts[(int)$productID] = (int)$quantity;
        }

        if (count($selectedProducts) === 0) {
            throw new RuntimeException(
                "Please select at least one product."
            );
        }

        $db = pdo();

        // Start a transaction so all order changes succeed together.
        $db->beginTransaction();

        $orderItems = [];
        $totalCost = 0.00;

        // Lock and check each selected product.
        $productQuery = $db->prepare(
            "SELECT
                product_id,
                display_name,
                original_price,
                sell_quantity,
                stock_quantity,
                reserved_quantity,
                is_archived,
                expiration_date
             FROM products
             WHERE product_id = :product_id
             FOR UPDATE"
        );

        foreach ($selectedProducts as $productID => $saleQuantity) {

            $productQuery->execute([
                ":product_id" => $productID
            ]);

            $product = $productQuery->fetch();

            if (!$product) {
                throw new RuntimeException(
                    "One of the selected products no longer exists."
                );
            }

            if ((int)$product["is_archived"] === 1) {
                throw new RuntimeException(
                    $product["display_name"] . " is no longer available."
                );
            }

            if ($product["expiration_date"] < date("Y-m-d")) {
                throw new RuntimeException(
                    $product["display_name"] . " has expired and cannot be ordered."
                );
            }

            $price = (float)$product["original_price"];
            $sellQuantity = (float)$product["sell_quantity"];

            $stock = (float)$product["stock_quantity"];
            $reserved = (float)$product["reserved_quantity"];

            if ($sellQuantity <= 0 || $price < 0) {
                throw new RuntimeException(
                    "Invalid selling information for " .
                    $product["display_name"] . "."
                );
            }

            // Calculate the physical stock required.
            $physicalStockRequired = $saleQuantity * $sellQuantity;

            // Calculate stock that has not already been reserved.
            $availableStock = $stock - $reserved;

            if ($physicalStockRequired > $availableStock) {
                throw new RuntimeException(
                    "Not enough stock available for " .
                    $product["display_name"] . "."
                );
            }

            // The customer pays for each sale unit.
            $subtotal = $saleQuantity * $price;

            $totalCost += $subtotal;

            $orderItems[] = [
                "product_id" => $productID,
                "product_name" => $product["display_name"],
                "sale_quantity" => $saleQuantity,
                "physical_quantity" => $physicalStockRequired,
                "unit_price" => $price
            ];
        }

        // Create the order.
        $orderStatement = $db->prepare(
            "INSERT INTO orders
                (customer_name, customer_phone, total_cost, order_status)
             VALUES
                (:customer_name, :customer_phone, :total_cost, 'pending')"
        );

        $orderStatement->execute([
            ":customer_name" => $customerName,
            ":customer_phone" => $customerPhone,
            ":total_cost" => $totalCost
        ]);

        $orderID = (int)$db->lastInsertId();

        // Insert each order item and reserve its stock.
        $itemStatement = $db->prepare(
            "INSERT INTO order_items
                (order_id, product_id, quantity, stored_unit_price)
             VALUES
                (:order_id, :product_id, :quantity, :unit_price)"
        );

        $reserveStatement = $db->prepare(
            "UPDATE products
             SET reserved_quantity = reserved_quantity + :quantity
             WHERE product_id = :product_id"
        );

        foreach ($orderItems as $item) {

            $itemStatement->execute([
                ":order_id" => $orderID,
                ":product_id" => $item["product_id"],
                ":quantity" => $item["sale_quantity"],
                ":unit_price" => $item["unit_price"]
            ]);

            $reserveStatement->execute([
                ":quantity" => $item["physical_quantity"],
                ":product_id" => $item["product_id"]
            ]);
        }

        // Record the order creation in the action log.
        $logStatement = $db->prepare(
            "INSERT INTO action_logs (action, description)
             VALUES (:action, :description)"
        );

        $logStatement->execute([
            ":action" => "create_order",
            ":description" => "Order #" . $orderID .
                " created for customer " . $customerName . "."
        ]);

        // Save all changes.
        $db->commit();

        // Redirect to prevent duplicate submissions on refresh.
        header(
            "Location: page-orders-admin.php?created=" . $orderID
        );
        exit();

    } catch (Throwable $e) {

        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }

        error_log($e->getMessage());

        $message = $e instanceof RuntimeException
            ? $e->getMessage()
            : "The order could not be created. Please check the database and try again.";

        $messageType = "error";
    }
}


// Display the successful order message.
if (isset($_GET["created"]) && ctype_digit($_GET["created"])) {

    $createdOrderID = (int)$_GET["created"];

    $message = "Order #" . $createdOrderID .
        " was successfully created.";

    $messageType = "success";
}


// Retrieve products for the order form.
try {

    $db = pdo();

    $productQuery = $db->query(
        "SELECT
            product_id,
            display_name,
            display_category,
            original_price,
            sell_quantity,
            stock_quantity,
            reserved_quantity,
            unit_of_measure,
            expiration_date,
            (stock_quantity - reserved_quantity) AS available_stock
         FROM products
         WHERE is_archived = 0
           AND expiration_date >= CURDATE()
           AND sell_quantity > 0
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


// HTML escaping helper.
function escapeHTML($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Yaad Goods - Make Order</title>

    <link rel="icon" href="images/business-logo.png" type="image/png">
    <link rel="stylesheet" href="master.css">
    <link rel="stylesheet" href="admin-master.css">

    <style>

        .order-container {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
        }

        .order-container h2 {
            margin-bottom: 1rem;
        }

        .order-form-section {
            background: white;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #ccc;
        }

        .order-form-section label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: bold;
        }

        .order-form-section input {
            width: 100%;
            padding: 0.7rem;
            margin-bottom: 1rem;
            box-sizing: border-box;
        }

        .order-product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .order-product-table th,
        .order-product-table td {
            border: 1px solid #ccc;
            padding: 0.7rem;
            text-align: left;
        }

        .order-product-table th {
            background: #005C32;
            color: white;
        }

        .order-quantity {
            width: 85px;
            padding: 0.4rem;
        }

        .order-total {
            font-size: 1.4rem;
            font-weight: bold;
            text-align: right;
            margin-top: 1.5rem;
        }

        .order-message {
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .order-success {
            background: #d8f3dc;
            border: 1px solid #2d6a4f;
            color: #1b4332;
        }

        .order-error {
            background: #ffe0e0;
            border: 1px solid #b00020;
            color: #800000;
        }

        .order-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }

        .order-buttons button,
        .order-buttons a {
            padding: 0.8rem 1.2rem;
            cursor: pointer;
        }

        .order-back-link {
            display: inline-block;
            margin-bottom: 1rem;
        }

        @media (max-width: 700px) {

            .order-product-table {
                font-size: 0.85rem;
            }

            .order-product-table th,
            .order-product-table td {
                padding: 0.4rem;
            }

        }

    </style>

</head>

<body>

    <header>
        <?php require "instance/navigation-bar.html"; ?>
    </header>

    <main class="admin">

        <?php include "instance/admin-header.html"; ?>

        <div class="order-container">

            <a class="order-back-link"
               href="page-home-admin.php">
                Back to Admin Dashboard
            </a>

            <h2>Make Order</h2>

            <?php if ($message !== ""): ?>

                <div class="order-message <?php
                    echo $messageType === "success"
                        ? "order-success"
                        : "order-error";
                ?>">
                    <?php echo escapeHTML($message); ?>
                </div>

            <?php endif; ?>


            <form method="POST" action="page-orders-admin.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo escapeHTML($_SESSION["order_csrf_token"]); ?>"
                >

                <section class="order-form-section">

                    <h3>Customer Information</h3>

                    <label for="customer-name">
                        Customer Name
                    </label>

                    <input
                        type="text"
                        id="customer-name"
                        name="customer_name"
                        maxlength="150"
                        required
                        placeholder="Enter customer name"
                    >

                    <label for="customer-phone">
                        Customer Phone Number
                    </label>

                    <input
                        type="tel"
                        id="customer-phone"
                        name="customer_phone"
                        maxlength="20"
                        required
                        placeholder="Enter customer phone number"
                    >

                </section>


                <section class="order-form-section">

                    <h3>Select Products</h3>

                    <p>
                        Enter the number of sale units required for each product.
                        The available stock shown is the physical stock that has
                        not already been reserved.
                    </p>

                    <?php if (count($products) > 0): ?>

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

                                <?php foreach ($products as $product): ?>

                                    <?php
                                        $maximumSales = (int)floor(
                                            ((float)$product["available_stock"] /
                                            (float)$product["sell_quantity"]) + 0.0000001
                                        );
                                    ?>

                                    <tr>

                                        <td>
                                            <?php echo escapeHTML($product["display_name"]); ?>
                                        </td>

                                        <td>
                                            <?php echo escapeHTML($product["display_category"]); ?>
                                        </td>

                                        <td>
                                            $<?php echo number_format(
                                                (float)$product["original_price"],
                                                2
                                            ); ?>
                                        </td>

                                        <td>
                                            <?php echo escapeHTML($product["sell_quantity"]); ?>
                                            <?php echo escapeHTML($product["unit_of_measure"]); ?>
                                        </td>

                                        <td>
                                            <?php echo escapeHTML($product["available_stock"]); ?>
                                            <?php echo escapeHTML($product["unit_of_measure"]); ?>
                                        </td>

                                        <td>

                                            <input
                                                class="order-quantity"
                                                type="number"
                                                name="quantities[<?php echo (int)$product["product_id"]; ?>]"
                                                min="0"
                                                max="<?php echo $maximumSales; ?>"
                                                step="1"
                                                value="0"
                                                data-price="<?php echo escapeHTML($product["original_price"]); ?>"
                                            >

                                        </td>

                                        <td class="order-subtotal">
                                            $0.00
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    <?php else: ?>

                        <p>
                            No products are currently available to order.
                        </p>

                    <?php endif; ?>

                    <div class="order-total">
                        Total: J$<span id="order-total">0.00</span>
                    </div>

                </section>


                <div class="order-buttons">

                    <button
                        type="submit"
                        <?php echo count($products) === 0 ? "disabled" : ""; ?>
                    >
                        Create Order
                    </button>

                    <button
                        type="reset"
                        onclick="resetOrderTotal()"
                    >
                        Clear Form
                    </button>

                    <button
                        type="button"
                        onclick="window.location.href='page-home-admin.php'"
                    >
                        Cancel
                    </button>

                </div>

            </form>

        </div>

    </main>

    <?php require "instance/copyright.html"; ?>


    <script>

        function updateOrderTotal() {

            let total = 0;

            document.querySelectorAll(".order-quantity").forEach(input => {

                const quantity = Number(input.value) || 0;
                const price = Number(input.dataset.price) || 0;

                const subtotal = quantity * price;

                const row = input.closest("tr");

                row.querySelector(".order-subtotal").textContent =
                    "$" + subtotal.toFixed(2);

                total += subtotal;

            });

            document.getElementById("order-total").textContent =
                total.toFixed(2);
        }


        function resetOrderTotal() {

            setTimeout(() => {
                updateOrderTotal();
            }, 0);

        }


        document.querySelectorAll(".order-quantity").forEach(input => {

            input.addEventListener("input", updateOrderTotal);

        });

    </script>

</body>

</html>
