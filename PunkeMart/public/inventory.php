<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/inventory_db.php';
require_once __DIR__ . '/inventory_controller.php';

$inventoryDB = new InventoryDB($conn);
$inventoryController = new InventoryController($inventoryDB);

$items = $inventoryController->handleRequest();

require_once __DIR__ . '/inventory_view.php';
