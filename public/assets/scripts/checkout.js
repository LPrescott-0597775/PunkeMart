document.addEventListener("DOMContentLoaded", function () {

    const checkoutForm =
        document.querySelector("#checkout-form");

    const sameAsShippingButton =
        document.querySelector("#same-as-shipping");

    const confirmationModal =
        document.querySelector("#order-confirmation");

    const confirmationNumber =
        document.querySelector("#confirmation-number");

    const closeConfirmation =
        document.querySelector("#close-confirmation");
    
    const checkoutTotal =
        document.querySelector("#checkout-total");


    if (!checkoutForm) {
        return;
    }
    
    const savedCart = localStorage.getItem("cart");

    const cart = savedCart
    ? JSON.parse(savedCart)
    : [];

    let total = 0;

    cart.forEach(function (item) {
        total += item.price * item.quantity;
});

if (checkoutTotal) {
    checkoutTotal.textContent = "$" + total.toFixed(2);
}

    /* ---------- SAME AS SHIPPING BUTTON ---------- */

    sameAsShippingButton.addEventListener(
        "click",
        function () {

            const fields = [
                "first-name",
                "last-name",
                "address",
                "city",
                "state",
                "zip",
                "phone",
                "email"
            ];


            fields.forEach(function (field) {

                const shippingField =
                    document.querySelector(
                        "#shipping-" + field
                    );

                const billingField =
                    document.querySelector(
                        "#billing-" + field
                    );


                if (shippingField && billingField) {
                    billingField.value =
                        shippingField.value;
                }

            });

        }
    );


    /* ---------- PLACE ORDER ---------- */

    checkoutForm.addEventListener(
    "submit",
    function (event) {

        event.preventDefault();

        if (!validateCheckout()) {
            return;
        }

        const orderNumber =
            generateOrderNumber();

        confirmationNumber.textContent =
            orderNumber;

        confirmationModal.hidden = false;

    }
);


    /* ---------- CHECKOUT VALIDATION ---------- */

function validateCheckout() {

    const requiredFields = [
        "shipping-first-name",
        "shipping-last-name",
        "shipping-address",
        "shipping-city",
        "shipping-state",
        "shipping-zip",
        "shipping-phone",
        "shipping-email",
        "billing-first-name",
        "billing-last-name",
        "billing-address",
        "billing-city",
        "billing-state",
        "billing-zip",
        "billing-phone",
        "billing-email"
    ];

    // Every field must have a value

    for (const fieldId of requiredFields) {

        const field =
            document.getElementById(fieldId);

        if (!field || field.value.trim() === "") {

            alert("Please complete all shipping and billing fields.");

            if (field) {
                field.focus();
            }

            return false;
        }
    }


    // ZIP validation

    const zipPattern = /^[0-9]{5}$/;

    const shippingZip =
        document.getElementById("shipping-zip");

    const billingZip =
        document.getElementById("billing-zip");

    if (
        !zipPattern.test(shippingZip.value) ||
        !zipPattern.test(billingZip.value)
    ) {

        alert("ZIP code must contain exactly 5 digits.");

        return false;
    }


    // Phone validation: 000-000-0000

    const phonePattern =
        /^[0-9]{3}-[0-9]{3}-[0-9]{4}$/;

    const shippingPhone =
        document.getElementById("shipping-phone");

    const billingPhone =
        document.getElementById("billing-phone");

    if (
        !phonePattern.test(shippingPhone.value) ||
        !phonePattern.test(billingPhone.value)
    ) {

        alert(
            "Phone number must use the format 806-555-1234."
        );

        return false;
    }


    // Email validation

    const emailPattern =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    const shippingEmail =
        document.getElementById("shipping-email");

    const billingEmail =
        document.getElementById("billing-email");

    if (
        !emailPattern.test(shippingEmail.value) ||
        !emailPattern.test(billingEmail.value)
    ) {

        alert(
            "Please enter a valid email address."
        );

        return false;
    }


    // Payment method validation

    const paymentMethod =
        document.querySelector(
            'input[name="payment-method"]:checked'
        );

    if (!paymentMethod) {

        alert("Please select a payment method.");

        return false;
    }


    return true;
}


    /* ---------- TEMPORARY ORDER NUMBER ---------- *
     * PHP/database order IDs can replace this later without changing form */

    function generateOrderNumber() {

        const randomNumber =
            Math.floor(
                100000 + Math.random() * 900000
            );


        return "PM-" + randomNumber;

    }


    /* ---------- CLOSE CONFIRMATION ---------- *
     * Closing the confirmation: clears the cart and returns to the home page */

    closeConfirmation.addEventListener(
        "click",
        function () {

            clearCompletedCart();

            window.location.href =
                "../index.html";

        }
    );


    /* ---------- CLEAR CART ---------- */

    function clearCompletedCart() {

       localStorage.removeItem("cart");

    }

});
