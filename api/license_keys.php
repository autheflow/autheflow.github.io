<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    sendError('Authentication required', 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch($action) {
    case 'list':
        if ($method === 'GET') {
            getAllLicenses();
        }
        break;
    case 'get':
        if ($method === 'GET') {
            getLicenseById();
        }
        break;
    case 'create':
        if ($method === 'POST') {
            createLicense();
        }
        break;
    case 'update':
        if ($method === 'PUT' || $method === 'POST') {
            updateLicense();
        }
        break;
    case 'delete':
        if ($method === 'DELETE' || $method === 'POST') {
            deleteLicense();
        }
        break;
    case 'generate':
        if ($method === 'GET') {
            generateLicenseKey();
        }
        break;
    case 'stats':
        if ($method === 'GET') {
            getLicenseStats();
        }
        break;
    case 'advanced_stats':
        if ($method === 'GET') {
            getAdvancedStats();
        }
        break;
    case 'extend':
        if ($method === 'POST') {
            extendLicense();
        }
        break;
    case 'expired':
        if ($method === 'GET') {
            getExpiredLicenses();
        }
        break;
    default:
        sendError('Invalid action', 400);
}

function generateRandomKey() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $segments = [];
    
    for ($i = 0; $i < 5; $i++) {
        $segment = '';
        for ($j = 0; $j < 5; $j++) {
            $segment .= $characters[random_int(0, strlen($characters) - 1)];
        }
        $segments[] = $segment;
    }
    
    return implode('-', $segments);
}

function generateLicenseKey() {
    try {
        $pdo = getDBConnection();
        $maxAttempts = 10;
        
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $key = generateRandomKey();
            
            $stmt = $pdo->prepare("SELECT id FROM licenses WHERE license_key = ?");
            $stmt->execute([$key]);
            
            if (!$stmt->fetch()) {
                sendSuccess(['license_key' => $key], 'License key generated successfully');
                return;
            }
        }
        
        sendError('Failed to generate unique license key after ' . $maxAttempts . ' attempts', 500);
        
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage(), 500);
    }
}

function getAllLicenses() {
    try {
        $pdo = getDBConnection();
        
        $software = $_GET['software'] ?? '';
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        
        $sql = "SELECT * FROM licenses WHERE 1=1";
        $params = [];
        
        if ($software) {
            $sql .= " AND software = ?";
            $params[] = $software;
        }
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        if ($search) {
            $sql .= " AND (license_key LIKE ? OR machine_code LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $licenses = $stmt->fetchAll();
        
        foreach ($licenses as &$license) {
            $license['computed_status'] = computeLicenseStatus($license);
        }
        
        sendSuccess($licenses);
    } catch (PDOException $e) {
        sendError('Failed to fetch licenses: ' . $e->getMessage(), 500);
    }
}

function computeLicenseStatus($license) {
    if (empty($license['machine_code'])) {
        return 'inactive';
    }
    
    $today = new DateTime();
    $expirationDate = new DateTime($license['expiration_date']);
    
    if ($today > $expirationDate) {
        return 'expired';
    }
    
    return 'active';
}

function getLicenseById() {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendError('License ID is required', 400);
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM licenses WHERE id = ?");
        $stmt->execute([$id]);
        $license = $stmt->fetch();
        
        if (!$license) {
            sendError('License not found', 404);
        }
        
        $license['computed_status'] = computeLicenseStatus($license);
        
        sendSuccess($license);
    } catch (PDOException $e) {
        sendError('Failed to fetch license: ' . $e->getMessage(), 500);
    }
}

