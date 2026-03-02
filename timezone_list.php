<?php
require_once 'api/config.php';

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
    
    $timezoneList = [];
    foreach ($data['timezones'] as $timezone) {
        $timezoneList[] = [
            'timezone_id' => $timezone['timezone_id'] ?? null,
            'timezone_name' => $timezone['timezone_name'] ?? null,
            'utc_offset' => $timezone['utc_offset'] ?? null,
            'region' => $timezone['region'] ?? null,
            'iana_timezone' => $timezone['iana_timezone'] ?? null
        ];
    }
    
    $result = [
        'success' => true,
        'total' => count($timezoneList),
        'timezones' => $timezoneList
    ];
    
    sendSuccess($result);
    
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage(), 500);
}
?>
