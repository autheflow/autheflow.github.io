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

    $tzPath = __DIR__ . '/json/timezone.json';

    if (!file_exists($tzPath)) {
        sendResponse(['success' => false, 'error' => 'Timezone config file not found'], 500);
    }

    $tzData    = json_decode(file_get_contents($tzPath), true);
    $timezones = $tzData['timezones'] ?? [];

    $cities = [];

    foreach ($timezones as $tz) {
        $regions = explode(',', $tz['region']);
        foreach ($regions as $city) {
            $city = trim($city);
            if ($city !== '') {
                $cities[] = $city;
            }
        }
    }

    sort($cities);

    sendResponse([
        'success'      => true,
        'total_cities' => count($cities),
        'cities'       => $cities,
    ], 200);

} catch (PDOException $e) {
    sendResponse(['success' => false, 'error' => 'Database error'], 500);
} catch (Exception $e) {
    sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
?>
