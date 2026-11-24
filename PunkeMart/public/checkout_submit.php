<?php
header('Content-Type: application/json');
function respond($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'POST required.']);
}
try {
    $fields = [];
    foreach (['shipping', 'billing'] as $prefix) {
        foreach (['first-name', 'last-name', 'address', 'city', 'state', 'zip', 'phone', 'email'] as $name) {
            $key = "$prefix-$name";
            $value = $_POST[$key] ?? '';
            if (!is_string($value) || trim($value) === '' || strlen(trim($value)) > 100) {
                throw new InvalidArgumentException('Please complete all address fields (100 characters maximum).');
            }
            $fields[$key] = trim($value);
        }
        if (!preg_match('/^[0-9]{5}$/D', $fields["$prefix-zip"]) ||
            !preg_match('/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/D', $fields["$prefix-phone"]) ||
            !preg_match('/^[A-Z]{2}$/D', $fields["$prefix-state"]) ||
            !filter_var($fields["$prefix-email"], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please check your ZIP, phone, state and email.');
        }
    }
    $payment = $_POST['payment-method'] ?? '';
    if (!in_array($payment, ['Credit Card', 'PayPal', 'Apple Pay'], true)) {
        throw new InvalidArgumentException('Invalid payment method.');
    }
    // Match the existing admin controller's spelling.
    if ($payment === 'PayPal') $payment = 'Paypal';
    $cart = json_decode($_POST['cart'] ?? '[]', true);
    if (!is_array($cart) || !$cart || count($cart) > 100) {
        throw new InvalidArgumentException('Invalid or empty cart.');
    }
    $quantities = [];
    foreach ($cart as $item) {
        $id = filter_var($item['itemID'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
        $qty = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]);
        if (!$id || !$qty) throw new InvalidArgumentException('Invalid item or quantity.');
        $quantities[$id] = ($quantities[$id] ?? 0) + $qty;
        if ($quantities[$id] > 10000) throw new InvalidArgumentException('Quantity too large.');
    }
    require_once __DIR__ . '/db.php';
    $conn->beginTransaction();
    $total = 0;
    $lookup = $conn->prepare('SELECT price, stockOnHand FROM items WHERE itemID = ? FOR UPDATE');
    ksort($quantities);
    foreach ($quantities as $id => $qty) {
        $lookup->execute([$id]);
        $item = $lookup->fetch();
        if (!$item || $qty > $item['stockOnHand']) {
            throw new InvalidArgumentException('An item is unavailable or exceeds current stock.');
        }
        $total += (int)round((float)$item['price'] * 100) * $qty;
    }
    $customer = $conn->prepare('INSERT INTO customers (firstName,lastName,email,phone,shAddress,shCity,shState,shZip,biAddress,biCity,biState,biZip) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    $customer->execute([
        $fields['shipping-first-name'], $fields['shipping-last-name'], $fields['shipping-email'],
        str_replace('-', '', $fields['shipping-phone']), $fields['shipping-address'],
        $fields['shipping-city'], $fields['shipping-state'], $fields['shipping-zip'],
        $fields['billing-address'], $fields['billing-city'], $fields['billing-state'], $fields['billing-zip']
    ]);
    $customerID = $conn->lastInsertId();
    $order = $conn->prepare('INSERT INTO orders (customerID,orderitemID,orderTotal,paymentType,datePlaced) VALUES (?,NULL,?,?,CURRENT_DATE())');
    $order->execute([$customerID, sprintf('%d.%02d', intdiv($total, 100), $total % 100), $payment]);
    $orderID = $conn->lastInsertId();
    $detail = $conn->prepare('INSERT INTO orderitems (orderID,itemID,quantity) VALUES (?,?,?)');
    $first = null;
    foreach ($quantities as $id => $qty) {
        $detail->execute([$orderID, $id, $qty]);
        if ($first === null) $first = $conn->lastInsertId();
    }
    $link = $conn->prepare('UPDATE orders SET orderitemID = ? WHERE orderID = ?');
    $link->execute([$first, $orderID]);
    $conn->commit();
    respond(200, ['success' => true, 'orderID' => $orderID]);
} catch (Throwable $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    if ($e instanceof InvalidArgumentException) {
        respond(400, ['success' => false, 'message' => $e->getMessage()]);
    }
    error_log($e->getMessage());
    respond(500, ['success' => false, 'message' => 'Could not save order. Check the PHP error log.']);
}
