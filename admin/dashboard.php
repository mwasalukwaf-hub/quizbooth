<?php
session_start();

// Security Check
if (!isset($_SESSION['admin_user_id'])) {
    header("Location: login.php");
    exit;
}

include '../api/db.php';

// Fetch Current User Role
$stmt = $pdo->prepare("SELECT role, username FROM admin_users WHERE id = ?");
$stmt->execute([$_SESSION['admin_user_id']]);
$currentUser = $stmt->fetch();

// If user not found (deleted) logout
if (!$currentUser) {
    header("Location: logout.php");
    exit;
}

$userRole = $currentUser['role'] ?? 'user';
$userName = $currentUser['username'];

$message = '';
$msg_type = 'success'; // success, danger, warning

// --- HANDLE FORM SUBMISSIONS ---

// 0. Change Own Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_pass_self') {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    
    $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
    $stmt->execute([$_SESSION['admin_user_id']]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($current_pass, $user['password_hash'])) {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$new_hash, $_SESSION['admin_user_id']]);
        $message = "Your password has been updated.";
    } else {
        $message = "Incorrect current password.";
        $msg_type = 'danger';
    }
}

// 1. Add Influencer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_influencer') {
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);
    
    if ($name && $code) {
        try {
            $stmt = $pdo->prepare("INSERT INTO influencers (name, code) VALUES (?, ?)");
            $stmt->execute([$name, $code]);
            $message = "Influencer added successfully!";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $msg_type = 'danger';
        }
    }
}

// 2. Add New User (ADMIN ONLY)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_admin') {
    if ($userRole !== 'admin') {
        $message = "Permission Denied.";
        $msg_type = 'danger';
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'] ?? 'user';
        
        if ($username && $password) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, is_active, role) VALUES (?, ?, 1, ?)");
                $stmt->execute([$username, $hash, $role]);
                $message = "New user account created.";
            } catch (PDOException $e) {
                $message = "Error/Duplicate: " . $e->getMessage();
                $msg_type = 'danger';
            }
        }
    }
}

// 2.1 Admin Actions (Reset Pass, Toggle Status, Delete) - ADMIN ONLY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Toggle Status
    if ($_POST['action'] === 'toggle_status') {
        if ($userRole !== 'admin') {
            $message = "Permission Denied.";
            $msg_type = 'danger';
        } else {
            $user_id = $_POST['user_id'];
            $new_status = $_POST['status']; // 1 or 0
            
            // Prevent deactivating self
            if ($user_id == $_SESSION['admin_user_id']) {
                $message = "You cannot deactivate your own account.";
                $msg_type = 'warning';
            } else {
                $stmt = $pdo->prepare("UPDATE admin_users SET is_active = ? WHERE id = ?");
                $stmt->execute([$new_status, $user_id]);
                $message = "User status updated.";
            }
        }
    }
    
    // Delete User
    if ($_POST['action'] === 'delete_user') {
        if ($userRole !== 'admin') {
            $message = "Permission Denied.";
            $msg_type = 'danger';
        } else {
            $user_id = $_POST['user_id'];
            
            // Prevent deleting self
            if ($user_id == $_SESSION['admin_user_id']) {
                $message = "You cannot delete your own account.";
                $msg_type = 'warning';
            } else {
                $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = "User deleted.";
            }
        }
    }
    
    // Admin Reset Password
    if ($_POST['action'] === 'admin_reset_pass') {
        if ($userRole !== 'admin') {
            $message = "Permission Denied.";
            $msg_type = 'danger';
        } else {
            $user_id = $_POST['user_id'];
            $new_pass = $_POST['new_password'];
            
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $user_id]);
            $message = "User password updated.";
        }
    }
}

// 3. Upload Media
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_media') {
    $flavor = $_POST['flavor'] ?? ''; // original, pineapple, guarana, celebrate
    $phase = $_POST['phase'] ?? '';   // 1, 2, or empty for video
    
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../assets/";
        
        if ($flavor === 'celebrate') {
            $filename = "Video_Generation_Successful.mp4";
            $allowed_types = ['video/mp4'];
        } else {
            $filename = $flavor . $phase . ".png"; // e.g. original1.png
            $allowed_types = ['image/png', 'image/jpeg'];
        }
        
        $target_file = $target_dir . $filename;
        $file_type = $_FILES['file']['type'];

        if (in_array($file_type, $allowed_types) || $flavor !== 'celebrate') {
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                $message = "File updated successfully.";
            } else {
                $message = "Error uploading file.";
                $msg_type = 'danger';
            }
        } else {
            $message = "Invalid file type. Please upload " . ($flavor === 'celebrate' ? "an MP4 video." : "a PNG or JPG image.");
            $msg_type = 'danger';
        }
    }
}

