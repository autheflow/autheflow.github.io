<?php
require_once 'api/config.php';
require_once 'BuildPropGenerator.php';

$licenseKey = $_GET['License'] ?? $_GET['license'] ?? '';
$model = $_GET['model'] ?? '';

if (empty($licenseKey)) {
    sendError('License key is required', 400);
}

if (empty($model)) {
    sendError('Model is required', 400);
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
    
    $deviceStmt = $phoneDb->prepare("
        SELECT id, brand, name, url, image_url, data, crawled_at 
        FROM devices 
        WHERE success = 1 AND (name = ? OR data LIKE ?)
        LIMIT 100
    ");
    $deviceStmt->execute([$model, '%"model":"' . $model . '"%']);
    
    $deviceFound = null;
    $foundBrand = null;
    
    while ($row = $deviceStmt->fetch(PDO::FETCH_ASSOC)) {
        $deviceData = json_decode($row['data'], true);
        $specs = $deviceData['specs'] ?? [];
        
        $deviceModel = $specs['model'] ?? $row['name'];
        
        if ($deviceModel === $model) {
            $deviceFound = [
                'id' => $row['id'],
                'brand' => $row['brand'],
                'name' => $row['name'],
                'url' => $row['url'],
                'image_url' => $row['image_url'],
                'crawled_at' => $row['crawled_at'],
                'specs' => $specs,
                'tags' => $deviceData['tags'] ?? [],
                'android_version' => $deviceData['android_version'] ?? null
            ];
            $foundBrand = $row['brand'];
            break;
        }
    }
    
    if (!$deviceFound) {
        sendError('Device with model "' . $model . '" not found', 404);
    }
    
    $brandCountStmt = $phoneDb->prepare("
        SELECT COUNT(*) as count 
        FROM devices 
        WHERE brand = ? AND success = 1
    ");
    $brandCountStmt->execute([$foundBrand]);
    $brandCount = $brandCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $generator = new BuildPropGenerator();
    $buildProp = $generator->generate($deviceFound);
    
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
            'brand_total_devices' => $brandCount,
            'device_brand' => $foundBrand
        ],
        'device' => $deviceFound,
        'build_prop' => $buildProp
    ];
    
    sendSuccess($result);
    
} catch (PDOException $e) {
    sendError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage(), 500);
}
?>
