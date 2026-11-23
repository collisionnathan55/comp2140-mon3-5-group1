jQuery(document).ready(function () {
    // stop submit form on enter press
    jQuery(".admin-form input").on("keydown", function(event) {
        if (event.key == "Enter") {
            event.preventDefault();
        }
    })
})