// 4. Quiz Content Management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Add Question
    if ($_POST['action'] === 'add_question') {
        $text = trim($_POST['question_text']);
        $quiz_id = 1; // Default to 1
        if ($text) {
            $stmt = $pdo->prepare("INSERT INTO quiz_questions (quiz_id, question) VALUES (?, ?)");
            $stmt->execute([$quiz_id, $text]);
            $message = "Question added.";
        }
    }

    // Edit Question
    if ($_POST['action'] === 'edit_question') {
        $id = $_POST['question_id'];
        $text = trim($_POST['question_text']);
        if ($text && $id) {
            $stmt = $pdo->prepare("UPDATE quiz_questions SET question = ? WHERE id = ?");
            $stmt->execute([$text, $id]);
            $message = "Question updated.";
        }
    }

    // Delete Question
    if ($_POST['action'] === 'delete_question') {
        $id = $_POST['question_id'];
        if ($id) {
            $pdo->prepare("DELETE FROM quiz_options WHERE question_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM quiz_questions WHERE id = ?")->execute([$id]);
            $message = "Question deleted.";
        }
    }

    // Add Option
    if ($_POST['action'] === 'add_option') {
        $q_id = $_POST['question_id'];
        $text = trim($_POST['option_text']);
        $result = $_POST['result_key'];
        if ($q_id && $text && $result) {
            $stmt = $pdo->prepare("INSERT INTO quiz_options (question_id, option_text, result_key) VALUES (?, ?, ?)");
            $stmt->execute([$q_id, $text, $result]);
            $message = "Option added.";
        }
    }

    // Delete Option
    if ($_POST['action'] === 'delete_option') {
        $id = $_POST['option_id'];
        if ($id) {
            $pdo->prepare("DELETE FROM quiz_options WHERE id = ?")->execute([$id]);
            $message = "Option deleted.";
        }
    }

    // Toggle Mobile Active Status
    if ($_POST['action'] === 'toggle_question_status') {
        $id = $_POST['question_id'];
        $new_status = $_POST['status']; // 1 or 0
        if ($id) {
            $stmt = $pdo->prepare("UPDATE quiz_questions SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            $message = "Question status updated.";
        }
    }

    // Toggle Option Status
    if ($_POST['action'] === 'toggle_option_status') {
        $id = $_POST['option_id'];
        $new_status = $_POST['status'];
        if ($id) {
            $pdo->prepare("UPDATE quiz_options SET is_active = ? WHERE id = ?")->execute([$new_status, $id]);
            $message = "Option status updated.";
        }
    }

    // Edit Option
    if ($_POST['action'] === 'edit_option') {
        $id = $_POST['option_id'];
        $text = trim($_POST['option_text']);
        $result = $_POST['result_key'];
        if ($id && $text && $result) {
            $pdo->prepare("UPDATE quiz_options SET option_text = ?, result_key = ? WHERE id = ?")->execute([$text, $result, $id]);
            $message = "Option updated.";
        }
    }
}

// 5. Manage SITES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_site') {
        $name = trim($_POST['name']);
        $location = trim($_POST['location']);
        // Generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        
        if ($name) {
            try {
                $stmt = $pdo->prepare("INSERT INTO sites (name, slug, location) VALUES (?, ?, ?)");
                $stmt->execute([$name, $slug, $location]);
                $message = "Site added successfully.";
            } catch (PDOException $e) {
                $message = "Error: " . $e->getMessage();
                $msg_type = 'danger';
            }
        }
    }
    
    if ($_POST['action'] === 'delete_site') {
        $id = $_POST['site_id'];
        try {
            $pdo->prepare("DELETE FROM sites WHERE id = ?")->execute([$id]);
            $message = "Site deleted.";
        } catch (Exception $e) {
             $message = "Cannot delete site with active data.";
             $msg_type = 'danger';
        }
    }
}

// 6. Manage PRIZES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_prize') {
        $name = trim($_POST['name']);
        $min_points = intval($_POST['min_points']);
        $prob = intval($_POST['probability']);
        $stock = intval($_POST['stock']);
        
        if ($name) {
            $stmt = $pdo->prepare("INSERT INTO prizes (name, min_points, probability, stock) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $min_points, $prob, $stock]);
            $message = "Prize added.";
        }
    }
    if ($_POST['action'] === 'edit_prize_stock') {
        $id = $_POST['prize_id'];
        $stock = intval($_POST['stock']);
        $pdo->prepare("UPDATE prizes SET stock = ? WHERE id = ?")->execute([$stock, $id]);
        $message = "Stock updated.";
    }
    if ($_POST['action'] === 'delete_prize') {
        $id = $_POST['prize_id'];
        $pdo->prepare("DELETE FROM prizes WHERE id = ?")->execute([$id]);
        $message = "Prize deleted.";
    }
}

// --- DATA FETCHING ---

// Stats
$total_plays = $pdo->query("SELECT COUNT(*) FROM quiz_sessions")->fetchColumn();
$unique_users = $pdo->query("SELECT COUNT(DISTINCT player_name_hash) FROM (SELECT MD5(token) as player_name_hash FROM quiz_sessions) as t")->fetchColumn();

