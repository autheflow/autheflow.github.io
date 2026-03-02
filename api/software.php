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
            getAllSoftware();
        }
        break;
    case 'get':
        if ($method === 'GET') {
            getSoftwareById();
        }
        break;
    case 'create':
        if ($method === 'POST') {
            createSoftware();
        }
        break;
    case 'update':
        if ($method === 'PUT' || $method === 'POST') {
            updateSoftware();
        }
        break;
    case 'delete':
        if ($method === 'DELETE' || $method === 'POST') {
            deleteSoftware();
        }
        break;
    default:
        sendError('Invalid action', 400);
}

function getAllSoftware() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT * FROM Software ORDER BY name ASC");
        $Software = $stmt->fetchAll();
        
        sendSuccess($Software);
    } catch (PDOException $e) {
        sendError('Failed to fetch Software list: ' . $e->getMessage(), 500);
    }
}

function getSoftwareById() {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendError('Software ID is required', 400);
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM Software WHERE id = ?");
        $stmt->execute([$id]);
        $Software = $stmt->fetch();
        
        if (!$Software) {
            sendError('Software not found', 404);
        }
        
        sendSuccess($Software);
    } catch (PDOException $e) {
        sendError('Failed to fetch Software: ' . $e->getMessage(), 500);
    }
}

function createSoftware() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = trim($data['name'] ?? '');
    
    if (empty($name)) {
        sendError('Software name is required', 400);
    }
    
    try {
        $pdo = getDBConnection();
        
        $checkStmt = $pdo->prepare("SELECT id FROM Software WHERE name = ?");
        $checkStmt->execute([$name]);
        if ($checkStmt->fetch()) {
            sendError('Software name already exists', 400);
        }
        
        $stmt = $pdo->prepare("INSERT INTO Software (name) VALUES (?)");
        $stmt->execute([$name]);
        
        sendSuccess([
            'id' => $pdo->lastInsertId(),
            'name' => $name
        ], 'Software created successfully');
        
    } catch (PDOException $e) {
        sendError('Failed to create Software: ' . $e->getMessage(), 500);
    }
}

function updateSoftware() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = $data['id'] ?? null;
    $name = trim($data['name'] ?? '');
    
    if (!$id) {
        sendError('Software ID is required', 400);
    }
    
    if (empty($name)) {
        sendError('Software name is required', 400);
    }
    
    try {
        $pdo = getDBConnection();
        
        $checkStmt = $pdo->prepare("SELECT id FROM Software WHERE name = ? AND id != ?");
        $checkStmt->execute([$name, $id]);
        if ($checkStmt->fetch()) {
            sendError('Software name already exists', 400);
        }
        
        $stmt = $pdo->prepare("UPDATE Software SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        
        if ($stmt->rowCount() === 0) {
            sendError('Software not found or no changes made', 404);
        }
        
        sendSuccess(['id' => $id], 'Software updated successfully');
        
    } catch (PDOException $e) {
        sendError('Failed to update Software: ' . $e->getMessage(), 500);
    }
}

function deleteSoftware() {
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$id) {
        sendError('Software ID is required', 400);
    }
    
    try {
        $pdo = getDBConnection();
        
        $checkLicenses = $pdo->prepare("SELECT COUNT(*) as count FROM licenses WHERE software = (SELECT name FROM Software WHERE id = ?)");
        $checkLicenses->execute([$id]);
        $result = $checkLicenses->fetch();
        
        if ($result['count'] > 0) {
            sendError('Cannot delete software with active licenses', 400);
        }
        
        $stmt = $pdo->prepare("DELETE FROM Software WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            sendError('Software not found', 404);
        }
        
        sendSuccess(['id' => $id], 'Software deleted successfully');
        
    } catch (PDOException $e) {
        sendError('Failed to delete software: ' . $e->getMessage(), 500);
    }
}
?>
