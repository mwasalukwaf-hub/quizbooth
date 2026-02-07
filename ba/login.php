<?php
session_start();
include '../api/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND role = 'ba'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['ba_user_id'] = $user['id'];
        $_SESSION['ba_site_id'] = $user['site_id'];
        
        // Fetch Site Name
        $siteStmt = $pdo->prepare("SELECT name FROM sites WHERE id = ?");
        $siteStmt->execute([$user['site_id']]);
        $_SESSION['site_name'] = $siteStmt->fetchColumn();

        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid credentials or not a BA account.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BA Login | Smirnoff</title>
    <link rel="stylesheet" href="../assets/css/ba.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="ba-container">
        <div class="auth-card">
            <div class="logo-area">
                <h1>Smirnoff</h1>
                <span>Campaign Portal</span>
            </div>
            
            <br><br>

            <?php if($error): ?>
                <div style="color: #ff4444; margin-bottom: 20px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <input type="text" name="username" class="form-control" placeholder="Promoter ID" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <button type="submit" class="btn-neon">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
