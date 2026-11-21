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

        <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
    </head>

    <body>
        <header>
            <?php require "instance/navigation-bar.html"?>
        </header>

        <main>
            <p>Pretend there's an admin menu here.</p>
        </main>

        <?php require "instance/copyright.html"?>
    </body>

</html>