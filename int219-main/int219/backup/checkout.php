<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are accepted', 405);
    }

    // Get and validate input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg(), 400);
    }

    // Validate required fields
    $required = ['user_id', 'shipping_address', 'payment_method'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field", 400);
        }
    }

    $user_id = $data['user_id'];

    // Fetch cart items from the `user` table
    $cart_items_query = "
        SELECT product_id, product_name, quantity, price 
        FROM user 
        WHERE user_id = ?
    ";
    $cart_stmt = $conn->prepare($cart_items_query);
    if (!$cart_stmt) {
        throw new Exception('Failed to prepare cart query: ' . $conn->error, 500);
    }

    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();

    if ($cart_result->num_rows === 0) {
        throw new Exception('Cart is empty', 400);
    }

    $cart_items = [];
    while ($row = $cart_result->fetch_assoc()) {
        $cart_items[] = $row;
    }

    // Begin database transaction
    $conn->begin_transaction();

    try {
        // Calculate total amount
        $total_amount = 0;
        foreach ($cart_items as $item) {
            if (empty($item['product_id']) || empty($item['quantity']) || empty($item['price'])) {
                throw new Exception('Invalid cart item format', 400);
            }
            $total_amount += $item['price'] * $item['quantity'];
        }

        // Insert order
        $stmt = $conn->prepare("
            INSERT INTO orders 
            (user_id, total_amount, status, shipping_address, payment_method, payment_status)
            VALUES (?, ?, 'pending', ?, ?, 'pending')
        ");
        if (!$stmt) {
            throw new Exception('Failed to prepare order query: ' . $conn->error, 500);
        }

        $stmt->bind_param(
            "idss",
            $user_id,
            $total_amount,
            $data['shipping_address'],
            $data['payment_method']
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to create order: ' . $stmt->error, 500);
        }

        $order_id = $conn->insert_id;

        // Insert order items
        foreach ($cart_items as $item) {
            $stmt = $conn->prepare("
                INSERT INTO order_items 
                (order_id, product_id, product_name, quantity, price)
                VALUES (?, ?, ?, ?, ?)
            ");
            if (!$stmt) {
                throw new Exception('Failed to prepare order items query: ' . $conn->error, 500);
            }

            $stmt->bind_param(
                "iisid",
                $order_id,
                $item['product_id'],
                $item['product_name'],
                $item['quantity'],
                $item['price']
            );

            if (!$stmt->execute()) {
                throw new Exception('Failed to add order item: ' . $stmt->error, 500);
            }
        }

        // Commit transaction
        $conn->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'order_id' => $order_id,
            'message' => 'Order placed successfully'
        ]);

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}
?>