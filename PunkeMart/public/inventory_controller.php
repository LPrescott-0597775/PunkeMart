<?php
class InventoryController
{
    private InventoryDB $inventory_db;

    public function __construct(InventoryDB $inventoryDB)
    {
        $this->inventory_db = $inventoryDB;
    }

public function handleRequest()

{
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['insert'])) {
            $this->createItem();
    } elseif (isset($_POST['update'])) {
        $this->updateItem();
    } elseif (isset($_POST['delete'])) {
        $this->deleteItem();
    }
    // Redirect after POST to prevent form submission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
    }
    // Return data for display
    return $this->getItems();
}
// CREATE: save a new inventory item.
public function createItem() {
    $name = clean('itemName');
    $description = clean('description');
    $price = clean('price', 'float');
    $stock = clean('stockOnHand', 'int');
    $reorderStatus = isset($_POST['reorderStatus']) ? 1 : 0;

    // Validation
    $errors = [];
    
    if ($name === '') $errors[] = 'Please enter an item name.';
    if ($description === '') $errors[] = 'Please enter an item description.';
    if (!is_numeric($price) || $price < 0) $errors[] = 'Price must be zero or greater.';
    if ($stock === '' || !filter_var($stock, FILTER_VALIDATE_INT) || $stock < 0) $errors[] = 'Stock must be a whole number of zero or greater.';

    if (!$errors) {
        try {
            $this->inventory_db->createItem(
                $name,
                $description,
                $price,
                $stock,
                $reorderStatus
            );
            $_SESSION['success_message'] = 'Item added successfully.';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'ERROR creating item.';
        }
    } else {
        $_SESSION['error_message'] = implode(' ', $errors);
    }

}

// UPDATE: change an existing item using its primary key.
public function updateItem() {
    $id = clean('itemID', 'int');
    $name = clean('itemName');
    $description = clean('description');
    $price = clean('price', 'float');
    $stock = clean('stockOnHand', 'int');
    $reorderStatus = isset($_POST['reorderStatus']) ? 1 : 0;

    // Validation
    $errors = [];
    if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) $errors[] = 'Invalid item ID.';
    if ($name === '') $errors[] = 'Please enter an item name.';
    if ($description === '') $errors[] = 'Please enter a description.';
    if (!is_numeric($price) || $price < 0) $errors[] = 'Price must be zero or greater.';
    if ($stock === '' || !filter_var($stock, FILTER_VALIDATE_INT) || $stock < 0) $errors[] = 'Stock must be a whole number of zero or greater.';

    if (!$errors) {
        try {
            $this->inventory_db->updateItem(
                $id,
                $name,
                $description,
                $price,
                $stock,
                $reorderStatus
            );
            $_SESSION['success_message'] = 'Item updated successfully.';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'ERROR updating item.';
        }
    } else $_SESSION['error_message'] = implode(' ', $errors);
}

// DELETE: a product cannot be deleted while an order item references it.
public function deleteItem() {
    $id = clean('itemID', 'int');
    try {
        $deleted = $this->inventory_db->deleteItem($id);

        $_SESSION['success_message'] = $deleted ? 'Item deleted successfully.' : 'No matching item was found.';
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $_SESSION['error_message'] = 'This item cannot be deleted because it is attached to an order.';
    } else {
        $_SESSION['error_message'] = 'ERROR deleting item.';
    }
    }
}

// READ: retrieve all items for the table below.
public function getItems() {
$items = $this->inventory_db->getAllItems();
    return $items;
 }

}
