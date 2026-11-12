<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helper.php';

// CREATE: create the order header first, then its first order-item row.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insert'])) {
    $customerID = clean('customerID', 'int');
    $itemID = clean('itemID', 'int');
    $quantity = clean('quantity', 'int');
    $paymentType = clean('paymentType');
    $datePlaced = clean('datePlaced');
    $errors = [];
    if (!filter_var($customerID, FILTER_VALIDATE_INT) || $customerID < 1) $errors[] = 'Select a customer.';
    if (!filter_var($itemID, FILTER_VALIDATE_INT) || $itemID < 1) $errors[] = 'Select an item.';
    if (!filter_var($quantity, FILTER_VALIDATE_INT) || $quantity < 1) $errors[] = 'Quantity must be at least 1.';
    if ($paymentType === '') $errors[] = 'Enter a payment type.';
    if ($datePlaced === '') $errors[] = 'Choose the order date.';

    if (!$errors) {
        try {
            $itemStmt = $conn->prepare('SELECT price FROM items WHERE itemID = :itemID');
            $itemStmt->bindParam(':itemID', $itemID, PDO::PARAM_INT);
            $itemStmt->execute();
            $selectedItem = $itemStmt->fetch();
            if (!$selectedItem) throw new Exception('The selected item does not exist.');
            $orderTotal = (float)$selectedItem['price'] * (int)$quantity;

            $conn->beginTransaction();
            // orderitemID is NOT NULL in the supplied schema, so 0 is used temporarily.
            $orderStmt = $conn->prepare('INSERT INTO orders (customerID, orderitemID, orderTotal, paymentType, datePlaced) VALUES (:customerID, 0, :total, :paymentType, :datePlaced)');
            $orderStmt->bindParam(':customerID', $customerID, PDO::PARAM_INT);
            $orderStmt->bindParam(':total', $orderTotal);
            $orderStmt->bindParam(':paymentType', $paymentType);
            $orderStmt->bindParam(':datePlaced', $datePlaced);
            $orderStmt->execute();
            $newOrderID = $conn->lastInsertId();

            $orderItemStmt = $conn->prepare('INSERT INTO orderitems (orderID, itemID, quantity) VALUES (:orderID, :itemID, :quantity)');
            $orderItemStmt->bindParam(':orderID', $newOrderID, PDO::PARAM_INT);
            $orderItemStmt->bindParam(':itemID', $itemID, PDO::PARAM_INT);
            $orderItemStmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $orderItemStmt->execute();
            $newOrderItemID = $conn->lastInsertId();

            // Keep the legacy orders.orderitemID column populated with the first detail row ID.
            $linkStmt = $conn->prepare('UPDATE orders SET orderitemID = :orderitemID WHERE orderID = :orderID');
            $linkStmt->bindParam(':orderitemID', $newOrderItemID, PDO::PARAM_INT);
            $linkStmt->bindParam(':orderID', $newOrderID, PDO::PARAM_INT);
            $linkStmt->execute();
            $conn->commit();
            $_SESSION['success_message'] = 'Order created successfully.';
        } catch (Throwable $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $_SESSION['error_message'] = 'ERROR creating order. Check that the customer and item exist.';
        }
    } else $_SESSION['error_message'] = implode(' ', $errors);
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// UPDATE: edit the order's administrative details.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = clean('orderID', 'int');
    $paymentType = clean('paymentType');
    $orderTotal = clean('orderTotal', 'float');
    $trackingID = clean('trackingID');
    $carrier = clean('carrier', 'int');
    $dateDelivery = clean('dateDelivery');
    $errors = [];
    if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) $errors[] = 'Invalid order ID.';
    if ($paymentType === '') $errors[] = 'Payment type is required.';
    if (!is_numeric($orderTotal) || $orderTotal < 0) $errors[] = 'Order total must be zero or greater.';
    if ($carrier !== '' && !filter_var($carrier, FILTER_VALIDATE_INT)) $errors[] = 'Carrier must be a whole number (the supplied database defines carrier as an integer).';

    if (!$errors) {
        try {
            $stmt = $conn->prepare('UPDATE orders SET paymentType = :paymentType, orderTotal = :orderTotal, trackingID = :trackingID, carrier = :carrier, dateDelivery = :dateDelivery WHERE orderID = :id');
            $stmt->bindParam(':paymentType', $paymentType);
            $stmt->bindParam(':orderTotal', $orderTotal);
            $stmt->bindValue(':trackingID', $trackingID === '' ? null : $trackingID, $trackingID === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':carrier', $carrier === '' ? null : $carrier, $carrier === '' ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':dateDelivery', $dateDelivery === '' ? null : $dateDelivery, $dateDelivery === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['success_message'] = 'Order updated successfully.';
        } catch (PDOException $e) { $_SESSION['error_message'] = 'ERROR updating order.'; }
    } else $_SESSION['error_message'] = implode(' ', $errors);
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// DELETE: remove detail rows before the order header to satisfy the foreign key.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = clean('orderID', 'int');
    try {
        $conn->beginTransaction();
        $detailStmt = $conn->prepare('DELETE FROM orderitems WHERE orderID = :id');
        $detailStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $detailStmt->execute();
        $orderStmt = $conn->prepare('DELETE FROM orders WHERE orderID = :id');
        $orderStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $orderStmt->execute();
        $conn->commit();
        $_SESSION['success_message'] = 'Order deleted successfully.';
    } catch (PDOException $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $_SESSION['error_message'] = 'ERROR deleting order.';
    }
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

$customers = $conn->query('SELECT customerID, firstName, lastName FROM customers ORDER BY lastName, firstName')->fetchAll();
$items = $conn->query('SELECT itemID, itemName, price, stockOnHand FROM items ORDER BY itemName')->fetchAll();
$orders = $conn->query('SELECT o.*, c.firstName, c.lastName, (SELECT GROUP_CONCAT(CONCAT(i.itemName, " (", oi.quantity, ")") SEPARATOR ", ") FROM orderitems oi JOIN items i ON oi.itemID = i.itemID WHERE oi.orderID = o.orderID) AS orderedItems FROM orders o JOIN customers c ON o.customerID = c.customerID ORDER BY o.orderID DESC')->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><title>PunkeMart - Orders</title><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link rel="stylesheet" href="assets/css/styles.css"><link rel="stylesheet" href="assets/css/images.css"><link rel="stylesheet" href="assets/css/shop.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"><script src="assets/scripts/global.js" defer></script><script src="assets/scripts/navigation.js" defer></script></head>
<body><div id="wrapper"><header><nav><button class="menuButton" type="button" aria-label="Open navigation menu" aria-expanded="false"><i class="fas fa-bars"></i></button><div class="nav-mobile"><a href="orders.php">View Orders</a><a href="inventory.php">View Inventory</a><a href="reorder.php">Re-Order Inventory</a><a href="index.html">Return to Public Site</a></div></nav><a href="admin_home.html" class="logo"><img src="assets/images/punkemart.svg" alt="PunkéMart"></a></header>
<main><h2>Orders</h2>
<?php if (isset($_SESSION['success_message'])): ?><p class="textContainer"><?= esc($_SESSION['success_message']); unset($_SESSION['success_message']); ?></p><?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?><p class="textContainer"><?= esc($_SESSION['error_message']); unset($_SESSION['error_message']); ?></p><?php endif; ?>
<section class="add-custom-reorder-section"><h3>Create an Order</h3>
<?php if (!$customers || !$items): ?><p>Add at least one customer and one inventory item to the database before creating an order.</p><?php else: ?>
<form method="post" action="<?= esc($_SERVER['PHP_SELF']); ?>"><div class="checkout-form-grid">
<div class="form-field"><label for="customerID">Customer</label><select id="customerID" name="customerID" required><option value="">Select customer</option><?php foreach ($customers as $customer): ?><option value="<?= esc($customer['customerID']); ?>"><?= esc($customer['lastName'] . ', ' . $customer['firstName']); ?></option><?php endforeach; ?></select></div>
<div class="form-field"><label for="itemID">Item</label><select id="itemID" name="itemID" required><option value="">Select item</option><?php foreach ($items as $item): ?><option value="<?= esc($item['itemID']); ?>"><?= esc($item['itemName']); ?> ($<?= number_format((float)$item['price'], 2); ?>; stock <?= esc($item['stockOnHand']); ?>)</option><?php endforeach; ?></select></div>
<div class="form-field"><label for="quantity">Quantity</label><input id="quantity" name="quantity" type="number" min="1" value="1" required></div>
<div class="form-field"><label for="paymentType">Payment Type</label><input id="paymentType" name="paymentType" required></div>
<div class="form-field"><label for="datePlaced">Date Placed</label><input id="datePlaced" name="datePlaced" type="date" value="<?= date('Y-m-d'); ?>" required></div>
</div><button class="action-button primary-button" type="submit" name="insert">Create Order</button></form><?php endif; ?></section>
<h3>Recent Orders</h3><div class="table-responsive"><table class="order-table"><thead><tr><th>Order ID</th><th>Customer</th><th>Items (quantity)</th><th>Total</th><th>Payment</th><th>Date Placed</th><th>Tracking / Delivery</th><th>Actions</th></tr></thead><tbody>
<?php if (!$orders): ?><tr><td colspan="8">There are no orders to review yet.</td></tr><?php endif; ?>
<?php foreach ($orders as $order): ?><tr><td><?= esc($order['orderID']); ?></td><td><?= esc($order['firstName'] . ' ' . $order['lastName']); ?></td><td><?= esc($order['orderedItems'] ?? 'No item details'); ?></td><td>$<?= number_format((float)$order['orderTotal'], 2); ?></td><td><?= esc($order['paymentType']); ?></td><td><?= esc($order['datePlaced']); ?></td><td><?= esc($order['trackingID'] ?? ''); ?><?php if ($order['dateDelivery']): ?><br>Delivery: <?= esc($order['dateDelivery']); ?><?php endif; ?></td><td>
<form method="post" action="<?= esc($_SERVER['PHP_SELF']); ?>"><input type="hidden" name="orderID" value="<?= esc($order['orderID']); ?>"><label>Payment <input name="paymentType" value="<?= esc($order['paymentType']); ?>" required></label><label>Total <input name="orderTotal" type="number" min="0" step="0.01" value="<?= esc($order['orderTotal']); ?>" required></label><label>Tracking ID <input name="trackingID" value="<?= esc($order['trackingID'] ?? ''); ?>"></label><label>Carrier number <input name="carrier" type="number" value="<?= esc($order['carrier'] ?? ''); ?>"></label><label>Delivery date <input name="dateDelivery" type="date" value="<?= esc($order['dateDelivery'] ?? ''); ?>"></label><button class="action-button secondary-button" type="submit" name="update">Update</button> <button class="action-button secondary-button" type="submit" name="delete" onclick="return confirm('Delete this order and its item rows?')">Delete</button></form>
</td></tr><?php endforeach; ?></tbody></table></div></main><footer>Copyright &copy; 2026<br>This page was last updated on: <span class="lastModified"></span></footer></div></body></html>
