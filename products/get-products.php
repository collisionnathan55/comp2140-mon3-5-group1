<?php require "../database/presets.php" ?>

<?php

function getAllProducts() {
    return db_query_products_name("");
}

function filterAvailable(array $products) {
    $available = [];
    foreach($products as $p) {
        if (!isset($p["stock_quantity"]) || !isset($p["reserved_quantity"]) || !isset($p["is_archived"])) {
            continue;
        }

        $stock = $p["stock_quantity"];
        $reserved = $p["reserved_quantity"];
        $archived = $p["is_archived"];

        if ($stock > 0 && $stock > $reserved && !$archived) {
            array_push($available, $p);
        }
    }

    return $available;
}

function getProductsbyName(string $product_name) {
    $alpha = '/^[A-Za-z ]+$/';
    if (preg_match($alpha, $product_name)) {
        return db_query_products_name($product_name);
    }

    return [];
}

function getProductID(string $product_name) {
    $ps = getAllProducts();
    foreach ($ps as $p) {
        if (isset($p["product_id"]) && isset($p["display_name"]) && $p["display_name"] == $product_name) {
            return $p["product_id"];
        }
    }

    return -1;
}

function getProductNames() {
    $names = [];
    $ps = getAllProducts();
    foreach ($ps as $p) {
        if (isset($p["display_name"]) && !in_array($p["display_name"], $names)) {
            array_push($names, $p["display_name"]);
        }
    }
    return $names;
}

function allProductCategories() {
    $names = [];
    $ps = getAllProducts();
    foreach ($ps as $p) {
        if (isset($p["display_category"]) && !in_array($p["display_category"], $names)) {
            array_push($names, $p["display_category"]);
        }
    }
    return $names;
}

?>