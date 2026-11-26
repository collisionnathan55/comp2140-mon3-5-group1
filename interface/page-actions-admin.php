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

            #admin-action-controls {
                width: 15vmax;
                flex-grow: 1;

                padding-right: 1vmax;

                display: flex;
                flex-direction: column;
                justify-content: space-between;
                align-items: center;
            }
            #admin-action-controls > div {
                width: 100%;

                display: flex;
                flex-direction: column;
                justify-content: start;

                gap: 2vmax;
            }
            #admin-action-controls button {
                width: 100%;
            }

            .admin-preview-table {
                table-layout: unset;
            }
            .admin-preview-table td {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .admin-preview-table td:last-of-type {
                width: 55%;
            }

            #admin-action-preview {
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
            #admin-action-preview > div {
                width: 100%;

                display: flex;
                flex-direction: row;

                align-items: start;
                gap: 1vmax;
            }
            #admin-action-sorting, #admin-action-sorting-asc {
                height: 100%;
            }
            #admin-action-preview #admin-action-details {
                padding-left: 2em;
                padding-right: 2em;
            }
        </style>

        <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
        <script src="admin-master.js"></script>
        <script type="text/javascript">
            const tableQueryColumns = ["log_id", "action", "category", "created_at", "description"];
            const tableDisplayColumns = ["ID", "Action", "Category", "Date-Time", "Description"];

            function populateSortingOptions() {
                var html = "";
                tableDisplayColumns.forEach(o => {
                    html += "<option value=\"" + o + "\">" + o + "</option>"
                })
                jQuery("#admin-action-sorting").html(html);
            }

            function populatePreviewTable() {
                jQuery.get("../authentication/action-authorization.php?attempt=get-actions")
                    .done(function(response) {
                        var json = jQuery.parseJSON(response);
                        var actions = json.dataList;
                        var displayedActions = filterAndSort(actions);

                        jQuery(".admin-preview-table").html(createTableFromArrays(displayedActions, tableQueryColumns, tableDisplayColumns));
                        jQuery(".admin-preview-table-check").hide();

                        if (displayedActions.length == 0) {
                            jQuery(".admin-preview-table").html("No action logs available or none match the filter.");
                        }
                    })
                    .fail(function(response) { console.log("Issue getting actions");
                     });
            }

            function filterAndSort(actions) {
                var filteredActions = [];
                var filter = jQuery("#admin-action-filter").val().toLowerCase();

                actions.forEach(a => {
                    var action = a["action"].toLowerCase();
                    var category = a["category"].toLowerCase();
                    var description = a["description"].toLowerCase();
                    var month = new Date(a["created_at"]).toLocaleString('default', { month: 'long' }).toLowerCase();

                    if (action == filter || category == filter || month == filter || a["created_at"].indexOf(filter) == 0 || description.indexOf(filter) != -1) {
                        filteredActions.push(a);
                    }
                });

                var sorting = tableQueryColumns[tableDisplayColumns.indexOf(jQuery("#admin-action-sorting").val())];

                filteredActions.sort(function(a, b) {
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
                if (jQuery("#admin-action-sorting-asc").val() == "1") {
                    filteredActions.reverse();
                }

                return filteredActions;
            }

            // Button events

            function getSelectedActions() {
                var selected = [];
                jQuery(".admin-preview-table tr").get().forEach(row => {
                    var checkbox = jQuery(row).find("input");
                    if (checkbox.get().length > 0 && checkbox[0].checked) {
                        selected.push(checkbox[0].name);
                    }
                })

                return selected;
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
                <section id="admin-action-controls">
                    <button onclick="navigation('page-home-admin.php')">Return</button>
                </section>
                <section id="admin-action-preview">
                    <div>
                        <label id="admin-action-filter-label">Search Filter</label>
                        <input id="admin-action-filter" type="search" name="action-filter" placeholder="eg. April. eg. Product. eg. Edit" pattern="^[A-Za-z_ \-0-9]+$" aria-labelledby="admin-action-filter-label" onkeyup="populatePreviewTable()"></input>
                        <label id="admin-action-sorting-label">Sort By</label>
                        <select id="admin-action-sorting" onchange="populatePreviewTable()" aria-labelledby="admin-action-sorting-label"></select>
                        <select id="admin-action-sorting-asc" onchange="populatePreviewTable()">
                            <option value="1">Descending</option>
                            <option value="0">Ascending</option>
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