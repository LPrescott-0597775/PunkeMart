
<?php
// CREATE: add a new inventory item.
function createItem($conn, $name, $description, $price, $stock, $reorderStatus) {
    $stmt = $conn->prepare(
        'INSERT INTO items
        (itemName, description, price, stockOnHand, reorderStatus)
        VALUES (:name, :description, :price, :stock, :reorderStatus)'
    );

    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':stock', $stock);
    $stmt->bindParam(':reorderStatus', $reorderStatus, PDO::PARAM_INT);

    return $stmt->execute();
}

// READ: retrieve all inventory items.
function getAllItems($conn) {
    $stmt = $conn->query('SELECT * FROM items ORDER BY itemID');
    return $stmt->fetchAll();
}

// UPDATE: change an existing inventory item.
function updateItem($conn, $id, $name, $description, $price, $stock, $reorderStatus) {
    $stmt = $conn->prepare(
        'UPDATE items
        SET itemName = :name,
            description = :description,
            price = :price,
            stockOnHand = :stock,
            reorderStatus = :reorderStatus
        WHERE itemID = :id'
    );

    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':stock', $stock);
    $stmt->bindParam(':reorderStatus', $reorderStatus, PDO::PARAM_INT);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    return $stmt->execute();
}

// DELETE: remove an inventory item.
function deleteItem($conn, $id) {
    $stmt = $conn->prepare('DELETE FROM items WHERE itemID = :id');
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->rowCount();
}
?>
