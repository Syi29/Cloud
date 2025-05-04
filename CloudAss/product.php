<?php
session_start();
require_once 'helpFunction.php';

$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';

$sql = "SELECT * FROM Product";
if ($category) {
    $sql .= " WHERE productCategory = '" . sanitize($category) . "'";
}
$sql .= " ORDER BY productID DESC";

$result = $conn->query($sql);
$products = [];
while($row = $result->fetch_assoc()) {
    $products[] = $row;
}

include 'header.php';
?>

<div class="products-header mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1><?php echo $category ? ucfirst($category) . 's' : 'All Products'; ?></h1>
        </div>
        <div class="col-md-6">
            <div class="d-flex justify-content-md-end">
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="categoryDropdown" data-bs-toggle="dropdown">
                        Filter by Category
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item <?php echo !$category ? 'active' : ''; ?>" href="product.php">All Products</a></li>
                        <li><a class="dropdown-item <?php echo $category == 'Bouquet' ? 'active' : ''; ?>" href="product.php?category=Bouquet">Graduation Bouquets</a></li>
                        <li><a class="dropdown-item <?php echo $category == 'T-shirt' ? 'active' : ''; ?>" href="product.php?category=T-shirt">T-Shirts</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php if (count($products) > 0): ?>
        <?php foreach($products as $product): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card product-card h-100">
                    <div class="product-image-wrapper">
                        <img src="<?php echo htmlspecialchars($product['productImageUrl']); ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($product['productName']); ?>">
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title product-title"><?php echo htmlspecialchars($product['productName']); ?></h5>
                        <p class="card-text text-primary fw-bold mb-3">RM <?php echo number_format($product['productPrice'], 2); ?></p>
                        <div class="mt-auto d-flex gap-2">
                            <a href="productDetails.php?id=<?php echo $product['productID']; ?>" 
                               class="btn btn-primary flex-grow-1">View Details</a>
                            <?php if ($product['stockQty'] > 0): ?>
                                <button onclick="addToCart('<?php echo $product['productID']; ?>', this)" 
                                        class="btn btn-outline-primary" 
                                        title="Add to Cart">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary" disabled>Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info">
                No products found in this category.
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function addToCart(productId, button) {
    button.disabled = true;
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('productID', productId);
    formData.append('quantity', '1');
    formData.append('return_url', window.location.href);

    fetch('cart.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const toast = document.createElement('div');
        toast.className = `alert alert-${data.success ? 'success' : 'danger'} cart-toast`;
        toast.innerHTML = data.message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);

        if (data.success) {
        const cartCount = document.querySelector('.cart-count, .badge.bg-primary');
        if (cartCount) {
            cartCount.textContent = data.cart_count;
        } else if (data.cart_count > 0) {
            const cartLink = document.querySelector('a[href="cart.php"]');
            if (cartLink) {
                const badge = document.createElement('span');
                badge.className = 'badge bg-primary cart-count';
                badge.textContent = data.cart_count;
                cartLink.appendChild(badge);
            }
        }
    }
    })
    .catch(error => {
        console.error('Error:', error);
    })
    .finally(() => {
        button.disabled = false;
    });
}
</script>

<style>
.product-title {
    font-size: 1rem;
    margin-bottom: 0.5rem;
    display: -webkit-box;
    display: box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    height: 2.5rem;
}

.product-image-wrapper {
    height: 280px;
    overflow: hidden;
    background-color: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.product-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 1rem;
}

.product-card {
    transition: transform 0.3s, box-shadow 0.3s;
    border: none;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.dropdown-item.active {
    background-color: #007bff;
    color: white;
}

.products-header {
    background-color: #f8f9fa;
    padding: 2rem 0;
    margin: -1.5rem 0 2rem 0;
    border-bottom: 1px solid #dee2e6;
}

@media (max-width: 768px) {
    .product-image-wrapper {
        height: 200px;
    }
    
    .product-title {
        font-size: 0.9rem;
        height: 2.2rem;
    }
}

.cart-toast {
    position: fixed;
    top: 30px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1000;
    min-width: 250px;
    max-width: 90vw;
    padding: 15px 25px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    animation: slideInDown 0.3s ease-out;
    text-align: center;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateX(-50%) translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
}
</style>

<?php include 'footer.php'; ?>
