<?php
session_start();
include '../api/db.php';

$error = '';

if (isset($_SESSION['admin_user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Check if active
            if (isset($user['is_active']) && $user['is_active'] == 0) {
                $error = "Account deactivated. Please contact administrator.";
            } else {
                $_SESSION['admin_user_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quizzify Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-card { max-width: 400px; width: 100%; border: none; border-radius: 12px; }
        .card-header { background: transparent; border-bottom: none; text-align: center; padding-top: 2rem; }
        .btn-primary { background-color: #0d6efd; border: none; }
    </style>
</head>
<body>

<div class="card login-card shadow-lg p-3">
    <div class="card-header">
        <h3>Admin Login</h3>
        <p class="text-muted">Enter your credentials to access the dashboard.</p>
    </div>
    <div class="card-body">
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
