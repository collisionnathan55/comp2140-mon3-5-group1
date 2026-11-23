<?php
    require "../authentication/action-authorization.php";
    if (!authorizePage(basename(__FILE__))) {
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

        <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
        <script type="text/javascript" src="admin-master.js"></script>

        <script>
            function defaultAndSubmitForm(event, type) {
                event.preventDefault();
                var form = event.target;

                setEmptyFieldDefaults(form);

                var submitData = jQuery(form).serialize();

                jQuery.post(form.action + "?attempt" + type, submitData)
                    .done((response) => formResponse(response))
                    .fail(function() { alert("There was an internal issue submitting the form.") });
            }

            function setEmptyFieldDefaults(form) {
                
            }

            function formResponse(response) {
                alert(response)
            }
        </script>
    </head>

    <body>
        <header>
            <?php require "instance/navigation-bar.html"?>
        </header>

        <main class="admin">
            <?php include "instance/admin-header.html" ?>
            <form class="admin-form" action="../authentication/action-authorization.php" method="post" onsubmit="defaultAndSubmitForm(event, <?php ($editID == null ? print('\'add-product\'') : print('\'edit-product\'')) ?>)">
                <article class="admin-form-grid">
                    <section>
                        <div>
                            <label id="product-label-category">Category</label>
                            <input list="product-system-category" id="product-create-category" type="text" name="product-category" aria-labelledby="product-label-category" placeholder="eg. Apple" pattern="^[A-Za-z ]+$"></input>
                            <datalist id="product-system-category">

                            </datalist>
                            <p>The encompassing category or product type for the item.</p>
                        </div>
                        <div>
                            <label id="product-label-name">Display Name</label>
                            <input id="product-create-name" type="text" name="product-name" aria-labelledby="product-label-name" placeholder="eg. Gala Apple" pattern="^[A-Za-z ]+$" required></input>
                            <p>The <strong>unique</strong>, displayed name identifier of the item.</p>
                        </div>
                        <div class="form-item-description">
                            <label id="product-label-description">Description</label>
                            <textarea id="product-create-description" name="product-description" aria-labelledby="product-label-description" required></textarea>
                        </div>
                    </section>
                    <div>
                        <label id="product-label-price">Price</label>
                        <input id="product-create-price" type="number" name="product-price" aria-labelledby="product-label-price" placeholder="0" required></input>
                        <p>The price in Jamaican dollars of the item.</p>
                    </div>
                    <section>
                        <div>
                            <label id="product-label-quantity">Quantity per Sale</label>
                            <input id="product-create-quantity" type="number" name="product-quantity" aria-labelledby="product-label-quantity" placeholder="0" required></input>
                            <p>The quantity sold per purchase of the item.</p>
                        </div>
                        <div>
                            <label id="product-label-unit">Unit</label>
                            <input id="product-create-unit" type="text" name="product-unit" aria-labelledby="product-label-unit" value="Item" pattern="^[A-Za-z\-]+$"></input>
                            <p>The unit of measurement for the item.</p>
                        </div>
                        <div>
                            <label id="product-label-stock">Stock</label>
                            <input id="product-create-stock" type="number" name="product-stock" aria-labelledby="product-label-stock" placeholder="0" required></input>
                            <p>The amount of current stock of the item.</p>
                        </div>
                    </section>
                    <section>
                        <div>
                            <label id="product-label-freshness">Best Sold By</label>
                            <input id="product-create-freshness" type="date" name="product-freshness" id="product-label-freshness" required></input>
                            <p>The date which the item may start becoming stale / spoilled.</p>
                        </div>
                        <div>
                            <label id="product-label-spoil">After 'Best Sold By' Price</label>
                            <input id="product-create-spoil" type="number" name="product-spoil" aria-labelledby="product-label-spoil" placeholder="0"></input>
                            <p>The price in Jamaican dollars which the item sells for within three (3) days of expiration.</p>
                        </div>
                    </section>
                </article>
                <button type="submit">Submit</button>
            </form>
        </main>

        <?php require "instance/copyright.html"?>
    </body>

</html>