// Page last updated function

document.addEventListener("DOMContentLoaded", function () {

    const lastModified =
        document.querySelector(".lastModified");

    if (lastModified) {
        lastModified.textContent =
            document.lastModified;
    }

});