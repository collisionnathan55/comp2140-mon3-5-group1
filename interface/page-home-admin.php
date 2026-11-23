<?php
    require "../authentication/action-authorization.php";
    if (!authorizePage(basename(__FILE__))) {
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

        <style>
            main {
                justify-content: space-between;
                gap: 2vmax;
            }

            main > div {
                width: 100%;
            }

            #admin-panel {
                padding-top: 2vmax;
                padding-left: 2vmax;

                display: flex;
                flex-direction: column;
                justify-content: space-between;
                align-items: start;

                gap: 3vmax;
            }
            #admin-panel > div {
                width: 70%;
            }
            #admin-panel > div > button {
                float: left;
                margin-right: 1rem;
                margin-bottom: 1rem;
            }

            #logout-btn {
                align-self: flex-end;
                margin-right: 3vw;
            }

            @media (max-width: 768px) {
                main {
                    padding: 1vmax;
                    gap: 5vmax;
                }

                #admin-panel > div {
                    width: 100%;
                    padding-left: 0vmax;
                }
                #admin-panel > div > button {
                    margin-bottom: 2rem;
                }

                #logout-btn {
                    align-self: center;
                    margin-right: 0;
                }
            }
        </style>

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