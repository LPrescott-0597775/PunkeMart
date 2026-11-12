<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/inventory_data.php';

// CREATE: save a new inventory item.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insert'])) {
    $name = clean('itemName');
    $description = clean('description');
    $price = clean('price', 'float');
    $stock = clean('stockOnHand', 'int');
    $reorderStatus = isset($_POST['reorderStatus']) ? 1 : 0;
    $errors = [];

    if ($name === '') $errors[] = 'Please enter an item name.';
    if ($description === '') $errors[] = 'Please enter an item description.';
    if (!is_numeric($price) || $price < 0) $errors[] = 'Price must be zero or greater.';
    if ($stock === '' || !filter_var($stock, FILTER_VALIDATE_INT) || $stock < 0) $errors[] = 'Stock must be a whole number of zero or greater.';

    if (!$errors) {
        try {
            insertItem($conn, $name, $description, $price, $stock, $reorderStatus);
            $_SESSION['success_message'] = 'Item added successfully.';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'ERROR creating item.';
        }
    } else {
        $_SESSION['error_message'] = implode(' ', $errors);
    }
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// UPDATE: change an existing item using its primary key.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = clean('itemID', 'int');
    $name = clean('itemName');
    $description = clean('description');
    $price = clean('price', 'float');
    $stock = clean('stockOnHand', 'int');
    $reorderStatus = isset($_POST['reorderStatus']) ? 1 : 0;
    $errors = [];
    if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) $errors[] = 'Invalid item ID.';
    if ($name === '') $errors[] = 'Please enter an item name.';
    if ($description === '') $errors[] = 'Please enter a description.';
    if (!is_numeric($price) || $price < 0) $errors[] = 'Price must be zero or greater.';
    if ($stock === '' || !filter_var($stock, FILTER_VALIDATE_INT) || $stock < 0) $errors[] = 'Stock must be a whole number of zero or greater.';

    if (!$errors) {
        try {
            updateItem($conn, $id, $name, $description, $price, $stock, $reorderStatus);
            $_SESSION['success_message'] = 'Item updated successfully.';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'ERROR updating item.';
        }
    } else $_SESSION['error_message'] = implode(' ', $errors);
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// DELETE: a product cannot be deleted while an order item references it.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = clean('itemID', 'int');
    try {
        $deleted = deleteItem($conn, $id);

        $_SESSION['success_message'] = $deleted ? 'Item deleted successfully.' : 'No matching item was found.';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'This item cannot be deleted because it is attached to an order.';
    }
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// READ: retrieve all items for the table below.
$items = getAllItems($conn);
?>
<!DOCTYPE html>
<html lang="en"><head>
<title>PunkeMart - Inventory</title><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/styles.css"><link rel="stylesheet" href="assets/css/images.css"><link rel="stylesheet" href="assets/css/shop.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="assets/scripts/global.js" defer></script><script src="assets/scripts/navigation.js" defer></script>
</head><body><div id="wrapper"><header><nav><button class="menuButton" type="button" aria-label="Open navigation menu" aria-expanded="false"><i class="fas fa-bars"></i></button><div class="nav-mobile"><a href="orders.php">View Orders</a><a href="inventory.php">View Inventory</a><a href="reorder.php">Re-Order Inventory</a><a href="index.html">Return to Public Site</a></div></nav><a href="admin_home.html" class="logo"><img src="assets/images/punkemart.svg" alt="PunkéMart"></a></header>
<main><h2>Inventory</h2>
<?php if (isset($_SESSION['success_message'])): ?><p class="textContainer"><?= esc($_SESSION['success_message']); unset($_SESSION['success_message']); ?></p><?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?><p class="textContainer"><?= esc($_SESSION['error_message']); unset($_SESSION['error_message']); ?></p><?php endif; ?>
<section class="add-custom-reorder-section"><h3>Add Inventory Item</h3><form method="post" action="<?= esc($_SERVER['PHP_SELF']); ?>">
<div class="checkout-form-grid">
<div class="form-field"><label for="itemName">Item Name</label><input id="itemName" name="itemName" maxlength="100" required></div>
<div class="form-field"><label for="price">Price</label><input id="price" name="price" type="number" min="0" step="0.01" required></div>
<div class="form-field"><label for="stockOnHand">Stock on Hand</label><input id="stockOnHand" name="stockOnHand" type="number" min="0" step="1" required></div>
<div class="form-field full-width"><label for="description">Description</label><input id="description" name="description" maxlength="1000" required></div>
</div><p><label><input type="checkbox" name="reorderStatus" value="1"> Mark for reorder</label></p><button class="action-button primary-button" type="submit" name="insert">Add Item</button></form></section>
<h3>Current Inventory</h3><div class="table-responsive"><table class="order-table"><thead><tr><th>ID</th><th>Item</th><th>Description</th><th>Price</th><th>Stock</th><th>Reorder</th><th>Actions</th></tr></thead><tbody>
<?php if (!$items): ?><tr><td colspan="7">No inventory items have been added yet.</td></tr><?php endif; ?>
<?php foreach ($items as $item): ?><tr><td><?= esc($item['itemID']); ?></td><td><?= esc($item['itemName']); ?></td><td><?= esc($item['description']); ?></td><td>$<?= number_format((float)$item['price'], 2); ?></td><td><?= esc($item['stockOnHand']); ?></td><td><?= $item['reorderStatus'] ? 'Yes' : 'No'; ?></td><td>
<form method="post" action="<?= esc($_SERVER['PHP_SELF']); ?>" class="inventory-edit-form"><input type="hidden" name="itemID" value="<?= esc($item['itemID']); ?>"><label>Name <input name="itemName" value="<?= esc($item['itemName']); ?>" required></label><label>Description <input name="description" value="<?= esc($item['description']); ?>" required></label><label>Price <input name="price" type="number" min="0" step="0.01" value="<?= esc($item['price']); ?>" required></label><label>Stock <input name="stockOnHand" type="number" min="0" value="<?= esc($item['stockOnHand']); ?>" required></label><label><input type="checkbox" name="reorderStatus" value="1" <?= $item['reorderStatus'] ? 'checked' : ''; ?>> Reorder</label><button class="action-button secondary-button" type="submit" name="update">Update</button> <button class="action-button secondary-button" type="submit" name="delete" onclick="return confirm('Delete this item?')">Delete</button></form>
</td></tr><?php endforeach; ?></tbody></table></div>
<p><a class="action-button secondary-button" href="reorder.php">Go to Reorder Inventory</a></p></main><footer>Copyright &copy; 2026<br>This page was last updated on: <span class="lastModified"></span></footer></div></body></html>
