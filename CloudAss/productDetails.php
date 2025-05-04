<?php
session_start();
require_once 'helpFunction.php';

$productID = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (!$productID) {
    header('Location: product.php');
    exit();
}

$sql = "SELECT * FROM Product WHERE productID = '$productID'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    header('Location: product.php');
    exit();
}

$product = $result->fetch_assoc();

$images = [];
$imageSql = "SELECT * FROM ProductImage WHERE productID = '$productID' ORDER BY sortOrder ASC";
$imageResult = $conn->query($imageSql);
while ($img = $imageResult->fetch_assoc()) {
    $images[] = $img;
}

include 'header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
            <li class="breadcrumb-item"><a href="product.php">Products</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['productName']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-6">
            <?php if (count($images) > 0): ?>
            <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
              <div class="carousel-inner">
                <?php foreach ($images as $idx => $img): ?>
                  <div class="carousel-item <?php if ($idx === 0) echo 'active'; ?>">
                    <img src="<?php echo htmlspecialchars($img['imageUrl']); ?>" class="d-block w-100" alt="">
                  </div>
                <?php endforeach; ?>
              </div>
              <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
              </button>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <h1 class="mb-3"><?php echo htmlspecialchars($product['productName']); ?></h1>
            <div class="price-tag mb-4">
                <h2 class="text-primary">RM <?php echo number_format($product['productPrice'], 2); ?></h2>
            </div>

            <div class="stock-status mb-3">
                <?php if ($product['stockQty'] > 0): ?>
                    <span class="badge bg-success">In Stock</span>
                    <span class="text-muted">(<?php echo $product['stockQty']; ?> available)</span>
                <?php else: ?>
                    <span class="badge bg-danger">Out of Stock</span>
                <?php endif; ?>
            </div>

            <?php if ($product['stockQty'] > 0): ?>
                <?php if (isset($_SESSION['userID'])): ?>
                    <form action="cart.php" method="post" class="mb-4">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="productID" value="<?php echo $product['productID']; ?>">
                        
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="input-group" style="width: 130px;">
                                    <button type="button" class="btn btn-outline-secondary" onclick="decrementQuantity()">-</button>
                                    <input type="number" class="form-control text-center" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stockQty']; ?>">
                                    <button type="button" class="btn btn-outline-secondary" onclick="incrementQuantity()">+</button>
                                </div>
                            </div>
                            <div class="col">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                </button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="mb-4">
                        <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to Add to Cart
                        </a>
                        <p class="text-muted mt-2">Please login to your account to add items to cart.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="product-category mb-3">
                <h5>Category</h5>
                <p><a href="product.php?category=<?php echo urlencode($product['productCategory']); ?>"><?php echo ucfirst($product['productCategory']); ?></a></p>
            </div>

            <div class="shipping-info">
                <h5>Shipping Information</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-truck me-2"></i>Free shipping for orders above RM100</li>
                    <li><i class="fas fa-clock me-2"></i>Delivery within 3-5 business days</li>
                    <li><i class="fas fa-box me-2"></i>Secure packaging</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.product-image-container {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.product-image-container img {
    width: 100%;
    height: auto;
    object-fit: contain;
}

.input-group input[type="number"] {
    border-left: 0;
    border-right: 0;
}

.input-group input[type="number"]::-webkit-inner-spin-button,
.input-group input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.carousel-control-prev-icon,
.carousel-control-next-icon {
    background-color: rgba(0,0,0,0.5); /* Black with some transparency */
    border-radius: 50%;
    background-size: 60% 60%;
}
</style>

<script>
function decrementQuantity() {
    var input = document.getElementById('quantity');
    var value = parseInt(input.value);
    if (value > 1) {
        input.value = value - 1;
    }
}

function incrementQuantity() {
    var input = document.getElementById('quantity');
    var value = parseInt(input.value);
    var max = parseInt(input.getAttribute('max'));
    if (value < max) {
        input.value = value + 1;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var carousel = document.getElementById('productCarousel');
    if (carousel) {
        var items = carousel.querySelectorAll('.carousel-item');
        if (items.length < 2) {
            carousel.querySelector('.carousel-control-prev').style.display = 'none';
            carousel.querySelector('.carousel-control-next').style.display = 'none';
        }
    }
});
</script>

<?php include 'footer.php'; ?>
