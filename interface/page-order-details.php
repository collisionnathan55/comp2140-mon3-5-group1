<?php
    require_once "../authentication/action-authorization.php";
    if (!authorizePage(basename(__FILE__))) {
        exit();
    }
    require_once "../orders/get-orders.php";
    require_once "../products/get-products.php";

    function isAllowed() {
        if (!isset($_GET["id"])) {
            return false;
        }

        if (!isAdmin()) {
            if (!isset($_GET["phone"])) {
                return false;
            }

            $idOrder = getOrderById($_GET["id"]);
            $phoneOrders = getOrdersByPhone($_GET["phone"]);

            if (count($phoneOrders) <= 0 || $idOrder == null || !in_array($idOrder, $phoneOrders)) {
                return false;
            }
        }

        return true;
    }

    if (!isAllowed()) {
        header("Location: ../authentication/index.php?dest=last");
        exit;
    }

    $order = getOrderById($_GET["id"]);
    $order_items = getOrderItemsByOrder($order["order_id"]);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Yaad Goods Store</title>
        <link rel="icon" href="images/business-logo.png" type="image/png"/>
        <link rel="stylesheet" href="master.css"/>

        <style>
            main {
                padding: 2vmax;
                display: flex;
                flex-direction: column;
                align-items: start;
                justify-content: space-between;

                gap: 2vmax;
            }
            main > small {
                align-self: center;
                display: flex;
                flex-direction: row;
                gap: 2vmax;
            }
            main h3 {
                margin-bottom: 1vmax;
                justify-self: center;
            }

            main > div {
                height: 100%;
                width: 100%;
                display: flex;
                flex-direction: row;
                gap: 2vmax;
            }
            main > div > aside {
                display: flex;
                flex-direction: column;
                gap: 1vmax;
            }

            #main-description {
                padding: 2vmax;
                width: max-content;
            }
            #main-description > div {
                display: grid;
                grid-template-columns: max-content max-content;
                grid-template-rows: 1fr 1fr;
                grid-auto-flow: row;

                column-gap: 2vmax;

                font-weight: 600;
            }

            #order-name {
                grid-row: 1;
                grid-column: 1;
            }
            #order-number {
                grid-row: 1;
                grid-column: 2;
            }
            #order-date {
                grid-row: 2;
                grid-column: 1;
            }
            #order-status {
                grid-row: 2;
                grid-column: 2;
                text-transform: uppercase;
                font-weight: 800;
                text-decoration: underline;
            }

            #subtotal-description .money {
                text-align: right;
            }
            #subtotal-description > div > div {
                display: grid;
                grid-template-columns: 1fr 1fr;
                grid-auto-flow: row;
            }
            #subtotal-description > hr {
                width: 100%;
                border-color: black;
                border-width: 0.1em;
                border-style: solid;
            }

            #order-breakdown {
                padding: 2vmax;
                padding-top: 0;
                width: 100%;

                display: flex;
                flex-direction: column;
                gap: 1vmax;

                border-style: solid;
                border-color: black;
                border-width: 0;
                border-left-width: 0.1em;
            }
            #order-breakdown h3 {
                justify-self: start;
                margin: 0;
            }
            #order-breakdown > article {
                box-sizing: border-box;
                width: 100%;

                padding: 1vmax;
                padding-left: 2vmax;
                padding-right: 2vmax;

                display: grid;
                grid-template-columns: 1fr 1fr 0.5fr 1fr 0.3fr 0.7fr 0.3fr;
                grid-template-rows: 1fr;
                grid-auto-flow: column;

                align-items: center;
            }
            #order-breakdown > article > div {
                width: max-content;
                display: flex;
                flex-direction: row;
                justify-content: start;
                gap: 1vmax;
            }
            #order-breakdown > article > button {
                font-size: smaller;
            }

            .different-price {
                text-decoration:line-through;
            }
        </style>

        <script type="text/javascript" src="navigation.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>

        <script type="text/javascript">
            <?php 
            echo "const phone =\"" . $order["customer_phone"] . "\";";
            $date = date_create($order["order_date"], timezone_open("EST"));
            echo "const date =\"" . $date->format("Y/m/d") . "\";";
            ?>

            jQuery().ready(function(event) {
                var phoneSplit = phone.split("-")
                var displayPhone =phone.replace("-", " ");
                if (phoneSplit.length > 2 && phoneSplit[0] == "876") {
                    displayPhone = "+1 (" + phoneSplit[0] + ") " + phoneSplit[1] + "-" + phoneSplit[2];
                }
                jQuery("#order-number").text(displayPhone);
                jQuery("#order-date").text("On: " + new Date(date).toLocaleString('default', { month: 'long', day:"2-digit", year:"numeric" }))
            })

        </script>
    </head>

    <body>
        <header>
            <?php require "instance/navigation-bar.html"?>
        </header>

        <main>
            <div>
                <aside>
                    <div class="bubble-div" id="main-description">
                        <h3 id="order-id">Order #<?php echo $order["order_id"];?></h3>
                        <div>
                            <p id="order-name">For: <?php echo $order["customer_name"];?></p>
                            <p id="order-number">Customer Number</p>
                            <p id="order-date">Placed date</p>
                            <p id="order-status"><?php echo $order["order_status"];?></p>
                        </div>
                    </div>
                    <div class="bubble-div" id="subtotal-description">
                        <h3>Stored Subtotal</h3>
                        <div>
                            <?php
                            $orderSubtotal = 0;
                            foreach ($order_items as $item) {
                                $product = getProductbyId($item["product_id"]);
                                $surfaceCost = $item["stored_unit_price"] * $item["quantity"];
                                $orderSubtotal += $surfaceCost;
                                echo "<div><p>" . ($product["display_name"] ?? "") . " x" . (float)$item["quantity"] . "</p><p class='money'>$" . sprintf("%.2f", $surfaceCost) . " JMD</p></div>";
                            }
                            ?>
                        </div>
                        <hr>
                        <p class="money">$<?php echo sprintf("%.2f", $orderSubtotal); ?> JMD</p>
                    </div>
                </aside>
                <section id="order-breakdown">
                    <?php
                    foreach ($order_items as $item) {
                        $product = getProductbyId($item["product_id"]);
                        if ($product == null) {
                            continue;
                        }

                        echo "<article class='bubble-div' id='order-breakdown-item'><h3>" . $product["display_name"] . "</h3>";
                        echo "<p>(" . $product["display_category"] . ")</p>";
                        echo "<p>" . (float)$product["sell_quantity"] . " " . $product["unit_of_measure"] . ($product["sell_quantity"] == 1 ? "" : "s") . "</p>";
                        echo "<div class='money'>" . ($product["original_price"] == $item["stored_unit_price"]? "" : "<p class='different-price'>$" . $product["original_price"] . "</p>") . "<p>$" . $item["stored_unit_price"] . "</p>" . "</div>";
                        echo "<p>x" . (int)$item["quantity"] . "</p>";
                        echo "<p>$" . sprintf("%.2f", $item["stored_unit_price"] * $item["quantity"]) . "</p>";
                        echo "<button onclick=\"navigation('page-product-details.php', " . $item["product_id"] . ")\">Details</button>";
                        echo "</article>";
                    }
                    ?>
                </section>
            </div>
            <small>
                <button onclick="navigation('form-get-orders.php')">Search Again</button>
                <?php 
                if (isAdmin()) {
                    echo "<button onclick='navigation(\"page-orders-admin.php\")'>Return to System Orders</button>";
                }
                ?>
            </small>
        </main>

        <?php require "instance/copyright.html"?>
    </body>

</html>