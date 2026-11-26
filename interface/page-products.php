<?php
    require "../authentication/action-authorization.php";
    if (!authorizePage(basename(__FILE__))) {
        exit();
    }

    require_once "../products/get-products.php";
    $productList = getAllProducts();

    $preFilter = "";
    if (isset($_GET["val"])) {
        $preFilter = $_GET["val"];
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
            }

            input, select {
                text-indent: 0.5rem;
                letter-spacing: 0.1rem;
                border-style: hidden;
                border-radius: 1rem;
            }
            select {
                padding-left: 1vmin;
                padding-right: 3vmin;
                text-overflow: ellipsis;
            }

            #product-toolbar {
                padding: 1vmax;
                padding-left: 3vw;
                padding-right: 3vw;
                margin-bottom: 2vmax;
                
                width: 100%;
                box-sizing: border-box;

                display: flex;
                flex-direction: row;
                justify-content: space-between;

                border-style: solid;
                border-width: 0.2em 0em 0em 0em;
                border-color: #DBDBDB;
            }
            #product-toolbar * {
                font-size: medium;
            }
            #product-toolbar label {
                font-weight: 600;
                text-transform: uppercase;
            }

            #product-toolbar > div {
                display: flex;
                flex-direction: row;
                gap: 1vmax;
            }

            #product-display-area {
                width: 100%;
                box-sizing: border-box;

                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: start;
                gap: 3vmax;
            }
            #product-display-area > .bubble-div {
                width: max-content;
                padding: 2vmax;
            }
            #product-display-area > div {
                display: flex;
                flex-direction: column;
                align-items: start;
                justify-content: space-between;
                gap: 1vmin;
            }
            #product-display-area > div > section {
                display: flex;
                flex-direction: column;
                align-items: start;
                gap: 1vmin;
            }
            #product-display-area h3 {
                align-self: center;
                text-align: center;
                font-weight: 900;
                margin-bottom: 1vmax;
            }
            #product-display-area h5 {
                align-self: center;
                text-align: center;
                font-weight: 600;
            }
            #product-display-area > div p span {
                text-decoration: line-through;
            }
            #product-display-area > div button {
                margin-top: 1vmax;
                font-size: small;
                align-self: end;
            }
        </style>

        <script type="text/javascript" src="navigation.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>

        <script type="text/javascript">
            const queryColumns = ["display_name", "display_category", "timely_price", "expiration_date"];
            const sortColumns = ["Name", "Category", "Price", "Expiration"];

            function populateSortingOptions() {
                var html = "";
                sortColumns.forEach(o => {
                    html += "<option value=\"" + o + "\">" + o + "</option>"
                })
                jQuery("#product-sorting").html(html);
            }

            function populateProductList() {
                jQuery.get("../authentication/action-authorization.php?attempt=get-products-available")
                    .done(function(response) {
                        var json = jQuery.parseJSON(response);
                        var products = json.dataList;
                        var displayedProducts = filterAndSort(products);

                        jQuery("#product-display-area").html(createProductDisplay(displayedProducts));

                        if (displayedProducts.length == 0) {
                            jQuery("#product-display-area").html("No products available or none match the filter.");
                        }
                    })
                    .fail(function(response) { console.log("Issue getting products");
                     });
            }

            function filterAndSort(products) {
                var filteredProducts = [];
                var catFilter = jQuery("#product-filter-category").val();
                var filter = jQuery("#product-filter").val().toLowerCase();

                products.forEach(p => {
                    var category = p["display_category"];
                    var name = p["display_name"].toLowerCase();

                    if (catFilter.toUpperCase() == "ALL" || category == catFilter) {
                        if (name.indexOf(filter) == 0) {
                            filteredProducts.push(p);
                        }
                    }
                });

                var sorting = queryColumns[sortColumns.indexOf(jQuery("#product-sorting").val())];

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
                if (jQuery("#product-sorting-asc").val() == "1") {
                    filteredProducts.reverse();
                }

                return filteredProducts;
            }

            function createProductDisplay(products) {
                var elementList = ""
                products.forEach(product => {
                    var quantity = Number.parseFloat(product["sell_quantity"]);
                    var unit = product["unit_of_measure"]

                    var element = "<div class='bubble-div'><section>"
                    element += "<h3>" + product["display_name"] + "</h3>"
                    //element += "<h5>(" + product["display_category"] + ")</h5>"

                    element += "<p>Sold as <b>" + quantity + "</b> " + unit + (quantity == 1 ? "" : "s") + " for <b>$" + Number.parseFloat(product["timely_price"]).toFixed(2) + "</b></p>"
                    if (product["timely_price"] != product["original_price"]) {
                        element+= "<p style='font-size:0.9em;'>Was: <span>$" + Number.parseFloat(product["original_price"]).toFixed(2) + "</span><p>"
                    }

                    element += "</section><section><p>" + product["available_groups"] + " order" + (product["available_groups"] == 1 ? "" : "s") + " available.</p>"
                    element += "<p>Best before: <b>" + product["expiration_date"] + "</b></p>";

                    element += "<button onclick='navigation(\"page-product-details.php\", " + product["product_id"] + ")'>Details</section></button></div>"
                    elementList += element;
                });

                return elementList;
            }

            jQuery().ready(function(event) {
                populateSortingOptions();
                populateProductList();
            })
        </script>
    </head>

    <body>
        <header>
            <?php require "instance/navigation-bar.html"?>
        </header>

        <main>
            <small class="bubble-div" id="product-toolbar">
                <div>
                    <label id="product-filter-category-label">Category</label>
                    <select id="product-filter-category" onchange="populateProductList()" aria-labelledby="product-filter-category-label" style="text-transform: uppercase;">
                        <option value="All">All</option>
                        <?php
                        foreach (getProductCategories() as $cat) {
                            echo "<option value=\"" . $cat . "\">" . $cat . "</option>";
                        } 
                        ?>
                    </select>
                </div>
                <div>
                    <label id="product-filter-label">Item</label>
                    <input id="product-filter" type="search" name="product-filter" placeholder="eg. Apple" pattern="^[A-Za-z_ ]+$" aria-labelledby="product-filter-name-label" onchange="populateProductList()" value="<?php echo $preFilter ?>"></input>
                    <label id="product-sorting-label">Sort By</label>
                    <select id="product-sorting" onchange="populateProductList()" aria-labelledby="product-sorting-label"></select>
                    <select id="product-sorting-asc" onchange="populateProductList()">
                        <option value="0">Ascending</option>
                        <option value="1">Descending</option>
                    </select>
                </div>
            </small>
            <section id="product-display-area">

            </section>
        </main>

        <?php require "instance/copyright.html"?>
    </body>

</html>