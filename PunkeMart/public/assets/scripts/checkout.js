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


    /* ---------- CALCULATE ORDER TOTAL ---------- */

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
        async function (event) {

            event.preventDefault();

            // Validate the checkout information
            if (!validateCheckout()) {
                return;
            }

            // Retrieve the current shopping cart
            const cart = getCart();

            if (!cart.length) {
                alert("Your cart is empty.");
                return;
            }

            // Prevent multiple order submissions
            const button = checkoutForm.querySelector(
                'button[type="submit"]'
            );

            if (button.disabled) {
                return;
            }

            button.disabled = true;

            try {

                // Collect shipping, billing, and payment information
                const data = new FormData(checkoutForm);

                // Add the cart items and quantities
                data.append(
                    "cart",
                    JSON.stringify(
                        cart.map(item => ({
                            itemID: Number(item.number),
                            quantity: Number(item.quantity)
                        }))
                    )
                );

                // Send the order information to PHP
                const response = await fetch(
                    "checkout_submit.php",
                    {
                        method: "POST",
                        body: data
                    }
                );

                // Read the response from PHP
                const result = await response.json();

                // Check whether the order was saved
                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || "Could not save order."
                    );
                }

                // Display the actual database order ID
                confirmationNumber.textContent =
                    "PM-" + result.orderID;

                // Clear the completed shopping cart
                clearCompletedCart();

                // Display the confirmation popup
                confirmationModal.hidden = false;

            } catch (error) {

                alert(
                    error.message || "Could not save order."
                );

                // Allow the customer to try again
                button.disabled = false;

            }

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

                alert(
                    "Please complete all shipping and billing fields."
                );

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


    /* ---------- CLOSE CONFIRMATION ---------- */

    closeConfirmation.addEventListener(
        "click",
        function () {

            clearCompletedCart();

            window.location.href = "index.html";

        }
    );


    /* ---------- CLEAR CART ---------- */

    function clearCompletedCart() {

        localStorage.removeItem("cart");

    }

});
