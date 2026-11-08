
document.addEventListener("DOMContentLoaded", function () {

    const shop = document.querySelector("main");

    if (!shop) {
        return;
    }

    /* ADD TO CART */
    shop.addEventListener("click", function (event) {

        const addButton = event.target.closest(".add-to-cart");

        if (!addButton) {
            return;
        }

        const productCard = addButton.closest(".product-card");

        if (!productCard) {
            return;
        }

        const productId = productCard.dataset.productId;
        const productName = productCard.dataset.productName;
        const productPrice = Number(productCard.dataset.productPrice);
        const productImage = productCard.querySelector(".shopImg").src;

        /* Item Quantity */
        const quantityInput =
            productCard.querySelector(".product-quantity");

        const quantity = quantityInput
            ? Number(quantityInput.value)
            : 1;

        /* ADD ITEM TO CART */
        addToCart(
            productId,
            productName,
            productPrice,
            quantity,
            productImage
        );

    });

});
