<?php require_once "../database/query-presets.php" ?>

<?php

function getAllActions() {
    return db_query_actions_all();
}

?>