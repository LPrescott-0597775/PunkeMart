
<?php

// CREATE: Create an order and its first order-item record.
function createOrder($conn, $customerID, $itemID, $quantity, $paymentType, $datePlaced) {

    // Find the price of the selected item.
    $itemStmt = $conn->prepare(
        'SELECT price FROM items WHERE itemID = :itemID'
    );
    $itemStmt->bindParam(':itemID', $itemID, PDO::PARAM_INT);
    $itemStmt->execute();

    $selectedItem = $itemStmt->fetch();

    if (!$selectedItem) {
        throw new Exception('The selected item does not exist.');
    }

    // Calculate the order total.
    $orderTotal = (float)$selectedItem['price'] * (int)$quantity;

    // Use a transaction so the order and its detail are saved together.
    $conn->beginTransaction();

    try {
        // Create the order header.
        $orderStmt = $conn->prepare(
            'INSERT INTO orders
             (customerID, orderitemID, orderTotal, paymentType, datePlaced)
             VALUES (:customerID, 0, :total, :paymentType, :datePlaced)'
        );

        $orderStmt->bindParam(':customerID', $customerID, PDO::PARAM_INT);
        $orderStmt->bindParam(':total', $orderTotal);
        $orderStmt->bindParam(':paymentType', $paymentType);
        $orderStmt->bindParam(':datePlaced', $datePlaced);
        $orderStmt->execute();

        $newOrderID = $conn->lastInsertId();

        // Create the order's item detail.
        $orderItemStmt = $conn->prepare(
            'INSERT INTO orderitems (orderID, itemID, quantity)
             VALUES (:orderID, :itemID, :quantity)'
        );

        $orderItemStmt->bindParam(':orderID', $newOrderID, PDO::PARAM_INT);
        $orderItemStmt->bindParam(':itemID', $itemID, PDO::PARAM_INT);
        $orderItemStmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $orderItemStmt->execute();

        $newOrderItemID = $conn->lastInsertId();

        // Update the order with the ID of its first detail row.
        $linkStmt = $conn->prepare(
            'UPDATE orders
             SET orderitemID = :orderitemID
             WHERE orderID = :orderID'
        );

        $linkStmt->bindParam(':orderitemID', $newOrderItemID, PDO::PARAM_INT);
        $linkStmt->bindParam(':orderID', $newOrderID, PDO::PARAM_INT);
        $linkStmt->execute();

        $conn->commit();

        return $newOrderID;

    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        throw $e;
    }
}


// READ: Retrieve all customers for the customer dropdown.
function getAllCustomers($conn) {
    $stmt = $conn->query(
        'SELECT customerID, firstName, lastName
         FROM customers
         ORDER BY lastName, firstName'
    );

    return $stmt->fetchAll();
}


// READ: Retrieve inventory items for the item dropdown.
function getAllOrderItems($conn) {
    $stmt = $conn->query(
        'SELECT itemID, itemName, price, stockOnHand
         FROM items
         ORDER BY itemName'
    );

    return $stmt->fetchAll();
}


// READ: Retrieve orders and their associated customer and item information.
function getAllOrders($conn) {
    $stmt = $conn->query(
        'SELECT o.*, c.firstName, c.lastName,
        (SELECT GROUP_CONCAT(
            CONCAT(i.itemName, " (", oi.quantity, ")")
            SEPARATOR ", "
        )
        FROM orderitems oi
        JOIN items i ON oi.itemID = i.itemID
        WHERE oi.orderID = o.orderID) AS orderedItems
        FROM orders o
        JOIN customers c ON o.customerID = c.customerID
        ORDER BY o.orderID DESC'
    );

    return $stmt->fetchAll();
}


// UPDATE: Change an order's administrative details.
function updateOrder($conn, $id, $paymentType, $orderTotal, $trackingID, $carrier, $dateDelivery) {
    $stmt = $conn->prepare(
        'UPDATE orders
         SET paymentType = :paymentType,
             orderTotal = :orderTotal,
             trackingID = :trackingID,
             carrier = :carrier,
             dateDelivery = :dateDelivery
         WHERE orderID = :id'
    );

    $stmt->bindParam(':paymentType', $paymentType);
    $stmt->bindParam(':orderTotal', $orderTotal);

    $stmt->bindValue(
        ':trackingID',
        $trackingID === '' ? null : $trackingID,
        $trackingID === '' ? PDO::PARAM_NULL : PDO::PARAM_STR
    );

    $stmt->bindValue(
        ':carrier',
        $carrier === '' ? null : $carrier,
        $carrier === '' ? PDO::PARAM_NULL : PDO::PARAM_INT
    );

    $stmt->bindValue(
        ':dateDelivery',
        $dateDelivery === '' ? null : $dateDelivery,
        $dateDelivery === '' ? PDO::PARAM_NULL : PDO::PARAM_STR
    );

    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    return $stmt->execute();
}


// DELETE: Delete order-item details before deleting the order.
function deleteOrder($conn, $id) {
    $conn->beginTransaction();

    try {
        // Delete the detail rows first because they reference the order.
        $detailStmt = $conn->prepare(
            'DELETE FROM orderitems WHERE orderID = :id'
        );
        $detailStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $detailStmt->execute();

        // Delete the order header.
        $orderStmt = $conn->prepare(
            'DELETE FROM orders WHERE orderID = :id'
        );
        $orderStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $orderStmt->execute();

        $deleted = $orderStmt->rowCount();

        $conn->commit();

        return $deleted;

    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        throw $e;
    }
}
