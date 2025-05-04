<?php
session_start();
require_once 'helpFunction.php';

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$orderID = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (!$orderID) {
    header('Location: home.php');
    exit();
}

$orderSql = "SELECT o.*, u.userName, u.userEmail, o.paymentMethod 
             FROM Orders o 
             JOIN Users u ON o.userID = u.userID 
             WHERE o.orderID = '$orderID' AND o.userID = '{$_SESSION['userID']}'";
$orderResult = $conn->query($orderSql);

if ($orderResult->num_rows === 0) {
    header('Location: home.php');
    exit();
}

$order = $orderResult->fetch_assoc();
$paymentMethod = $order['paymentMethod'];

$itemsSql = "SELECT oi.*, p.productName, p.productImageUrl 
             FROM OrderItem oi 
             JOIN Product p ON oi.productID = p.productID 
             WHERE oi.orderID = '$orderID'";
$itemsResult = $conn->query($itemsSql);
$orderItems = [];

while ($item = $itemsResult->fetch_assoc()) {
    $orderItems[] = $item;
}

$orderDateTime = new DateTime($order['orderDate']);
$now = new DateTime();
$interval = $orderDateTime->diff($now);
$days = $interval->days;

if ($days < 1) {
    $shippingStatus = "Order Placed";
} elseif ($days < 2) {
    $shippingStatus = "Processing";
} elseif ($days < 3) {
    $shippingStatus = "Shipped";
} else {
    $shippingStatus = "Delivered";
}

include 'header.php';
?>

<div class="container py-4">
    <div class="text-center mb-4">
        <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
        <h1>Order Confirmed!</h1>
        <p class="lead">Thank you for your purchase. Your order has been successfully placed.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row text-center text-md-start">
                        <div class="col-md-4 mb-4 mb-md-0">
                            <h5 class="card-title">Order Information</h5>
                            <p class="mb-1"><strong>Order ID:</strong> <?php echo $orderID; ?></p>
                            <p class="mb-1"><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['orderDate'])); ?></p>
                            <p class="mb-1"><strong>Total Amount:</strong> RM <?php echo number_format($order['totalPrice'], 2); ?></p>
                        </div>
                        <div class="col-md-4 mb-4 mb-md-0 border-md-start border-md-end d-flex flex-column align-items-center justify-content-center">
                            <h5 class="card-title">Payment Method</h5>
                            <div class="d-flex align-items-center justify-content-center mt-2">
                                <?php
                                if ($paymentMethod === "Touch 'n Go") {
                                    echo "<img src='image/product/Payment/logo_tng.png' alt='Touch n Go' style='height:28px;margin-right:8px;'> Touch 'n Go eWallet";
                                } elseif ($paymentMethod === "FPX") {
                                    echo "<img src='image/product/Payment/logo_fpx.png' alt='FPX' style='height:28px;margin-right:8px;'> FPX Online Banking";
                                } elseif ($paymentMethod === "VISA") {
                                    echo "<img src='image/product/Payment/logo_visa.png' alt='VISA' style='height:28px;margin-right:8px;'> VISA";
                                } else {
                                    echo htmlspecialchars($paymentMethod);
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h5 class="card-title">Customer Information</h5>
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['userName']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['userEmail']); ?></p>
                            <p class="mb-1"><strong>Shipping Address:</strong><br><?php echo nl2br(htmlspecialchars($order['shippingAddress'])); ?></p>
                        </div>
                        <div class="col-md-3 text-end">
                            <span class="badge 
                                <?php
                                    if ($shippingStatus == 'Order Placed') echo 'bg-secondary';
                                    elseif ($shippingStatus == 'Processing') echo 'bg-warning text-dark';
                                    elseif ($shippingStatus == 'Shipped') echo 'bg-info text-dark';
                                    elseif ($shippingStatus == 'Delivered') echo 'bg-success';
                                ?>">
                                <?php echo $shippingStatus; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order Items</h5>
                    <?php foreach ($orderItems as $item): ?>
                        <div class="d-flex mb-3 pb-3 border-bottom">
                            <img src="<?php echo htmlspecialchars($item['productImageUrl']); ?>" 
                                 class="me-3" style="width: 64px; height: 64px; object-fit: cover;" 
                                 alt="<?php echo htmlspecialchars($item['productName']); ?>">
                            <div class="flex-grow-1">
                                <h6 class="mb-0"><?php echo htmlspecialchars($item['productName']); ?></h6>
                                <small class="text-muted">
                                    Quantity: <?php echo $item['quantity']; ?> × 
                                    RM <?php echo number_format($item['unitPrice'], 2); ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="fw-bold">
                                    RM <?php echo number_format($item['quantity'] * $item['unitPrice'], 2); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>RM <?php echo number_format($order['totalPrice'] >= 100 ? $order['totalPrice'] : $order['totalPrice'] - 10, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping Fees</span>
                            <span><?php echo $order['totalPrice'] >= 100 ? 'Free' : 'RM 10.00'; ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total</strong>
                            <strong>RM <?php echo number_format($order['totalPrice'], 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col">
                    <strong>Shipping Status:</strong>
                    <span class="badge 
                        <?php
                            if ($shippingStatus == 'Order Placed') echo 'bg-secondary';
                            elseif ($shippingStatus == 'Processing') echo 'bg-warning text-dark';
                            elseif ($shippingStatus == 'Shipped') echo 'bg-info text-dark';
                            elseif ($shippingStatus == 'Delivered') echo 'bg-success';
                        ?>">
                        <?php echo $shippingStatus; ?>
                    </span>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="orderHistory.php" class="btn btn-primary me-2">View Order History</a>
                <a href="product.php" class="btn btn-outline-primary">Continue Shopping</a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<style>
@media (min-width: 768px) {
    .border-md-start {
        border-left: 2px solid #eee !important;
    }
    .border-md-end {
        border-right: 2px solid #eee !important;
    }
}
</style>
