<?php
    require "../authentication/action-authorization.php";
    if (!authorizePage(basename(__FILE__))) {
        exit();
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

        <style>
            .admin > div {
                width: 100%;
                display: flex;
                flex-direction: row;

                flex-grow: 1;
            }

            #admin-product-controls {
                width: 15vmax;
                flex-grow: 1;

                padding-right: 1vmax;

                display: flex;
                flex-direction: column;
                justify-content: start;
                align-items: center;

                gap: 2vmax;
            }
            #admin-product-controls > button {
                width: 100%;
            }

            #admin-product-preview {
                flex-grow: 1;
                width: 100%;

                padding-left: 1vmax;
                border-style: solid;
                border-width: 0em;
                border-left-width: 0.1em;
                border-color: darkgray;

                display: flex;
                flex-direction: column;
                justify-content: start;
                align-items: center;

                gap: 2vmin;
            }
            #admin-product-preview > div {
                width: 100%;

                display: flex;
                flex-direction: row;
                gap: 1vmin;

                align-items: center;
            }
            #admin-product-preview input {
                flex-grow: 1;
            }

            @media (max-width: 405px) {
                #admin-product-preview > div {
                    flex-direction: column-reverse;
                    align-items: start;
                }
                #admin-product-preview input {
                    flex-grow: 0;
                    width: 100%;
                }
            }
        </style>

        <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
        <script src="admin-master.js"></script>
        <script type="text/javascript">

            function populatePreviewTable() {
                jQuery.get("../authentication/action-authorization.php?attempt=get-products-all")
                    .done(function(response) {
                        var json = jQuery.parseJSON(response);
                        var products = json.dataList;

                        jQuery(".admin-preview-table").html(
                            createTableFromArrays(products, 
                            ["product_id", "display_name", "display_category", "is_available", "original_price", "stock_quantity", "reserved_quantity", "unit_of_measure", "expiration_date"], 
                            ["ID", "Name", "Category", "Availabile", "Price", "Stock", "Reserved Stock", "Unit", "Expiration"]
                        ));
                    })
                    .fail(function(response) { console.log("Issue getting products");
                     });
            }
            populatePreviewTable();

            function getSelectedProducts() {
                var selected = [];
                jQuery(".admin-preview-table tr").get().forEach(row => {
                    var checkbox = jQuery(row).find("input");
                    if (checkbox.get().length > 0 && checkbox[0].checked) {
                        selected.push(checkbox[0].name);
                    }
                })

                return selected;
            }

            function clickEditProduct() {
                if (getSelectedProducts().length > 0) {
                    navigation('form-add-product.php', getSelectedProducts()[0]);
                }
            }

        </script>
    </head>

    <body>
        <header>
            <?php require "instance/navigation-bar.html"?>
        </header>

        <main class="admin">
            <?php include "instance/admin-header.html" ?>
            <div>
                <section id="admin-product-controls">
                    <button onclick="navigation('form-add-product.php')">Create Product</button>
                    <button onclick="clickEditProduct()">Edit Product</button>
                    <button>Archive Selected</button>
                </section>
                <section id="admin-product-preview">
                    <div>
                        <label id="admin-product-filter-label">Search Filter</label>
                        <input id="admin-product-filter" type="search" name="product-filter" placeholder="eg. Apple" pattern="^[A-Za-z_ ]+$" aria-labelledby="admin-product-filter-label"></input>
                    </div>
                    <table class="admin-preview-table">

                    </table>
                </section>
            </div>
        </main>

        <?php require "instance/copyright.html"?>
    </body>

</html>