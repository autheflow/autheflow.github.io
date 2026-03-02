<?php
require_once 'api/config.php';

try {
    $jsonFile = __DIR__ . '/json/updateversion.json';
    
    if (!file_exists($jsonFile)) {
        sendError('Version data file not found', 404);
    }
    
    $jsonContent = file_get_contents($jsonFile);
    $versionData = json_decode($jsonContent, true);
    
    if (!is_array($versionData)) {
        sendError('Invalid version data format', 500);
    }
    
    $result = [
        'success' => true,
        'version_info' => $versionData
    ];
    
    sendSuccess($result);
    
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage(), 500);
}
?>
