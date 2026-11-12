
<!DOCTYPE html><html lang="en"><head><title>PunkeMart - Orders</title><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link rel="stylesheet" href="../assets/css/styles.css"><link rel="stylesheet" href="../assets/css/images.css"><link rel="stylesheet" href="../assets/css/shop.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"><script src="../assets/scripts/global.js" defer></script><script src="../assets/scripts/navigation.js" defer></script></head>
<body><div id="wrapper"><header><nav><button class="menuButton" type="button" aria-label="Open navigation menu" aria-expanded="false"><i class="fas fa-bars"></i></button><div class="nav-mobile"><a href="orders.php">View Orders</a><a href="inventory.php">View Inventory</a><a href="reorder.php">Re-Order Inventory</a><a href="../index.html">Return to Public Site</a></div></nav><a href="admin_home.html" class="logo"><img src="../assets/images/punkemart.svg" alt="PunkéMart"></a></header>
<main><h2>Orders</h2>
<?php if (isset($_SESSION['success_message'])): ?><p class="textContainer"><?= esc($_SESSION['success_message']); unset($_SESSION['success_message']); ?></p><?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?><p class="textContainer"><?= esc($_SESSION['error_message']); unset($_SESSION['error_message']); ?></p><?php endif; ?>
<section class="add-custom-reorder-section"><h3>Create an Order</h3>
<?php if (!$customers || !$items): ?><p>Add at least one customer and one inventory item to the database before creating an order.</p><?php else: ?>
<form method="post" action="<?= esc($_SERVER['PHP_SELF']); ?>"><div class="checkout-form-grid">
<div class="form-field"><label for="customerID">Customer</label><select id="customerID" name="customerID" required><option value="">Select customer</option><?php foreach ($customers as $customer): ?><option value="<?= esc($customer['customerID']); ?>"><?= esc($customer['lastName'] . ', ' . $customer['firstName']); ?></option><?php endforeach; ?></select></div>
<div class="form-field"><label for="itemID">Item</label><select id="itemID" name="itemID" required><option value="">Select item</option><?php foreach ($items as $item): ?><option value="<?= esc($item['itemID']); ?>"><?= esc($item['itemName']); ?> ($<?= number_format((float)$item['price'], 2); ?>; stock <?= esc($item['stockOnHand']); ?>)</option><?php endforeach; ?></select></div>
<div class="form-field"><label for="quantity">Quantity</label><input id="quantity" name="quantity" type="number" min="1" value="1" required></div>
<div class="form-field">
    <label for="paymentType">Payment Type</label>
    <select id="paymentType" name="paymentType" required>
        <option value="">Select Payment Type</option>
        <option value="Credit Card">Credit Card</option>
        <option value="Paypal">Paypal</option>
        <option value="Apple Pay">Apple Pay</option>
    </select>
</div><div class="form-field"><label for="datePlaced">Date Placed</label><input id="datePlaced" name="datePlaced" type="date" value="<?= date('Y-m-d'); ?>" required></div>
</div><button class="action-button primary-button" type="submit" name="insert">Create Order</button></form><?php endif; ?></section>
<h3>Recent Orders</h3><div class="table-responsive"><table class="order-table"><thead><tr><th>Order ID</th><th>Customer</th><th>Items (quantity)</th><th>Total</th><th>Payment</th><th>Date Placed</th><th>Tracking / Delivery</th><th>Actions</th></tr></thead><tbody>
<?php if (!$orders): ?><tr><td colspan="8">There are no orders to review yet.</td></tr><?php endif; ?>
<?php foreach ($orders as $order): ?><tr><td><?= esc($order['orderID']); ?></td><td><?= esc($order['firstName'] . ' ' . $order['lastName']); ?></td><td><?= esc($order['orderedItems'] ?? 'No item details'); ?></td><td>$<?= number_format((float)$order['orderTotal'], 2); ?></td><td><?= esc($order['paymentType']); ?></td><td><?= esc($order['datePlaced']); ?></td><td><?= esc($order['trackingID'] ?? ''); ?><?php if ($order['dateDelivery']): ?><br>Delivery: <?= esc($order['dateDelivery']); ?><?php endif; ?></td><td>
<form method="post" action="<?= esc($_SERVER['PHP_SELF']); ?>"><input type="hidden" name="orderID" value="<?= esc($order['orderID']); ?>"><label for="paymentType<?= esc($order['orderID']); ?>">Payment
    <select id="paymentType<?= esc($order['orderID']); ?>" name="paymentType" required>
        <option value="Credit Card" <?= $order['paymentType'] === 'Credit Card' ? 'selected' : ''; ?>>Credit Card</option>
        <option value="Paypal" <?= $order['paymentType'] === 'Paypal' ? 'selected' : ''; ?>>Paypal</option>
        <option value="Apple Pay" <?= $order['paymentType'] === 'Apple Pay' ? 'selected' : ''; ?>>Apple Pay</option>
    </select>
</label><label>Total <input name="orderTotal" type="number" min="0" step="0.01" value="<?= esc($order['orderTotal']); ?>" required></label><label>Tracking ID <input name="trackingID" value="<?= esc($order['trackingID'] ?? ''); ?>"></label><label>Carrier number <input name="carrier" type="number" value="<?= esc($order['carrier'] ?? ''); ?>"></label><label>Delivery date <input name="dateDelivery" type="date" value="<?= esc($order['dateDelivery'] ?? ''); ?>"></label>
<button class="action-button secondary-button" type="submit" name="update">Update</button> <button class="action-button secondary-button" type="submit" name="delete" onclick="return confirm('Delete this order and its item rows?')">Delete</button></form>
</td></tr><?php endforeach; ?></tbody></table></div></main><footer>Copyright &copy; 2026<br>This page was last updated on: <span class="lastModified"></span></footer></div></body></html>
