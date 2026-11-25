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
        <title>Yaad Goods Store</title>
        <link rel="icon" href="images/business-logo.png" type="image/png">
        <link rel="stylesheet" href="master.css">

        <style>
            #order-request-area {
                align-self: center;

                width: 50%;
                height: max-content;

                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: start;

                gap: 2rem;
            }

            #order-request-area > form {
                width: 100%;
                height: 100%;

                display: flex;
                flex-direction: column;

                align-items: start;
                gap: 2rem;
            }
            #order-request-area > form > section {
                width: 100%;

                display: flex;
                flex-direction: column;
            }

            #order-request-area > form input {
                margin-bottom: 0.5em;

                width: 100%;

                font-size: 1.5em;
                text-indent: 1em;
                letter-spacing: 0.4em;

                border-width: 0.1em;
                border-radius: 2em;
                border-color: #005533;
            }
            #order-request-area > form label {
                margin-left: 1em;
            }

            #order-response-area {
                align-self: center;
                margin-top: 2vmax;
                margin-bottom: 2vmax;
                width: 50%;

                display: flex;
                flex-direction: column;
                align-items: start;
                justify-content: start;

                gap: 1vmax;
            }
            #order-response-area > div {
                width: 100%;

                display: grid;
                grid-template-rows: 1fr;
                grid-template-columns: 1fr 1fr 1fr 0.8fr;
                justify-items: start;
                align-items: center;
            }
            #order-response-area button {
                font-size: small;
                justify-self: end;
            }

            @media (max-width: 768px) {
                #order-request-area, #order-response-area {
                    width: 80%;
                }
            }
            @media (max-width: 400px) {
                #order-response-area {
                    font-size: 0.8em;
                }
                #order-response-area button {
                    font-size: x-small;
                }
            }
        </style>

        <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
        <script>
            function submitOrderQuery(event) {
                event.preventDefault();
                var form = event.target;
                var submitData = jQuery(form).serialize();

                jQuery.post(form.action + "?attempt=get-orders-phone", submitData)
                .done(function(response) {
                    var json = jQuery.parseJSON(response);

                    if (json.status == "select") {
                        jQuery("#order-response-area").html(json.html);
                        jQuery("#order-response-area").show();
                    }
                })
            }

            function openOrderDetail(id, phone) {
                window.location.href = "page-order-details.php?phone=" + phone + "&id=" + id;
            }

            jQuery().ready(function() {
                jQuery("#order-response-area").hide();
            })
        </script>
    </head>

    <body>
        <header>
            <?php require "instance/navigation-bar.html"?>
        </header>

        <main>
            <div class="bubble-div" id="order-request-area">
                <h1>Enter Your Phone Number</h1>
                <form onsubmit="submitOrderQuery(event)" action="../authentication/action-authorization.php" method="post">
                    <section>
                        <input type="tel" name="order-phone-number" aria-labelledby="order-phone-number-label" pattern="\d{3}-\d{3}-\d{4}"></input>
                        <label id="order-phone-number-label">Phone Number (876-123-1234)</label>
                    </section>
                    <button>Search for Orders</button>
                </form>
            </div>
            <div class="bubble-div" id="order-response-area">

            </div>
        </main>

        <?php require "instance/copyright.html"?>
    </body>

</html>