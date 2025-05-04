<?php
session_start();
require_once 'helpFunction.php';

if (!isset($_SESSION['userID'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = $_SESSION['userID'];
    $cartID = $_SESSION['cartID'];
    $shippingAddress = sanitize($_POST['shipping_address']);
    $paymentMethod = isset($_POST['payment_method']) ? sanitize($_POST['payment_method']) : '';

    $conn->begin_transaction();

    try {
        $cartSql = "SELECT ci.*, p.productPrice, p.stockQty 
                    FROM Cart c 
                    JOIN CartItem ci ON c.cartID = ci.cartID
                    JOIN Product p ON ci.productID = p.productID 
                    WHERE c.cartID = '$cartID'";
        $cartResult = $conn->query($cartSql);
        
        if ($cartResult->num_rows === 0) {
            throw new Exception('Cart is empty');
        }

        $total = 0;
        $cartItems = [];
        while ($item = $cartResult->fetch_assoc()) {
            if ($item['quantity'] > $item['stockQty']) {
                throw new Exception('Not enough stock for some items');
            }
            $total += $item['quantity'] * $item['productPrice'];
            $cartItems[] = $item;
        }

        if ($total < 100) {
            $total += 10;
        }

        $orderID = generateUniqueID('O');
        $orderSql = "INSERT INTO Orders (orderID, userID, shippingAddress, paymentMethod, totalPrice) 
                     VALUES ('$orderID', '$userID', '$shippingAddress', '$paymentMethod', $total)";
        $conn->query($orderSql);

        foreach ($cartItems as $item) {
            $orderItemID = generateUniqueID('OT');
            $itemSql = "INSERT INTO OrderItem (orderItemID, orderID, productID, quantity, unitPrice) 
                       VALUES ('$orderItemID', '$orderID', '{$item['productID']}', {$item['quantity']}, {$item['productPrice']})";
            $conn->query($itemSql);

            $updateStockSql = "UPDATE Product 
                              SET stockQty = stockQty - {$item['quantity']} 
                              WHERE productID = '{$item['productID']}'";
            $conn->query($updateStockSql);
        }

        $clearCartSql = "DELETE FROM Cart WHERE cartID = '$cartID'";
        $conn->query($clearCartSql);
        unset($_SESSION['cartID']);
        $_SESSION['cart_count'] = 0;

        $conn->commit();

        header("Location: orderConfirm.php?id=$orderID");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_msg'] = $e->getMessage();
        header('Location: checkout.php');
        exit();
    }
}

$cartID = $_SESSION['cartID'] ?? '';
$sql = "SELECT ci.*, p.productName, p.productPrice, p.productImageUrl 
        FROM Cart c 
        JOIN CartItem ci ON c.cartID = ci.cartID
        JOIN Product p ON ci.productID = p.productID 
        WHERE c.cartID = '$cartID'";
$result = $conn->query($sql);

$cartItems = [];
$subtotal = 0;
while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
    $subtotal += $row['quantity'] * $row['productPrice'];
}

$userID = $_SESSION['userID'];
$userSql = "SELECT * FROM Users WHERE userID = '$userID'";
$userResult = $conn->query($userSql);
$user = $userResult->fetch_assoc();

include 'header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Checkout</h1>

    <?php if (count($cartItems) > 0): ?>
        <form method="post" action="checkout.php" id="checkout-form">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Shipping Information</h5>
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($user['userName']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['userEmail']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="shipping_address" class="form-label">Shipping Address</label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user['userAddress']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Payment Method</h5>
                            <div class="payment-methods mb-3">
                                <label class="payment-method-box">
                                    <input class="form-check-input" type="radio" name="payment_method" value="Touch 'n Go" required>
                                    <img src="image/product/Payment/logo_tng.png" alt="Touch 'n Go" style="height:32px;"> Touch 'n Go eWallet
                                </label>
                                <label class="payment-method-box">
                                    <input class="form-check-input" type="radio" name="payment_method" value="FPX">
                                    <img src="image/product/Payment/logo_fpx.png" alt="FPX" style="height:32px;"> FPX Online Banking
                                </label>
                                <label class="payment-method-box">
                                    <input class="form-check-input" type="radio" name="payment_method" value="VISA">
                                    <img src="image/product/Payment/logo_visa.png" alt="VISA" style="height:32px;"> VISA
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Order Items</h5>
                            <?php foreach ($cartItems as $item): ?>
                                <div class="d-flex mb-3">
                                    <img src="<?php echo htmlspecialchars($item['productImageUrl']); ?>" 
                                         class="me-3" style="width: 64px; height: 64px; object-fit: cover;" 
                                         alt="<?php echo htmlspecialchars($item['productName']); ?>">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['productName']); ?></h6>
                                        <small class="text-muted">
                                            Quantity: <?php echo $item['quantity']; ?> × 
                                            RM <?php echo number_format($item['productPrice'], 2); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold">
                                            RM <?php echo number_format($item['quantity'] * $item['productPrice'], 2); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <span>RM <?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping</span>
                                <span><?php echo $subtotal >= 100 ? 'Free' : 'RM 10.00'; ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total</strong>
                                <strong>RM <?php echo number_format($subtotal >= 100 ? $subtotal : $subtotal + 10, 2); ?></strong>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Place Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h3>Your cart is empty</h3>
            <p class="text-muted">Add some items to your cart before checking out.</p>
            <a href="product.php" class="btn btn-primary">Continue Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<style>
.payment-methods {
    display: flex;
    gap: 1.5rem;
}
.payment-method-box {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    display: flex;
    align-items: center;
    transition: border-color 0.2s;
    background: #fafbfc;
}
.payment-method-box input[type="radio"] {
    margin-right: 0.5rem;
}
.payment-method-box.selected {
    border-color: #007bff;
    background: #eaf4ff;
}
.payment-method-box img {
    margin-right: 0.5rem;
}
</style>

<script>
document.querySelectorAll('.payment-method-box input[type="radio"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.payment-method-box').forEach(function(box) {
            box.classList.remove('selected');
        });
        this.closest('.payment-method-box').classList.add('selected');
    });
});
</script>
