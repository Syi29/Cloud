<?php
session_start();
require_once 'helpFunction.php';

$sql = "SELECT * FROM Product ORDER BY productID DESC LIMIT 4";
$result = $conn->query($sql);
$recentProducts = [];
while($row = $result->fetch_assoc()) {
    $recentProducts[] = $row;
}

include 'header.php';
?>

<div class="hero-section position-relative mb-5">
    <img src="image/main_image.png" class="w-100" style="height: 500px; object-fit: cover; filter: brightness(0.7); user-select: none;" alt="Hero Background">
    <div class="position-absolute top-50 start-50 translate-middle text-center text-white">
        <h1 class="display-4 fw-bold" style="user-select: none;">Graduation Essentials</h1>
        <p class="lead mb-4" style="user-select: none;">Find the perfect graduation gifts and memorabilia</p>
        <a href="product.php" class="btn btn-primary btn-lg px-5">Shop Now</a>
    </div>
</div>

<div class="categories-section mb-5">
    <h2 class="text-center mb-4">Shop by Category</h2>
    <div class="row justify-content-center g-4">
        <div class="col-md-5">
            <div class="card category-card h-100">
                <div class="category-image-wrapper">
                    <img src="image/product/Flower/category-bouquet.png" class="card-img-top" alt="Graduation Bouquets">
                </div>
                <div class="card-body text-center">
                    <h3 class="category-title">Graduation Bouquets</h3>
                    <a href="product.php?category=Bouquet" class="btn btn-outline-primary px-4">View Collection</a>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card category-card h-100">
                <div class="category-image-wrapper">
                    <img src="image/product/T-shirt/category-tshirt.png" class="card-img-top" alt="Graduation T-Shirts">
                </div>
                <div class="card-body text-center">
                    <h3 class="category-title">Graduation T-Shirts</h3>
                    <a href="product.php?category=T-shirt" class="btn btn-outline-primary px-4">View Collection</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="recent-products mb-5">
    <h2 class="text-center mb-4">Recently Added Products</h2>
    <div class="row g-4">
        <?php foreach($recentProducts as $product): ?>
        <div class="col-md-3">
            <div class="card recent-product-card h-100">
                <div class="product-image-container">
                    <img src="<?php echo htmlspecialchars($product['productImageUrl']); ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($product['productName']); ?>">
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title product-title"><?php echo htmlspecialchars($product['productName']); ?></h5>
                    <p class="card-text text-primary fw-bold mb-3">RM <?php echo number_format($product['productPrice'], 2); ?></p>
                    <div class="mt-auto text-center">
                        <a href="productDetails.php?id=<?php echo $product['productID']; ?>" 
                           class="btn btn-primary w-100">View Details</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="features-section bg-light py-5 mb-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4">
                <i class="fas fa-shipping-fast fa-3x mb-3"></i>
                <h4>Fast Delivery</h4>
                <p>Quick and reliable shipping to your doorstep</p>
            </div>
            <div class="col-md-4">
                <i class="fas fa-gift fa-3x mb-3"></i>
                <h4>Gift Ready</h4>
                <p>Beautiful packaging for the perfect graduation gift</p>
            </div>
            <div class="col-md-4">
                <i class="fas fa-headset fa-3x mb-3"></i>
                <h4>24/7 Support</h4>
                <p>Always here to help with your orders</p>
            </div>
        </div>
    </div>
</div>

<style>
.hero-section {
    position: relative;
    overflow: hidden;
    margin-top: -1.5rem;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    z-index: 1;
}

.hero-section .position-absolute {
    z-index: 2;
}

.hero-section h1 {
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}

.hero-section .lead {
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
}

.categories-section {
    padding: 3rem 0;
}

.category-card {
    transition: transform 0.3s, box-shadow 0.3s;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    background-color: white;
    border-radius: 12px;
    overflow: hidden;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.category-image-wrapper {
    height: 300px;
    padding: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.category-image-wrapper img {
    max-height: 100%;
    width: auto;
    max-width: 100%;
    object-fit: contain;
}

.category-title {
    font-size: 1.5rem;
    margin-bottom: 1.25rem;
    color: #333;
    font-weight: 600;
}

.category-card .card-body {
    padding: 2rem;
    background-color: white;
}

.category-card .btn {
    padding: 0.75rem 2rem;
    font-weight: 500;
    border-width: 2px;
}

.product-card img {
    height: 200px;
    object-fit: cover;
}

.product-card {
    transition: transform 0.3s;
}

.product-card:hover {
    transform: translateY(-5px);
}

.features-section i {
    color: #007bff;
}

.recent-product-card {
    transition: transform 0.3s, box-shadow 0.3s;
    border: none;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    background-color: white;
}

.recent-product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.product-image-container {
    height: 280px;
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #fff;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.product-image-container img {
    max-height: 100%;
    width: auto;
    max-width: 100%;
    object-fit: contain;
}

.product-title {
    font-size: 1rem;
    margin-bottom: 0.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    height: 2.5rem;
}

@media (max-width: 768px) {
    .product-image-container {
        height: 200px;
    }
    
    .product-title {
        font-size: 0.9rem;
        height: 2.2rem;
    }
}

.recent-products {
    padding: 2rem 0;
    background-color: #f8f9fa;
    border-radius: 10px;
}

.recent-products h2 {
    color: #333;
    margin-bottom: 2rem;
    position: relative;
}

.recent-products h2:after {
    content: '';
    display: block;
    width: 50px;
    height: 3px;
    background: #007bff;
    margin: 15px auto 0;
}

.card-body {
    padding: 1.25rem;
}

.btn-primary {
    padding: 0.5rem 1.5rem;
    font-weight: 500;
}

@media (max-width: 768px) {
    .category-image-wrapper {
        height: 200px;
        padding: 1rem;
    }
    
    .category-title {
        font-size: 1.25rem;
    }
    
    .category-card .card-body {
        padding: 1.5rem;
    }
}
</style>

<?php include 'footer.php'; ?>
