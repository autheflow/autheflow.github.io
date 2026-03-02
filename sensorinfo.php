<?php
require_once 'api/config.php';

$licenseKey = $_GET['License'] ?? $_GET['license'] ?? '';

if (empty($licenseKey)) {
    sendResponse(['success' => false, 'error' => 'License key is required'], 400);
}

$licenseKey = strtoupper(trim($licenseKey));

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM licenses WHERE license_key = ?");
    $stmt->execute([$licenseKey]);
    $license = $stmt->fetch();

    if (!$license) {
        sendResponse(['success' => false, 'error' => 'Invalid license key'], 401);
    }

    if (empty($license['machine_code'])) {
        sendResponse(['success' => false, 'error' => 'License not activated'], 403);
    }

    $today          = new DateTime();
    $expirationDate = new DateTime($license['expiration_date']);

    if ($today > $expirationDate) {
        sendResponse(['success' => false, 'error' => 'License has expired'], 403);
    }

    $scsiPath   = __DIR__ . '/json/scsi.config';
    $sensorPath = __DIR__ . '/json/sensor.config';

    if (!file_exists($scsiPath)) {
        sendResponse(['success' => false, 'error' => 'scsi.config not found'], 500);
    }

    if (!file_exists($sensorPath)) {
        sendResponse(['success' => false, 'error' => 'sensor.config not found'], 500);
    }

    $scsiList   = json_decode(file_get_contents($scsiPath), true);
    $sensorList = json_decode(file_get_contents($sensorPath), true);

    $scsiEntry = $scsiList[array_rand($scsiList)];

    $sensorKeys = array_rand($sensorList, min(12, count($sensorList)));
    $sensors    = [];
    foreach ((array)$sensorKeys as $key) {
        $sensors[] = $sensorList[$key];
    }

    sendResponse([
        'success' => true,
        'storage' => [
            'vendor' => $scsiEntry['vendor'],
            'model'  => $scsiEntry['model'],
        ],
        'sensors' => $sensors,
    ], 200);

} catch (PDOException $e) {
    sendResponse(['success' => false, 'error' => 'Database error'], 500);
} catch (Exception $e) {
    sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
?>
