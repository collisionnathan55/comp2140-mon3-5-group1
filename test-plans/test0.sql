DELETE FROM order_items;
DELETE FROM orders;
DELETE FROM products;
DELETE FROM action_logs;

INSERT INTO products (product_id, display_category, display_name, description, original_price, sell_quantity, stock_quantity, reserved_quantity, expiration_date, stale_price) VALUES (1, "Apple", "Granny", "An apple cultivar that originated in Australia in 1868.", 500, 2, 8, 3, '2026-12-31', 150);
INSERT INTO products (product_id, display_category, display_name, description, original_price, sell_quantity, stock_quantity, reserved_quantity, expiration_date, stale_price) VALUES (2, "Dragonfruit", "Pitaya", "This is a dragonfruit.", 1000, 1, 9, 4, '2027-12-31', 500);
INSERT INTO products (product_id, display_category, display_name, description, original_price, sell_quantity, stock_quantity, reserved_quantity, expiration_date, stale_price) VALUES (3, "Apple", "Green", "This is NOT a dragonfruit.", 700, 1, 5, 0, '2026-11-30', 100);
INSERT INTO products (product_id, display_category, display_name, description, original_price, sell_quantity, stock_quantity, reserved_quantity, expiration_date, stale_price) VALUES (4, "Banana", "Banana", "Curveball!", 300, 1, 13, 8, '2026-11-30', 100);

INSERT INTO orders (order_id, customer_name, customer_phone, total_cost) VALUES (1, "Senku Ishigami", "876-314-1592", 3000);
INSERT INTO order_items (order_id, product_id, quantity, stored_unit_price) VALUES (1, 2, 3, 1000);

INSERT INTO orders (order_id, customer_name, customer_phone, total_cost) VALUES (2, "Johnny", "876-001-0002", 2500);
INSERT INTO order_items (order_id, product_id, quantity, stored_unit_price) VALUES (2, 2, 1, 1000);
INSERT INTO order_items (order_id, product_id, quantity, stored_unit_price) VALUES (2, 1, 3, 500);

INSERT INTO orders (order_id, customer_name, customer_phone, total_cost) VALUES (3, "DK", "876-590-3000", 900);
INSERT INTO order_items (order_id, product_id, quantity, stored_unit_price) VALUES (3, 4, 3, 300);

INSERT INTO orders (order_id, customer_name, customer_phone, total_cost) VALUES (4, "DK", "876-590-3000", 1500);
INSERT INTO order_items (order_id, product_id, quantity, stored_unit_price) VALUES (4, 4, 5, 300);

INSERT INTO action_logs (log_id, action, category, description) VALUES (1, "ADD", "PRODUCT", "Created new product Granny under Apple to be sold at $500.00 for 2.00 item(s). Expiration:2026-12-31.");
INSERT INTO action_logs (log_id, action, category, description) VALUES (2, "ADD", "PRODUCT", "Created new product Pitaya under Dragonfruit to be sold at $1000.00 for 1.00 item(s). Expiration:2027-12-31.");
INSERT INTO action_logs (log_id, action, category, description) VALUES (3, "ADD", "PRODUCT", "Created new product Green under Apple to be sold at $700.00 for 1.00 item(s). Expiration:2026-11-30.");
INSERT INTO action_logs (log_id, action, category, description) VALUES (4, "ADD", "PRODUCT", "Created new product Banana under Banana to be sold at $300.00 for 1.00 item(s). Expiration:2026-11-30.");

INSERT INTO action_logs (log_id, action, category, description) VALUES (5, "EDIT", "PRODUCT", "Dummy Edit");