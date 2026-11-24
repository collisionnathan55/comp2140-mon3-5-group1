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

function createTableFromArrays(a_arrays, columns, aliases = null) {
    if (aliases == null) {
        aliases = [];
        columns.forEach(c => {
            aliases.push(c.replaceAll("_", " "));
        })
    }

    var table = "<tr><th class=\"admin-preview-table-check\"/>";
    aliases.forEach(a => {
        table += "<th>" + a + "</th>";
    })
    table += "</tr>";

    a_arrays.forEach(row => {
        table += "<tr>";
        table += "<td class=\"admin-preview-table-check\"><input type=\"checkbox\" name=\"" + row[Object.keys(row)[0]] + "\"/></td>"
        
        columns.forEach(c => {
            table += "<td id=\"" + c + "\">" + row[c] + "</td>";
        })
        table += "</tr>";
    })

    return table;
}