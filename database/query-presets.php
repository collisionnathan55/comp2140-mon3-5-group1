<?php require_once "database-config.php" ?>

<?php

// Queries

function db_query_product_id(int $id) {
    $stmt = pdo()->prepare("SELECT * FROM products WHERE products.product_id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function db_query_products_name(string $product)
{
    $stmt = pdo()->prepare("SELECT * FROM products WHERE products.display_name LIKE :product");
    $param = $product . "%";
    $stmt->bindParam(":product", $param, PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function db_query_orders_all() {
    $stmt = pdo()->prepare("SELECT * FROM orders");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function db_query_order_id(int $id) {
    $stmt = pdo()->prepare("SELECT * FROM orders WHERE orders.order_id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function db_query_orders_name(string $customerName) {
    $stmt = pdo()->prepare("SELECT * FROM orders WHERE orders.customer_name LIKE :name");
    $param = $customerName . "%";
    $stmt->bindParam(":name", $param, PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function db_query_orders_phone(string $phone) {
    $stmt = pdo()->prepare("SELECT * FROM orders WHERE orders.customer_phone LIKE :phone");
    $stmt->bindParam(":phone", $phone, PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function db_query_orderItems_order(int $order_id) {
    $stmt = pdo()->prepare("SELECT * FROM order_items WHERE order_items.order_id = :id");
    $stmt->bindParam(":id", $order_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function db_query_orderItems_product(int $product_id) {
    $stmt = pdo()->prepare("SELECT * FROM order_items WHERE order_items.product_id = :id");
    $stmt->bindParam(":id", $product_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Additions

function db_add_product(string $category, string $name, string $description, float $price, float $sell, float $stock, string $unit, string $expiration, float $stale_price) {
    $stmt = pdo()->prepare("INSERT INTO products (display_category, display_name, description, original_price, sell_quantity, stock_quantity, unit_of_measure, expiration_date, stale_price) VALUES (:category, :name, :description, :salePrice, :saleAm, :stock, :unit, :expiration, :spoilPrice)");
    $stmt->bindParam(":category", $category, PDO::PARAM_STR);
    $stmt->bindParam(":name", $name, PDO::PARAM_STR);
    $stmt->bindParam(":description", $description, PDO::PARAM_STR);

    $stmt->bindParam(":salePrice", $price, PDO::PARAM_STR); // no param type for decimals
    $stmt->bindParam(":saleAm", $sell, PDO::PARAM_STR);
    $stmt->bindParam(":stock", $stock, PDO::PARAM_STR);

    $stmt->bindParam(":unit", $unit, PDO::PARAM_STR);

    $stmt->bindParam(":expiration", $expiration, PDO::PARAM_STR);
    $stmt->bindParam(":spoilPrice", $stale_price, PDO::PARAM_STR);
    
    $stmt->execute();
}

// Edits

function db_update_product(int $id, string $category, string $description, float $price, float $sell, float $stock, float $reserve, string $unit, string $expiration, float $stale_price) {
    $stmt = pdo()->prepare("UPDATE products SET display_category = :category, description = :description, original_price = :salePrice, sell_quantity = :saleAm, stock_quantity = :stock, reserved_quantity = :reserved, unit_of_measure = :unit, expiration_date = :expiration, stale_price = :spoilPrice WHERE products.product_id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);

    $stmt->bindParam(":category", $category, PDO::PARAM_STR);
    $stmt->bindParam(":description", $description, PDO::PARAM_STR);

    $stmt->bindParam(":salePrice", $price, PDO::PARAM_STR); // no param type for decimals
    $stmt->bindParam(":saleAm", $sell, PDO::PARAM_STR);
    $stmt->bindParam(":stock", $stock, PDO::PARAM_STR);
    $stmt->bindParam(":reserved", $reserve, PDO::PARAM_STR);

    $stmt->bindParam(":unit", $unit, PDO::PARAM_STR);

    $stmt->bindParam(":expiration", $expiration, PDO::PARAM_STR);
    $stmt->bindParam(":spoilPrice", $stale_price, PDO::PARAM_STR);
    
    $stmt->execute();
}
function db_archive_product(int $id, bool $archived) {
    $stmt = pdo()->prepare("UPDATE products SET is_archived = :archived WHERE products.product_id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->bindParam(":archived", $archived, PDO::PARAM_BOOL);
    $stmt->execute();
}
function db_update_product_stock(int $id, float $stock, float $reserve) {
    $stmt = pdo()->prepare("UPDATE products SET stock_quantity = :stock, reserved_quantity = :reserve WHERE products.product_id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->bindParam(":stock", $stock, PDO::PARAM_STR);
    $stmt->bindParam(":reserve", $reserve, PDO::PARAM_STR);
    $stmt->execute();
}

function db_update_order_status(int $id, string $status) {
    $stmt = pdo()->prepare("UPDATE orders SET order_status = :status WHERE orders.order_id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->bindParam(":status", $status, PDO::PARAM_STR);
    $stmt->execute();
}

// Deletes

function db_delete_product(int $id) {
    $stmt = pdo()->prepare("DELETE FROM products WHERE products.product_id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);

    $stmt->execute();
}

function db_delete_orderItem(int $id) {
    $stmt = pdo()->prepare("DELETE FROM order_items WHERE order_items.order_item_id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
}
function db_delete_order(int $id) {
    $stmt = pdo()->prepare("DELETE FROM orders WHERE orders.order_id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
}

?>