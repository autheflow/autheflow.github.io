<?php
require_once 'api/config.php';

function getLanguageDisplayNames($languageId) {
    $names = [
        'vi_VN' => ['vi' => 'Tiếng Việt', 'en' => 'Vietnamese'],
        'id_ID' => ['vi' => 'Tiếng Indonesia', 'en' => 'Indonesian'],
        'th_TH' => ['vi' => 'Tiếng Thái', 'en' => 'Thai'],
        'ms_MY' => ['vi' => 'Tiếng Mã Lai', 'en' => 'Malay'],
        'fil_PH' => ['vi' => 'Tiếng Philippines', 'en' => 'Filipino'],
        'ja_JP' => ['vi' => 'Tiếng Nhật', 'en' => 'Japanese'],
        'ko_KR' => ['vi' => 'Tiếng Hàn', 'en' => 'Korean'],
        'zh_CN' => ['vi' => 'Tiếng Trung (Giản thể)', 'en' => 'Chinese (Simplified)'],
        'zh_TW' => ['vi' => 'Tiếng Trung (Phồn thể)', 'en' => 'Chinese (Traditional)'],
        'zh_HK' => ['vi' => 'Tiếng Trung (Hồng Kông)', 'en' => 'Chinese (Hong Kong)'],
        'hi_IN' => ['vi' => 'Tiếng Hindi', 'en' => 'Hindi'],
        'bn_BD' => ['vi' => 'Tiếng Bengal', 'en' => 'Bengali'],
        'ur_PK' => ['vi' => 'Tiếng Urdu', 'en' => 'Urdu'],
        'ar_SA' => ['vi' => 'Tiếng Ả Rập', 'en' => 'Arabic'],
        'he_IL' => ['vi' => 'Tiếng Do Thái', 'en' => 'Hebrew'],
        'en_GB' => ['vi' => 'Tiếng Anh (Anh)', 'en' => 'English (UK)'],
        'fr_FR' => ['vi' => 'Tiếng Pháp', 'en' => 'French'],
        'de_DE' => ['vi' => 'Tiếng Đức', 'en' => 'German'],
        'es_ES' => ['vi' => 'Tiếng Tây Ban Nha', 'en' => 'Spanish (Spain)'],
        'it_IT' => ['vi' => 'Tiếng Ý', 'en' => 'Italian'],
        'ru_RU' => ['vi' => 'Tiếng Nga', 'en' => 'Russian'],
        'pt_PT' => ['vi' => 'Tiếng Bồ Đào Nha', 'en' => 'Portuguese (Portugal)'],
        'nl_NL' => ['vi' => 'Tiếng Hà Lan', 'en' => 'Dutch'],
        'pl_PL' => ['vi' => 'Tiếng Ba Lan', 'en' => 'Polish'],
        'sv_SE' => ['vi' => 'Tiếng Thụy Điển', 'en' => 'Swedish'],
        'fi_FI' => ['vi' => 'Tiếng Phần Lan', 'en' => 'Finnish'],
        'cs_CZ' => ['vi' => 'Tiếng Séc', 'en' => 'Czech'],
        'en_US' => ['vi' => 'Tiếng Anh (Mỹ)', 'en' => 'English (US)'],
        'es_MX' => ['vi' => 'Tiếng Tây Ban Nha (Mexico)', 'en' => 'Spanish (Mexico)'],
        'pt_BR' => ['vi' => 'Tiếng Bồ Đào Nha (Brazil)', 'en' => 'Portuguese (Brazil)'],
        'es_AR' => ['vi' => 'Tiếng Tây Ban Nha (Argentina)', 'en' => 'Spanish (Argentina)'],
        'es_CL' => ['vi' => 'Tiếng Tây Ban Nha (Chile)', 'en' => 'Spanish (Chile)'],
        'es_CO' => ['vi' => 'Tiếng Tây Ban Nha (Colombia)', 'en' => 'Spanish (Colombia)'],
        'fr_CA' => ['vi' => 'Tiếng Pháp (Canada)', 'en' => 'French (Canada)'],
        'en_CA' => ['vi' => 'Tiếng Anh (Canada)', 'en' => 'English (Canada)'],
        'es_PE' => ['vi' => 'Tiếng Tây Ban Nha (Peru)', 'en' => 'Spanish (Peru)'],
        'es_VE' => ['vi' => 'Tiếng Tây Ban Nha (Venezuela)', 'en' => 'Spanish (Venezuela)'],
        'en_AU' => ['vi' => 'Tiếng Anh (Úc)', 'en' => 'English (Australia)'],
        'es_EC' => ['vi' => 'Tiếng Tây Ban Nha (Ecuador)', 'en' => 'Spanish (Ecuador)']
    ];
    
    return $names[$languageId] ?? ['vi' => $languageId, 'en' => $languageId];
}

$languageId = $_GET['language'] ?? $_GET['languageId'] ?? '';

if (empty($languageId)) {
    sendError('Language ID is required', 400);
}

try {
    $jsonFile = __DIR__ . '/json/languageid.json';
    
    if (!file_exists($jsonFile)) {
        sendError('Language data file not found', 404);
    }
    
    $jsonContent = file_get_contents($jsonFile);
    
    $jsonContent = preg_replace('/\/\/.*$/m', '', $jsonContent);
    
    $data = json_decode($jsonContent, true);
    
    if (!is_array($data)) {
        sendError('Invalid language data format', 500);
    }
    
    $languageFound = null;
    
    foreach ($data as $language) {
        if (isset($language['languageId']) && 
            $language['languageId'] === $languageId) {
            $languageFound = $language;
            break;
        }
    }
    
    if (!$languageFound) {
        sendError('Language "' . $languageId . '" not found', 404);
    }
    
    $displayNames = getLanguageDisplayNames($languageId);
    $languageFound['displayName_vi'] = $displayNames['vi'];
    $languageFound['displayName_en'] = $displayNames['en'];
    
    $result = [
        'success' => true,
        'language' => $languageFound
    ];
    
    sendSuccess($result);
    
} catch (Exception $e) {
    sendError('Error: ' . $e->getMessage(), 500);
}
?>
