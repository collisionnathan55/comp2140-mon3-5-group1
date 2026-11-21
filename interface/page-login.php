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
                        <input type="password" name="login-password" pattern="^[A-Za-z_ 0-9]+$" aria-labelledby="login-password-label"></input>
                        <label id="login-password-label">Password</label>
                    </section>
                    <button>Log in</button>
                </form>
            </div>
        </main>

        <?php require "instance/copyright.html"?>
    </body>

</html>