<?php
require __DIR__ . '/config.php';
require_login();

$user = current_user($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    echo 'Product not found. <a href="index.php">Back to store</a>';
    exit;
}

$added = isset($_GET['added']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($product['name']) ?> - l33t Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">🛒 l33t Store <span class="badge bg-danger">TRAINING LAB</span></a>
        <div class="d-flex align-items-center gap-3">
            <span class="credit-badge">Credit: $<?= number_format((float)$user['store_credit'], 2) ?></span>
            <a href="cart.php" class="btn btn-outline-light btn-sm">Cart</a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Log out</a>
        </div>
    </div>
</nav>

<div class="container mt-4" style="max-width: 640px;">
    <a href="index.php" class="btn btn-link px-0">&larr; Back to store</a>

    <?php if ($added): ?>
        <div class="alert alert-success">Added to cart!</div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="product-emoji mb-3"><?= e($product['image_emoji']) ?></div>
            <h3><?= e($product['name']) ?></h3>
            <p class="text-muted"><?= e($product['description']) ?></p>
            <div class="price-tag mb-3">$<?= number_format((float)$product['price'], 2) ?></div>

            <form method="POST" action="cart.php" class="row g-2 align-items-center">
                <input type="hidden" name="productId" value="<?= (int)$product['id'] ?>">
                <input type="hidden" name="price" value="<?= e(number_format((float)$product['price'], 2, '.', '')) ?>">
                <div class="col-auto">
                    <label class="col-form-label">Qty</label>
                </div>
                <div class="col-auto">
                    <input type="number" name="quantity" value="1" min="1" max="10" class="form-control" style="width:90px;">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-success">Add to cart</button>
                </div>
            </form>
        </div>
    </div>
</div>

<footer class="lab-footer">Excessive Trust in Client-Side Controls &mdash; local training lab</footer>
</body>
</html>
