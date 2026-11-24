<?php require "get-products.php" ?>

<?php

// validate

function validateProduct(array $packed_product, bool $edit) {
    $errorMessages = [];

    if (!isset($packed_product["product-name"]) || $packed_product["product-name"] == "") {
        array_push($errorMessages, "Display name required.");
    }
    if (isset($packed_product["product-name"])) {
        if (!$edit && in_array($packed_product["product-name"], getProductNames())) {
            array_push($errorMessages, $packed_product["product-name"] . " already exists.");
        }
    }

    return $errorMessages;
}

// add

function createProduct(array $p) {
    try {
        db_add_product($p["product-category"], $p["product-name"], $p["product-description"], $p["product-price"], $p["product-quantity"], $p["product-stock"], $p["product-unit"], $p["product-freshness"], $p["product-spoil"]);
    }
    catch(Exception $e) {
        throw new RuntimeException("Failed to add product.");
    }
}

// edit

function updateProduct(array $p) {
    try {
        db_update_product($p["product-id"], $p["product-category"], $p["product-description"], $p["product-price"], $p["product-quantity"], $p["product-stock"], $p["product-reserved"], $p["product-unit"], $p["product-freshness"], $p["product-spoil"]);
    }
    catch(Exception $e) {
        throw new RuntimeException("Failed to update product.");
    }
}
function toggleArchiveProduct(int $id, bool $archive) {
    if (getProductbyId($id) != null) {
        try {
            db_archive_product($id, $archive);
        }
        catch (Exception $e) {
            throw new RuntimeException("Failed to update product.");
        }
    }
}

// utils

function packProductQuery(array $request) {
    $obj = [];

    $obj["product-id"] = $request["product-id"] ?? -1;
    $obj["product-name"] = $request["product-name"] ?? "";
    $obj["product-category"] = $request["product-category"] ?? "";
    $obj["product-description"] = $request["product-description"] ?? "";

    $obj["product-price"] = $request["product-price"] ?? 0.00;
    $obj["product-quantity"] = $request["product-quantity"] ?? 0.00;
    $obj["product-stock"] = $request["product-stock"] ?? 0.00;
    $obj["product-reserved"] = $request["product-reserved"] ?? 0.00;
    $obj["product-unit"] = $request["product-unit"] ?? "";

    $obj["product-freshness"] = $request["product-freshness"] ?? date_create('now', timezone_open("EST"))->add(new DateInterval('P7D'))->format("Y-m-d");
    $obj["product-spoil"] = $request["product-spoil"] ?? $obj["product-price"];

    return $obj;
}
function productConfirmationRequest(array $packed_product) {
    $html = <<<EOT
    <div>
        <h1>Confirmation Request</h1>
        <p>
            Please confirm <b>%s (%s)</b> set to self-expire in %d day(s) on %s. It will be sold at $%.2f per %.2f %s(s). There are %.2f %s(s) in currently stock.
        </p>
    </div> 
    <form method="dialog">
        <button onclick="confirmLastSubmission()">Confirm</button>
        <button>Cancel</button>
    </form>
    EOT;

    $timezone = timezone_open("EST");
    $expiration = date_create($packed_product["product-freshness"], $timezone);
    $duration = $expiration->diff(date_create('now', $timezone))->days;

    return sprintf($html, 
        $packed_product["product-name"], $packed_product["product-category"], $duration, $packed_product["product-freshness"],
        $packed_product["product-price"], $packed_product["product-quantity"], $packed_product["product-unit"],
        $packed_product["product-stock"], $packed_product["product-unit"]
    );
}
function productRemovalConfirmationRequest(array $ids, string $method) {
    $html = <<<EOT
    <div>
        <h1>Confirmation Request</h1>
        <p>Please confirm the %s of the following products:</p>
        %s
    </div>
    <form method="dialog">
        <button onclick="confirmRemovalSubmission()">Confirm</button>
        <button>Cancel</button>
    </form>
    EOT;

    $products = "<ul>";
    foreach ($ids as $id) {
        $p = getProductbyId($id);
        if ($p != null) {
            $products .= "\n<li>" . $p["display_name"] . "</li>";
        }
    }
    $products .= "</ul>";

    return sprintf($html, $method, $products);
}
?>