<?php
    require_once "../authentication/action-authorization.php";
    if (!authorizePage(basename(__FILE__))) {
        exit();
    }
    require_once "../products/get-products.php";

    $productID = (isset($_GET["val"]) ? (int)$_GET["val"] : null);
    $product = null;
    if ($productID != null) {
        $product = getProductbyId($productID);
    }

    if ($product == null) {
        header("Location: ../authentication/index.php?dest=last");
    }
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

                gap: 3vmax;
            }
            main > button {
                align-self: center;
            }

            main > div {
                display: flex;
                flex-direction: column;
                gap: 3vmax;
            }

            #main-description {
                width: 90%;
                padding: 2vmax;

                align-self: center;
            }
            #main-description h2 {
                margin-bottom: 1vmax;
            }
            #main-description :last-child {
                margin-top: 2vmax;
                margin-right: 1vmax;

                text-align: right;
                font-weight: 600;
                font-size: 0.9em;
            }

            #multi-description {
                display: grid;
                grid-template-rows: 1fr;
                grid-template-columns: repeat(auto-fit, 1fr);
                grid-auto-flow: column;
            }
            #multi-description > div {
                padding: 1vmax;
                width: 80%;
                
                display: flex;
                flex-direction: column;
                align-items: start;
                justify-content: start;
            }
            #multi-description > div > h3 {
                align-self: center;
            }
            #multi-description > div ul {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
            #multi-description > div li {
                margin: 1vmin;
            }

            #stock-description > p {
                margin-top: 1vmax;
                margin-right: 1vmax;

                align-self: end;

                text-align: right;
                font-weight: 600;
                font-size: 0.9em;
            }

            @media (max-width: 768px) {
                #multi-description {
                    display: grid;
                    grid-template-rows: repeat(auto-fit, 1fr);
                    grid-template-columns: repeat(auto-fit, 1fr);
                    grid-auto-flow: dense;

                    justify-items: center;

                    gap: 3vmax;
                }
            }
            @media (max-width: 380px) {
                main {
                    font-size: 0.93em;
                }
            }
        </style>

        <script type="text/javascript" src="navigation.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>

        <script type="text/javascript">
            <?php 
            echo "const category =\"" . $product["display_category"] . "\";";
            echo "const name =\"" . $product["display_name"] . "\";";
            echo "const description =\"" . $product["description"] . "\";";
            
            echo "const unitPrice = " . $product["original_price"] . ";";
            echo "const unitPerSale = " . $product["sell_quantity"] . ";";
            echo "const availableStock = " . $product["stock_quantity"] - $product["reserved_quantity"] . ";";
            echo "const unit =\"" . $product["unit_of_measure"] . "\";";

            echo "const expiration =\"" . $product["expiration_date"] . "\";";
            echo "const stale =\"" . $product["stale_price"] . "\";";

            echo "const addDate =\"" . $product["created_at"] . "\";";
            echo "const updateDate =\"" . $product["updated_at"] . "\";";
            ?>

            <?php if (isAdmin()) {
                require_once "../orders/get-orders.php";
                $relatedOrderItems = filterLiveOrderItems(getOrderItemsByProduct($productID));
                echo "const relatedOrders = " . count(filterLiveOrders(getOrdersByProduct($productID))) . ";";
                $reservedStock = 0;
                $totalStockPrice = 0;
                foreach ($relatedOrderItems as $item) {
                    $reservedStock += $product["sell_quantity"] * $item["quantity"];
                    $totalStockPrice += (daysToExpiration($product) <= 3 ? $product["stale_price"] : $product["original_price"]) * $item["quantity"];
                }
                echo "const totalReserved = " . $reservedStock . ";";
                echo "const totalStockPrice = " . $totalStockPrice . ";";
                echo "const archived =\"" . ($product["is_archived"] ? "Archived" : "Not Archived") . "\";";
            }; ?>

            jQuery().ready(function(event) {
                jQuery("#product-name").text(name + "\t(" + category + ")");
                jQuery("#product-description").text(description);
                jQuery("#product-sell-price").text("This is being sold at $" + unitPrice.toFixed(2) + " JMD for " + unitPerSale + " " + unit + (unitPerSale == 1 ? "" : "s") + " of " + name);

                jQuery("#product-available").text("There " + (availableStock == 1 ? "is" : "are") + " currently " + availableStock + " "+ unit + (availableStock == 1 ? "" : "s") + " of " + name + " available for purchase.")

                var date = new Date(expiration);
                jQuery("#product-best-before").text("Best purchased before " + date.toLocaleString('default', { month: 'long', day:"2-digit", year:"numeric" }) + ".");

                jQuery("#product-single-unit").text("$" + (1 / unitPerSale * unitPrice).toFixed(2) + " JMD for 1 " + unit + " of " + name + ".");
                jQuery("#product-stale-price").text("$" + stale + " JMD for " + unitPerSale + " " + unit + (unitPerSale == 1 ? "" : "s") + " three (3) days near its expiration date");

                date = new Date(addDate);
                jQuery("#product-created").text("Added on " + date.toLocaleString('default', { month: 'long', day:"2-digit", year:"numeric" }) + ".");
                date = new Date(updateDate);
                jQuery("#product-updated").text("Last updated on " + date.toLocaleString('default', { month: 'long', day:"2-digit", year:"numeric" }) + ".");

                <?php if (isAdmin()) {
                    echo "jQuery('#product-related-orders').text('There ' + (relatedOrders == 1 ? 'is' : 'are') + ' ' + relatedOrders + ' customer' + (relatedOrders == 1 ? '' : 's') + ' with this product, reserving ' + totalReserved + ' unit' + (totalReserved == 1 ? '' : 's') + '.');";
                    echo "jQuery('#product-total-units').text('$' + totalStockPrice.toFixed(2) + ' JMD total for all reserved units of ' + name + ' at this time.');";
                    echo "jQuery('#product-archived').text(archived)";
                }; ?>
            })

        </script>
    </head>

    <body>
        <header>
            <?php require "instance/navigation-bar.html"?>
        </header>

        <main>
            <div>
                <div class="bubble-div" id="main-description">
                    <h2 id="product-name">Product Name (Product Category)</h2>
                    <p id="product-description">This line here is for the product description. It's really long so put yer reading glasses on haha. I'm sure you don't really want to read all this but it's really important in you making an informed decision about your purchases.</p>
                    <p id="product-sell-price">$300 JMD for 0.2 Unit(s) of Product-Name</p>
                </div>
                <section id="multi-description">
                    <div class="bubble-div" id="stock-description">
                        <h3>Available Stock</h3>
                        <ul>
                            <li id="product-available">There is(are) currently 20 Unit(s) of Product-Name available for purchase.</li>
                            <?php if (isAdmin()) { echo "<li id='product-related-orders'>(Admin) There are 3 customer order(s) with this product, reserving 30 Unit(s).</li>"; } ?>
                            <!-- <li>(If in Cart) You have purchased 3 units of this item.</li> -->
                        </ul>
                        <p id="product-best-before">Best purchased before March 7 2027.</p> 
                    </div>
                    <div class="bubble-div" id="price-description">
                        <h3>Price per Unit</h3>
                        <ul>
                            <li id="product-single-unit">$300 JMD for 1 unit of product-name.</li>
                            <li id="product-stale-price">$200 JMD for 0.2 unit(s) near its expiration date.</li>
                            <?php if (isAdmin()) { echo "<li id='product-total-units'>(Admin) $900 JMD total for all unit(s) of product-name.</li>"; } ?>
                        </ul>
                    </div>
                    <div class="bubble-div" id="system-description">
                        <h3>Alt</h3>
                        <ul>
                            <li id="product-created">Added on March 12 2026</li>
                            <li id="product-updated">Last updated on March 13 2026</li>
                            <?php if (isAdmin()) { echo "<li id='product-archived'>(Admin) Archived / Not Archived</li>"; } ?>
                        </ul>
                    </div>
                </section>
            </div>
            <button onclick="navigationReturn()">Return</button>
        </main>

        <?php require "instance/copyright.html"?>
    </body>

</html>