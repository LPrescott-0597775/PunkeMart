<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helper.php';

// Reorder is an inventory UPDATE: add the received quantity to stockOnHand.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock'])) {
    $id = clean('itemID', 'int');
    $quantity = clean('quantity', 'int');
    if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1 || !filter_var($quantity, FILTER_VALIDATE_INT) || $quantity < 1) {
        $_SESSION['error_message'] = 'Choose an item and enter a restock quantity greater than zero.';
    } else {
        try {
            $stmt = $conn->prepare('UPDATE items SET stockOnHand = stockOnHand + :quantity, reorderStatus = 0 WHERE itemID = :id');
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['success_message'] = 'Inventory restocked successfully.';
        } catch (PDOException $e) { $_SESSION['error_message'] = 'ERROR updating inventory.'; }
    }
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}
$items = $conn->query('SELECT * FROM items ORDER BY (stockOnHand < 10) DESC, itemName')->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><title>PunkeMart - Reorder Inventory</title><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link rel="stylesheet" href="assets/css/styles.css"><link rel="stylesheet" href="assets/css/images.css"><link rel="stylesheet" href="assets/css/shop.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"><script src="assets/scripts/global.js" defer></script><script src="assets/scripts/navigation.js" defer></script></head><body><div id="wrapper"><header><nav><button class="menuButton" type="button" aria-label="Open navigation menu" aria-expanded="false"><i class="fas fa-bars"></i></button><div class="nav-mobile"><a href="orders.php">View Orders</a><a href="inventory.php">View Inventory</a><a href="reorder.php">Re-Order Inventory</a><a href="index.html">Return to Public Site</a></div></nav><a href="admin_home.html" class="logo"><img src="assets/images/punkemart.svg" alt="PunkéMart"></a></header><main><h2>Reorder Inventory</h2><div class="add-custom-reorder-section"><p>Items with fewer than 10 units are listed first. Enter the quantity received to update inventory.</p></div>
<?php if (isset($_SESSION['success_message'])): ?><p class="textContainer"><?= esc($_SESSION['success_message']); unset($_SESSION['success_message']); ?></p><?php endif; ?><?php if (isset($_SESSION['error_message'])): ?><p class="textContainer"><?= esc($_SESSION['error_message']); unset($_SESSION['error_message']); ?></p><?php endif; ?>
<div class="table-responsive"><table class="order-table"><thead><tr><th>Product ID</th><th>Product Name</th><th>Current Stock</th><th>Reorder Status</th><th>Quantity Received</th><th>Action</th></tr></thead><tbody><?php if (!$items): ?><tr><td colspan="6">No inventory items found.</td></tr><?php endif; ?><?php foreach ($items as $item): ?><tr><td><?= esc($item['itemID']); ?></td><td><?= esc($item['itemName']); ?></td><td><?= esc($item['stockOnHand']); ?></td><td><?= ((int)$item['stockOnHand'] < 10 || $item['reorderStatus']) ? 'Needs reorder' : 'In stock'; ?></td><td><form method="post" action="<?= esc($_SERVER['PHP_SELF']); ?>"><input type="hidden" name="itemID" value="<?= esc($item['itemID']); ?>"><input class="reorder-input" type="number" name="quantity" min="1" max="500" value="10" required></td><td><button class="action-button secondary-button" type="submit" name="restock">Add Stock</button></form></td></tr><?php endforeach; ?></tbody></table></div><div class="action-buttons"><a href="inventory.php" class="action-button secondary-button">Back to Inventory</a></div></main><footer>Copyright &copy; 2026<br>This page was last updated on: <span class="lastModified"></span></footer></div></body></html>
