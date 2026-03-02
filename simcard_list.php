<?php
require_once 'api/config.php';

try {
    $jsonFile = __DIR__ . '/json/simcard.json';
    
    if (!file_exists($jsonFile)) {
        sendError('Simcard data file not found', 404);
    }
    
    $jsonContent = file_get_contents($jsonFile);
    $data = json_decode($jsonContent, true);
    
    if (!isset($data['region']) || !is_array($data['region'])) {
        sendError('Invalid simcard data format', 500);
    }
    
    $simcardList = [];
    foreach ($data['region'] as $simcard) {
        $simcardList[] = [
            'sim_country' => $simcard['sim_country'] ?? null,
            'net_operatorname' => $simcard['net_operatorname'] ?? null,
            'sim_operator' => $simcard['sim_operator'] ?? null,
            'sim_operatorname' => $simcard['sim_operatorname'] ?? null
        ];
    }
    
    $result = [
        'success' => true,
        'total' => count($simcardList),
        'simcards' => $simcardList
    ];
    
    sendSuccess($result);
    
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage(), 500);
}
?>
