jQuery(document).ready(function () {
    // stop submit form on enter press
    jQuery(".admin-form input").on("keydown", function(event) {
        if (event.key == "Enter") {
            event.preventDefault();
        }
    })

    // highlight all required form fields
    document.querySelectorAll(".admin-form-grid div").forEach(item => {
        var inputs = item.querySelectorAll("input, textarea");
        if (inputs.length > 0) {
            if (inputs[0].required) {
                var labels = item.querySelectorAll("label");
                labels.forEach(label => {
                    label.style.fontWeight = 900;
                });
            }
        }
    });
})