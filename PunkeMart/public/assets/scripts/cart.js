// 1. GET THE CURRENT CART
function getCart() {

    const savedCart = localStorage.getItem("cart");

    // If there is no cart saved yet, return an empty array
    if (!savedCart) {
        return [];
    }

    // Turn the saved text back into JavaScript data
     try {
        return JSON.parse(savedCart);
    } catch (error) {
        console.error("Could not read shopping cart:", error);
        return [];
    }
}

// 2. SAVE THE CART
function saveCart(cart) {

    // Convert the cart into text and save it in the browser
    localStorage.setItem("cart", JSON.stringify(cart));
}

// 3. ADD AN ITEM TO THE CART
function addToCart(itemNumber, itemName, price, quantity = 1, size = "") {

    // Get whatever is already in the cart
    const cart = getCart();

    // Make sure quantity is a number
    quantity = Number(quantity);

    // Make sure price is a number
    price = Number(price);

    // Look for an item that is already in the cart
    // with the same item number AND the same size
    const existingItem = cart.find(item =>
        item.number === itemNumber &&
        item.size === size
    );
    // If the item is already in the cart
    if (existingItem) {
        // Increase its quantity instead of creating
        // another separate cart entry
        existingItem.quantity += quantity;
    }
    // If this is a new item
    else {
        cart.push({
            number: itemNumber,
            name: itemName,
            price: price,
            quantity: quantity,
            size: size
        });
    }
    // Save the updated cart
    saveCart(cart);
    // Take the customer to the cart page
    window.location.href = "cart.html";
}

// 4. REMOVE AN ITEM FROM THE CART
function removeFromCart(index) {

    // Get the current cart
    const cart = getCart();

    // Remove one item from the specified position
    cart.splice(index, 1);

    // Save the updated cart
    saveCart(cart);

    // Redraw the cart
    displayCart();
}
// 5. CHANGE THE QUANTITY OF AN ITEM
function changeQuantity(index, newQuantity) {

    const cart = getCart();

    // Convert the input into a number
    newQuantity = Number(newQuantity);

    // Don't allow zero, negative numbers,
    // or invalid values
    if (newQuantity < 1 || isNaN(newQuantity)) {
        newQuantity = 1;
    }
    // Change the quantity
    cart[index].quantity = newQuantity;

    // Save the updated cart
    saveCart(cart);

    // Redraw the cart
    displayCart();
}
// 6. CALCULATE THE TOTAL
function calculateTotal(cart) {

    let total = 0;
    // Go through every item in the cart
    cart.forEach(item => {
        total += item.price * item.quantity;
    });
    return total;
}
// 7. DISPLAY THE CART
function displayCart() {

    // Find the area where cart items will be displayed
    const cartContainer = document.getElementById("cart-items");

    // Find the "add items to your cart" message
    const emptyMessage = document.getElementById("empty-cart");

    // If this page doesn't have a cart,
    // stop the function.
    // This allows cart.js to also be loaded on shop.html.
    if (!cartContainer) {
        return;
    }
    // Get the current cart
    const cart = getCart();
    // Clear the current cart display
    cartContainer.innerHTML = "";

    // EMPTY CART
    if (cart.length === 0) {

        if (emptyMessage) {
            emptyMessage.style.display = "block";
        }
        return;
    }
    // CART HAS ITEMS
    if (emptyMessage) {
        emptyMessage.style.display = "none";
    }

    // Create each cart item
    cart.forEach((item, index) => {

        // Create a new div
        const itemElement = document.createElement("div");

        // Give it our CSS class
        itemElement.className = "cart-item";

        // Calculate the price for this particular item
        const itemTotal = item.price * item.quantity;

        // Create the cart item's HTML
        itemElement.innerHTML = `

            <div class="cart-item-info">
                <h3>${item.name}</h3>
                <p>Item #${item.number}</p>
                <p>Price: $${item.price.toFixed(2)}</p>
                ${
                    item.size
                    ? `<p>Size: ${item.size}</p>`
                    : ""
                }
                <label>
                    Quantity:
                    <input
                        type="number"
                        min="1"
                        value="${item.quantity}"
                        onchange="changeQuantity(${index}, this.value)"
                    >
                </label>
                <p>
                    Item Total:
                    $${itemTotal.toFixed(2)}
                </p>
            </div>
            <button
                type="button"
                onclick="removeFromCart(${index})"
            >
                Remove item from cart
            </button>

        `;

        // Add this item to the cart page
        cartContainer.appendChild(itemElement);
    });

    // CART TOTAL
    const total = calculateTotal(cart);

    // Look for an existing total
    let totalElement = document.getElementById("cart-total");

    // If one doesn't exist, create it
    if (!totalElement) {

        totalElement = document.createElement("div");
        totalElement.id = "cart-total";
        cartContainer.parentNode.appendChild(totalElement);
    }
    // Display the total
    totalElement.innerHTML = `
        <h2>Cart Total: $${total.toFixed(2)}</h2>
    `;
}

// 8. RUN displayCart WHEN THE PAGE LOADS
document.addEventListener("DOMContentLoaded", function () {

    displayCart();
});