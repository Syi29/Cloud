<?php
session_start();
require_once 'helpFunction.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['userID'])) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => false, 'message' => 'Please login to add item to your cart']);
            exit;
        } else {
            header('Location: login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? 'product.php'));
            exit;
        }
    }
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $productID = isset($_POST['productID']) ? sanitize($_POST['productID']) : '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $returnURL = isset($_POST['return_url']) ? $_POST['return_url'] : '';

    if (!isset($_SESSION['cartID'])) {
        if (!isset($_SESSION['userID'])) {
            $existingCartSql = "SELECT c.cartID FROM Cart c WHERE c.sessionID = '" . session_id() . "' LIMIT 1";
            $existingCartResult = $conn->query($existingCartSql);
            if ($existingCartResult && $existingCartResult->num_rows > 0) {
                $_SESSION['cartID'] = $existingCartResult->fetch_assoc()['cartID'];
            } else {
                $_SESSION['cartID'] = generateUniqueID('C');
                $createCartSql = "INSERT INTO Cart (cartID, sessionID) VALUES ('{$_SESSION['cartID']}', '" . session_id() . "')";
                $conn->query($createCartSql);
            }
        } else {
            $existingCartSql = "SELECT c.cartID FROM Cart c WHERE c.userID = '{$_SESSION['userID']}' LIMIT 1";
            $existingCartResult = $conn->query($existingCartSql);
            if ($existingCartResult && $existingCartResult->num_rows > 0) {
                $_SESSION['cartID'] = $existingCartResult->fetch_assoc()['cartID'];
            } else {
                $_SESSION['cartID'] = generateUniqueID('C');
                $createCartSql = "INSERT INTO Cart (cartID, userID) VALUES ('{$_SESSION['cartID']}', '{$_SESSION['userID']}')";
                $conn->query($createCartSql);
            }
        }
    }
    
    $cartID = $_SESSION['cartID'];
    $response = array('success' => false, 'message' => '', 'total' => 0);

    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'add':
                $stockSql = "SELECT stockQty FROM Product WHERE productID = '$productID' FOR UPDATE";
                $stockResult = $conn->query($stockSql);
                
                if (!$stockResult || $stockResult->num_rows === 0) {
                    throw new Exception('Product not found!');
                }
                
                $product = $stockResult->fetch_assoc();
                $availableStock = $product['stockQty'];
                
                $checkSql = "SELECT quantity FROM CartItem WHERE cartID = '$cartID' AND productID = '$productID'";
                $checkResult = $conn->query($checkSql);
                
                if ($checkResult && $checkResult->num_rows > 0) {
                    $cartItem = $checkResult->fetch_assoc();
                    $newQuantity = $cartItem['quantity'] + $quantity;
                    
                    if ($newQuantity > $availableStock) {
                        throw new Exception('Not enough stock available!');
                    }
                    
                    $updateSql = "UPDATE CartItem SET quantity = $newQuantity 
                                 WHERE cartID = '$cartID' AND productID = '$productID'";
                    if (!$conn->query($updateSql)) {
                        throw new Exception('Error updating cart quantity.');
                    }
                    
                    $countSql = "SELECT SUM(quantity) as count FROM CartItem WHERE cartID = '$cartID'";
                    $countResult = $conn->query($countSql);
                    $_SESSION['cart_count'] = $countResult->fetch_assoc()['count'] ?? 0;
                    
                    $response['success'] = true;
                    $response['message'] = 'Product quantity updated in cart successfully!';
                } else {
                    if ($quantity > $availableStock) {
                        throw new Exception('Not enough stock available!');
                    }
                    
                    $insertSql = "INSERT INTO CartItem (cartID, productID, quantity) 
                                VALUES ('$cartID', '$productID', $quantity)";
                    if (!$conn->query($insertSql)) {
                        throw new Exception('Error adding product to cart.');
                    }
                    
                    $countSql = "SELECT SUM(quantity) as count FROM CartItem WHERE cartID = '$cartID'";
                    $countResult = $conn->query($countSql);
                    $_SESSION['cart_count'] = $countResult->fetch_assoc()['count'] ?? 0;
                    
                    $response['success'] = true;
                    $response['message'] = 'Product added to cart successfully!';
                }
                break;

            case 'update':
                if ($quantity <= 0) {
                    throw new Exception('Invalid quantity!');
                }
                
                $stockSql = "SELECT stockQty FROM Product WHERE productID = '$productID' FOR UPDATE";
                $stockResult = $conn->query($stockSql);
                $product = $stockResult->fetch_assoc();
                
                if ($quantity > $product['stockQty']) {
                    throw new Exception('Not enough stock available!');
                }
                
                $updateSql = "UPDATE CartItem SET quantity = $quantity 
                             WHERE cartID = '$cartID' AND productID = '$productID'";
                if (!$conn->query($updateSql)) {
                    throw new Exception('Error updating cart.');
                }
                
                $countSql = "SELECT SUM(quantity) as count FROM CartItem WHERE cartID = '$cartID'";
                $countResult = $conn->query($countSql);
                $_SESSION['cart_count'] = $countResult->fetch_assoc()['count'] ?? 0;
                
                $response['success'] = true;
                $response['message'] = 'Cart updated successfully!';
                break;

            case 'remove':
                $deleteSql = "DELETE FROM CartItem WHERE cartID = '$cartID' AND productID = '$productID'";
                if (!$conn->query($deleteSql)) {
                    throw new Exception('Error removing product from cart.');
                }
                
                $countSql = "SELECT SUM(quantity) as count FROM CartItem WHERE cartID = '$cartID'";
                $countResult = $conn->query($countSql);
                $_SESSION['cart_count'] = $countResult->fetch_assoc()['count'] ?? 0;
                
                $response['success'] = true;
                $response['message'] = 'Product removed from cart successfully!';
                break;
        }

        $conn->commit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = $e->getMessage();
    }

    $totalSql = "SELECT SUM(ci.quantity * p.productPrice) as total 
                 FROM CartItem ci 
                 JOIN Product p ON ci.productID = p.productID 
                 WHERE ci.cartID = '$cartID'";
    $totalResult = $conn->query($totalSql);
    $total = $totalResult->fetch_assoc()['total'] ?? 0;
    $response['total'] = number_format($total, 2);

    $response['cart_count'] = $_SESSION['cart_count'];

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode($response);
        exit;
    }

    if ($response['success']) {
        $_SESSION['success_msg'] = $response['message'];
    } else {
        $_SESSION['error_msg'] = $response['message'];
    }

    header('Location: ' . ($returnURL ?: 'cart.php'));
    exit;
}