function createLicense() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $licenseKey = strtoupper(trim($data['license_key'] ?? ''));
    $softwareName = trim($data['software'] ?? '');
    $expirationDate = $data['expiration_date'] ?? '';
    
    if (empty($licenseKey)) {
        sendError('License key is required', 400);
    }
    
    if (!preg_match('/^[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/', $licenseKey)) {
        sendError('Invalid license key format. Expected: XXXXX-XXXXX-XXXXX-XXXXX-XXXXX', 400);
    }
    
    if (empty($softwareName)) {
        sendError('Software name is required', 400);
    }
    
    if (empty($expirationDate)) {
        sendError('Expiration date is required', 400);
    }
    
    try {
        $pdo = getDBConnection();
        
        $checkStmt = $pdo->prepare("SELECT id FROM licenses WHERE license_key = ?");
        $checkStmt->execute([$licenseKey]);
        if ($checkStmt->fetch()) {
            sendError('License key already exists', 400);
        }
        
        $checkSoftware = $pdo->prepare("SELECT id FROM Software WHERE name = ?");
        $checkSoftware->execute([$softwareName]);
        if (!$checkSoftware->fetch()) {
            sendError('Software name does not exist', 400);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO licenses (license_key, software, expiration_date, status) 
            VALUES (?, ?, ?, 'inactive')
        ");
        
        $stmt->execute([$licenseKey, $softwareName, $expirationDate]);
        
        $licenseId = $pdo->lastInsertId();
        
        $newLicense = $pdo->prepare("SELECT * FROM licenses WHERE id = ?");
        $newLicense->execute([$licenseId]);
        $license = $newLicense->fetch();
        
        sendSuccess($license, 'License created successfully');
        
    } catch (PDOException $e) {
        sendError('Failed to create license: ' . $e->getMessage(), 500);
    }
}

function updateLicense() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = $data['id'] ?? null;
    
    if (!$id) {
        sendError('License ID is required', 400);
    }
    
    try {
        $pdo = getDBConnection();
        
        $checkStmt = $pdo->prepare("SELECT * FROM licenses WHERE id = ?");
        $checkStmt->execute([$id]);
        $existingLicense = $checkStmt->fetch();
        
        if (!$existingLicense) {
            sendError('License not found', 404);
        }
        
        $updateFields = [];
        $params = [];
        
        if (isset($data['software'])) {
            $softwareName = trim($data['software']);
            $checkSoftware = $pdo->prepare("SELECT id FROM Software WHERE name = ?");
            $checkSoftware->execute([$softwareName]);
            if (!$checkSoftware->fetch()) {
                sendError('Software name does not exist', 400);
            }
            $updateFields[] = "software = ?";
            $params[] = $softwareName;
        }
        
        if (isset($data['expiration_date'])) {
            $updateFields[] = "expiration_date = ?";
            $params[] = $data['expiration_date'];
        }
        
        if (isset($data['machine_code'])) {
            if (!empty($existingLicense['machine_code'])) {
                sendError('Machine code cannot be modified once set', 400);
            }
            $updateFields[] = "machine_code = ?";
            $params[] = trim($data['machine_code']);
        }
        
        if (empty($updateFields)) {
            sendError('No fields to update', 400);
        }
        
        $params[] = $id;
        
        $sql = "UPDATE licenses SET " . implode(", ", $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        sendSuccess(['id' => $id], 'License updated successfully');
        
    } catch (PDOException $e) {
        sendError('Failed to update license: ' . $e->getMessage(), 500);
    }
}

function deleteLicense() {
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$id) {
        sendError('License ID is required', 400);
    }
    
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("DELETE FROM licenses WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            sendError('License not found', 404);
        }
        
        sendSuccess(['id' => $id], 'License deleted successfully');
        
    } catch (PDOException $e) {
        sendError('Failed to delete license: ' . $e->getMessage(), 500);
    }
}

function getLicenseStats() {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN machine_code IS NULL THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN machine_code IS NOT NULL AND expiration_date >= CURDATE() THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN machine_code IS NOT NULL AND expiration_date < CURDATE() THEN 1 ELSE 0 END) as expired
            FROM licenses
        ");
        
        $stats = $stmt->fetch();
        
        sendSuccess($stats);
        
    } catch (PDOException $e) {
        sendError('Failed to fetch statistics: ' . $e->getMessage(), 500);
    }
}

