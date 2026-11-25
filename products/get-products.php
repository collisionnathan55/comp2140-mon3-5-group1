<?php require_once "../database/query-presets.php" ?>

<?php

function getAllProducts() {
    return db_query_products_name("");
}

function filterAvailable(array $products) {
    $available = [];
    foreach($products as $p) {
        if (isAvailable($p)) {
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
function getProductbyId(int $id) {
    return count(db_query_product_id($id)) > 0 ? db_query_product_id($id)[0] : null;
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
function getProductCategories() {
    $names = [];
    $ps = getAllProducts();
    foreach ($ps as $p) {
        if (isset($p["display_category"]) && !in_array($p["display_category"], $names)) {
            array_push($names, $p["display_category"]);
        }
    }
    return $names;
}

// product functions

function isInStock(array $product) {
    if (!isset($product["stock_quantity"]) || !isset($product["reserved_quantity"])) {
        return false;
    }

    $stock = $product["stock_quantity"];
    $reserved = $product["reserved_quantity"];
    return $stock > 0 && $stock > $reserved;
}
function daysToExpiration(array $product) {
    if (!isset($product["expiration_date"])) {
        return 365;
    }

    $expiration = date_create($product["expiration_date"], timezone_open("EST"));
    $today = date_create('now', timezone_open("EST"));

    return $expiration->diff($today)->days * ($expiration > $today ? 1 : -1);
}
function isAvailable(array $product) {
    if (!isset($product["is_archived"])) {
        return false;
    }

    if (isInStock($product) && !$product["is_archived"] && daysToExpiration($product) > 0) {
        return true;
    }

    return false;
}

function getTimelyPrice(array $product) {
    if (daysToExpiration($product) <= 3) {
        return (float) $product["stale_price"];
    }
    return (float) $product["original_price"];
}

?>