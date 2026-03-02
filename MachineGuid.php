<?php
require_once 'api/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method !== 'GET') {
    sendResponse([
        'success' => false,
        'error' => 'Only GET method is allowed'
    ], 405);
}

$licenseKey = $_GET['License'] ?? $_GET['license'] ?? '';
$machineGuid = $_GET['id'] ?? '';

if (empty($licenseKey)) {
    sendResponse([
        'success' => false,
        'error' => 'License key is required'
    ], 400);
}

if (empty($machineGuid)) {
    sendResponse([
        'success' => false,
        'error' => 'Machine GUID (id parameter) is required'
    ], 400);
}

$licenseKey = strtoupper(trim($licenseKey));
$machineGuid = trim($machineGuid);

if (!preg_match('/^[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/', $licenseKey)) {
    sendResponse([
        'success' => false,
        'error' => 'Invalid license key format'
    ], 400);
}

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT id, machine_code, software, expiration_date FROM licenses WHERE license_key = ?");
    $stmt->execute([$licenseKey]);
    $license = $stmt->fetch();
    
    if (!$license) {
        sendResponse([
            'success' => false,
            'error' => 'License key not found'
        ], 404);
    }
    
    if (!empty($license['machine_code'])) {
        if ($license['machine_code'] === $machineGuid) {
            sendResponse([
                'success' => true,
                'message' => 'License already activated on this machine',
                'data' => [
                    'license_key' => $licenseKey,
                    'machine_code' => $license['machine_code'],
                    'software' => $license['software']
                ]
            ], 200);
        } else {
            sendResponse([
                'success' => false,
                'error' => 'License already activated on another machine',
                'message' => 'This license is already bound to a different device and cannot be transferred'
            ], 403);
        }
    }
    
    $updateStmt = $pdo->prepare("
        UPDATE licenses 
        SET machine_code = ?, activation_date = NOW() 
        WHERE license_key = ? AND (machine_code IS NULL OR machine_code = '')
    ");
    $updateStmt->execute([$machineGuid, $licenseKey]);
    
    if ($updateStmt->rowCount() === 0) {
        sendResponse([
            'success' => false,
            'error' => 'Failed to activate license. It may have been activated by another request.'
        ], 500);
    }
    
    sendResponse([
        'success' => true,
        'message' => 'License activated successfully',
        'data' => [
            'license_key' => $licenseKey,
            'machine_code' => $machineGuid,
            'software' => $license['software'],
            'activation_date' => date('Y-m-d H:i:s')
        ]
    ], 200);
    
} catch (PDOException $e) {
    sendResponse([
        'success' => false,
        'error' => 'Database error occurred'
    ], 500);
}
?>
