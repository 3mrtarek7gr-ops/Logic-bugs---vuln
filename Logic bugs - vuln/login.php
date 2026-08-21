<?php
require __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && hash_equals($user['password'], $password)) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit;
    }

    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - l33t Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">🛒 l33t Store <span class="badge bg-danger">TRAINING LAB</span></a>
    </div>
</nav>

<div class="container" style="max-width: 420px; margin-top: 4rem;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h4 class="mb-3">Log in</h4>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" autofocus required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Log in</button>
            </form>

            <hr>
            <small class="text-muted">
                Lab credentials: <code class="inline">wiener</code> / <code class="inline">peter</code>
            </small>
        </div>
    </div>
</div>

<footer class="lab-footer">Excessive Trust in Client-Side Controls &mdash; local training lab</footer>
</body>
</html>