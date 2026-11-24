<?php require "database-config.php" ?>

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

?>