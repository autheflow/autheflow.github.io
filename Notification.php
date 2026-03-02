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

    $today          = new DateTime('today');
    $expirationDate = new DateTime($license['expiration_date']);

    $isExpired    = $today > $expirationDate;
    $daysRemaining = $isExpired ? 0 : (int)$today->diff($expirationDate)->days;

    $notifPath = __DIR__ . '/json/Notification.json';

    if (!file_exists($notifPath)) {
        sendResponse(['success' => false, 'error' => 'Notification config not found'], 500);
    }

    $notifData = json_decode(file_get_contents($notifPath), true);

    $descriptions = [];
    foreach ($notifData['notifications'] as $key => $value) {
        if (trim($value) !== '') {
            $descriptions[] = $value;
        }
    }

    sendResponse([
        'success'       => true,
        'license'       => [
            'license_key'    => $license['license_key'],
            'software'       => $license['software'],
            'expiration_date'=> $license['expiration_date'],
            'days_remaining' => $daysRemaining,
            'status'         => $isExpired ? 'expired' : 'active',
        ],
        'notifications' => $descriptions,
        'version'       => $notifData['version'],
    ], 200);

} catch (PDOException $e) {
    sendResponse(['success' => false, 'error' => 'Database error'], 500);
} catch (Exception $e) {
    sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
?>
