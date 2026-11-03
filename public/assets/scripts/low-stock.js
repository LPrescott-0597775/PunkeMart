document.addEventListener("DOMContentLoaded", initInventory);

// Force Firefox to completely reload the page from storage when returning to it
window.addEventListener("pageshow", (event) => {
    if (event.persisted || performance.navigation.type === 2) {
        window.location.reload();
    }
});

function initInventory() {
	// Put this as the VERY FIRST LINE inside initInventory()
console.log("RAW localStorage string on load:", localStorage.getItem("inventoryProducts"));
    const gearListContainer = document.getElementById("gear-list");
    if (!gearListContainer) {
        console.error("ERROR: #gear-list not found!");
        return;
    }

    // Master product catalog
    const defaultProducts = [
        { id: "001", name: "Punké Hacky", image: "punkehacky.png", stock: 20, category: "gear" },
        { id: "002", name: "Great Hacky", image: "greathacky.png", stock: 7, category: "gear" },
        { id: "003", name: "Ultra Hacky", image: "ultrahacky.png", stock: 87, category: "gear" },
        { id: "004", name: "Vapor", image: "vapor.png", stock: 312, category: "gear" },
        { id: "005", name: "Super Vapor", image: "supervapor.png", stock: 45, category: "gear" },
        { id: "006", name: "Hyper Vapor", image: "hypervapor.png", stock: 4, category: "gear" },
        { id: "007", name: "The Purge", image: "thepurge.png", stock: 12, category: "gear" },
        { id: "008", name: "The Cure", image: "thecure.png", stock: 410, category: "gear" },
        { id: "009", name: "Healing Crystal", image: "healingcrystal.png", stock: 6, category: "gear" },
        { id: "010", name: "Bailout Belt", image: "bailoutbelt.png", stock: 78, alt: "Placeholder apparel product", category: "apparel" }
    ];

    // Load live inventory from localStorage, or initialize it if it doesn't exist yet
    let allProducts = JSON.parse(localStorage.getItem("inventoryProducts"));
	console.log("DEBUG: Data loaded from localStorage on page load:", allProducts);
    if (!allProducts) {
        allProducts = defaultProducts;
        localStorage.setItem("inventoryProducts", JSON.stringify(allProducts));
    }

    const gearProducts = allProducts.filter(p => p.category === "gear");
	console.log("DEBUG: Great Hacky stock in memory is:", gearProducts.find(p => p.id === "002")?.stock);
    const apparelProducts = allProducts.filter(p => p.category === "apparel");

    // Function to render products and dynamically check for low stock
    function renderProducts(products, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = products.map(product => {
            const isLowStock = Number(product.stock) < 10;
            const cardClass = isLowStock ? "product-card low-stock" : "product-card";

            return `
                <div class="${cardClass}"
                     data-product-id="${product.id}"
                     data-product-name="${product.name}"
                     data-product-stock="${product.stock}">

                    <img class="shopImg"
                         src="../assets/images/${product.image}"
                         alt="${product.alt || 'Placeholder product'}">

                    <p class="product-name">
                        ${product.name}
                    </p>

                    <p class="product-price">
                        ${product.stock}
                    </p>
                </div>
            `;
        }).join('');
    }

    // Render both lists into their respective sections
    renderProducts(gearProducts, "gear-list");
    renderProducts(apparelProducts, "apparel-list");

    // Handle Reorder Inventory Button Click
    const reorderBtn = document.getElementById("reorder-btn");
    if (reorderBtn) {
        reorderBtn.addEventListener("click", () => {
            localStorage.setItem("inventoryProducts", JSON.stringify(allProducts));
            window.location.href = "reorder.html";
        });
    }
}