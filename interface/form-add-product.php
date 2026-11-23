<?php
    require "../authentication/user-auth-check.php";
    if (!checkPageAuth(basename(__FILE__))) {
        exit();
    }

    $editID = (isset($_GET["nav-query"]) ? $_GET["nav-query"] : null);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Yaad Goods - Admin</title>
        <link rel="icon" href="images/business-logo.png" type="image/png">
        <link rel="stylesheet" href="master.css">
        <link rel="stylesheet" href="admin-master.css">
    </head>

    <body>
        <header>
            <?php require "instance/navigation-bar.html"?>
        </header>

        <main class="admin">
            <?php include "instance/admin-header.html" ?>
            <form class="admin-form" method="post">
                <section>
                    <div>
                        <label id="product-label-category">Category</label>
                        <input list="product-system-category" id="product-create-category" type="text" name="product-category" aria-labelledby="product-label-category" placeholder="eg. Apple"></input>
                        <datalist id="product-system-category">

                        </datalist>
                        <p>The encompassing category or product type for the item.</p>
                    </div>
                    <div>
                        <label id="product-label-name">Display Name</label>
                        <input id="product-create-name" type="text" name="product-name" aria-labelledby="product-label-name" placeholder="eg. Gala Apple"></input>
                        <p>The unique, displayed name identifier of the item.</p>
                    </div>
                </section>

                <section>
                    <div>
                        <label id="product-label-price">Price</label>
                        <input id="product-create-price" type="number" name="product-price" aria-labelledby="product-label-price" placeholder="0"></input>
                        <p>The price in Jamaican dollars of the item.</p>
                    </div>
                    <div>
                        <label id="product-label-quantity">Quantity</label>
                        <input id="product-create-quantity" type="number" name="product-quantity" aria-labelledby="product-label-quantity" placeholder="0"></input>
                        <p>The quantity sold per purchase of the item.</p>
                    </div>
                </section>

                <section>
                    <div>
                        <label id="product-label-stock">Stock</label>
                        <input id="product-create-stock" type="number" name="product-stock" aria-labelledby="product-label-stock" placeholder="0"></input>
                        <p>The amount of current - unreserved to any customer - stock of the item.</p>
                    </div>
                </section>

                <section>
                    <div>
                        <label id="product-label-freshness">Best Sold by</label>
                        <input id="product-create-freshness" type="date" name="product-freshness" id="product-label-freshness"></input>
                        <p>The date which the item may start becoming stale / spoilled.</p>
                    </div>
                    <div>
                        <label id="product-label-spoil">After-Best Price</label>
                        <input id="product-create-spoil" type="number" name="product-spoil" aria-labelledby="product-label-spoil" placeholder="0"></input>
                        <p>The price in Jamaican dollars which the item sells for within three (3) days of expiration.</p>
                    </div>
                </section>
            </form>
        </main>

        <?php require "instance/copyright.html"?>
    </body>

</html>