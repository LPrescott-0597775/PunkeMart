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
require_once __DIR__ . '/inventory_view.php';