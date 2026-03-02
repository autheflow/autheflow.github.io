<?php
require_once 'api/config.php';

$operatorName = $_GET['operator'] ?? '';

if (empty($operatorName)) {
    sendError('Operator name is required', 400);
}

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
    
    $simcardFound = null;
    
    foreach ($data['region'] as $simcard) {
        if (isset($simcard['net_operatorname']) && 
            $simcard['net_operatorname'] === $operatorName) {
            $simcardFound = $simcard;
            break;
        }
    }
    
    if (!$simcardFound) {
        sendError('Operator "' . $operatorName . '" not found', 404);
    }
    
    $result = [
        'success' => true,
        'simcard' => $simcardFound
    ];
    
    sendSuccess($result);
    
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage(), 500);
}
?>
