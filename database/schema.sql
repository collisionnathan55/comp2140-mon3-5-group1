
CREATE DATABASE IF NOT EXISTS yaad_goods
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE yaad_goods;


-- =====================================
-- 1. PRODUCTS
-- =====================================

CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    display_category VARCHAR(150) NOT NULL,
    display_name VARCHAR(150) NOT NULL UNIQUE,
    description TEXT,

    original_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    sell_quantity DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    stock_quantity DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    reserved_quantity DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    unit_of_measure VARCHAR(20) NOT NULL DEFAULT 'item',

    expiration_date DATE NOT NULL DEFAULT CURRENT_DATE,
    stale_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    -- Internal Fields

    is_archived TINYINT(1) NOT NULL DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_product_name (display_name)
) ENGINE=InnoDB;


-- =====================================
-- 2. USERS
-- =====================================

/* CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB; */


-- =====================================
-- 3. ORDERS
-- =====================================

CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    order_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_order_date (order_date),
    KEY idx_order_status (order_status)
) ENGINE=InnoDB;


-- =====================================
-- 4. ORDER ITEMS
-- =====================================

CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    stored_unit_price DECIMAL(10,2) NOT NULL,

    UNIQUE KEY unique_order_product (order_id, product_id),

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;


-- =====================================
-- 5. ACTION LOGS
-- =====================================

CREATE TABLE IF NOT EXISTS action_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    KEY idx_action_created_at (created_at),

    CONSTRAINT fk_action_logs_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;
