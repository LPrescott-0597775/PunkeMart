
<?php

class InventoryDB
{
    private PDO $conn;

    public function __construct(PDO $connection)
    {
        $this->conn = $connection;
    }

    // CREATE: add a new inventory item.
    public function createItem($name, $description, $price, $stock, $reorderStatus)
    {
        try {
            $stmt = $this->conn->prepare(
                'INSERT INTO items
                (itemName, description, price, stockOnHand, reorderStatus)
                VALUES (:name, :description, :price, :stock, :reorderStatus)'
            );

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':reorderStatus', $reorderStatus, PDO::PARAM_INT);

            $stmt->execute();

            return $this->conn->lastInsertId();

        } catch (PDOException $e) {
            error_log("Database error in createItem: " . $e->getMessage());
            throw $e;
        }
    }

    // READ: retrieve all inventory items.
    public function getAllItems()
    {
        try {
            $stmt = $this->conn->query('SELECT * FROM items ORDER BY itemID');

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Database error in getAllItems: " . $e->getMessage());
            throw $e;
        }
    }

    // UPDATE: change an existing item.
    public function updateItem($id, $name, $description, $price, $stock, $reorderStatus)
    {
        try {
            $stmt = $this->conn->prepare(
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

            $stmt->execute();

            return $stmt->rowCount();

        } catch (PDOException $e) {
            error_log("Database error in updateItem: " . $e->getMessage());
            throw $e;
        }
    }

    // DELETE: remove an inventory item.
    public function deleteItem($id)
    {
        try {
            $stmt = $this->conn->prepare('DELETE FROM items WHERE itemID = :id');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount();

        } catch (PDOException $e) {
            error_log("Database error in deleteItem: " . $e->getMessage());
            throw $e;
        }
    }
}

