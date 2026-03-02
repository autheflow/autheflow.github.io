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

    // ── Load net.config ───────────────────────────────────────────────────────
    $netConfigPath = __DIR__ . '/json/net.config';

    if (!file_exists($netConfigPath)) {
        sendResponse(['success' => false, 'error' => 'Network config file not found'], 500);
    }

    $netData   = json_decode(file_get_contents($netConfigPath), true);
    $operators = $netData['mobile_operators'] ?? [];

    // ── Group by country ──────────────────────────────────────────────────────
    $grouped = [];

    foreach ($operators as $op) {
        $code = $op['country_code'];
        $name = $op['country_name'];

        if (!isset($grouped[$code])) {
            $grouped[$code] = [
                'country_code'    => $code,
                'country_name'    => $name,
                'total_operators' => 0,
                'operators'       => [],
            ];
        }

        $grouped[$code]['operators'][]    = $op['network_operator'];
        $grouped[$code]['total_operators']++;
    }

    $countries = array_values($grouped);

    sendResponse([
        'success'         => true,
        'total_countries' => count($countries),
        'countries'       => $countries,
    ], 200);

} catch (PDOException $e) {
    sendResponse(['success' => false, 'error' => 'Database error'], 500);
} catch (Exception $e) {
    sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
?>
