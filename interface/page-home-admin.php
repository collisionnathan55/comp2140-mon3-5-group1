<?php
    require "../authentication/user-auth-check.php";
    if (!checkPageAuth(basename(__FILE__))) {
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Yaad Goods - Admin</title>
        <link rel="icon" href="images/business-logo.png" type="image/png">
        <link rel="stylesheet" href="master.css">
        <link rel="stylesheet" href="admin-master.css">
        <link rel="stylesheet" href="page-home-admin.css">

        <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
    </head>

    <body>
        <header>
            <?php require "instance/navigation-bar.html"?>
        </header>

        <main class="admin">
            <div>
                <?php include "instance/admin-header.html" ?>
                <section id="admin-panel">
                    <div>
                        <button onclick="navigation('page-products-admin.php')">Enter</button>
                        <h3>Manage Products</h3>
                        <p>Add products to the store database. View and edit products in the store database. Archive or delete products as necessary.</p>
                    </div>
                    <div>
                        <button onclick="navigation('page-orders-admin.php')">Enter</button>
                        <h3>Manage Orders</h3>
                        <p>Manually create and edit orders as processing requires. View orders and mark them for cancel or completion.</p>
                    </div>
                    <div>
                        <button>Enter</button>
                        <h3>View System Actions</h3>
                        <p>View the last 30 administrative system additions, edits and removals.</p>
                    </div>
                    <div>
                        <button>Enter</button>
                        <h3>Notification Configuration</h3>
                        <p>Setup channels to receive system notifications on product stock and order statuses.</p>
                    </div>
                </section>
            </div>

            <form id="logout-btn" action="../authentication/user-authentication.php" method="post">
                <input type="hidden" name="logout" value="true"></input>
                <button>Logout</button>
            </form>
        </main>

        <?php require "instance/copyright.html"?>
    </body>

</html>