<?php
    require "../authentication/user-auth-check.php";
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Yaad Goods Store</title>
        <link rel="icon" href="images/business-logo.png" type="image/png">
        <link rel="stylesheet" href="master.css">
        <link rel="stylesheet" href="page-home.css">
        <script type="text/javascript" src="navigation.js"></script>
    </head>

    <body>
        <header>
            <?php require "instance/navigation-bar.html"?>
        </header>

        <main>
            <div id="tagline">
                <h1>Fresh. Natural. Jamaican.</h1>
                <div>
                    <div>
                        <p>Top quality fruits and vegetables in Jamaica🍍🍉🥬. Wholesale and retail options available.</p>
                        <p>Visit us in person on the UWI Mona campus!</p>
                    </div>
                    <button>Find Us</button>
                </div>
            </div>

            <div id="instagram-carousel">
                <?php include "instance/home-page-instagram.html" ?>
            </div>

            <section id="get-started">
                <h1>Ready to make an order?</h1>
                <p>Browse our products and add them to your cart and finalize your order whenever you're ready. Find us on campus to pay and collect, or request delivery.</p>
                <div>
                    <button>Browse all Products</button>
                    <button>Make an Order</button>
                </div>
            </section>
            <small id="continue-blurb">
                <p>Already have an order?</p>
                <button>Track it here</button>
            </small>
        </main>

        <?php require "instance/copyright.html"?>
    </body>

</html>