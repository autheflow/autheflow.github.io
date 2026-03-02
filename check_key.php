<?php
require_once 'api/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method === 'GET') {
    checkLicenseKey();
} elseif ($method === 'POST') {
    activateLicenseKey();
} else {
    sendResponse(['found' => false, 'error' => 'Invalid request method'], 405);
}

function checkLicenseKey() {
    $licenseKey = $_GET['License'] ?? $_GET['license'] ?? '';
    
    if (empty($licenseKey)) {
        sendResponse(['found' => false, 'error' => 'License key is required'], 400);
        return;
    }
    
    $licenseKey = strtoupper(trim($licenseKey));
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT 
                license_key,
                machine_code,
                software,
                activation_date,
                expiration_date
            FROM licenses 
            WHERE license_key = ?
        ");
        $stmt->execute([$licenseKey]);
        $license = $stmt->fetch();
        
        if (!$license) {
            sendResponse(['found' => false, 'error' => 'License key not found'], 404);
            return;
        }
        
        $computedStatus = computeStatus($license);
        
        $response = [
            'found' => true,
            'data' => [
                'license_key' => $license['license_key'],
                'software' => $license['software'],
                'expiration_date' => $license['expiration_date'],
                'computed_status' => $computedStatus,
                'machine_code' => $license['machine_code']
            ]
        ];
        
        if (!empty($license['activation_date'])) {
            $response['data']['activation_date'] = $license['activation_date'];
        }
        
        sendResponse($response, 200);
        
    } catch (PDOException $e) {
        sendResponse(['found' => false, 'error' => 'Database error'], 500);
    }
}

function activateLicenseKey() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $licenseKey = strtoupper(trim($data['license'] ?? ''));
    $machineCode = trim($data['machine_code'] ?? '');
    
    if (empty($licenseKey)) {
        sendResponse(['success' => false, 'error' => 'License key is required'], 400);
        return;
    }
    
    if (empty($machineCode)) {
        sendResponse(['success' => false, 'error' => 'Machine code is required'], 400);
        return;
    }
    
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("SELECT * FROM licenses WHERE license_key = ?");
        $stmt->execute([$licenseKey]);
        $license = $stmt->fetch();
        
        if (!$license) {
            sendResponse(['success' => false, 'error' => 'License key not found'], 404);
            return;
        }
        
        if (!empty($license['machine_code'])) {
            if ($license['machine_code'] === $machineCode) {
                $computedStatus = computeStatus($license);
                sendResponse([
                    'success' => true,
                    'message' => 'License already activated on this machine',
                    'data' => [
                        'license_key' => $license['license_key'],
                        'software' => $license['software'],
                        'expiration_date' => $license['expiration_date'],
                        'activation_date' => $license['activation_date'],
                        'computed_status' => $computedStatus,
                        'machine_code' => $license['machine_code']
                    ]
                ], 200);
            } else {
                sendResponse([
                    'success' => false,
                    'error' => 'License already activated on another machine'
                ], 403);
            }
            return;
        }
        
        $updateStmt = $pdo->prepare("
            UPDATE licenses 
            SET machine_code = ?, 
                activation_date = NOW() 
            WHERE license_key = ? AND machine_code IS NULL
        ");
        $updateStmt->execute([$machineCode, $licenseKey]);
        
        if ($updateStmt->rowCount() === 0) {
            sendResponse(['success' => false, 'error' => 'Failed to activate license'], 500);
            return;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM licenses WHERE license_key = ?");
        $stmt->execute([$licenseKey]);
        $license = $stmt->fetch();
        
        $computedStatus = computeStatus($license);
        
        sendResponse([
            'success' => true,
            'message' => 'License activated successfully',
            'data' => [
                'license_key' => $license['license_key'],
                'software' => $license['software'],
                'expiration_date' => $license['expiration_date'],
                'activation_date' => $license['activation_date'],
                'computed_status' => $computedStatus,
                'machine_code' => $license['machine_code']
            ]
        ], 200);
        
    } catch (PDOException $e) {
        sendResponse(['success' => false, 'error' => 'Database error'], 500);
    }
}

function computeStatus($license) {
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
?>
