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
        <link rel="stylesheet" href="page-products-admin.css">

        <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
    </head>

    <body>
        <header>
            <?php require "instance/navigation-bar.html"?>
        </header>

        <main class="admin">
            <?php include "instance/admin-header.html" ?>
            <div>
                <section id="admin-product-controls">
                    <button onclick="navigation('form-add-product.php')">Create Product</button>
                    <button>Edit Product</button>
                    <button>Archive Selected</button>
                </section>
                <section id="admin-product-preview">
                    <div>
                        <label id="admin-product-filter-label">Search Filter</label>
                        <input id="admin-product-filter" type="search" name="product-filter" placeholder="eg. Apple" pattern="^[A-Za-z_ ]+$" aria-labelledby="admin-product-filter-label"></input>
                    </div>
                    <table>

                    </table>
                </section>
            </div>
        </main>

        <?php require "instance/copyright.html"?>
    </body>

</html>