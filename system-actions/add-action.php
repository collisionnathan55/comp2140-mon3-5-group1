<?php require_once "get-actions.php" ?>

<?php

// add

function createAction_addProduct(array $packedProduct) {
    try {
        $action = "ADD";
        $category = "PRODUCT";
        $desription = "Created new product %s (%s) to be sold at $%.2f for %.2f. Expiration:%s.";
        $desription = sprintf($desription, $packedProduct["product-name"], $packedProduct["product-category"], $packedProduct["product-price"], $packedProduct["product-quantity"], $packedProduct["product-freshness"]);

        db_add_action($action, $category, $desription);
    }
    catch(Exception $e) {
        error_log("Failed to log action for create product.\n" . $e);
    }
}
function createAction_editProduct(array $oldp, array $newp) {
    try {
        $action = "EDIT";
        $category = "PRODUCT";
        $desription = "Edited " . $oldp["display_name"] . ": ";
        foreach ($newp as $key => $value) {
            if (!in_array($key, ["updated_at"]) && isset($oldp[$key]) && $oldp[$key] != $value) {
                $desription .= $oldp[$key] . "->" . $value . "(" . $key . ") ";
            }
        }

        db_add_action($action, $category, $desription);
    }
    catch(Exception $e) {
        error_log("Failed to log action for edit product.\n" . $e);
    }
}
function createAction_archiveProduct(array $product, bool $toggle) {
    try {
        $action = "ARCHIVE";
        $category = "PRODUCT";
        $desription = ($toggle ? "Archived" : "Unarchived") . " " . $product["display_name"];
        db_add_action($action, $category, $desription);
    }
    catch(Exception $e) {
        error_log("Failed to log action for archive product.\n" . $e);
    }
}
function createAction_deleteProduct(string $pname) {
    try {
        $action = "DELETE";
        $category = "PRODUCT";
        $desription = "Permanently deleted " . $pname . " and related orders.";
        db_add_action($action, $category, $desription);
    }
    catch(Exception $e) {
        error_log("Failed to log action for delete product.\n" . $e);
    }
}

function createAction_editOrder(string $id, string $oname) {
    try {
        $action = "EDIT";
        $category = "ORDER";
        $desription = "Edited order for " . $oname . " (#" . $id . ")";
        db_add_action($action, $category, $desription);
    }
    catch(Exception $e) {
        error_log("Failed to log action for edit product.\n" . $e);
    }
}
function createAction_cancelOrder(string $id, string $oname) {
    try {
        $action = "CANCEL";
        $category = "ORDER";
        $desription = "Cancelled order for " . $oname . " (#" . $id . ") and released its reserves.";
        db_add_action($action, $category, $desription);
    }
    catch(Exception $e) {
        error_log("Failed to log action for cancel order.\n" . $e);
    }
}
function createAction_completeOrder(string $id, string $oname) {
    try {
        $action = "COMPLETE";
        $category = "ORDER";
        $desription = "Completed order for " . $oname . " (#" . $id . ") and reduced its related stock.";
        db_add_action($action, $category, $desription);
    }
    catch(Exception $e) {
        error_log("Failed to log action for complete order.\n" . $e);
    }
}

// remove

function deleteAction(int $id) {
    db_delete_action($id);
}
function deleteAgedActions() {
    $timezone = timezone_open("EST");
    $old = 90;

    $actions = getAllActions();
    foreach ($actions as $action) {
        $timespan = new DateTime($action["created_at"], $timezone)->diff(new DateTime('now', $timezone));
        if ($timespan->days != false && $timespan->days > $old) {
            deleteAction($action["log_id"]);
        }
    }
}
?>