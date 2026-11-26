DELETE FROM order_items;
DELETE FROM orders;
DELETE FROM products;
DELETE FROM action_logs;

INSERT INTO action_logs (action, category, description, created_at) VALUES ("ADD", "PRODUCT", "Dummy1", "2026-11-25 18:58:59");
INSERT INTO action_logs (action, category, description, created_at) VALUES ("ADD", "PRODUCT", "Dummy1", "2026-10-25 18:58:59");

INSERT INTO action_logs (action, category, description, created_at) VALUES ("EDIT", "PRODUCT", "Dummy1", "2025-11-25 18:58:59");
INSERT INTO action_logs (action, category, description, created_at) VALUES ("EDIT", "PRODUCT", "Dummy1", "2026-05-25 18:58:59");