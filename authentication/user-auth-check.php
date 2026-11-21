<?php

?>

<?php

if (!session_start(["cookie_samesite" => "Strict", "cookie_lifetime" => 300])) {
    //3600
    http_response_code(500);
    exit();
}

echo session_id();

?>