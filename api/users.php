<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    sendError('Authentication required', 401);
}

if ($_SESSION['role'] !== 'admin') {
    sendError('Access denied. Admin privileges required.', 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch($action) {
    case 'list':
        if ($method === 'GET') {
            getAllUsers();
        }
        break;
    case 'get':
        if ($method === 'GET') {
            getUserById();
        }
        break;
    case 'create':
        if ($method === 'POST') {
            createUser();
        }
        break;
    case 'update':
        if ($method === 'PUT' || $method === 'POST') {
            updateUser();
        }
        break;
    case 'delete':
        if ($method === 'DELETE' || $method === 'POST') {
            deleteUser();
        }
        break;
    case 'stats':
        if ($method === 'GET') {
            getUserStats();
        }
        break;
    default:
        sendError('Invalid action', 400);
}

function getAllUsers() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT id, username, role, full_name, email, status, created_at, updated_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll();
        
        sendSuccess($users);
    } catch (PDOException $e) {
        sendError('Failed to fetch users: ' . $e->getMessage(), 500);
    }
}

function getUserById() {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendError('User ID is required', 400);
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, role, full_name, email, status, created_at, updated_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendError('User not found', 404);
        }
        
        sendSuccess($user);
    } catch (PDOException $e) {
        sendError('Failed to fetch user: ' . $e->getMessage(), 500);
    }
}

function createUser() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = $data['username'] ?? null;
    $password = $data['password'] ?? null;
    $role = $data['role'] ?? 'nhan_vien';
    $fullName = $data['full_name'] ?? '';
    $email = $data['email'] ?? '';
    $status = $data['status'] ?? 'active';
    
    if (!$username || !$password) {
        sendError('Username and password are required', 400);
    }
    
    if (!in_array($role, ['admin', 'nhan_vien', 'moderator'])) {
        sendError('Invalid role', 400);
    }
    
    if (!in_array($status, ['active', 'inactive'])) {
        sendError('Invalid status', 400);
    }
    
    if (strlen($password) < 6) {
        sendError('Password must be at least 6 characters', 400);
    }
    
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format', 400);
    }
    
    try {
        $pdo = getDBConnection();
        
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->execute([$username]);
        if ($checkStmt->fetch()) {
            sendError('Username already exists', 400);
        }
        
        if ($email) {
            $checkEmailStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmailStmt->execute([$email]);
            if ($checkEmailStmt->fetch()) {
                sendError('Email already exists', 400);
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, role, full_name, email, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$username, $password, $role, $fullName, $email, $status]);
        
        sendSuccess([
            'id' => $pdo->lastInsertId(),
            'username' => $username,
            'role' => $role,
            'full_name' => $fullName,
            'email' => $email,
            'status' => $status
        ], 'User created successfully');
        
    } catch (PDOException $e) {
        sendError('Failed to create user: ' . $e->getMessage(), 500);
    }
}

function updateUser() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = $data['id'] ?? null;
    
    if (!$id) {
        sendError('User ID is required', 400);
    }
    
    if ($id == $_SESSION['user_id']) {
        sendError('Cannot modify your own account from here. Use profile settings.', 400);
    }
    
    $updateFields = [];
    $params = [];
    
    if (isset($data['role'])) {
        if (!in_array($data['role'], ['admin', 'nhan_vien', 'moderator'])) {
            sendError('Invalid role', 400);
        }
        $updateFields[] = "role = ?";
        $params[] = $data['role'];
    }
    
    if (isset($data['status'])) {
        if (!in_array($data['status'], ['active', 'inactive'])) {
            sendError('Invalid status', 400);
        }
        $updateFields[] = "status = ?";
        $params[] = $data['status'];
    }
    
    if (isset($data['full_name'])) {
        $updateFields[] = "full_name = ?";
        $params[] = $data['full_name'];
    }
    
    if (isset($data['email'])) {
        if ($data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            sendError('Invalid email format', 400);
        }
        $updateFields[] = "email = ?";
        $params[] = $data['email'];
    }
    
    if (isset($data['password']) && !empty($data['password'])) {
        if (strlen($data['password']) < 6) {
            sendError('Password must be at least 6 characters', 400);
        }
        $updateFields[] = "password = ?";
        $params[] = $data['password'];
    }
    
    if (empty($updateFields)) {
        sendError('No fields to update', 400);
    }
    
    $params[] = $id;
    
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?");
        $stmt->execute($params);
        
        if ($stmt->rowCount() === 0) {
            sendError('User not found or no changes made', 404);
        }
        
        sendSuccess(['id' => $id], 'User updated successfully');
        
    } catch (PDOException $e) {
        sendError('Failed to update user: ' . $e->getMessage(), 500);
    }
}

function deleteUser() {
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$id) {
        sendError('User ID is required', 400);
    }
    
    if ($id == $_SESSION['user_id']) {
        sendError('Cannot delete your own account', 400);
    }
    
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            sendError('User not found', 404);
        }
        
        sendSuccess(['id' => $id], 'User deleted successfully');
        
    } catch (PDOException $e) {
        sendError('Failed to delete user: ' . $e->getMessage(), 500);
    }
}

function getUserStats() {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
                SUM(CASE WHEN role = 'nhan_vien' THEN 1 ELSE 0 END) as nhan_vien_count,
                SUM(CASE WHEN role = 'moderator' THEN 1 ELSE 0 END) as moderator_count,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count
            FROM users
        ");
        
        $stats = $stmt->fetch();
        
        sendSuccess($stats);
        
    } catch (PDOException $e) {
        sendError('Failed to fetch statistics: ' . $e->getMessage(), 500);
    }
}
?>
