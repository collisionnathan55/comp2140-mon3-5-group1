<?php
    require "../authentication/action-authorization.php";
    if (!authorizePage(basename(__FILE__))) {
        exit();
    }

    $editID = (isset($_GET["val"]) ? (int)$_GET["val"] : null);
    $edit = false;
    if ($editID != null) {
        include "../products/get-products.php";
        if (getProductbyId($editID) != null) {
            $edit = true;
        }
        else {
            header("Location: ../interface/page-products-admin.php");
        }
    }
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

            function populateCategories() {
                jQuery.get("../authentication/action-authorization.php?attempt=get-products-categories")
                    .done(function(response) {
                        var json = jQuery.parseJSON(response);
                        var categories = json.dataList;

                        var catOptions = "";
                        categories.forEach(c => {
                            catOptions += "<option value=\"" + c + "\"/>";
                        })
                        jQuery("#product-system-category").html(catOptions);
                    })
                    .fail(function(response) { console.log("Issue getting product categories");
                     });
            }
            populateCategories();

            function prefillFormWithProduct(product) {
                jQuery.get("../authentication/action-authorization.php?attempt=get-products&id=" + product)
                    .done(function(response) {
                        var json = jQuery.parseJSON(response);
                        prefillFormWithProduct_C(json.dataList);
                    })
                    .fail(function(response) { 
                        console.log("Issue getting product");
                        navigation("page-products-admin.php");
                    });
            }
            function prefillFormWithProduct_C(product) {
                jQuery("#product-create-id").val(product["product_id"]);

                jQuery("#product-create-category").val(product["display_category"]);
                jQuery("#product-create-name").val(product["display_name"]);
                jQuery("#product-create-description").val(product["description"]);

                jQuery("#product-create-price").val(product["original_price"]);
                jQuery("#product-create-quantity").val(product["sell_quantity"]);
                jQuery("#product-create-stock").val(product["stock_quantity"]);

                jQuery("#product-create-reserved").val(product["reserved_quantity"]);

                jQuery("#product-create-freshness").val(product["expiration_date"]);
                jQuery("#product-create-spoil").val(product["stale_price"]);

                jQuery("#product-create-archived").prop("checked", product["is_archived"]);

                // lock

                jQuery("#product-create-name").prop("readonly", true);
                jQuery("#product-create-name").css("background-color", "#d8d8d8");

                jQuery("#product-create-reserved").prop("readonly", true);
                jQuery("#product-create-reserved").css("background-color", "#d8d8d8");

                jQuery("#product-create-stock").prop("min", product["reserved_quantity"]);
            }
            <?php $edit ? print("prefillFormWithProduct(" . $editID . ")") : print("") ?>

            //

            function defaultAndSubmitForm(event, type) {
                event.preventDefault();
                var form = event.target;
                var submitData = jQuery(form).serialize();

                jQuery.post(form.action + "?attempt=" + type, submitData)
                    .done((response) => formResponse(response))
                    .fail(function() { alert("There was an internal issue submitting the form.") });
            }

            function formResponse(response) {
                var json = jQuery.parseJSON(response);
                
                if (json.alert != null) {
                    alert(json.alert);
                }

                if (json.status == "confirmation") {
                    var dialog = jQuery(".admin-form-confirmation");
                    dialog.html(json.html);
                    dialog[0].showModal();
                }

                if (json.status == "form-reset") {
                    jQuery(".admin-form")[0].reset();
                }

                if (json.status == "form-leave") {
                    navigation("page-products-admin.php");
                }
            }

            function confirmLastSubmission() {
                jQuery.ajax("../authentication/action-authorization.php?attempt=confirm-product")
                    .done((response) => formResponse(response))
                    .fail(function() { alert("There was an internal issue submitting the form.") });
            }
        </script>
    </head>

    <body>
        <header>
            <?php require "instance/navigation-bar.html"?>
        </header>

        <main class="admin">
            <?php include "instance/admin-header.html" ?>

            <dialog class="admin-form-confirmation" closedby="closerequest">
            </dialog>
            
            <form class="admin-form" action="../authentication/action-authorization.php" method="post" onsubmit="defaultAndSubmitForm(event, <?php (!$edit ? print('\'add-product\'') : print('\'edit-product\'')) ?>)">
                <article class="admin-form-grid">
                    <section>
                        <?php $edit ? print('<input type="hidden" id="product-create-id" name="product-id" value="-1" readonly/>') : print('')?>
                        <div>
                            <label id="product-label-category">Category</label>
                            <input list="product-system-category" id="product-create-category" type="text" name="product-category" aria-labelledby="product-label-category" placeholder="eg. Apple"  pattern="^[A-Za-z ]+$"></input>
                            <datalist id="product-system-category">

                            </datalist>
                            <p>The encompassing category or product type for the item.</p>
                        </div>
                        <div>
                            <label id="product-label-name">Display Name</label>
                            <input id="product-create-name" type="text" name="product-name" aria-labelledby="product-label-name" placeholder="eg. Gala" pattern="^[A-Za-z ]+$" required></input>
                            <p>The <strong>unique</strong>, displayed name identifier of the item.</p>
                        </div>
                        <div class="form-item-description">
                            <label id="product-label-description">Description</label>
                            <textarea id="product-create-description" name="product-description" aria-labelledby="product-label-description" required></textarea>
                        </div>
                    </section>
                    <div>
                        <label id="product-label-price">Price</label>
                        <input id="product-create-price" type="number" name="product-price" aria-labelledby="product-label-price" placeholder="0" step="0.01" min="0" required></input>
                        <p>The price in Jamaican dollars of the item.</p>
                    </div>
                    <section>
                        <div>
                            <label id="product-label-quantity">Quantity per Sale</label>
                            <input id="product-create-quantity" type="number" name="product-quantity" aria-labelledby="product-label-quantity" placeholder="0" step="0.1" min="0" required></input>
                            <p>The quantity of stock sold per purchase of the item.</p>
                        </div>
                        <div>
                            <label id="product-label-unit">Unit</label>
                            <input id="product-create-unit" type="text" name="product-unit" aria-labelledby="product-label-unit" value="Item" pattern="^[A-Za-z\-]+$" required></input>
                            <p>The unit of measurement for the stock of the item.</p>
                        </div>
                        <div>
                            <label id="product-label-stock">Stock</label>
                            <input id="product-create-stock" type="number" name="product-stock" aria-labelledby="product-label-stock" placeholder="0" step="0.1" min="0"></input>
                            <p>The amount of current individual stock of the item.</p>
                        </div>
                        <?php if ($edit) {
                        echo '<div><label id="product-label-reserved">Reserved Stock</label>';
                        echo '<input id="product-create-reserved" type="number" name="product-reserved" aria-labelledby="product-label-reserved" placeholder="0" step="0.1" min="0" readonly></input>';
                        echo '<p>The amount of individual stock reserved by customers for the item.</p></div>';
                    } ?>
                    </section>
                    <section>
                        <div>
                            <label id="product-label-freshness">Best Sold By</label>
                            <input id="product-create-freshness" type="date" name="product-freshness" id="product-label-freshness" required></input>
                            <p>The date which the item may start becoming stale / spoilled.</p>
                        </div>
                        <div>
                            <label id="product-label-spoil">After 'Best Sold By' Price</label>
                            <input id="product-create-spoil" type="number" name="product-spoil" aria-labelledby="product-label-spoil" placeholder="0" step="0.01" min="0"></input>
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