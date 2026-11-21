const root = "comp2140-mon3-5-group1/";
var rootWindow = window.location.href.substring(0, window.location.href.indexOf(root) + root.length);

function navigation(navTo) {
    window.location.href = rootWindow + "authentication/index.php" + (navTo == null ? "" : "?dest=" + navTo);
}