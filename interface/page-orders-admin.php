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

            #admin-order-controls {
                width: 15vmax;
                flex-grow: 1;

                padding-right: 1vmax;

                display: flex;
                flex-direction: column;
                justify-content: space-between;
                align-items: center;
            }
            #admin-order-controls > div {
                width: 100%;

                display: flex;
                flex-direction: column;
                justify-content: start;

                gap: 2vmax;
            }
            #admin-order-controls button {
                width: 100%;
            }

            #admin-order-preview {
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
            #admin-order-preview > div {
                width: 100%;

                display: flex;
                flex-direction: row;

                align-items: start;
                gap: 1vmax;
            }
            #admin-order-preview > div > button {
                padding: 0.3em;
                font-size: smaller;
                border-width: 0.01em;
            }
            #admin-order-sorting, #admin-order-sorting-asc {
                
                height: 100%;
            }
            #admin-order-preview #admin-order-details {
                padding-left: 2em;
                padding-right: 2em;
            }
            #admin-order-preview input {
                flex-grow: 1;
            }
        </style>

        <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
        <script src="admin-master.js"></script>
        <script type="text/javascript">
            const tableQueryColumns = ["order_id", "customer_name", "customer_phone", "order_status", "order_date", "related_products", "total_cost"];
            const tableDisplayColumns = ["ID", "Name", "Phone Number", "Status", "Date Ordered", "Related Products", "Sub Total"];

            function populateSortingOptions() {
                var html = "";
                tableDisplayColumns.forEach(o => {
                    html += "<option value=\"" + o + "\">" + o + "</option>"
                })
                jQuery("#admin-order-sorting").html(html);
            }

            function populatePreviewTable() {
                jQuery.get("../authentication/action-authorization.php?attempt=get-orders-all")
                    .done(function(response) {
                        var json = jQuery.parseJSON(response);
                        var orders = json.dataList;
                        var displayedOrders = filterAndSort(orders);

                        jQuery(".admin-preview-table").html(createTableFromArrays(displayedOrders, tableQueryColumns, tableDisplayColumns));

                        if (displayedOrders.length == 0) {
                            jQuery(".admin-preview-table").html("No orders available or none match the filter.");
                        }
                    })
                    .fail(function(response) { console.log("Issue getting orders");
                     });
            }

            function filterAndSort(orders) {
                var filteredOrders = [];
                var filter = jQuery("#admin-order-filter").val().toLowerCase();

                orders.forEach(p => {
                    var customer = p["customer_name"].toLowerCase();
                    var phone = p["customer_phone"].toLowerCase();
                    var status = p["order_status"].toLowerCase();

                    // starts with filter
                    if (customer.indexOf(filter) == 0 || phone.indexOf(filter) == 0 || status == filter) {
                        filteredOrders.push(p);
                    }
                });

                var sorting = tableQueryColumns[tableDisplayColumns.indexOf(jQuery("#admin-order-sorting").val())];

                filteredOrders.sort(function(a, b) {
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
                if (jQuery("#admin-order-sorting-asc").val() == "1") {
                    filteredOrders.reverse();
                }

                return filteredOrders;
            }

            // Button events

            function getSelectedOrders() {
                var selected = [];
                jQuery(".admin-preview-table tr").get().forEach(row => {
                    var checkbox = jQuery(row).find("input");
                    if (checkbox.get().length > 0 && checkbox[0].checked) {
                        selected.push(checkbox[0].name);
                    }
                })

                return selected;
            }

            function clickOrderDetails() {
                if (getSelectedOrders().length > 0) {
                    window.location.href = "page-order-details.php?id=" + getSelectedOrders()[0];
                }
            }
            function clickEditOrder() {
                if (getSelectedOrders().length > 0) {
                    navigation('form-edit-order.php', getSelectedOrders()[0]);
                }
            }

            function clickCancelOrder() {
                if (getSelectedOrders().length > 0) {
                    var archiveQuery = "../authentication/action-authorization.php";

                    jQuery.get(archiveQuery + "?attempt=cancel-order&id=" + getSelectedOrders()[0])
                    .done(function(response) {
                        var json = jQuery.parseJSON(response);
                        if (json.alert != null) {
                            alert(json.alert)
                        }

                        if (json.status == "confirmation") {
                            var dialog = jQuery(".admin-form-confirmation");
                            dialog.html(json.html);
                            dialog[0].showModal();
                        }
                    });
                }
            }
            function clickCompleteOrder() {
                if (getSelectedOrders().length > 0) {
                    var archiveQuery = "../authentication/action-authorization.php";

                    jQuery.get(archiveQuery + "?attempt=complete-order&id=" + getSelectedOrders()[0])
                    .done(function(response) {
                        var json = jQuery.parseJSON(response);
                        if (json.alert != null) {
                            alert(json.alert)
                        }

                        if (json.status == "confirmation") {
                            var dialog = jQuery(".admin-form-confirmation");
                            dialog.html(json.html);
                            dialog[0].showModal();
                        }
                    });
                }
            }
            function confirmRemovalSubmission() {
                jQuery.ajax("../authentication/action-authorization.php?attempt=confirm-order-removal")
                    .done(function(response) {
                        var json = jQuery.parseJSON(response);
                        if (json.alert != null) {
                            alert(json.alert);
                        }

                        jQuery(".admin-preview-table input").prop("checked", false);
                        populatePreviewTable();
                    })
                    .fail(function() { alert("There was an internal issue in cancelling/completing the order.") });
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
                <section id="admin-order-controls">
                    <div>
                        <button onclick="navigation('form-add-order.php')">Create Order</button>
                        <button onclick="clickEditOrder()">Edit Order</button>
                    </div>
                    <button onclick="navigation('page-home-admin.php')">Return</button>
                </section>
                <section id="admin-order-preview">
                    <dialog class="admin-form-confirmation" closedby="closerequest">
                    </dialog>
                    <div>
                        <button id="admin-order-details" onclick="clickOrderDetails()">Details</button>
                        <button onclick="clickCompleteOrder()">Compelte Order</button>
                        <button onclick="clickCancelOrder()">Cancel Order</button>
                        <label id="admin-order-filter-label">Search Filter</label>
                        <input id="admin-order-filter" type="search" name="order-filter" placeholder="eg. Senku Ishigami. eg. 876-384-3923" pattern="^[A-Za-z_ \-0-9]+$" aria-labelledby="admin-order-filter-label" onkeyup="populatePreviewTable()"></input>
                        <select id="admin-order-sorting" onchange="populatePreviewTable()"></select>
                        <select id="admin-order-sorting-asc" onchange="populatePreviewTable()">
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