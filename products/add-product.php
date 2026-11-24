<?php

function packProductQuery(array $request) {
    $obj = [];
    $obj["product-name"] = $request["product-name"] ?? "";
    $obj["product-category"] = $request["product-category"] ?? "";

    $obj["product-price"] = $request["product-price"] ?? 0;
    $obj["product-quantity"] = $request["product-quantity"] ?? 0;
    $obj["product-unit"] = $request["product-unit"] ?? "";
    $obj["product-stock"] = $request["product-stock"] ?? 0;

    $obj["product-freshness"] = $request["product-freshness"] ?? "";

    return $obj;
}

function validateProduct(array $packed_product, bool $edit) {
    $errorMessages = [];

    // fill with data once database is up

    // check for unique product ID

    // send messages for client into error messages

    return $errorMessages;
}

function productConfirmationRequest(array $packed_product) {
    $html = <<<EOT
    <div>
        <h1>Confirmation Request</h1>
        <p>
            Please confirm <b>%s (%s)</b> set to self-expire in %d day(s) on %s. It will be sold at $%d per %d %s(s). There are %d %s(s) in currently stock.
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

function createProduct($packed_product) {

}
?>