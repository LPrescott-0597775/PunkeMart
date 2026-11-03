document.addEventListener("DOMContentLoaded", () => {
    const tableBody = document.getElementById("reorder-table-body");
    if (!tableBody) return;

    const noReordersContainer = document.getElementById("no-reorders-container");
    const confirmBtn = document.getElementById("confirm-reorder-btn");
    const tableContainer = document.querySelector(".table-responsive");
    
    const productSelect = document.getElementById("additional-product-select");
    const addCustomBtn = document.getElementById("add-to-reorder-btn");
    const customQtyInput = document.getElementById("additional-qty-input");

    // Load master store products strictly from localStorage (passed from inventory page)
    let allStoreProducts = JSON.parse(localStorage.getItem("inventoryProducts")) || [];

    // Fallback failsafe if someone navigates here directly without visiting inventory.html first
    if (allStoreProducts.length === 0) {
        allStoreProducts = [
            { id: "001", name: "Punké Hacky", stock: 20, category: "gear" },
            { id: "002", name: "Great Hacky", stock: 7, category: "gear" },
            { id: "003", name: "Ultra Hacky", stock: 87, category: "gear" },
            { id: "004", name: "Vapor", stock: 312, category: "gear" },
            { id: "005", name: "Super Vapor", stock: 45, category: "gear" },
            { id: "006", name: "Hyper Vapor", stock: 4, category: "gear" },
            { id: "007", name: "The Purge", stock: 12, category: "gear" },
            { id: "008", name: "The Cure", stock: 410, category: "gear" },
            { id: "009", name: "Healing Crystal", stock: 6, category: "gear" },
            { id: "010", name: "Bailout Belt", stock: 78, category: "apparel" }
        ];
        localStorage.setItem("inventoryProducts", JSON.stringify(allStoreProducts));
    }

    // Populate the dropdown selector using the passed data
    if (productSelect) {
        productSelect.innerHTML = '<option value="">-- Select a product to restock --</option>' + 
            allStoreProducts.map(p => `<option value="${p.id}">${p.name} (Current Stock: ${p.stock})</option>`).join('');
    }

    // Automatically filter low-stock items (< 10) for the reorder list
    let reorderItems = allStoreProducts
        .filter(product => Number(product.stock) < 10)
        .map(product => ({
            id: product.id,
            name: product.name,
            currentStock: Number(product.stock),
            reorderQuantity: Math.max(5, 10 - Number(product.stock)),
            isDangerZone: true
        }));

    // Render the table rows (READ-ONLY regarding stock until form submission)
    function renderTable() {
        if (reorderItems.length > 0) {
            tableBody.innerHTML = reorderItems.map((item, index) => {
                const storeMatch = allStoreProducts.find(p => p.id === item.id);
                const currentStock = storeMatch ? Number(storeMatch.stock) : item.currentStock;
                
                const minQty = 5;
                const maxQty = Math.max(minQty, 500 - currentStock);

                const removeBtnHtml = item.isDangerZone
                    ? `<button type="button" class="action-button secondary-button" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; opacity: 0.5; cursor: not-allowed;" disabled title="Item is low on stock, and needs to be re-ordered!">Protected</button>`
                    : `<button type="button" class="action-button secondary-button remove-item-btn" data-index="${index}" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">Remove</button>`;

                return `
                    <tr data-index="${index}">
                        <td>${item.id}</td>
                        <td>${item.name}</td>
                        <td>
                            <input type="number" 
                                   class="reorder-input" 
                                   value="${item.reorderQuantity}" 
                                   min="${minQty}" 
                                   max="${maxQty}" 
                                   data-index="${index}">
                            <span style="font-size: 0.8rem; color: #666; display: block; margin-top: 4px;">(Current Stock: ${currentStock} | Min Qty: ${minQty})</span>
                        </td>
                        <td>${removeBtnHtml}</td>
                    </tr>
                `;
            }).join('');

            if (tableContainer) tableContainer.style.display = "block";
            if (noReordersContainer) noReordersContainer.style.display = "none";
            if (confirmBtn) confirmBtn.style.display = "inline-block";
        } else {
            if (tableContainer) tableContainer.style.display = "none";
            if (noReordersContainer) noReordersContainer.style.display = "block";
            if (confirmBtn) confirmBtn.style.display = "none";
        }
    }

    renderTable();

    // Handle adding an extra item from the dropdown
    if (addCustomBtn) {
        addCustomBtn.addEventListener("click", () => {
            const selectedId = productSelect.value;
            const customQty = parseInt(customQtyInput.value) || 5;

            if (!selectedId) {
                alert("Please select a product from the dropdown first.");
                return;
            }

            const productData = allStoreProducts.find(p => p.id === selectedId);
            if (!productData) return;

            const existingIndex = reorderItems.findIndex(item => item.id === selectedId);
            if (existingIndex > -1) {
                reorderItems[existingIndex].reorderQuantity += customQty;
            } else {
                reorderItems.push({
                    id: productData.id,
                    name: productData.name,
                    currentStock: Number(productData.stock),
                    reorderQuantity: Math.max(5, customQty),
                    isDangerZone: Number(productData.stock) < 10
                });
            }

            renderTable();
            productSelect.value = "";
            customQtyInput.value = "10";
        });
    }

    // Handle removing a non-danger-zone item
    tableBody.addEventListener("click", (e) => {
        if (e.target.classList.contains("remove-item-btn")) {
            const index = parseInt(e.target.getAttribute("data-index"));
            if (!reorderItems[index].isDangerZone) {
                reorderItems.splice(index, 1);
                renderTable();
            }
        }
    });

    // Handle order submission confirmation: ONLY HERE do we apply quantities to stock and save
    if (confirmBtn) {
        confirmBtn.addEventListener("click", (e) => {
			e.preventDefault();
            const inputs = document.querySelectorAll(".reorder-input");
            let isValid = true;
            let finalReorderList = [];

            inputs.forEach(input => {
                const index = parseInt(input.getAttribute("data-index"));
                const newQty = parseInt(input.value);
                const min = parseInt(input.min);
                const max = parseInt(input.max);

                if (isNaN(newQty) || newQty < min || newQty > max) {
                    isValid = false;
                    input.style.borderColor = "#e74c3c";
                } else {
                    input.style.borderColor = "";
                    finalReorderList.push({
                        ...reorderItems[index],
                        reorderQuantity: newQty
                    });
                }
            });

            if (!isValid) {
                alert("Please ensure all quantities meet the minimum requirement of 5 and do not exceed the 500 stock limit.");
                return;
            }

            // Update actual stock values in memory upon explicit confirmation
			console.log("DEBUG: finalReorderList is:", finalReorderList);
            finalReorderList.forEach(item => {
                const productInStore = allStoreProducts.find(p => p.id === item.id);
                if (productInStore) {
                    productInStore.stock = Number(productInStore.stock) + item.reorderQuantity;
                }
            });

            // Save updated master inventory back to localStorage
			console.log("DEBUG: allStoreProducts right before save:", allStoreProducts);
            localStorage.setItem("inventoryProducts", JSON.stringify(allStoreProducts));

            alert("Reorder successfully submitted and inventory updated!");
            window.location.href = "inventory.html";
        });
    }
});