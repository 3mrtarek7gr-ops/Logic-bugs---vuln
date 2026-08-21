<?php
require __DIR__ . '/config.php';
require_login();

$user = current_user($pdo);

$products = $pdo->query('SELECT * FROM products ORDER BY id ASC')->fetchAll();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) AS c FROM cart_items WHERE user_id = ?');
$stmt->execute([$user['id']]);
$cartCount = (int) $stmt->fetch()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>l33t Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">🛒 l33t Store <span class="badge bg-danger">TRAINING LAB</span></a>
        <div class="d-flex align-items-center gap-3">
            <span class="credit-badge">Credit: $<?= number_format((float)$user['store_credit'], 2) ?></span>
            <a href="cart.php" class="btn btn-outline-light btn-sm">Cart (<?= $cartCount ?>)</a>
            <span class="text-light">Hi, <?= e($user['username']) ?></span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Log out</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="mb-4">Products</h3>
    <div class="row g-4">
        <?php foreach ($products as $p): ?>
            <div class="col-md-4">
                <div class="product-card d-flex flex-column">
                    <div class="product-emoji mb-2"><?= e($p['image_emoji']) ?></div>
                    <h5><?= e($p['name']) ?></h5>
                    <p class="text-muted small flex-grow-1"><?= e($p['description']) ?></p>
                    <div class="price-tag mb-2">$<?= number_format((float)$p['price'], 2) ?></div>
                    <a href="product.php?id=<?= (int)$p['id'] ?>" class="btn btn-primary">View product</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<footer class="lab-footer">Excessive Trust in Client-Side Controls &mdash; local training lab</footer>
</body>
</html>