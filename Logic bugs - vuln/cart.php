<?php

require __DIR__ . '/config.php';
require_login();

$user = current_user($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $productId = isset($_POST['productId']) ? (int) $_POST['productId'] : 0;
    $quantity  = isset($_POST['quantity'])  ? (int) $_POST['quantity'] : 1;
    $price     = isset($_POST['price'])     ? (float) $_POST['price'] : 0.0;

    if ($quantity < 1)  { $quantity = 1; }
    if ($quantity > 99) { $quantity = 99; }

    $stmt = $pdo->prepare('SELECT id, price FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $productRow = $stmt->fetch();

    if (!$productRow) {
        http_response_code(400);
        echo 'Invalid product.';
        exit;
    }

    if ($price < 0) {
        $price = 0;
    }

    $insert = $pdo->prepare(
        'INSERT INTO cart_items (user_id, product_id, quantity, price) VALUES (?, ?, ?, ?)'
    );
    $insert->execute([$user['id'], $productRow['id'], $quantity, $price]);

    header('Location: product.php?id=' . $productRow['id'] . '&added=1');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT ci.id AS cart_item_id, ci.quantity, ci.price AS cart_price,
            p.id AS product_id, p.name, p.price AS real_price, p.image_emoji
     FROM cart_items ci
     JOIN products p ON p.id = ci.product_id
     WHERE ci.user_id = ?
     ORDER BY ci.id ASC'
);
$stmt->execute([$user['id']]);
$items = $stmt->fetchAll();

$total = 0.0;
foreach ($items as $it) {
    $total += $it['cart_price'] * $it['quantity'];
}

if (isset($_GET['remove'])) {
    $removeId = (int) $_GET['remove'];
    $del = $pdo->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
    $del->execute([$removeId, $user['id']]);
    header('Location: cart.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart - l33t Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">🛒 l33t Store <span class="badge bg-danger">TRAINING LAB</span></a>
        <div class="d-flex align-items-center gap-3">
            <span class="credit-badge">Credit: $<?= number_format((float)$user['store_credit'], 2) ?></span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Log out</a>
        </div>
    </div>
</nav>

<div class="container mt-4" style="max-width: 760px;">
    <a href="index.php" class="btn btn-link px-0">&larr; Continue shopping</a>
    <h3 class="mb-3">Your Cart</h3>

    <?php if (empty($items)): ?>
        <div class="alert alert-secondary">Your cart is empty. <a href="index.php">Browse products</a>.</div>
    <?php else: ?>
        <table class="table table-bordered bg-white cart-table">
            <thead class="table-light">
                <tr>
                    <th></th>
                    <th>Product</th>
                    <th>Real price</th>
                    <th>Cart price</th>
                    <th>Qty</th>
                    <th>Line total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <?php $lineTotal = $it['cart_price'] * $it['quantity']; ?>
                <tr>
                    <td><?= e($it['image_emoji']) ?></td>
                    <td><?= e($it['name']) ?></td>
                    <td>$<?= number_format((float)$it['real_price'], 2) ?></td>
                    <td>
                        <strong<?= ((float)$it['cart_price'] < (float)$it['real_price']) ? ' class="text-danger"' : '' ?>>
                            $<?= number_format((float)$it['cart_price'], 2) ?>
                        </strong>
                        <?php if ((float)$it['cart_price'] < (float)$it['real_price']): ?>
                            <span class="badge bg-danger">tampered</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)$it['quantity'] ?></td>
                    <td>$<?= number_format($lineTotal, 2) ?></td>
                    <td><a href="cart.php?remove=<?= (int)$it['cart_item_id'] ?>" class="btn btn-sm btn-outline-danger">Remove</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" class="text-end">Cart total</th>
                    <th colspan="2">$<?= number_format($total, 2) ?></th>
                </tr>
            </tfoot>
        </table>

        <div class="d-flex justify-content-between align-items-center">
            <span>Your store credit: <strong>$<?= number_format((float)$user['store_credit'], 2) ?></strong></span>
            <a href="checkout.php" class="btn btn-primary btn-lg">Proceed to checkout</a>
        </div>
    <?php endif; ?>
</div>

<footer class="lab-footer">Excessive Trust in Client-Side Controls &mdash; local training lab</footer>
</body>
</html>