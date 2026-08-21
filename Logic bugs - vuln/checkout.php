<?php

require __DIR__ . '/config.php';
require_login();

$user = current_user($pdo);

function get_cart(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT ci.id AS cart_item_id, ci.quantity, ci.price AS cart_price,
                p.id AS product_id, p.name, p.price AS real_price, p.image_emoji
         FROM cart_items ci
         JOIN products p ON p.id = ci.product_id
         WHERE ci.user_id = ?
         ORDER BY ci.id ASC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

$items = get_cart($pdo, $user['id']);

$total = 0.0;
$tampered = false;
foreach ($items as $it) {
    $total += $it['cart_price'] * $it['quantity'];
    if ((float) $it['cart_price'] < (float) $it['real_price']) {
        $tampered = true;
    }
}

$error = '';
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {

    if (empty($items)) {
        $error = 'Your cart is empty.';
    } elseif ($total > (float) $user['store_credit']) {
        $error = sprintf(
            'Insufficient store credit. Order total is $%s but your balance is only $%s.',
            number_format($total, 2),
            number_format((float) $user['store_credit'], 2)
        );
    } else {
        $pdo->beginTransaction();
        try {
            $orderStmt = $pdo->prepare('INSERT INTO orders (user_id, total) VALUES (?, ?)');
            $orderStmt->execute([$user['id'], $total]);
            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)'
            );
            foreach ($items as $it) {
                $itemStmt->execute([$orderId, $it['product_id'], $it['quantity'], $it['cart_price']]);
            }

            $newCredit = (float) $user['store_credit'] - $total;
            $creditStmt = $pdo->prepare('UPDATE users SET store_credit = ? WHERE id = ?');
            $creditStmt->execute([$newCredit, $user['id']]);

            $clearStmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
            $clearStmt->execute([$user['id']]);

            $pdo->commit();

            $success = [
                'order_id'   => $orderId,
                'total'      => $total,
                'tampered'   => $tampered,
                'new_credit' => $newCredit,
                'items'      => $items,
            ];
            $user['store_credit'] = $newCredit;
        } catch (Exception $ex) {
            $pdo->rollBack();
            $error = 'Order failed: ' . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - l33t Store</title>
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
    <a href="cart.php" class="btn btn-link px-0">&larr; Back to cart</a>
    <h3 class="mb-3">Checkout</h3>

    <?php if ($success): ?>

        <?php if ($success['tampered']): ?>
            <div class="solved-banner mb-4">✅ LAB SOLVED</div>
            <div class="alert alert-warning">
                Order #<?= (int)$success['order_id'] ?> was completed for
                <strong>$<?= number_format($success['total'], 2) ?></strong>,
                even though the real price of at least one item was higher.
                The backend trusted the client-supplied <code class="inline">price</code>
                parameter from <code class="inline">POST /cart</code> instead of
                re-checking it against <code class="inline">products.price</code>.
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                Order #<?= (int)$success['order_id'] ?> placed successfully for
                $<?= number_format($success['total'], 2) ?>.
            </div>
        <?php endif; ?>

        <table class="table table-bordered bg-white">
            <thead class="table-light">
                <tr><th>Product</th><th>Qty</th><th>Price charged</th><th>Line total</th></tr>
            </thead>
            <tbody>
            <?php foreach ($success['items'] as $it): ?>
                <tr>
                    <td><?= e($it['name']) ?></td>
                    <td><?= (int)$it['quantity'] ?></td>
                    <td>$<?= number_format((float)$it['cart_price'], 2) ?></td>
                    <td>$<?= number_format((float)$it['cart_price'] * $it['quantity'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p>Remaining store credit: <strong>$<?= number_format($success['new_credit'], 2) ?></strong></p>
        <a href="index.php" class="btn btn-primary">Back to store</a>

    <?php else: ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="alert alert-secondary">Your cart is empty. <a href="index.php">Browse products</a>.</div>
        <?php else: ?>
            <table class="table table-bordered bg-white">
                <thead class="table-light">
                    <tr><th>Product</th><th>Qty</th><th>Price (from cart)</th><th>Line total</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= e($it['name']) ?></td>
                        <td><?= (int)$it['quantity'] ?></td>
                        <td>$<?= number_format((float)$it['cart_price'], 2) ?></td>
                        <td>$<?= number_format((float)$it['cart_price'] * $it['quantity'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Order total</th>
                        <th>$<?= number_format($total, 2) ?></th>
                    </tr>
                </tfoot>
            </table>

            <p>Your store credit: <strong>$<?= number_format((float)$user['store_credit'], 2) ?></strong></p>

            <form method="POST" action="checkout.php">
                <button type="submit" name="confirm" value="1" class="btn btn-success btn-lg">Confirm &amp; pay</button>
            </form>
        <?php endif; ?>

    <?php endif; ?>
</div>

<footer class="lab-footer">Excessive Trust in Client-Side Controls &mdash; local training lab</footer>
</body>
</html>