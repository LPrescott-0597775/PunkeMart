
<!DOCTYPE html>
<html lang="en">
    
<head>
    <title>PunkeMart - Orders</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/images.css">
    <link rel="stylesheet" href="assets/css/shop.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <script src="assets/scripts/global.js" defer></script>
    <script src="assets/scripts/navigation.js" defer></script>
</head>

<body>
    <div id="wrapper">

        <header>
            <nav>
                <button class="menuButton" type="button" aria-label="Open navigation menu" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="nav-mobile">
                    <a href="orders.php">View Orders</a>
                    <a href="inventory.php">View Inventory</a>
                    <a href="reorder.php">Re-Order Inventory</a>
                    <a href="index.html">Return to Public Site</a>
                </div>
            </nav>
            <a href="admin_home.html" class="logo">
                <img src="assets/images/punkemart.svg" alt="PunkéMart">
            </a>
        </header>

        <main>
            <h2>Orders</h2>
            <?php if (isset($_SESSION['success_message'])): ?>
                <p class="textContainer">
                    <?= esc($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                </p>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <p class="textContainer">
                    <?= esc($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                </p>
            <?php endif; ?>
            
            <section class="add-custom-reorder-section">
                <h3>Search Orders</h3>
                <div class="form-field">
                    <label for="orderSearch">Search recent orders</label>
                    <input type="search" id="orderSearch"
                           placeholder="Search by order ID, customer, item, or tracking ID..."
                           aria-controls="recentOrdersBody">
                </div>
                <p id="orderSearchStatus" role="status"></p>
            </section>


            <!-- Recent Orders Section -->
            <h3>Recent Orders</h3>

            <div class="table-responsive">

                <table class="order-table">

                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Items (quantity)</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Date Placed</th>
                            <th>Tracking / Delivery</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody id="recentOrdersBody">

                        <?php if (!$orders): ?>
                            <tr>
                                <td colspan="8">
                                    There are no orders to review yet.
                                </td>
                            </tr>
                        <?php endif; ?>


                        <?php foreach ($orders as $order): ?>

                            <tr class="recent-order-row">

                                <td>
                                    <?= esc($order['orderID']); ?>
                                </td>

                                <td>
                                    <?= esc($order['firstName'] . ' ' . $order['lastName']); ?>
                                </td>

                                <td>
                                    <?= esc($order['orderedItems'] ?? 'No item details'); ?>
                                </td>

                                <td>
                                    $<?= number_format((float)$order['orderTotal'], 2); ?>
                                </td>

                                <td>
                                    <?= esc($order['paymentType']); ?>
                                </td>

                                <td>
                                    <?= esc($order['datePlaced']); ?>
                                </td>

                                <td>
                                    <?= esc($order['trackingID'] ?? ''); ?>

                                    <?php if ($order['dateDelivery']): ?>
                                        <br>
                                        Delivery: <?= esc($order['dateDelivery']); ?>
                                    <?php endif; ?>

                                </td>

                                <td>

                                    <form method="post" action="<?= esc($_SERVER['PHP_SELF']); ?>">

                                        <input
                                            type="hidden"
                                            name="orderID"
                                            value="<?= esc($order['orderID']); ?>"
                                        >


                                        <label for="paymentType<?= esc($order['orderID']); ?>">
                                            Payment

                                            <select
                                                id="paymentType<?= esc($order['orderID']); ?>"
                                                name="paymentType"
                                                required
                                            >
                                                <option value="Credit Card" <?= $order['paymentType'] === 'Credit Card' ? 'selected' : ''; ?>>
                                                    Credit Card
                                                </option>

                                                <option value="Paypal" <?= strcasecmp($order['paymentType'], 'PayPal') === 0 ? 'selected' : ''; ?>>
                                                    PayPal
                                                </option>

                                                <option value="Apple Pay" <?= $order['paymentType'] === 'Apple Pay' ? 'selected' : ''; ?>>
                                                    Apple Pay
                                                </option>
                                            </select>
                                        </label><br>


                                        <label>
                                            Total

                                            <input
                                                name="orderTotal"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                value="<?= esc($order['orderTotal']); ?>"
                                                required
                                            >
                                        </label><br>


                                        <label>
                                            Tracking ID

                                            <input
                                                name="trackingID"
                                                value="<?= esc($order['trackingID'] ?? ''); ?>"
                                            >
                                        </label><br>


                                        <label>
                                            Carrier number

                                            <input
                                                name="carrier"
                                                type="number"
                                                value="<?= esc($order['carrier'] ?? ''); ?>"
                                            >
                                        </label><br>


                                        <label>
                                            Delivery date

                                            <input
                                                name="dateDelivery"
                                                type="date"
                                                value="<?= esc($order['dateDelivery'] ?? ''); ?>"
                                            >
                                        </label><br>


                                        <button
                                            class="action-button secondary-button"
                                            type="submit"
                                            name="update"
                                        >
                                            Update
                                        </button>

                                        <button
                                            class="action-button secondary-button"
                                            type="submit"
                                            name="delete"
                                            onclick="return confirm('Delete this order and its item rows?')"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </main>


        <footer>
            Copyright &copy; 2026
            <br>
            This page was last updated on:
            <span class="lastModified"></span>
        </footer>

    </div>

    <script>
        const orderSearch = document.getElementById('orderSearch');
        const orderRows = document.querySelectorAll('#recentOrdersBody .recent-order-row');
        const orderSearchStatus = document.getElementById('orderSearchStatus');

        orderSearch.addEventListener('input', function () {
            const search = this.value.trim().toLowerCase();
            let matches = 0;

            orderRows.forEach(function (row) {
                // Search displayed order details, not the Update/Delete form.
                const details = Array.from(row.cells).slice(0, 7)
                    .map(cell => cell.textContent.trim())
                    .join(' ').replace(/\s+/g, ' ').toLowerCase();
                const match = details.includes(search);
                row.hidden = !match;
                row.style.display = match ? '' : 'none';
                if (match) matches++;
            });

            orderSearchStatus.textContent = search
                ? (matches ? matches + ' matching order(s).' : 'No matching orders found.')
                : '';
        });
    </script>
</body>
</html>