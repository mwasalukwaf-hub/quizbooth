<?php
include '../api/db.php';

header('Content-Type: application/json');

// Check authorization
session_start();
if (!isset($_SESSION['admin_user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Fetch Current User Role
$stmt = $pdo->prepare("SELECT role FROM admin_users WHERE id = ?");
$stmt->execute([$_SESSION['admin_user_id']]);
$currentUser = $stmt->fetch();
$userRole = $currentUser['role'] ?? 'user';

$action = $_POST['action'] ?? '';

try {
    if ($action === 'delete_option') {
        $id = $_POST['option_id'];
        if ($id) {
            $pdo->prepare("DELETE FROM quiz_options WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Option deleted.']);
        } else {
             throw new Exception('Invalid ID');
        }
    }
    elseif ($action === 'delete_question') {
        $id = $_POST['question_id'];
        if ($id) {
            $pdo->prepare("DELETE FROM quiz_options WHERE question_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM quiz_questions WHERE id = ?")->execute([$id]);
             echo json_encode(['success' => true, 'message' => 'Question deleted.']);
        } else {
             throw new Exception('Invalid ID');
        }
    }
    elseif ($action === 'delete_site') {
        $id = $_POST['site_id'];
         if ($id) {
            $pdo->prepare("DELETE FROM sites WHERE id = ?")->execute([$id]);
             echo json_encode(['success' => true, 'message' => 'Site deleted.']);
        } else {
             throw new Exception('Invalid ID');
        }
    }
    elseif ($action === 'delete_prize') {
        $id = $_POST['prize_id'];
        if ($id) {
            $pdo->prepare("DELETE FROM prizes WHERE id = ?")->execute([$id]);
             echo json_encode(['success' => true, 'message' => 'Prize deleted.']);
        } else {
             throw new Exception('Invalid ID');
        }
    }
    elseif ($action === 'delete_user') {
        if ($userRole !== 'admin') {
             throw new Exception('Permission Denied');
        }
        $id = $_POST['user_id'];
        if ($id == $_SESSION['admin_user_id']) throw new Exception('Cannot delete self');
        
        $pdo->prepare("DELETE FROM admin_users WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'User deleted.']);
    }
    elseif ($action === 'toggle_question_status') {
         $id = $_POST['question_id'];
         $status = $_POST['status'];
         $pdo->prepare("UPDATE quiz_questions SET is_active = ? WHERE id = ?")->execute([$status, $id]);
         echo json_encode(['success' => true, 'message' => 'Status updated.']);
    }
    elseif ($action === 'toggle_option_status') {
         $id = $_POST['option_id'];
         $status = $_POST['status'];
         $pdo->prepare("UPDATE quiz_options SET is_active = ? WHERE id = ?")->execute([$status, $id]);
         echo json_encode(['success' => true, 'message' => 'Status updated.']);
    }
    elseif ($action === 'edit_option') {
          $id = $_POST['option_id'];
          $text = trim($_POST['option_text']);
          $result = $_POST['result_key'];
          $pdo->prepare("UPDATE quiz_options SET option_text = ?, result_key = ? WHERE id = ?")->execute([$text, $result, $id]);
          echo json_encode(['success' => true, 'message' => 'Option updated.']);
    }
    elseif ($action === 'edit_question') {
          $id = $_POST['question_id'];
          $text = trim($_POST['question_text']);
          $pdo->prepare("UPDATE quiz_questions SET question = ? WHERE id = ?")->execute([$text, $id]);
          echo json_encode(['success' => true, 'message' => 'Question updated.']);
    }
    elseif ($action === 'add_question') {
        $text = trim($_POST['question_text']);
        $quiz_id = 1; 
        if ($text) {
            $stmt = $pdo->prepare("INSERT INTO quiz_questions (quiz_id, question) VALUES (?, ?)");
            $stmt->execute([$quiz_id, $text]);
            echo json_encode(['success' => true, 'message' => 'Question added.', 'id' => $pdo->lastInsertId()]);
        } else {
             throw new Exception('Invalid Data');
        }
    }
    elseif ($action === 'add_option') {
        $q_id = $_POST['question_id'];
        $text = trim($_POST['option_text']);
        $result = $_POST['result_key'];
        if ($q_id && $text && $result) {
            $stmt = $pdo->prepare("INSERT INTO quiz_options (question_id, option_text, result_key) VALUES (?, ?, ?)");
            $stmt->execute([$q_id, $text, $result]);
            echo json_encode(['success' => true, 'message' => 'Option added.', 'id' => $pdo->lastInsertId()]);
        } else {
             throw new Exception('Invalid Data');
        }
    }
    elseif ($action === 'upload_media') {
        $flavor = $_POST['flavor'] ?? '';
        $phase = $_POST['phase'] ?? '';
        
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../assets/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            if ($flavor === 'celebrate') {
                $filename = "Video_Generation_Successful.mp4";
                $allowed = ['video/mp4'];
            } else {
                $filename = $flavor . $phase . ".png";
                $allowed = ['image/png', 'image/jpeg', 'image/jpg']; 
            }
            
            $file_type = $_FILES['file']['type'];
            
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_dir . $filename)) {
                echo json_encode(['success' => true, 'message' => 'File uploaded successfully.']);
            } else {
                throw new Exception("Failed to move uploaded file.");
            }
        } else {
             throw new Exception("No file uploaded or upload error.");
        }
    }
    else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
