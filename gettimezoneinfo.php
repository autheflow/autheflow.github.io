<?php
require_once 'api/config.php';

$licenseKey = $_GET['License'] ?? $_GET['license'] ?? '';
$region     = trim($_GET['region'] ?? '');

if (empty($licenseKey)) {
    sendResponse(['success' => false, 'error' => 'License key is required'], 400);
}

if (empty($region)) {
    sendResponse(['success' => false, 'error' => 'Parameter "region" is required'], 400);
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

    $found = null;

    foreach ($timezones as $tz) {
        $cities = array_map('trim', explode(',', $tz['region']));
        foreach ($cities as $city) {
            if (strcasecmp($city, $region) === 0) {
                $found = $tz;
                break 2;
            }
        }
    }

    if (!$found) {
        sendResponse([
            'success' => false,
            'error'   => 'City "' . $region . '" not found. Use /gettimezone.php to see all available cities.',
        ], 404);
    }

    sendResponse([
        'success'       => true,
        'iana_timezone' => $found['iana_timezone'],
        'timezone_id'   => $found['timezone_id'],
        'timezone_name' => $found['timezone_name'],
        'utc_offset'    => $found['utc_offset'],
    ], 200);

} catch (PDOException $e) {
    sendResponse(['success' => false, 'error' => 'Database error'], 500);
} catch (Exception $e) {
    sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
?>
