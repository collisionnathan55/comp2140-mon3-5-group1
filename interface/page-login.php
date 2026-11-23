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
        <title>Yaad Goods - Login</title>
        <link rel="icon" href="images/business-logo.png" type="image/png">
        <link rel="stylesheet" href="master.css">
        <link rel="stylesheet" href="page-login.css">

        <style>
            #login-area {
                align-self: center;
                margin-top: 5vh;

                width: 50%;
                height: 35vh;

                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: start;

                gap: 2rem;
            }

            #login-area > form {
                width: 100%;
                height: 100%;

                display: flex;
                flex-direction: column;

                align-items: start;
                justify-content: space-between;
            }
            #login-area > form > section {
                width: 100%;

                display: flex;
                flex-direction: column;
            }

            #login-area > form input {
                margin-bottom: 0.5em;

                width: 100%;

                font-size: 2em;
                text-indent: 1em;
                letter-spacing: 0.5em;

                border-width: 0.1em;
                border-radius: 2em;
                border-color: #005533;
            }
            #login-area > form label {
                margin-left: 1em;
            }

            @media (max-width: 768px) {
                #login-area {
                    width: 70%;
                }
            }
        </style>
    </head>

    <body>
        <header>
            <?php require "instance/navigation-bar.html"?>
        </header>

        <main>
            <div class="bubble-div" id="login-area">
                <h1>Admin Portal</h1>
                <form action="../authentication/user-authentication.php" method="post">
                    <section>
                        <input type="password" name="login-password" aria-labelledby="login-password-label"></input>
                        <label id="login-password-label">Password</label>
                    </section>
                    <button>Log in</button>
                </form>
            </div>
        </main>

        <?php require "instance/copyright.html"?>
    </body>

</html>