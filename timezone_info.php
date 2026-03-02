<?php
require_once 'api/config.php';

$ianaTimezone = $_GET['timezone'] ?? '';

if (empty($ianaTimezone)) {
    sendError('Timezone is required', 400);
}

try {
    $jsonFile = __DIR__ . '/json/timezone.json';
    
    if (!file_exists($jsonFile)) {
        sendError('Timezone data file not found', 404);
    }
    
    $jsonContent = file_get_contents($jsonFile);
    $data = json_decode($jsonContent, true);
    
    if (!isset($data['timezones']) || !is_array($data['timezones'])) {
        sendError('Invalid timezone data format', 500);
    }
    
    $timezoneFound = null;
    
    foreach ($data['timezones'] as $timezone) {
        if (isset($timezone['iana_timezone']) && 
            $timezone['iana_timezone'] === $ianaTimezone) {
            $timezoneFound = $timezone;
            break;
        }
    }
    
    if (!$timezoneFound) {
        sendError('Timezone "' . $ianaTimezone . '" not found', 404);
    }
    
    $result = [
        'success' => true,
        'timezone' => $timezoneFound
    ];
    
    sendSuccess($result);
    
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage(), 500);
}
?>