function getAdvancedStats() {
    try {
        $pdo = getDBConnection();
        
        $todayStats = $pdo->query("
            SELECT 
                COUNT(*) as today_total,
                SUM(CASE WHEN machine_code IS NOT NULL AND expiration_date >= CURDATE() THEN 1 ELSE 0 END) as today_active
            FROM licenses 
            WHERE DATE(created_at) = CURDATE()
        ")->fetch();
        
        $yesterdayStats = $pdo->query("
            SELECT COUNT(*) as yesterday_total
            FROM licenses 
            WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        ")->fetch();
        
        $thisMonthStats = $pdo->query("
            SELECT COUNT(*) as month_total
            FROM licenses 
            WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())
        ")->fetch();
        
        $lastMonthStats = $pdo->query("
            SELECT COUNT(*) as last_month_total
            FROM licenses 
            WHERE YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
            AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
        ")->fetch();
        
        $thisYearStats = $pdo->query("
            SELECT COUNT(*) as year_total
            FROM licenses 
            WHERE YEAR(created_at) = YEAR(CURDATE())
        ")->fetch();
        
        $expiringStats = $pdo->query("
            SELECT COUNT(*) as expiring_soon
            FROM licenses 
            WHERE expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND machine_code IS NOT NULL
        ")->fetch();
        
        $dailyGrowth = $yesterdayStats['yesterday_total'] > 0 
            ? round((($todayStats['today_total'] - $yesterdayStats['yesterday_total']) / $yesterdayStats['yesterday_total']) * 100, 2) 
            : 0;
            
        $monthlyGrowth = $lastMonthStats['last_month_total'] > 0 
            ? round((($thisMonthStats['month_total'] - $lastMonthStats['last_month_total']) / $lastMonthStats['last_month_total']) * 100, 2) 
            : 0;
        
        sendSuccess([
            'today' => [
                'total' => (int)$todayStats['today_total'],
                'active' => (int)$todayStats['today_active'],
                'yesterday_total' => (int)$yesterdayStats['yesterday_total'],
                'growth' => $dailyGrowth
            ],
            'month' => [
                'total' => (int)$thisMonthStats['month_total'],
                'last_month_total' => (int)$lastMonthStats['last_month_total'],
                'growth' => $monthlyGrowth
            ],
            'year' => [
                'total' => (int)$thisYearStats['year_total']
            ],
            'expiring_soon' => (int)$expiringStats['expiring_soon']
        ]);
        
    } catch (PDOException $e) {
        sendError('Failed to fetch advanced statistics: ' . $e->getMessage(), 500);
    }
}

function extendLicense() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = $data['id'] ?? null;
    $extendDays = $data['extend_days'] ?? null;
    
    if (!$id) {
        sendError('License ID is required', 400);
    }
    
    if (!$extendDays || !is_numeric($extendDays) || $extendDays <= 0) {
        sendError('Valid extend days required', 400);
    }
    
    try {
        $pdo = getDBConnection();
        
        $checkStmt = $pdo->prepare("SELECT * FROM licenses WHERE id = ?");
        $checkStmt->execute([$id]);
        $license = $checkStmt->fetch();
        
        if (!$license) {
            sendError('License not found', 404);
        }
        
        $stmt = $pdo->prepare("
            UPDATE licenses 
            SET expiration_date = DATE_ADD(expiration_date, INTERVAL ? DAY)
            WHERE id = ?
        ");
        $stmt->execute([$extendDays, $id]);
        
        $newLicense = $pdo->prepare("SELECT * FROM licenses WHERE id = ?");
        $newLicense->execute([$id]);
        $updated = $newLicense->fetch();
        
        sendSuccess([
            'id' => $id,
            'old_expiration' => $license['expiration_date'],
            'new_expiration' => $updated['expiration_date'],
            'extended_days' => $extendDays
        ], 'License extended successfully');
        
    } catch (PDOException $e) {
        sendError('Failed to extend license: ' . $e->getMessage(), 500);
    }
}

function getExpiredLicenses() {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->query("
            SELECT * FROM licenses 
            WHERE expiration_date < CURDATE()
            ORDER BY expiration_date DESC
        ");
        
        $licenses = $stmt->fetchAll();
        
        foreach ($licenses as &$license) {
            $license['computed_status'] = computeLicenseStatus($license);
        }
        
        sendSuccess($licenses);
        
    } catch (PDOException $e) {
        sendError('Failed to fetch expired licenses: ' . $e->getMessage(), 500);
    }
}
?>