$flavor_stats = $pdo->query("
    SELECT result_key, COUNT(*) as count 
    FROM quiz_sessions 
    WHERE result_key IS NOT NULL 
    GROUP BY result_key
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Lists
$influencers = $pdo->query("SELECT * FROM influencers ORDER BY created_at DESC")->fetchAll();
$sites_list = $pdo->query("SELECT * FROM sites ORDER BY created_at DESC")->fetchAll();
$prizes_list = $pdo->query("SELECT * FROM prizes ORDER BY min_points ASC")->fetchAll();

// Only fetch admins list if user is admin
$admins = [];
if ($userRole === 'admin') {
    $admins = $pdo->query("SELECT * FROM admin_users ORDER BY created_at DESC")->fetchAll();
}

// Quiz Content
$questions = $pdo->query("SELECT * FROM quiz_questions WHERE quiz_id = 1")->fetchAll();
$q_options_raw = $pdo->query("SELECT * FROM quiz_options")->fetchAll();
$q_options = [];
foreach($q_options_raw as $opt) {
    $q_options[$opt['question_id']][] = $opt;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzify Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-color: #d41b2c; /* Smirnoff Red */
            --bg-color: #f4f6f9;
        }
        body { background-color: var(--bg-color); font-family: 'Segoe UI', Roboto, sans-serif; overflow-x: hidden;}
        
        /* Sidebar */
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh; width: var(--sidebar-width);
            background: #1e1e2d; color: #fff; z-index: 1000;
            transition: all 0.3s;
            display: flex; flex-direction: column;
        }
        .sidebar-brand {
            padding: 20px; font-size: 1.5rem; font-weight: bold; 
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: var(--primary-color);
        }
        .nav-link {
            color: #a6a6b7; padding: 15px 20px; font-weight: 500;
            display: flex; align-items: center; border-left: 3px solid transparent;
        }
        .nav-link:hover, .nav-link.active {
            color: #fff; background: rgba(255,255,255,0.05);
            border-left-color: var(--primary-color);
        }
        .nav-link i { width: 30px; }
        
        .sidebar-footer {
            margin-top: auto; padding: 20px; border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width); padding: 30px;
            transition: margin-left 0.3s;
        }
        
        /* Cards */
        .stat-card {
            background: #fff; border-radius: 10px; padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary-color);
        }
        .content-card {
            background: #fff; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); border: none;
        }
        .card-header {
            background: #fff; border-bottom: 1px solid #eee; padding: 20px;
            font-weight: 700; font-size: 1.1rem;
        }

        /* Mobile */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .mobile-toggle { display: block; }
        }
        .mobile-toggle { display: none; position: fixed; bottom: 20px; right: 20px; z-index: 1001; }
        
        /* Image Preview Box */
        .img-preview-box {
            width: 100%; height: 150px; background: #f8f9fa; border: 2px dashed #ddd;
            border-radius: 8px; display: flex; align-items: center; justify-content: center;
            overflow: hidden; margin-bottom: 10px; position: relative;
        }
        .img-preview-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
        
        .status-badge { font-size: 0.8em; padding: 5px 10px; border-radius: 20px; }
        .status-active { background: #e8f5e9; color: #2e7d32; }
        .status-inactive { background: #ffebee; color: #c62828; }
        .role-badge { font-size: 0.75em; padding: 3px 8px; border-radius: 4px; border: 1px solid #ddd; margin-left: 5px; }
    </style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-glass-cheers me-2"></i> QUIZZIFY
    </div>
    <div class="nav flex-column mt-3">
        <a href="#overview" class="nav-link active" onclick="showTab('overview', this)">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="#influencers" class="nav-link" onclick="showTab('influencers', this)">
            <i class="fas fa-users"></i> Influencers
        </a>
        <a href="#sites" class="nav-link" onclick="showTab('sites', this)">
            <i class="fas fa-map-marker-alt"></i> Sites & BAs
        </a>
        <a href="#prizes" class="nav-link" onclick="showTab('prizes', this)">
            <i class="fas fa-gift"></i> Prizes
        </a>
        <a href="#media" class="nav-link" onclick="showTab('media', this)">
            <i class="fas fa-images"></i> Media Manager
        </a>
        <a href="#quiz-content" class="nav-link" onclick="showTab('quiz-content', this)">
            <i class="fas fa-list-ul"></i> Quiz Questions
        </a>
        <?php if($userRole === 'admin'): ?>
        <a href="#admins" class="nav-link" onclick="showTab('admins', this)">
            <i class="fas fa-user-shield"></i> Admin Users
        </a>
        <?php endif; ?>
    </div>
    <div class="sidebar-footer">
        <div class="text-white-50 small mb-2 ps-1">
            Logged in as: <strong class="text-white"><?php echo htmlspecialchars($userName); ?></strong><br>
            Role: <span class="badge bg-secondary"><?php echo ucfirst($userRole); ?></span>
        </div>
        <a href="#" class="text-white text-decoration-none d-block mb-3" data-bs-toggle="modal" data-bs-target="#changePassModal">
            <i class="fas fa-key me-2"></i> Change Password
        </a>
        <a href="logout.php" class="text-danger text-decoration-none">
            <i class="fas fa-sign-out-alt me-2"></i> Logout
        </a>
    </div>
</nav>

<!-- Mobile Toggle -->
<button class="btn btn-primary rounded-circle p-3 shadow-lg mobile-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- Main Content -->
<div class="main-content">
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0">Overview</h3>
        <!-- <div class="text-muted">Welcome, <?php echo htmlspecialchars($userName); ?></div> -->
    </div>

    <?php if($message): ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show shadow-sm" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- CONTENT: OVERVIEW -->
    <div id="overview" class="tab-content">
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <h6 class="text-muted text-uppercase small">Total Sessions</h6>
                    <h2 class="mb-0 fw-bold"><?php echo number_format($total_plays); ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-left-color: #00d2ff;">
                    <h6 class="text-muted text-uppercase small">Original Vibe</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $flavor_stats['original'] ?? 0; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-left-color: #ffe600;">
                    <h6 class="text-muted text-uppercase small">Pineapple Vibe</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $flavor_stats['pineapple'] ?? 0; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-left-color: #ff0055;">
                    <h6 class="text-muted text-uppercase small">Guarana Vibe</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $flavor_stats['guarana'] ?? 0; ?></h2>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-8">
                <div class="card content-card h-100">
                    <div class="card-header border-0 bg-white">Answer Distribution</div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="mainChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card content-card h-100">
                    <div class="card-header border-0 bg-white">Top Influencers</div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php
                            $top_stats = $pdo->query("SELECT influencer, COUNT(*) as c FROM quiz_sessions WHERE influencer IS NOT NULL AND influencer != '' GROUP BY influencer ORDER BY c DESC LIMIT 6")->fetchAll();
                            foreach($top_stats as $stat): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-4 py-3 border-0">
                                    <span class="fw-medium"><?php echo htmlspecialchars($stat['influencer']); ?></span>
                                    <span class="badge bg-light text-dark rounded-pill"><?php echo $stat['c']; ?></span>
                                </div>
                            <?php endforeach; ?>
                            <?php if(empty($top_stats)): ?>
                                <div class="p-4 text-center text-muted">No data yet</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENT: INFLUENCERS -->
    <div id="influencers" class="tab-content" style="display:none;">
        <div class="card content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Registered Influencers / Venues</span>
                <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addInfluencerModal">
                    <i class="fas fa-plus me-1"></i> Add New
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light"><tr><th class="ps-4">Name</th><th>Code</th><th>Joined</th></tr></thead>
                        <tbody>
                        <?php foreach($influencers as $inf): ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?php echo htmlspecialchars($inf['name']); ?></td>
                                <td><code><?php echo htmlspecialchars($inf['code']); ?></code></td>
                                <td class="text-muted small"><?php echo date('M d, Y', strtotime($inf['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENT: SITES -->
    <div id="sites" class="tab-content" style="display:none;">
        <div class="row g-4">
            <!-- Add Site -->
            <div class="col-md-4">
                <div class="card content-card sticky-top" style="top: 20px;">
                    <div class="card-header">Add New Site</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_site">
                            <div class="mb-3">
                                <label class="form-label">Venue Name</label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g. Elements">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Location (Area)</label>
                                <input type="text" name="location" class="form-control" placeholder="e.g. Masaki">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Add Site</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- List Sites -->
            <div class="col-md-8">
                <div class="card content-card">
                    <div class="card-header">Registered Sites</div>
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light"><tr><th class="ps-4">Name/Slug</th><th>QP Code Link</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach($sites_list as $s): 
                                $qrLink = "http://" . $_SERVER['HTTP_HOST'] . "/quizbooth/?site=" . $s['slug'];
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?php echo htmlspecialchars($s['name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($s['slug']); ?></small>
                                    </td>
                                    <td>
                                        <input type="text" readonly class="form-control form-control-sm" value="<?php echo $qrLink; ?>" onclick="this.select()">
                                    </td>
                                    <td>
                                        <button type="button" onclick="confirmDelete(this, <?php echo $s['id']; ?>, 'delete_site', 'Delete Site?', 'Active quizzes on this site might be affected.')" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENT: PRIZES -->
    <div id="prizes" class="tab-content" style="display:none;">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card content-card">
                    <div class="card-header">Add Prize</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_prize">
                            <div class="mb-3">
                                <label class="form-label">Prize Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Min Buckets (Points)</label>
                                <input type="number" name="min_points" class="form-control" value="1" min="1">
                                <small class="text-muted">Buckets needed to unlock this prize.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Probability (Weight)</label>
                                <input type="number" name="probability" class="form-control" value="10" min="1">
                                <small class="text-muted">Higher = More frequent.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Initial Stock</label>
                                <input type="number" name="stock" class="form-control" value="100">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Add Prize</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card content-card">
                    <div class="card-header">Prize Pool Config</div>
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light"><tr><th class="ps-4">Name</th><th>Min Pts</th><th>Weight</th><th>Stock</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach($prizes_list as $p): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $p['min_points']; ?></span></td>
                                    <td><?php echo $p['probability']; ?></td>
                                    <td>
                                        <form method="POST" class="d-flex gap-2">
                                            <input type="hidden" name="action" value="edit_prize_stock">
                                            <input type="hidden" name="prize_id" value="<?php echo $p['id']; ?>">
                                            <input type="number" name="stock" value="<?php echo $p['stock']; ?>" class="form-control form-control-sm" style="width: 70px;">
                                            <button type="submit" class="btn btn-sm btn-light"><i class="fas fa-save"></i></button>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" onclick="confirmDelete(this, <?php echo $p['id']; ?>, 'delete_prize', 'Delete Prize?', 'This will remove it from the prize pool.')" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENT: MEDIA MANAGER -->
    <div id="media" class="tab-content" style="display:none;">
        <div class="row g-4">
            <?php 
            $flavors = [
                'original' => ['color' => '#00d2ff', 'name' => 'Original'], 
                'pineapple' => ['color' => '#ffe600', 'name' => 'Pineapple'], 
                'guarana' => ['color' => '#ff0055', 'name' => 'Guarana']
            ];
            foreach($flavors as $key => $info): 
            ?>
            <div class="col-md-4">
                <div class="card content-card h-100">
                    <div class="card-header text-white" style="background-color: <?php echo $info['color']; ?>">
                        <i class="fas fa-cocktail me-2"></i> <?php echo $info['name']; ?> Results
                    </div>
                    <div class="card-body">
                        <!-- Phase 1 -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">PHASE 1 (REVEAL)</label>
                            <div class="img-preview-box">
                                <img src="../assets/<?php echo $key; ?>1.png?v=<?php echo time(); ?>" alt="Current Image">
                            </div>
                            <form method="POST" enctype="multipart/form-data" class="d-flex gap-2" onsubmit="return handleUpload(event)">
                                <input type="hidden" name="action" value="upload_media">
                                <input type="hidden" name="flavor" value="<?php echo $key; ?>">
                                <input type="hidden" name="phase" value="1">
                                <input type="file" name="file" class="form-control form-control-sm" required accept="image/png, image/jpeg, image/jpg">
                                <button type="submit" class="btn btn-sm btn-outline-dark"><i class="fas fa-upload"></i></button>
                            </form>
                        </div>
                        
                        <!-- Phase 2 -->
                        <div>
                            <label class="form-label small fw-bold text-muted">PHASE 2 (DETAILS)</label>
                            <div class="img-preview-box">
                                <img src="../assets/<?php echo $key; ?>2.png?v=<?php echo time(); ?>" alt="Current Image">
                            </div>
                            <form method="POST" enctype="multipart/form-data" class="d-flex gap-2" onsubmit="return handleUpload(event)">
                                <input type="hidden" name="action" value="upload_media">
                                <input type="hidden" name="flavor" value="<?php echo $key; ?>">
                                <input type="hidden" name="phase" value="2">
                                <input type="file" name="file" class="form-control form-control-sm" required accept="image/png, image/jpeg, image/jpg">
                                <button type="submit" class="btn btn-sm btn-outline-dark"><i class="fas fa-upload"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CONTENT: QUIZ QUESTIONS -->
    <div id="quiz-content" class="tab-content" style="display:none;">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Manage Questions (Quiz 1)</span>
                        <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                            <i class="fas fa-plus me-1"></i> Add Question
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="questionsAccordion">
                            <?php foreach($questions as $index => $q): 
                                $opts = $q_options[$q['id']] ?? [];
                            ?>
                            <div class="accordion-item border-0 mb-3 shadow-sm rounded overflow-hidden">
                                <h2 class="accordion-header">
                                    <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?> <?php echo ($q['is_active'] ?? 1) == 0 ? 'bg-secondary text-white-50' : 'bg-light'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $q['id']; ?>">
                                        <span class="fw-bold me-2">Q<?php echo $index+1; ?>:</span> 
                                        <span id="q-text-<?php echo $q['id']; ?>"><?php echo htmlspecialchars($q['question']); ?></span>
                                        <?php if(($q['is_active'] ?? 1) == 0): ?>
                                            <span class="badge bg-danger ms-2">INACTIVE</span>
                                        <?php endif; ?>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $q['id']; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" data-bs-parent="#questionsAccordion">
                                    <div class="accordion-body bg-white">
                                        <div class="d-flex justify-content-end mb-3 gap-2">
                                            <!-- Toggle Active Button -->
                                            <!-- Toggle Active Button -->
                                            <?php $isActive = $q['is_active'] ?? 1; ?>
                                            <?php if($isActive): ?>
                                                <button type="button" onclick="toggleStatus(this, 'toggle_question_status', <?php echo $q['id']; ?>, 0)" class="btn btn-sm btn-outline-warning"><i class="fas fa-eye-slash"></i> Deactivate</button>
                                            <?php else: ?>
                                                <button type="button" onclick="toggleStatus(this, 'toggle_question_status', <?php echo $q['id']; ?>, 1)" class="btn btn-sm btn-outline-success"><i class="fas fa-eye"></i> Activate</button>
                                            <?php endif; ?>

                                            <button class="btn btn-sm btn-outline-primary" onclick='openEditQuestionModal(<?php echo $q['id']; ?>, <?php echo json_encode($q['question']); ?>)'>
                                                <i class="fas fa-edit"></i> Edit Text
                                            </button>
                                            <button type="button" onclick="confirmDelete(this, <?php echo $q['id']; ?>, 'delete_question', 'Delete Question?', 'This will also delete associated options.')" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> Delete</button>
                                        </div>
                                        
                                        <h6 class="text-muted text-uppercase small fw-bold mb-3">Options</h6>
                                        <ul class="list-group mb-3">
                                            <?php foreach($opts as $opt): 
                                                $optActive = $opt['is_active'] ?? 1;
                                            ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $optActive ? '' : 'bg-light text-muted'; ?>">
                                                <div>
                                                    <span class="badge bg-secondary me-2"><?php echo $opt['result_key']; ?></span>
                                                    <span id="opt-text-<?php echo $opt['id']; ?>"><?php echo htmlspecialchars($opt['option_text']); ?></span>
                                                    <?php if(!$optActive): ?>
                                                        <span class="badge bg-danger ms-1" style="font-size: 0.6em">INACTIVE</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <!-- Toggle Option -->
                                                    <!-- Toggle Option -->
                                                    <button type="button" onclick="toggleStatus(this, 'toggle_option_status', <?php echo $opt['id']; ?>, <?php echo $optActive ? 0 : 1; ?>)" class="btn btn-link p-0 text-<?php echo $optActive ? 'warning' : 'success'; ?>" title="<?php echo $optActive ? 'Deactivate' : 'Activate'; ?>">
                                                        <i class="fas fa-<?php echo $optActive ? 'eye-slash' : 'eye'; ?>"></i>
                                                    </button>

                                                    <!-- Edit Option -->
                                                    <button class="btn btn-link p-0 text-primary" onclick='openEditOptionModal(<?php echo $opt['id']; ?>, <?php echo json_encode($opt['option_text']); ?>, "<?php echo $opt['result_key']; ?>")'>
                                                        <i class="fas fa-edit"></i>
                                                    </button>

                                                    <!-- Delete Option -->
                                                    <!-- Delete Option -->
                                                    <button type="button" onclick="confirmDelete(this, <?php echo $opt['id']; ?>, 'delete_option', 'Delete Option?', 'Are you sure you want to remove this option?')" class="btn btn-link text-danger p-0"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        
                                        <!-- Add Option Form -->
                                        <form method="POST" onsubmit="return handleAdd(event, 'option')" class="row g-2 align-items-center bg-light p-2 rounded">
                                            <input type="hidden" name="action" value="add_option">
                                            <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                                            <div class="col-md-7">
                                                <input type="text" name="option_text" class="form-control form-control-sm" placeholder="New option text..." required>
                                            </div>
                                            <div class="col-md-3">
                                                <select name="result_key" class="form-select form-select-sm">
                                                    <option value="original">Original</option>
                                                    <option value="pineapple">Pineapple</option>
                                                    <option value="guarana">Guarana</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-sm btn-dark w-100">Add Option</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENT: ADMIN USERS (ADMIN ROLE ONLY) -->
    <?php if($userRole === 'admin'): ?>
    <div id="admins" class="tab-content" style="display:none;">
        <div class="row g-4">
            <div class="col-md-8">
                <div class="card content-card h-100">
                    <div class="card-header">System Administrators</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light"><tr><th class="ps-4">Username</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
                                <?php foreach($admins as $u): 
                                    $isActive = !isset($u['is_active']) || $u['is_active'] == 1;
                                    $uRole = $u['role'] ?? 'user';
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold d-block"><?php echo htmlspecialchars($u['username']); ?></span>
                                            <small class="text-muted">Created: <?php echo date('M d, Y', strtotime($u['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="role-badge <?php echo $uRole === 'admin' ? 'bg-dark text-white' : 'bg-light text-dark'; ?>">
                                                <?php echo strtoupper($uRole); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($isActive): ?>
                                                <span class="badge status-badge status-active">Active</span>
                                            <?php else: ?>
                                                <span class="badge status-badge status-inactive">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light rounded-circle" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu border-0 shadow">
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="openAdminPassModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')">
                                                            Reset Password
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form method="POST" style="display:inline;" onsubmit="return confirmSubmit(event, '<?php echo $isActive ? 'Deactivate' : 'Activate'; ?> User?', 'Change access status for this user.');">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                            <input type="hidden" name="status" value="<?php echo $isActive ? '0' : '1'; ?>">
                                                            <button type="submit" class="dropdown-item">
                                                                <?php echo $isActive ? 'Deactivate' : 'Activate'; ?> User
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button" onclick="confirmDelete(this, <?php echo $u['id']; ?>, 'delete_user', 'Delete User?', 'This action is permanent!')" class="dropdown-item text-danger">
                                                            Delete User
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card content-card bg-light border sticky-top" style="top: 20px;">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Add New User</h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_admin">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required autocomplete="new-password">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select">
                                    <option value="user">Normal User</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-dark w-100">Create Account</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- DATA MODALS -->

<!-- Add Influencer Modal -->
<div class="modal fade" id="addInfluencerModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Add Influencer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
      <div class="modal-body">
          <input type="hidden" name="action" value="add_influencer">
          <div class="mb-3">
              <label class="form-label">Name or Venue</label>
              <input type="text" name="name" class="form-control form-control-lg" required placeholder="e.g. The Alchemist">
          </div>
          <div class="mb-3">
              <label class="form-label">Unique Code (Optional identifier)</label>
              <input type="text" name="code" class="form-control" placeholder="e.g. ALCHEMIST01">
          </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary px-4">Save</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Change Own Password Modal -->
<div class="modal fade" id="changePassModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Change My Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
      <div class="modal-body">
          <input type="hidden" name="action" value="change_pass_self">
          <div class="mb-3">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" required minlength="6">
          </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary px-4">Update Password</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Admin Reset User Password Modal -->
<div class="modal fade" id="adminResetPassModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Reset Password for <span id="resetTargetName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
      <div class="modal-body">
          <input type="hidden" name="action" value="admin_reset_pass">
          <input type="hidden" name="user_id" id="resetTargetId">
          <div class="mb-3">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" required minlength="6">
          </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger px-4">Reset Password</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">New Question</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" onsubmit="return handleAdd(event, 'question')">
      <div class="modal-body">
          <input type="hidden" name="action" value="add_question">
          <div class="mb-3">
              <label class="form-label">Question Text</label>
              <textarea name="question_text" class="form-control" rows="3" required></textarea>
          </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary px-4">Add Question</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Question Modal -->
<div class="modal fade" id="editQuestionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Edit Question</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" onsubmit="return handleEdit(event, 'question')">
      <div class="modal-body">
          <input type="hidden" name="action" value="edit_question">
          <input type="hidden" name="question_id" id="editQId">
          <div class="mb-3">
              <label class="form-label">Question Text</label>
              <textarea name="question_text" id="editQText" class="form-control" rows="3" required></textarea>
          </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
      </div>
      </form>
    </div>
  </div>
</div>



<!-- DATA MODALS -->

<!-- Add Influencer Modal -->
<div class="modal fade" id="addInfluencerModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Add Influencer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
      <div class="modal-body">
          <input type="hidden" name="action" value="add_influencer">
          <div class="mb-3">
              <label class="form-label">Name or Venue</label>
              <input type="text" name="name" class="form-control form-control-lg" required placeholder="e.g. The Alchemist">
          </div>
          <div class="mb-3">
              <label class="form-label">Unique Code (Optional identifier)</label>
              <input type="text" name="code" class="form-control" placeholder="e.g. ALCHEMIST01">
          </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary px-4">Save</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Change Own Password Modal -->
<div class="modal fade" id="changePassModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Change My Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
      <div class="modal-body">
          <input type="hidden" name="action" value="change_pass_self">
          <div class="mb-3">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" required minlength="6">
          </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary px-4">Update Password</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Admin Reset User Password Modal -->
<div class="modal fade" id="adminResetPassModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Reset Password for <span id="resetTargetName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
      <div class="modal-body">
          <input type="hidden" name="action" value="admin_reset_pass">
          <input type="hidden" name="user_id" id="resetTargetId">
          <div class="mb-3">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" required minlength="6">
          </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger px-4">Reset Password</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Option Modal -->
<div class="modal fade" id="editOptionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">Edit Option</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" onsubmit="return handleEdit(event, 'option')">
      <div class="modal-body">
          <input type="hidden" name="action" value="edit_option">
          <input type="hidden" name="option_id" id="editOptId">
          <div class="mb-3">
              <label class="form-label">Option Text</label>
              <input type="text" name="option_text" id="editOptText" class="form-control" required>
          </div>
          <div class="mb-3">
              <label class="form-label">Result Mapping</label>
              <select name="result_key" id="editOptResult" class="form-select">
                  <option value="original">Original</option>
                  <option value="pineapple">Pineapple</option>
                  <option value="guarana">Guarana</option>
              </select>
          </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
      </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// AJAX Helper
async function performAction(action, data, successMsg) {
    const formData = new FormData();
    formData.append('action', action);
    for (const key in data) {
        formData.append(key, data[key]);
    }
    
    try {
        const response = await fetch('../api/admin_actions.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            Toast.fire({
                icon: 'success',
                title: result.message || successMsg
            });
            return true;
        } else {
            Swal.fire('Error', result.message || 'Action failed', 'error');
            return false;
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', 'Network or server error', 'error');
        return false;
    }
}

function confirmDelete(btn, id, action, title, text) {
    Swal.fire({
        title: title || 'Are you sure?',
        text: text || "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d41b2c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it'
    }).then(async (result) => {
        if (result.isConfirmed) {
            let data = {};
            if (action === 'delete_option') data = { option_id: id };
            if (action === 'delete_question') data = { question_id: id };
            if (action === 'delete_site') data = { site_id: id };
            if (action === 'delete_prize') data = { prize_id: id };
            if (action === 'delete_user') data = { user_id: id };
            
            const success = await performAction(action, data, 'Item deleted successfully.');
            if(success) {
                // Find and remove closest container (tr for tables, li for list items, .accordion-item for questions)
                const container = btn.closest('tr') || btn.closest('li') || btn.closest('.accordion-item');
                if(container) {
                    container.style.transition = 'all 0.3s';
                    container.style.opacity = '0';
                    setTimeout(() => container.remove(), 300);
                }
            }
        }
    });
}

function toggleStatus(btn, action, id, newStatus) {
    let data = { status: newStatus };
    if (action === 'toggle_question_status') data.question_id = id;
    if (action === 'toggle_option_status') data.option_id = id;
    
    performAction(action, data, 'Status updated.').then(success => {
        if(success) {
            // Update Button State
            if(newStatus == 0) {
                // Became Inactive
                btn.setAttribute('onclick', `toggleStatus(this, '${action}', ${id}, 1)`);
                if(btn.tagName === 'BUTTON') {
                     // Handle Icon/Text
                     if(btn.classList.contains('btn-outline-warning')) {
                         btn.classList.replace('btn-outline-warning', 'btn-outline-success');
                         btn.innerHTML = '<i class="fas fa-eye"></i> Activate';
                     } else {
                         // Option link style
                         btn.className = 'btn btn-link p-0 text-success';
                         btn.title = 'Activate';
                         btn.innerHTML = '<i class="fas fa-eye"></i>';
                     }
                }
            } else {
                // Became Active
                btn.setAttribute('onclick', `toggleStatus(this, '${action}', ${id}, 0)`);
                 if(btn.tagName === 'BUTTON') {
                     if(btn.classList.contains('btn-outline-success')) {
                         btn.classList.replace('btn-outline-success', 'btn-outline-warning');
                         btn.innerHTML = '<i class="fas fa-eye-slash"></i> Deactivate';
                     } else {
                         // Option link style
                         btn.className = 'btn btn-link p-0 text-warning';
                         btn.title = 'Deactivate';
                         btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
                     }
                }
            }
        }
    });
}


async function handleAdd(e, type) {
    e.preventDefault();
    const form = e.target;
    // ... logic ...
    const formData = new FormData(form);
    
    try {
        const response = await fetch('../api/admin_actions.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success) {
            // Success Notification
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            Toast.fire({
                icon: 'success',
                title: result.message
            });
            
            if(type === 'question') {
                 const newId = result.id;
                 const text = formData.get('question_text');
                 
                 const html = `
                    <div class="accordion-item border-0 mb-3 shadow-sm rounded overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-secondary text-white-50" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${newId}">
                                <span class="fw-bold me-2">New:</span> <span id="q-text-${newId}">${text}</span> <span class="badge bg-danger ms-2">INACTIVE</span>
                            </button>
                        </h2>
                        <div id="collapse${newId}" class="accordion-collapse collapse" data-bs-parent="#questionsAccordion">
                            <div class="accordion-body bg-white">
                                <div class="d-flex justify-content-end mb-3 gap-2">
                                    <button type="button" onclick="toggleStatus(this, 'toggle_question_status', ${newId}, 1)" class="btn btn-sm btn-outline-success"><i class="fas fa-eye"></i> Activate</button>
                                    
                                    <button class="btn btn-sm btn-outline-primary" onclick='openEditQuestionModal(${newId}, ${JSON.stringify(text)})'>
                                        <i class="fas fa-edit"></i> Edit Text
                                    </button>
                                    <button type="button" onclick="confirmDelete(this, ${newId}, 'delete_question', 'Delete Question?', 'This will also delete associated options.')" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> Delete</button>
                                </div>
                                <h6 class="text-muted text-uppercase small fw-bold mb-3">Options</h6>
                                <ul class="list-group mb-3"></ul>
                                <form method="POST" onsubmit="return handleAdd(event, 'option')" class="row g-2 align-items-center bg-light p-2 rounded">
                                    <input type="hidden" name="action" value="add_option">
                                    <input type="hidden" name="question_id" value="${newId}">
                                    <div class="col-md-7">
                                        <input type="text" name="option_text" class="form-control form-control-sm" placeholder="New option text..." required>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="result_key" class="form-select form-select-sm">
                                            <option value="original">Original</option>
                                            <option value="pineapple">Pineapple</option>
                                            <option value="guarana">Guarana</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-sm btn-dark w-100">Add Option</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>`;
                 
                 document.getElementById('questionsAccordion').insertAdjacentHTML('beforeend', html);
                 
                 const modal = bootstrap.Modal.getInstance(document.getElementById('addQuestionModal'));
                 if(modal) modal.hide();
                 form.reset();
            }
            else if(type === 'option') {
                 const newId = result.id;
                 const text = formData.get('option_text');
                 const key = formData.get('result_key');
                 
                 const html = `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-secondary me-2">${key}</span> <span id="opt-text-${newId}">${text}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" onclick="toggleStatus(this, 'toggle_option_status', ${newId}, 0)" class="btn btn-link p-0 text-success" title="Activate">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-link p-0 text-primary" onclick='openEditOptionModal(${newId}, ${JSON.stringify(text)}, "${key}")'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" onclick="confirmDelete(this, ${newId}, 'delete_option', 'Delete Option?', 'Are you sure you want to remove this option?')" class="btn btn-link text-danger p-0"><i class="fas fa-trash"></i></button>
                        </div>
                    </li>`;
                 
                 const ul = form.previousElementSibling;
                 if(ul) ul.insertAdjacentHTML('beforeend', html);
                 form.reset();
            }
        } else {
             Swal.fire('Error', result.message || 'Action failed', 'error');
        }
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Server Error', 'error');
    }
}

async function handleEdit(e, type) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('../api/admin_actions.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            Toast.fire({
                icon: 'success',
                title: result.message
            });
            
            if(type === 'question') {
                const id = formData.get('question_id');
                const text = formData.get('question_text');
                const el = document.getElementById('q-text-' + id);
                if(el) el.innerText = text;
                
                // Update the onclick of the Edit button to have the new text
                // Attempt to find the edit button in the DOM?
                // The openEditQuestionModal call has the text embedded. This is tricky.
                // If the user clicks Edit *again*, it will show the OLD text unless I update the onClick attribute.
                // Or I can change openEditQuestionModal to fetch existing text from DOM instead of param.
                
                bootstrap.Modal.getInstance(document.getElementById('editQuestionModal')).hide();
            }
            else if(type === 'option') {
                const id = formData.get('option_id');
                const text = formData.get('option_text');
                const el = document.getElementById('opt-text-' + id);
                if(el) el.innerText = text;
                
                // Maybe update result key badge too?
                // The form likely doesn't allow editing result key?
                // Wait, it DOES allow editing result key: <select name="result_key" ...> in Edit Option Modal.
                // I need to update the badge text too.
                // I didn't verify if Edit Option Modal has Result Key select.
                // Let's check... Yes it does (lines 1269-1274 in previous view).
                
                // I need to find the Badge which is the previous sibling of the text span.
                if(el) {
                    const badge = el.previousElementSibling;
                    if(badge && badge.classList.contains('badge')) {
                        // Wait, result_key in form might not be present if I didn't check.
                        // Actually the Edit Option form DOES have result_key select.
                        // I should update it.
                         const key = formData.get('result_key');
                         if(key) badge.innerText = key;
                    }
                }
                
                bootstrap.Modal.getInstance(document.getElementById('editOptionModal')).hide();
            }
        } else {
             Swal.fire('Error', result.message || 'Action failed', 'error');
        }
    } catch(error) {
         console.error(error);
         Swal.fire('Error', 'Server Error', 'error');
    }
}

// Tab Logic

async function handleUpload(e) {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    const formData = new FormData(form);
    
    try {
        const response = await fetch('../api/admin_actions.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            Toast.fire({
                icon: 'success',
                title: result.message
            });
            
            const parentDiv = form.parentElement;
            const img = parentDiv.querySelector('img');
            const video = parentDiv.querySelector('video source');
            const videoEl = parentDiv.querySelector('video');
            
            const timestamp = new Date().getTime();
            
            if (img) {
                const src = img.src.split('?')[0];
                img.src = src + '?v=' + timestamp;
            }
            if (video && videoEl) {
                const src = video.src.split('?')[0];
                video.src = src + '?v=' + timestamp;
                videoEl.load();
            }
            
            form.reset();
        } else {
             Swal.fire('Error', result.message || 'Upload failed', 'error');
        }
    } catch(error) {
         console.error(error);
         Swal.fire('Error', 'Server Error', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

function showTab(tabId, el) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(d => d.style.display = 'none');
    // Show target
    document.getElementById(tabId).style.display = 'block';
    
    // Nav Active State
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    el.classList.add('active');
    
    // Update Header Text
    const titles = {
        'overview': 'Overview', 
        'influencers': 'Manage Influencers', 
        'sites': 'Site Management',
        'prizes': 'Prize Configuration',
        'media': 'Media Assets', 
        'quiz-content': 'Quiz Questions',
        'admins': 'Access Control'
    };
    document.querySelector('h3').innerText = titles[tabId];

    // Mobile sidebar close
    if(window.innerWidth < 768) toggleSidebar();
}

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
}

function openAdminPassModal(id, username) {
    document.getElementById('resetTargetId').value = id;
    document.getElementById('resetTargetName').innerText = username;
    new bootstrap.Modal(document.getElementById('adminResetPassModal')).show();
}

function openEditQuestionModal(id, text) {
    document.getElementById('editQId').value = id;
    // Try to get fresh text from DOM if available
    const el = document.getElementById('q-text-' + id);
    document.getElementById('editQText').value = el ? el.innerText : text;
    new bootstrap.Modal(document.getElementById('editQuestionModal')).show();
}

function openEditOptionModal(id, text, resultKey) {
    document.getElementById('editOptId').value = id;
    // Try to get fresh text from DOM
    const el = document.getElementById('opt-text-' + id);
    document.getElementById('editOptText').value = el ? el.innerText : text;
    document.getElementById('editOptResult').value = resultKey;
    new bootstrap.Modal(document.getElementById('editOptionModal')).show();
}

// Charts
const ctx = document.getElementById('mainChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Original', 'Pineapple', 'Guarana'],
        datasets: [{
            label: 'Total Selections',
            data: [
                <?php echo $flavor_stats['original'] ?? 0; ?>, 
                <?php echo $flavor_stats['pineapple'] ?? 0; ?>, 
                <?php echo $flavor_stats['guarana'] ?? 0; ?>
            ],
            backgroundColor: ['#00d2ff', '#ffe600', '#ff0055'],
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>
