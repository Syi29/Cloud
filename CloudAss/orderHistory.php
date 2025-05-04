<?php
session_start();
require_once 'helpFunction.php';

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$userID = $_SESSION['userID'];

$ordersSql = "SELECT o.*, 
              (SELECT COUNT(*) FROM OrderItem WHERE orderID = o.orderID) as itemCount 
              FROM Orders o 
              WHERE o.userID = '$userID' 
              ORDER BY o.orderDate DESC";
$ordersResult = $conn->query($ordersSql);

include 'header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Order History</h1>

    <?php if ($ordersResult->num_rows > 0): ?>
        <div class="row">
            <div class="col-lg-12">
                <?php while ($order = $ordersResult->fetch_assoc()): 
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
                    $itemsSql = "SELECT oi.*, p.productName, p.productImageUrl 
                                FROM OrderItem oi 
                                JOIN Product p ON oi.productID = p.productID 
                                WHERE oi.orderID = '{$order['orderID']}'";
                    $itemsResult = $conn->query($itemsSql);
                ?>
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <strong>Order ID:</strong><br>
                                    <?php echo $order['orderID']; ?>
                                </div>
                                <div class="col-md-2">
                                    <strong>Order Date:</strong><br>
                                    <?php echo date('F j, Y g:i A', strtotime($order['orderDate'])); ?>
                                </div>
                                <div class="col-md-2">
                                    <strong>Total Amount:</strong><br>
                                    RM <?php echo number_format($order['totalPrice'], 2); ?>
                                </div>
                                <div class="col-md-2">
                                    <strong>Items:</strong><br>
                                    <?php echo $order['itemCount']; ?> item(s)
                                </div>
                                <div class="col-md-4 text-end">
                                    <strong>Shipping Status:</strong><br>
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
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <?php while ($item = $itemsResult->fetch_assoc()): ?>
                                        <div class="d-flex mb-3">
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
                                    <?php endwhile; ?>
                                </div>
                                <div class="col-md-4">
                                    <div class="shipping-info">
                                        <h6>Shipping Address</h6>
                                        <p class="mb-0 text-muted">
                                            <?php echo nl2br(htmlspecialchars($order['shippingAddress'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mt-3">
                                <a href="orderConfirm.php?id=<?php echo $order['orderID']; ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    View Order Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
            <h3>No Orders Yet</h3>
            <p class="text-muted">You haven't placed any orders yet.</p>
            <a href="product.php" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<style>
.card-header {
    border-bottom: 1px solid rgba(0,0,0,.125);
}

.shipping-info {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
}
</style>

<?php include 'footer.php'; ?>
