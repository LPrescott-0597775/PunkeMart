
<?php
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/orders_data.php';

// Available payment methods for creating and updating orders.
$paymentTypes = ['Credit Card', 'Paypal', 'Apple Pay'];


// CREATE: Add a new order.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insert'])) {

    // Collect and clean the form information.
    $customerID = clean('customerID', 'int');
    $itemID = clean('itemID', 'int');
    $quantity = clean('quantity', 'int');
    $paymentType = clean('paymentType');
    $datePlaced = clean('datePlaced');

    // Check that the submitted information is valid.
    $errors = [];

    if (!filter_var($customerID, FILTER_VALIDATE_INT) || $customerID < 1) {
        $errors[] = 'Select a customer.';
    }

    if (!filter_var($itemID, FILTER_VALIDATE_INT) || $itemID < 1) {
        $errors[] = 'Select an item.';
    }

    if (!filter_var($quantity, FILTER_VALIDATE_INT) || $quantity < 1) {
        $errors[] = 'Quantity must be at least 1.';
    }

    if (!in_array($paymentType, $paymentTypes, true)) {
        $errors[] = 'Please select a valid payment type.';
    }

    if ($datePlaced === '') {
        $errors[] = 'Choose the order date.';
    }

    // If there are no errors, create the order.
    if (!$errors) {
        try {
            createOrder(
                $conn,
                $customerID,
                $itemID,
                $quantity,
                $paymentType,
                $datePlaced
            );

            $_SESSION['success_message'] = 'Order created successfully.';

        } catch (Throwable $e) {
            $_SESSION['error_message'] =
                'ERROR creating order. Check that the customer and item exist.';
        }

    } else {
        $_SESSION['error_message'] = implode(' ', $errors);
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


// UPDATE: Edit an existing order.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    // Collect and clean the form information.
    $id = clean('orderID', 'int');
    $paymentType = clean('paymentType');
    $orderTotal = clean('orderTotal', 'float');
    $trackingID = clean('trackingID');
    $carrier = clean('carrier', 'int');
    $dateDelivery = clean('dateDelivery');

    // Check that the submitted information is valid.
    $errors = [];

    if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) {
        $errors[] = 'Invalid order ID.';
    }

    if (!in_array($paymentType, $paymentTypes, true)) {
        $errors[] = 'Please select a valid payment type.';
    }

    if (!is_numeric($orderTotal) || $orderTotal < 0) {
        $errors[] = 'Order total must be zero or greater.';
    }

    if ($carrier !== '' && !filter_var($carrier, FILTER_VALIDATE_INT)) {
        $errors[] = 'Carrier must be a whole number.';
    }

    // If there are no errors, update the order.
    if (!$errors) {
        try {
            updateOrder(
                $conn,
                $id,
                $paymentType,
                $orderTotal,
                $trackingID,
                $carrier,
                $dateDelivery
            );

            $_SESSION['success_message'] = 'Order updated successfully.';

        } catch (Throwable $e) {
            $_SESSION['error_message'] = 'ERROR updating order.';
        }

    } else {
        $_SESSION['error_message'] = implode(' ', $errors);
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


// DELETE: Remove an order.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {

    $id = clean('orderID', 'int');

    if (!filter_var($id, FILTER_VALIDATE_INT) || $id < 1) {
        $_SESSION['error_message'] = 'Invalid order ID.';

    } else {
        try {
            $deleted = deleteOrder($conn, $id);

            if ($deleted > 0) {
                $_SESSION['success_message'] = 'Order deleted successfully.';
            } else {
                $_SESSION['error_message'] = 'Order was not found.';
            }

        } catch (Throwable $e) {
            $_SESSION['error_message'] = 'ERROR deleting order.';
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


// READ: Retrieve the information needed to display the page.
$customers = getAllCustomers($conn);
$items = getAllOrderItems($conn);
$orders = getAllOrders($conn);


// Load the HTML and PHP display code.
require_once __DIR__ . '/orders_view.php';
