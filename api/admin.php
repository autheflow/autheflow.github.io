<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    sendError('Authentication required', 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch($action) {
    case 'profile':
        if ($method === 'GET') {
            getProfile();
        }
        break;
    case 'change-password':
        if ($method === 'POST') {
            changePassword();
        }
        break;
    case 'update-profile':
        if ($method === 'POST') {
            updateProfile();
        }
        break;
    default:
        sendError('Invalid action', 400);
}

function getProfile() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, email, full_name, role, status, created_at, updated_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendError('User not found', 404);
        }
        
        sendSuccess($user);
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage(), 500);
    }
}

function changePassword() {
    $data = json_decode(file_get_contents('php://input'), true);
    $currentPassword = $data['current_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        sendError('All password fields are required', 400);
    }
    
    if ($newPassword !== $confirmPassword) {
        sendError('New passwords do not match', 400);
    }
    
    if (strlen($newPassword) < 6) {
        sendError('New password must be at least 6 characters', 400);
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendError('User not found', 404);
        }
        
        if ($currentPassword !== $user['password']) {
            sendError('Current password is incorrect', 401);
        }
        
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->execute([$newPassword, $_SESSION['user_id']]);
        
        sendSuccess([], 'Password changed successfully');
        
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage(), 500);
    }
}

function updateProfile() {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $fullName = $data['full_name'] ?? '';
    
    if (empty($email) || empty($fullName)) {
        sendError('Email and full name are required', 400);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format', 400);
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE users SET email = ?, full_name = ? WHERE id = ?");
        $stmt->execute([$email, $fullName, $_SESSION['user_id']]);
        
        $_SESSION['full_name'] = $fullName;
        $_SESSION['email'] = $email;
        
        sendSuccess([
            'email' => $email,
            'full_name' => $fullName
        ], 'Profile updated successfully');
        
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage(), 500);
    }
}
?>
