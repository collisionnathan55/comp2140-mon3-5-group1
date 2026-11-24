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
                justify-content: space-between;
                align-items: center;
            }
            #admin-product-controls > div {
                display: flex;
                flex-direction: column;
                justify-content: start;
                align-items: center;

                gap: 2vmax;
            }
            #admin-product-controls button {
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
            const tableQueryColumns = ["product_id", "display_name", "display_category", "is_available", "original_price", "stock_quantity", "reserved_quantity", "unit_of_measure", "expiration_date"];
            const tableDisplayColumns = ["ID", "Name", "Category", "Availabile", "Price", "Stock", "Reserved Stock", "Unit", "Expiration"];

            function populateSortingOptions() {
                var html = "";
                tableDisplayColumns.forEach(o => {
                    html += "<option value=\"" + o + "\">" + o + "</option>"
                })
                jQuery("#admin-product-sorting").html(html);
            }

            function populatePreviewTable() {
                jQuery.get("../authentication/action-authorization.php?attempt=get-products-all")
                    .done(function(response) {
                        var json = jQuery.parseJSON(response);
                        var products = json.dataList;

                        jQuery(".admin-preview-table").html(createTableFromArrays(filterAndSort(products), tableQueryColumns, tableDisplayColumns));
                    })
                    .fail(function(response) { console.log("Issue getting products");
                     });
            }

            function filterAndSort(products) {
                var filteredProducts = [];
                var filter = jQuery("#admin-product-filter").val().toLowerCase();

                products.forEach(p => {
                    var category = p["display_category"].toLowerCase();
                    var name = p["display_name"].toLowerCase();
                    
                    var available = p["is_available"].split("<br>");
                    var availabilityMatch = false
                    available.forEach(a => {
                        if (a.toLowerCase() == filter) {
                            availabilityMatch = true;
                        }
                    })

                    // starts with filter
                    if (category.indexOf(filter) == 0 || name.indexOf(filter) == 0 || availabilityMatch) {
                        filteredProducts.push(p);
                    }
                });

                var sorting = tableQueryColumns[tableDisplayColumns.indexOf(jQuery("#admin-product-sorting").val())];

                filteredProducts.sort(function(a, b) {
                    var sortA = Number.parseFloat(a[sorting]);
                    sortA = Number.isNaN(sortA) ? a[sorting] : sortA
                    
                    var sortB = Number.parseFloat(b[sorting]);
                    sortB = Number.isNaN(sortB) ? b[sorting] : sortB

                    if (sortA < sortB) {
                        return -1;
                    }
                    if (sortA > sortB) {
                        return 1;
                    }

                    return 0;
                })
                if (jQuery("#admin-product-sorting-asc").val() == "1") {
                    filteredProducts.reverse();
                }

                return filteredProducts;
            }

            // Button events

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
            function clickArchiveProduct(archive) {
                var selected = getSelectedProducts();
                
                if (selected.length > 0) {
                    var archiveQuery = "../authentication/action-authorization.php";
                    var archiveParams = new URLSearchParams();

                    archiveParams.append("attempt", "archive-products");
                    archiveParams.append("set", archive);
                    selected.forEach(p => {
                        archiveParams.append("ids[]", p);
                    })

                    jQuery.get(archiveQuery + "?" + archiveParams.toString())
                    .done(function(response) {
                        var json = jQuery.parseJSON(response);

                        if (json.status == "confirmation") {
                            var dialog = jQuery(".admin-form-confirmation");
                            dialog.html(json.html);
                            dialog[0].showModal();
                        }
                    });
                }
            }
            function clickDeleteProduct(archive) {
                var selected = getSelectedProducts();
                
                if (selected.length > 0) {
                    var archiveQuery = "../authentication/action-authorization.php";
                    var archiveParams = new URLSearchParams();

                    archiveParams.append("attempt", "delete-products");
                    selected.forEach(p => {
                        archiveParams.append("ids[]", p);
                    })

                    jQuery.get(archiveQuery + "?" + archiveParams.toString())
                    .done(function(response) {
                        var json = jQuery.parseJSON(response);

                        if (json.status == "confirmation") {
                            var dialog = jQuery(".admin-form-confirmation");
                            dialog.html(json.html);
                            dialog[0].showModal();
                        }
                    });
                }
            }
            function confirmRemovalSubmission() {
                jQuery.ajax("../authentication/action-authorization.php?attempt=confirm-product-removal")
                    .done(function(response) {
                        jQuery(".admin-preview-table input").prop("checked", false);
                        populatePreviewTable();
                    })
                    .fail(function() { alert("There was an internal issue deleting the products.") });
            }

            // document load

            jQuery().ready(function(event) {
                populateSortingOptions();
                populatePreviewTable();
            })

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
                    <div>
                        <button onclick="navigation('form-add-product.php')">Create Product</button>
                        <button onclick="clickEditProduct()">Edit Product</button>
                        <button onclick="clickArchiveProduct(true)">Archive Selected</button>
                        <button onclick="clickArchiveProduct(false)">Un-Archive Selected</button>
                        <button onclick="clickDeleteProduct()">Delete Product</button>
                    </div>
                    <button onclick="navigation('page-home-admin.php')">Return</button>
                </section>
                <section id="admin-product-preview">
                    <dialog class="admin-form-confirmation" closedby="closerequest">
                    </dialog>
                    <div>
                        <label id="admin-product-filter-label">Search Filter</label>
                        <input id="admin-product-filter" type="search" name="product-filter" placeholder="eg. Apple. eg. Archived" pattern="^[A-Za-z_ ]+$" aria-labelledby="admin-product-filter-label" onkeyup="populatePreviewTable()"></input>
                        <select id="admin-product-sorting" onchange="populatePreviewTable()"></select>
                        <select id="admin-product-sorting-asc" onchange="populatePreviewTable()">
                            <option value="0">Ascending</option>
                            <option value="1">Descending</option>
                        </select>
                    </div>
                    <table class="admin-preview-table">

                    </table>
                </section>
            </div>
        </main>

        <?php require "instance/copyright.html"?>
    </body>

</html>