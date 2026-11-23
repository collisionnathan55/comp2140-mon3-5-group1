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
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Yaad Goods Store</title>
        <link rel="icon" href="images/business-logo.png" type="image/png"/>
        <link rel="stylesheet" href="master.css"/>
        <link rel="stylesheet" href="page-home.css"/>

        <style>
            #tagline {
                margin-top: 1vmax;
                margin-bottom: 2vmax;

                align-self: center;
            }
            #tagline > div {
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
            #tagline h1 {
                padding-bottom: 2vmin;
            }
            #tagline p {
                font-weight: 500;
                padding-bottom: 1vmin;
            }


            #instagram-carousel {
                align-self: center;

                display: flex;
                flex-direction: column;

                align-items: center;
                justify-items: center;
            }


            #get-started {
                margin-top: 1vmax;
                align-self: center;
            }
            #get-started > h1 {
                margin-bottom: 1vmax;
            }
            #get-started > p {
                margin-bottom: 3rem;

                font-size: large;
                font-weight: 600;
            }
            #get-started > div {
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: center;
                gap: 5rem;
            }
            #continue-blurb {
                margin-left: calc(15% - 4vmax + 2rem);  
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
                margin-bottom: 1rem;

                display: flex;
                flex-direction: row;
                align-items: center;

                font-weight: 600;

                gap: 1.5vw;
            }
            #continue-blurb > button {
                font-size: 0.9em;
                font-weight: 400;
            }

            /* Responsiveness */

            @media (max-width: 768px) {
                #tagline > div {
                    flex-direction: column;
                    align-items: start;
                    gap: 2vmax;
                }
                #tagline > div > button {
                    align-self: center;
                }
                #get-started > p {
                    font-size:small;
                    font-weight: 400;
                    margin-bottom: 1.5rem;
                }
                #continue-blurb {
                    margin-left: calc(10% - 4vmax + 2rem);
                }
            }

        </style>

        <script type="text/javascript" src="navigation.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
    </head>

    <body>
        <header>
            <?php require "instance/navigation-bar.html"?>
        </header>

        <main>
            <div class="bubble-div" id="tagline">
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

            <section class="bubble-div" id="get-started">
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