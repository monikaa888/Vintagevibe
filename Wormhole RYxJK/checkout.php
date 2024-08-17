<?php
session_start();

$host = 'localhost';
$dbname = 'vintage_vibe';
$dbUsername = 'root';
$dbPassword = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbUsername, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $userStmt = $pdo->prepare("SELECT id, username, first_name, last_name, email FROM user WHERE id = :id");
    $userStmt->execute([':id' => $user_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error fetching user data: ' . $e->getMessage()]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $paymentMethod = isset($_POST['payment-method']) ? $_POST['payment-method'] : '';
    $cardDetails = isset($_POST['card-details']) ? $_POST['card-details'] : '';
    $totalAmount = isset($_POST['total_amount']) ? $_POST['total_amount'] : '';
    $products = isset($_POST['products']) ? json_decode($_POST['products'], true) : [];

    if (empty($products)) {
        echo json_encode(['status' => 'error', 'message' => 'No products in cart.']);
        exit();
    }

    $orderDate = date('Y-m-d H:i:s');

    $productList = [];
    foreach ($products as $product) {
        $productList[] = $product['name'] . ' (Qty: ' . $product['quantity'] . ')';
    }
    $productString = implode(', ', $productList);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, username, first_name, last_name, address, product, quantity, payment_method, card_details, total_amount, order_date) 
            VALUES (:user_id, :username, :first_name, :last_name, :address, :product, :quantity, :payment_method, :card_details, :total_amount, :order_date)
        ");
        $stmt->execute([
            ':user_id' => $user['id'],
            ':username' => $user['username'],
            ':first_name' => $user['first_name'],
            ':last_name' => $user['last_name'],
            ':address' => $address,
            ':product' => $productString,
            ':quantity' => array_sum(array_column($products, 'quantity')),
            ':payment_method' => $paymentMethod,
            ':card_details' => $cardDetails,
            ':total_amount' => $totalAmount,
            ':order_date' => $orderDate
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Order inserted successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error inserting order: ' . $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="checkout.css">
    <title>Checkout</title>
</head>
<body>

<div class="checkout-wrapper">
    <h2 class="checkout-title">Checkout</h2>
    
    <div class="cart-items" id="cart-items"></div>
    
    <div class="cart-summary">
        <h3>Total: <span id="cart-total">Rs 0</span></h3>
    </div>
    
    <form class="checkout-form" method="POST" id="checkout-form">
        <input type="hidden" id="user_id" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
        <input type="hidden" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>">
        <input type="hidden" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>">
        <input type="hidden" id="total_amount" name="total_amount" value="1000">
        <input type="hidden" id="products" name="products" value="">

        <div class="form-group">
            <label for="address">Shipping Address</label>
            <input type="text" id="address" name="address" placeholder="123 Main St, City, Country" required>
            <div class="error-message" id="address-error"></div>
        </div>
        <div class="form-group">
            <label for="payment-method">Payment Method</label>
            <select id="payment-method" name="payment-method" required>
                <option value="">Select Payment Method</option>
                <option value="credit-card">Credit Card</option>
                <option value="paypal">PayPal</option>
            </select>
            <div class="error-message" id="payment-method-error"></div>
        </div>
        <div class="form-group">
            <label for="card-details">Credit Card Details (if applicable)</label>
            <input type="text" id="card-details" name="card-details" placeholder="Card Number" maxlength="16">
            <div class="error-message" id="card-details-error"></div>
        </div>
        <button type="submit">Complete Purchase</button>
    </form>
</div>

<script src="cart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('cart-items')) {
            loadCartItems();
        }
    });

    function loadCartItems() {
        const cart = JSON.parse(localStorage.getItem('cart') || '[]');
        const cartItemsContainer = document.getElementById('cart-items');
        const cartTotalElement = document.getElementById('cart-total');

        if (cart.length > 0) {
            let total = 0;
            cartItemsContainer.innerHTML = '';

            cart.forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.className = 'cart-item';
                itemElement.innerHTML = `
                    <div>${item.name}</div>
                    <div>Qty: ${item.quantity}</div>
                    <div>Price: Rs ${item.price}</div>
                `;
                cartItemsContainer.appendChild(itemElement);
                total += item.price * item.quantity;
            });

            cartTotalElement.textContent = 'Rs ' + total;

            document.getElementById('total_amount').value = total;
            document.getElementById('products').value = JSON.stringify(cart);
        } else {
            cartItemsContainer.innerHTML = '<p>Your cart is empty.</p>';
            cartTotalElement.textContent = 'Rs 0';
        }
    }

    document.getElementById('checkout-form').addEventListener('submit', function(event) {
        let valid = true;

        document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

        const address = document.getElementById('address').value;
        if (!address) {
            document.getElementById('address-error').textContent = 'Shipping Address is required.';
            valid = false;
        }

        const paymentMethod = document.getElementById('payment-method').value;
        if (!paymentMethod) {
            document.getElementById('payment-method-error').textContent = 'Payment Method is required.';
            valid = false;
        }

        const cardDetails = document.getElementById('card-details').value;
        if (paymentMethod === 'credit-card' && !cardDetails) {
            document.getElementById('card-details-error').textContent = 'Credit Card Details are required.';
            valid = false;
        }

        if (!valid) {
            event.preventDefault();
            return;
        }

        const formData = new FormData(this);

        fetch('checkout.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Order placed successfully!');
                localStorage.removeItem('cart');
                window.location.href = 'purchase_completed.html';
            } else {
                alert('Error placing order: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing your order.');
        });

        event.preventDefault();
    });
</script>

</body>
</html>