$cartID = $_SESSION['cartID'] ?? '';
$sql = "SELECT ci.*, p.productName, p.productPrice, p.productImageUrl, p.stockQty 
        FROM CartItem ci 
        JOIN Product p ON ci.productID = p.productID 
        WHERE ci.cartID = '$cartID'";
$result = $conn->query($sql);
$cartItems = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
    $total += $row['quantity'] * $row['productPrice'];
}

$countSql = "SELECT SUM(quantity) as count FROM CartItem WHERE cartID = '$cartID'";
$countResult = $conn->query($countSql);
$_SESSION['cart_count'] = $countResult->fetch_assoc()['count'] ?? 0;

include 'header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Shopping Cart</h1>

    <?php if (count($cartItems) > 0): ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item mb-3 pb-3 border-bottom" data-product-id="<?php echo $item['productID']; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <img src="<?php echo htmlspecialchars($item['productImageUrl']); ?>" 
                                             class="img-fluid rounded" 
                                             alt="<?php echo htmlspecialchars($item['productName']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <h5 class="mb-0">
                                            <a href="productDetails.php?id=<?php echo $item['productID']; ?>" 
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars($item['productName']); ?>
                                            </a>
                                        </h5>
                                        <small class="text-muted">Unit Price: RM <?php echo number_format($item['productPrice'], 2); ?></small>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group" style="width: 130px;">
                                            <button type="button" class="btn btn-outline-secondary" 
                                                    onclick="updateQuantity('<?php echo $item['productID']; ?>', 'decrease')">-</button>
                                            <input type="number" class="form-control text-center quantity-input" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" 
                                                   max="<?php echo $item['stockQty']; ?>"
                                                   data-product-id="<?php echo $item['productID']; ?>">
                                            <button type="button" class="btn btn-outline-secondary" 
                                                    onclick="updateQuantity('<?php echo $item['productID']; ?>', 'increase')">+</button>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="fw-bold">RM <?php echo number_format($item['quantity'] * $item['productPrice'], 2); ?></span>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-link text-danger delete-cart-item" 
                                                data-product-id="<?php echo $item['productID']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
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
                            <span>RM <?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span><?php echo $total >= 100 ? 'Free' : 'RM 10.00'; ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total</strong>
                            <strong>RM <?php echo number_format($total >= 100 ? $total : $total + 10, 2); ?></strong>
                        </div>
                        <a href="checkout.php" class="btn btn-primary w-100">Proceed to Checkout</a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h3>Your cart is empty</h3>
            <p class="text-muted">looks like you still haven't added any items to your cart yet</p>
            <a href="product.php" class="btn btn-primary">Continue Shopping</a>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteConfirmLabel">Remove Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to remove this item from your cart?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Remove</button>
      </div>
    </div>
  </div>
</div>

<script>
function updateQuantity(productId, action) {
    const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
    let value = parseInt(input.value);
    const max = parseInt(input.getAttribute('max'));

    if (action === 'increase' && value < max) {
        value++;
    } else if (action === 'decrease' && value > 1) {
        value--;
    }

    if (value !== parseInt(input.value)) {
        input.value = value;
        updateCart(productId, value);
    }
}

let productIdToDelete = null;

document.querySelectorAll('.delete-cart-item').forEach(function(btn) {
    btn.addEventListener('click', function() {
        productIdToDelete = this.getAttribute('data-product-id');
        var modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        modal.show();
    });
});

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (productIdToDelete) {
        removeFromCart(productIdToDelete);
        var modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
        modal.hide();
    }
});

function removeFromCart(productId) {
    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('productID', productId);

    fetch('cart.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
            if (item) item.remove();

            if (typeof data.cart_count !== 'undefined') {
                const cartCountBadge = document.querySelector('.cart-count-badge');
                if (cartCountBadge) {
                    cartCountBadge.textContent = data.cart_count;
                    cartCountBadge.style.display = data.cart_count > 0 ? 'inline-block' : 'none';
                }
            }

            location.reload();
        } else {
            alert(data.message || 'fail to remove item from cart.');
        }
    });
}

function updateCart(productId, quantity) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('productID', productId);
    formData.append('quantity', quantity);

    fetch('cart.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

document.querySelectorAll('.quantity-input').forEach(input => {
    input.addEventListener('change', function() {
        const productId = this.dataset.productId;
        const value = parseInt(this.value);
        const max = parseInt(this.getAttribute('max'));
        
        if (value < 1) {
            this.value = 1;
        } else if (value > max) {
            this.value = max;
        }
        
        updateCart(productId, this.value);
    });
});
</script>

<?php include 'footer.php'; ?>
