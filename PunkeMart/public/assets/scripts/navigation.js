document.addEventListener("DOMContentLoaded", function () {

    const menuButton = document.querySelector(".menuButton");
    const navigation = document.querySelector(".nav-mobile");

    if (!menuButton || !navigation) {
        return;
    }


    // Open and close menu when hamburger is clicked
    menuButton.addEventListener("click", function () {

        navigation.classList.toggle("open");

        const isOpen = navigation.classList.contains("open");

        menuButton.setAttribute("aria-expanded", isOpen);

    });


    // Close menu when clicking outside of navigation
    document.addEventListener("click", function (event) {

        if (
            !navigation.contains(event.target) &&
            !menuButton.contains(event.target)
        ) {

            navigation.classList.remove("open");

            menuButton.setAttribute("aria-expanded", "false");

        }

    });


    // Close menu with Escape key
    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {

            navigation.classList.remove("open");

            menuButton.setAttribute("aria-expanded", "false");

            menuButton.focus();

        }

    });

});