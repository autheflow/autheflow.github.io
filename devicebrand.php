<?php
require_once 'api/config.php';

$licenseKey = $_GET['License'] ?? $_GET['license'] ?? '';

if (empty($licenseKey)) {
    sendError('License key is required', 400);
}

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM licenses WHERE license_key = ?");
    $stmt->execute([$licenseKey]);
    $license = $stmt->fetch();
    
    if (!$license) {
        sendError('Invalid license key', 401);
    }
    
    if (empty($license['machine_code'])) {
        sendError('License not activated', 403);
    }
    
    $today = new DateTime();
    $expirationDate = new DateTime($license['expiration_date']);
    
    if ($today > $expirationDate) {
        sendError('License has expired', 403);
    }
    
    $dbPath = __DIR__ . '/json/phonedb.db';
    
    if (!file_exists($dbPath)) {
        sendError('Device database not found', 404);
    }
    
    $phoneDb = new PDO('sqlite:' . $dbPath);
    $phoneDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $totalStmt = $phoneDb->query("SELECT COUNT(*) as total FROM devices WHERE success = 1");
    $totalDevices = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $brandsStmt = $phoneDb->query("
        SELECT brand, COUNT(*) as count 
        FROM devices 
        WHERE success = 1 
        GROUP BY brand 
        ORDER BY brand
    ");
    $brandsData = $brandsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [
        'success' => true,
        'license_info' => [
            'license_key' => $license['license_key'],
            'software' => $license['software'],
            'expiration_date' => $license['expiration_date'],
            'status' => 'active'
        ],
        'statistics' => [
            'total_devices' => $totalDevices,
            'total_brands' => count($brandsData)
        ],
        'brands' => [],
        'models_by_brand' => []
    ];
    
    foreach ($brandsData as $brandInfo) {
        $brandName = $brandInfo['brand'];
        $result['brands'][] = $brandName;
        
        $devicesStmt = $phoneDb->prepare("
            SELECT id, name, data 
            FROM devices 
            WHERE brand = ? AND success = 1 
            ORDER BY name
        ");
        $devicesStmt->execute([$brandName]);
        $devices = $devicesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $models = [];
        foreach ($devices as $device) {
            $deviceData = json_decode($device['data'], true);
            $specs = $deviceData['specs'] ?? [];
            
            $models[] = [
                'model' => $specs['model'] ?? $device['name'],
                'codename' => $specs['codename'] ?? null
            ];
        }
        
        $result['models_by_brand'][$brandName] = [
            'brand' => ucfirst($brandName),
            'total_models' => count($models),
            'models' => $models
        ];
    }
    
    sendSuccess($result);
    
} catch (PDOException $e) {
    sendError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage(), 500);
}
?>
