<?php

class BuildPropGenerator {
    private $templatesDir;
    private $brandPatterns;
    
    public function __construct() {
        $this->templatesDir = __DIR__ . '/holdsbuildpropdata/';
        $this->initBrandPatterns();
    }
    
    private function initBrandPatterns() {
        $this->brandPatterns = [
            'Asus' => [
                'build_user' => 'builder6',
                'build_type' => 'user',
                'build_host_prefix' => 'builder',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{MODEL}{RANDOM}',
            ],
            'Google' => [
                'build_user' => 'android-build',
                'build_type' => 'user',
                'build_host_prefix' => '',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{RANDOM}',
            ],
            'HTC' => [
                'build_user' => 'buildteam',
                'build_type' => 'user',
                'build_host_prefix' => 'ABM',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{MODEL}{RANDOM}',
            ],
            'Huawei' => [
                'build_user' => 'test',
                'build_type' => 'user',
                'build_host_prefix' => 'cn-east-hcd-4a-',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{MODEL}.C{RANDOM}',
            ],
            'Lenovo' => [
                'build_user' => 'buildslave',
                'build_type' => 'user',
                'build_host_prefix' => 'hq-wh-slave-',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{MODEL}{RANDOM}',
            ],
            'Meizu' => [
                'build_user' => 'flyme',
                'build_type' => 'user',
                'build_host_prefix' => 'Mz-Builder-L',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{RANDOM}',
            ],
            'Motorola' => [
                'build_user' => 'hudsoncm',
                'build_type' => 'user',
                'build_host_prefix' => 'ilclbld',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{MODEL}{RANDOM}',
            ],
            'Nothing' => [
                'build_user' => 'nothing',
                'build_type' => 'user',
                'build_host_prefix' => 'NTSV-J',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{RANDOM}',
            ],
            'Oppo' => [
                'build_user' => 'root',
                'build_type' => 'user',
                'build_host_prefix' => 'kvm-slave-build-s-system-',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{MODEL}.{RANDOM}',
            ],
            'Samsung' => [
                'build_user' => 'dpi',
                'build_type' => 'user',
                'build_host_prefix' => 'SWDK',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{MODEL}{RANDOM}',
            ],
            'Sharp' => [
                'build_user' => 'cm',
                'build_type' => 'user',
                'build_host_prefix' => 'cm-build-V',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{MODEL}{RANDOM}',
            ],
            'Sony' => [
                'build_user' => 'hudsonslave',
                'build_type' => 'user',
                'build_host_prefix' => 'ip-10-26-25-',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{RANDOM}',
            ],
            'TCL' => [
                'build_user' => 'jenkins',
                'build_type' => 'user',
                'build_host_prefix' => 'ub16-',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{MODEL}{RANDOM}',
            ],
            'T-Mobile' => [
                'build_user' => 'cibuild',
                'build_type' => 'user',
                'build_host_prefix' => 'ubuntu',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{MODEL}{RANDOM}',
            ],
            'Xiaomi' => [
                'build_user' => 'builder',
                'build_type' => 'user',
                'build_host_prefix' => 'pangu-build-component-system-',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => 'OS{VERSION}.{RANDOM}',
            ],
            'ZTE' => [
                'build_user' => 'zte',
                'build_type' => 'userdebug',
                'build_host_prefix' => 'SCL-',
                'build_host_suffix' => '',
                'build_tags' => 'test-keys',
                'incremental_pattern' => '{MODEL}{RANDOM}',
            ],
            'ZUK' => [
                'build_user' => 'buildslave',
                'build_type' => 'user',
                'build_host_prefix' => 'byws',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{MODEL}{RANDOM}',
            ],
            'Alcatel' => [
                'build_user' => 'root',
                'build_type' => 'user',
                'build_host_prefix' => 'build-',
                'build_host_suffix' => '',
                'build_tags' => 'release-keys',
                'incremental_pattern' => '{RANDOM}',
            ],
        ];
    }
    
    public function generate($device) {
        $specs = $device['specs'] ?? [];
        $brand = $device['brand'] ?? 'Generic';
        $name = $device['name'] ?? '';
        
        $androidVersion = $this->extractAndroidVersion($specs);
        $sdkVersion = $this->androidVersionToSdk($androidVersion);
        
        $fullModel = $specs['Model'] ?? $specs['model'] ?? $name;
        $codename = $this->extractCodename($specs, $name);
        $oemId = $specs['OEM ID'] ?? $specs['oem_id'] ?? null;
        $modelCode = $this->extractModelCode($fullModel, $brand, $oemId, $codename);
        $productName = $this->generateProductName($brand, $codename);
        $productDevice = $this->extractProductDevice($codename, $modelCode);
        
        $template = $this->loadTemplate($brand, $codename);
        
        $buildType = $this->getBuildType($brand);
        $buildTags = $this->getBuildTags($brand);
        
        $buildId = $this->generateBuildId($androidVersion, $brand);
        $incremental = $this->generateIncremental($brand, $modelCode, $androidVersion);
        $displayId = $buildId . '.' . $incremental;
        
        $flavor = $this->generateFlavor($brand, $productName, $buildType);
        $description = $this->generateDescription($flavor, $androidVersion, $buildId, $incremental, $buildTags);
        $fingerprint = $this->generateFingerprint($brand, $productName, $productDevice, $androidVersion, $buildId, $incremental, $buildType, $buildTags);
        
        $cpuString = $specs['CPU'] ?? $specs['cpu'] ?? '';
        $gpuString = $specs['Graphical Controller'] ?? $specs['graphical_controller'] ?? '';
        
        $cpuBrandName = $this->extractCpuManufacturer($cpuString);
        $cpuModelCode = $this->extractCpuModelCode($cpuString);
        $gpuModelName = $this->extractGpuName($gpuString, $cpuString);
        
        $resolution = $this->extractResolution($specs);
        $cpuCores = $this->extractCpuCores($cpuString);
        $btName = $this->generateBluetoothName($brand, $codename, $specs);
        $memoryTotal = $this->extractMemoryTotal($specs);
        
        $oui      = $this->getBrandOUI($brand);
        $btMac    = $oui . ':' . $this->randomHexByteMac() . ':' . $this->randomHexByteMac() . ':' . $this->randomHexByteMac();
        $wifiMac  = $oui . ':' . $this->randomHexByteMac() . ':' . $this->randomHexByteMac() . ':' . $this->randomHexByteMac();
        $serial   = $this->generateSerialNumber($brand, $oemId);
        $wifiSSID = $this->generateWiFiSSID($brand);

        $buildProp = [
            'ro.build.id' => $buildId,
            'ro.build.display.id' => $displayId,
            'ro.build.version.incremental' => $incremental,
            'ro.build.user' => $this->getBuildUser($brand),
            'ro.build.type' => $buildType,
            'ro.build.host' => $this->generateBuildHost($brand),
            'ro.build.tags' => $buildTags,
            'ro.build.flavor' => $flavor,
            'ro.product.model' => $modelCode,
            'ro.product.brand' => strtolower($brand),
            'ro.product.name' => $productName,
            'ro.product.manufacturer' => $brand,
            'ro.build.product' => $productDevice,
            'ro.product.device' => $productDevice,
            'ro.product.locale' => $this->generateLocale($specs),
            'ro.build.description' => $description,
            'ro.build.fingerprint' => $fingerprint,
            'ro.vendor.build.fingerprint' => $this->generateVendorFingerprint($brand, $productName, $productDevice, $androidVersion, $incremental, $buildType, $buildTags),
            'ro.product.board' => $this->extractBoard($specs),
            'ro.board.platform' => $this->extractPlatform($specs),
            'ro.product.vendor.manufacturer' => $brand,
            'ro.product.vendor.model' => $modelCode,
            'ro.product.vendor.brand' => strtolower($brand),
            'ro.product.vendor.name' => $productName,
            'ro.product.vendor.device' => $productDevice,
            'gsm.version.baseband' => $this->generateBaseband($brand, $modelCode),
            'ro.vendor.radio.version' => $this->generateRadioVersion($brand, $modelCode),
            'ro.boot.serialno' => $serial,
            'ro.bootloader' => $this->generateBootloader($brand, $modelCode),
            'ro.vendor.bootloader' => $this->generateBootloader($brand, $modelCode),
            'ro.boot.bootloader' => $this->generateBootloader($brand, $modelCode),
            'ro.bootimage.build.fingerprint' => $fingerprint,
            'cpu_brand_name' => $cpuBrandName,
            'cpu_model_code' => $cpuModelCode,
            'gpu_model_name' => $gpuModelName,
            'net.bt.name' => $btName,
            'device_name' => $modelCode,
            'display_width' => $resolution['width'],
            'display_height' => $resolution['height'],
            'cpu_cores' => $cpuCores,
            'memory_total' => $memoryTotal,
            'UserAgent' => $this->generateUserAgent($modelCode, $androidVersion, $buildId),
            'battery_level' => rand(50, 95),
            'battery_capacity' => $this->extractBatteryCapacity($specs),
        ];

        $deviceIDs = [
            'AndroidID'              => bin2hex(random_bytes(8)),
            'BuildSerialNumber'      => $serial,
            'BluetoothMACAddress'    => $btMac,
            'WiFiMACAddress'         => $wifiMac,
            'WiFiSSID'               => $wifiSSID,
            'GoogleAdvertisingID'    => $this->generateUUIDv4(),
            'GoogleServiceFramework' => bin2hex(random_bytes(8)),
            'ClientUUID'             => $this->generateUUIDv4(),
            'OAID'                   => $this->generateUUIDv4(),
        ];

        return array_merge($template, $buildProp, $deviceIDs);
    }
    
    private function loadTemplate($brand, $codename) {
        $brandDir = $this->templatesDir . $brand;
        
        if (!is_dir($brandDir)) {
            return [];
        }
        
        $dirs = glob($brandDir . '/*', GLOB_ONLYDIR);
        if (empty($dirs)) {
            return [];
        }
        
        $templateDir = $dirs[array_rand($dirs)];
        
        $template = [];
        if (file_exists($templateDir . '/system_build.prop')) {
            $template = array_merge($template, $this->parseBuildProp($templateDir . '/system_build.prop'));
        }
        if (file_exists($templateDir . '/vendor_build.prop')) {
            $template = array_merge($template, $this->parseBuildProp($templateDir . '/vendor_build.prop'));
        }
        
        return $template;
    }
    
    private function parseBuildProp($file) {
        $props = [];
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (strpos($key, 'ro.') === 0 || strpos($key, 'dalvik.') === 0 || strpos($key, 'persist.') === 0) {
                    $props[$key] = $value;
                }
            }
        }
        
        return $props;
    }
    
    private function extractAndroidVersion($specs) {
        return 9;
    }
    
    private function androidVersionToSdk($androidVersion) {
        $mapping = [
            14 => 34, 15 => 35, 16 => 36,
            13 => 33, 12 => 32, 11 => 30
        ];
        return $mapping[$androidVersion] ?? 34;
    }
    
    private function extractModelCode($model, $brand = '', $oemId = null, $codename = null) {
        $brandPatterns = [
            'Samsung' => [
                'patterns' => ['/\bSM-[A-Z][0-9]{3}[A-Z0-9]{0,5}(?:\/DS)?\b/i', '/\bGT-[A-Z][0-9]{4}[A-Z]?\b/i', '/\bSC-[0-9]{2}[A-Z]\b/i'],
                'position' => 'start'
            ],
            'Huawei' => [
                'patterns' => ['/\b[A-Z]{2,5}-[A-Z]{1,3}[0-9]{1,3}[A-Z0-9]*\b/i'],
                'position' => 'end'
            ],
            'BBK' => [
                'patterns' => ['/\bV[0-9]{4}[A-Z]{1,2}\b/i', '/\bV[0-9]{4}\b/i', '/\b[0-9]{4}[A-Z]?\b/'],
                'position' => 'end'
            ],
            'Oppo' => [
                'patterns' => ['/\bCPH[0-9]{4}\b/i', '/\bRMX[0-9]{4}\b/i', '/\bP[A-Z]{3}[0-9]{2}\b/i', '/\bPCH[A-Z][0-9]{2}\b/i'],
                'position' => 'end'
            ],
            'OnePlus' => [
                'patterns' => [
                    '/\bOP[A-Z][0-9]{4}\b/i',
                    '/\bP[A-Z]{3}[0-9]{2}\b/i',
                    '/\b[A-Z]{4}[0-9]{2}\b/',
                    '/\b[A-Z]{2}[0-9]{4}\b/'
                ],
                'position' => 'end'
            ],
            'Xiaomi' => [
                'patterns' => ['/\b[0-9]{4,8}[A-Z]{2,5}[0-9][A-Z]{1,2}\b/', '/\b[0-9]{5,8}[A-Z]{2,3}\b/', '/\b[A-Z]{2,4}-[A-Z][0-9]\b/i'],
                'position' => 'end'
            ],
            'Google' => [
                'patterns' => ['/\b[A-Z]{2,3}[0-9][A-Z0-9]{1,4}\b/'],
                'position' => 'end'
            ],
            'Sony' => [
                'patterns' => ['/\bXQ-[A-Z]{2}[0-9]{2}\b/i', '/\b[A-Z]{2}[0-9]{4,5}\b/', '/\bSO-[0-9]{2}[A-Z]\b/i'],
                'position' => 'end'
            ],
            'Motorola' => [
                'patterns' => ['/\bXT[0-9]{4}-[0-9]+\b/i', '/\bXT[0-9]{4}\b/i'],
                'position' => 'end'
            ],
            'HTC' => [
                'patterns' => ['/\b[A-Z0-9]{6,10}\b/'],
                'position' => 'end',
                'prefer_oem_id' => true
            ],
            'Lenovo' => [
                'patterns' => ['/\bXT[0-9]{4}-[0-9]+\b/i', '/\b[A-Z]{2,4}[0-9]{4,8}[A-Z]{0,2}\b/'],
                'position' => 'end'
            ],
            'ZTE' => [
                'patterns' => [
                    '/\b[A-Z]{2}[0-9]{3,5}[A-Z](?:-[A-Z0-9]+)?\b/i',
                    '/\b[A-Z][0-9]{3,4}[A-Z]{2}[0-9]?S?\b/',
                    '/\bZ[0-9]{4}[A-Z]{1,2}[0-9]?S?\b/i',
                    '/\b[0-9]{3}[A-Z]{2}\b/'
                ],
                'position' => 'end'
            ],
            'Nothing' => [
                'patterns' => ['/\b[A-Z][0-9]{3}[A-Z]?\b/'],
                'position' => 'end'
            ],
            'TCL' => [
                'patterns' => ['/\bT[0-9]{3,4}[A-Z0-9]\b/i', '/\b[0-9]{4}[A-Z][0-9]?\b/'],
                'position' => 'end'
            ],
            'Alcatel' => [
                'patterns' => ['/\b[0-9]{4}[A-Z]{1,2}\b/'],
                'position' => 'end'
            ],
            'Sharp' => [
                'patterns' => ['/\bSH-[A-Z][0-9]{2,3}[A-Z0-9]*\b/i', '/\b[0-9]{3}SH\b/i'],
                'position' => 'end'
            ],
            'Meizu' => [
                'patterns' => ['/\bM[0-9]{3,4}[A-Z]\b/i', '/\bMZ-[0-9A-Z]+\b/i'],
                'position' => 'end'
            ],
        ];
        
        $config = $brandPatterns[$brand] ?? null;
        
        if ($config && isset($config['prefer_oem_id']) && $config['prefer_oem_id'] && $oemId) {
            if (preg_match('/[A-Z0-9]{4,}/i', $oemId)) {
                return strtoupper(trim($oemId));
            }
        }
        
        if ($config && !empty($model)) {
            foreach ($config['patterns'] as $pattern) {
                if (preg_match_all($pattern, $model, $matches)) {
                    $found = $matches[0];
                    if (!empty($found)) {
                        $result = ($config['position'] === 'start') ? $found[0] : end($found);
                        
                        if (strpos($result, '/') !== false) {
                            $result = explode('/', $result)[0];
                        }
                        
                        return strtoupper(trim($result));
                    }
                }
            }
        }
        
        $generalCode = $this->generalExtract($model);
        if ($generalCode) {
            return $generalCode;
        }
        
        if ($oemId && trim($oemId)) {
            return strtoupper(trim($oemId));
        }
        
        if ($codename) {
            $codenameExtracted = $this->extractFromCodename($codename);
            if ($codenameExtracted) {
                return $codenameExtracted;
            }
        }
        
        if (strlen($generalCode ?? '') <= 2 && !empty($brand)) {
            $cleanBrand = preg_replace('/[^A-Za-z0-9]/', '', $brand);
            return $cleanBrand . ($generalCode ?? substr(strtoupper(md5($model)), 0, 4));
        }
        
        return strtoupper(substr(md5($model ?: 'unknown'), 0, 8));
    }
    
    private function extractFromCodename($codename) {
        if (!$codename) return null;
        
        $parts = preg_split('/\s+/', trim($codename));
        if (count($parts) < 2) return null;
        
        $codePart = implode('', array_slice($parts, 1));
        
        if (preg_match('/[A-Z]/i', $codePart) && preg_match('/[0-9]/', $codePart)) {
            return strtoupper(str_replace(' ', '', $codePart));
        }
        
        return strtoupper($codePart);
    }
    
    private function generalExtract($text) {
        if (!$text) return null;
        
        $ignoreTokens = ['LTE', 'NFC', 'GPS', 'USB', 'RAM', 'ROM', 'SIM', 'DUAL', 'PREMIUM', 'EDITION', 
                         'PLUS', 'PRO', 'MAX', 'ULTRA', 'NOTE', 'MINI', 'LITE', 'WIFI', '5G', '4G', '3G',
                         'GLOBAL', 'STANDARD', 'CN', 'EU', 'US', 'JP', 'GALAXY', 'PIXEL'];
        
        $candidatePatterns = [
            '/\b[A-Z]{1,5}-[A-Z]{1,3}[0-9]{2}[A-Z0-9]{0,6}\b/',
            '/\b[A-Z]{1,4}[0-9]{3,6}[A-Z0-9]{0,6}\b/',
            '/\b[0-9]{4,6}[A-Z]{2,6}[0-9]?[A-Z]?\b/',
            '/\b[0-9]{3,4}[A-Z]{2,3}\b/'
        ];
        
        $candidates = [];
        foreach ($candidatePatterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                $candidates = array_merge($candidates, $matches[0]);
            }
        }
        
        $valid = [];
        foreach ($candidates as $c) {
            $stripped = trim($c, '-');
            
            if (strpos($stripped, '/') !== false) {
                $stripped = explode('/', $stripped)[0];
            }
            
            if (!preg_match('/[A-Z]/i', $stripped) || !preg_match('/[0-9]/', $stripped)) {
                continue;
            }
            
            if (preg_match('/^[0-9]+(?:GB|TB|MB|GIB)$/i', $stripped)) {
                continue;
            }
            
            if (in_array(strtoupper($stripped), $ignoreTokens)) {
                continue;
            }
            
            if (strlen($stripped) >= 3 && strlen($stripped) <= 15) {
                $valid[] = strtoupper($stripped);
            }
        }
        
        return !empty($valid) ? end($valid) : null;
    }
    
    private function generateBuildId($androidVersion, $brand) {
        $prefixes = [
            'Samsung' => 'UP1A',
            'Xiaomi' => 'BP2A',
            'Oppo' => 'TP1A',
            'Huawei' => 'HP1A',
            'Google' => 'AP2A',
            'Sony' => 'RP1A',
            'Motorola' => 'CP1A',
            'HTC' => 'HP2A',
            'Asus' => 'BP1A',
            'Lenovo' => 'LP1A',
            'ZTE' => 'ZP1A',
            'TCL' => 'TP2A',
            'Meizu' => 'MP1A',
            'Sharp' => 'SP1A',
            'Nothing' => 'NP1A',
            'ZUK' => 'ZK1A',
            'Alcatel' => 'AP1A',
            'T-Mobile' => 'TP3A',
        ];
        
        $prefix = $prefixes[$brand] ?? 'AP1A';
        $date = date('ymd');
        $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        
        return $prefix . '.' . $date . '.' . $random;
    }
    
    private function generateIncremental($brand, $modelCode, $androidVersion) {
        $pattern = $this->brandPatterns[$brand]['incremental_pattern'] ?? '{MODEL}{RANDOM}';
        
        $replacements = [
            '{MODEL}' => $modelCode,
            '{VERSION}' => $androidVersion,
            '{RANDOM}' => strtoupper(substr(md5(uniqid()), 0, 10)),
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $pattern);
    }
    
    private function getBuildUser($brand) {
        return $this->brandPatterns[$brand]['build_user'] ?? 'root';
    }
    
    private function getBuildType($brand) {
        return $this->brandPatterns[$brand]['build_type'] ?? 'user';
    }
    
    private function getBuildTags($brand) {
        return $this->brandPatterns[$brand]['build_tags'] ?? 'release-keys';
    }
    
    private function generateBuildHost($brand) {
        $pattern = $this->brandPatterns[$brand] ?? null;
        
        if (!$pattern) {
            return 'build-' . rand(1000, 9999);
        }
        
        $prefix = $pattern['build_host_prefix'];
        $suffix = $pattern['build_host_suffix'];
        
        if (empty($prefix) && empty($suffix)) {
            return strtoupper(bin2hex(random_bytes(6)));
        }
        
        if ($brand === 'Google') {
            return strtolower(bin2hex(random_bytes(6)));
        }
        
        if ($brand === 'Huawei') {
            return $prefix . strtolower(bin2hex(random_bytes(8))) . '-' . strtolower(bin2hex(random_bytes(5)));
        }
        
        if ($brand === 'Oppo') {
            return $prefix . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        }
        
        if ($brand === 'Xiaomi') {
            return $prefix . rand(100000, 999999) . '-' . strtolower(substr(bin2hex(random_bytes(3)), 0, 5)) . '-' . strtolower(substr(bin2hex(random_bytes(3)), 0, 5));
        }
        
        return $prefix . rand(1, 999) . $suffix;
    }
    
    private function generateFlavor($brand, $productName, $buildType = null) {
        if ($buildType === null) {
            $buildType = $this->getBuildType($brand);
        }
        
        if ($brand === 'Samsung') {
            return $productName . 'xxx-' . $buildType;
        }
        
        return $productName . '-' . $buildType;
    }
    
    private function generateProductName($brand, $codename) {
        return $codename;
    }
    
    private function generateLocale($specs) {
        $locales = ['en-US', 'en-GB', 'zh-CN', 'ja-JP', 'ko-KR', 'vi-VN'];
        return $locales[array_rand($locales)];
    }
    
    private function extractCodename($specs, $name) {
        if (isset($specs['Codename']) && !empty($specs['Codename'])) {
            $codename = trim($specs['Codename']);
            
            $parts = preg_split('/\s+/', $codename);
            if (count($parts) >= 2) {
                $codePart = implode('', array_slice($parts, 1));
                if (!empty($codePart)) {
                    return strtolower($codePart);
                }
            }
            
            if (preg_match('/\s+([A-Z][a-z0-9]+)$/i', $codename, $matches)) {
                return strtolower($matches[1]);
            }
            return strtolower(str_replace(' ', '', $codename));
        }
        
        if (isset($specs['codename']) && !empty($specs['codename'])) {
            return strtolower($specs['codename']);
        }
        
        if (!empty($name)) {
            if (preg_match('/\(([^)]+)\)$/', $name, $matches)) {
                $extracted = trim($matches[1]);
                $parts = preg_split('/\s+/', $extracted);
                if (count($parts) >= 2) {
                    return strtolower(implode('', array_slice($parts, 1)));
                }
                return strtolower(str_replace(' ', '', $extracted));
            }
        }
        
        if (isset($specs['OEM ID']) && !empty($specs['OEM ID'])) {
            return strtolower($specs['OEM ID']);
        }
        
        return 'generic';
    }
    
    private function extractProductDevice($codename, $modelCode) {
        if (preg_match('/^[a-z]+\d+[a-z]*$/', $codename)) {
            return $codename;
        }
        
        $device = preg_replace('/[^a-z0-9]/', '', strtolower($modelCode));
        return $device;
    }
    
    private function generateDescription($flavor, $androidVersion, $buildId, $incremental, $buildTags) {
        return $flavor . ' ' . $androidVersion . ' ' . $buildId . ' ' . $incremental . ' ' . $buildTags;
    }
    
    private function generateFingerprint($brand, $productName, $productDevice, $androidVersion, $buildId, $incremental, $buildType, $buildTags) {
        $brandLower = strtolower($brand);
        
        return $brandLower . '/' . $productName . '/' . $productDevice . ':' . $androidVersion . '/' . $buildId . '/' . $incremental . ':' . $buildType . '/' . $buildTags;
    }
    
    private function generateVendorFingerprint($brand, $productName, $productDevice, $androidVersion, $incremental, $buildType, $buildTags) {
        $brandLower = strtolower($brand);
        
        $buildId = 'TP1A.' . date('ymd') . '.' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        
        return $brandLower . '/' . $productName . '/' . $productDevice . ':' . $androidVersion . '/' . $buildId . '/' . $incremental . ':' . $buildType . '/' . $buildTags;
    }
    
    private function extractHardwareEgl($gpu) {
        if (stripos($gpu, 'Adreno') !== false) {
            return 'adreno';
        }
        if (stripos($gpu, 'Mali') !== false) {
            return 'mali';
        }
        if (stripos($gpu, 'PowerVR') !== false) {
            return 'powervr';
        }
        return 'adreno';
    }
    
    private function extractHardwareVulkan($gpu) {
        if (stripos($gpu, 'Adreno') !== false) {
            return 'adreno';
        }
        if (stripos($gpu, 'Mali') !== false) {
            return 'mali';
        }
        if (stripos($gpu, 'PowerVR') !== false) {
            return 'powervr';
        }
        return 'adreno';
    }
    
    private function extractBoard($specs) {
        if (isset($specs['cpu']) && preg_match('/([a-z0-9]+)$/i', $specs['cpu'], $matches)) {
            return strtolower($matches[1]);
        }
        return 'universal' . rand(1000, 9999);
    }
    
    private function extractCpuManufacturer($cpuString) {
        if (!$cpuString) return null;
        
        $manufacturers = [
            '/\bQualcomm\b/i' => 'Qualcomm',
            '/\bMediaTek\b/i' => 'MediaTek',
            '/\bSamsung\s+Exynos\b/i' => 'Samsung',
            '/\bGoogle\s+Tensor\b/i' => 'Google',
            '/\bHiSilicon\b/i' => 'HiSilicon',
            '/\bUnisoc\b/i' => 'Unisoc',
            '/\bSpreadtrum\b/i' => 'Unisoc',
            '/\bNVIDIA\b/i' => 'NVIDIA',
            '/\bIntel\b/i' => 'Intel',
            '/\bMarvell\b/i' => 'Marvell',
            '/\bApple\b/i' => 'Apple',
        ];
        
        foreach ($manufacturers as $pattern => $name) {
            if (preg_match($pattern, $cpuString)) {
                return $name;
            }
        }
        
        return null;
    }
    
    private function extractCpuModelCode($cpuString) {
        if (!$cpuString) return null;
        
        if (preg_match('/\(([A-Z]{1,5}[0-9]{2,6}[A-Z0-9\/-]*)\)/i', $cpuString, $matches)) {
            $parenCode = $matches[1];
            if ($this->isCpuChipPattern($parenCode)) {
                return strtoupper($parenCode);
            }
        }
        
        $chipPatterns = [
            '/\bSM[0-9]{4}-[A-Z0-9]+\b/i',
            '/\bSM[0-9]{4}[A-Z]{0,2}\b/i',
            '/\bMSM[0-9]{4}[A-Z0-9]*\b/i',
            '/\bAPQ[0-9]{4}[A-Z0-9]*\b/i',
            '/\bSDM[0-9]{3}[A-Z]?\b/i',
            '/\bMT[0-9]{4}[A-Z0-9]*(?:\/[A-Z0-9]+)?\b/i',
            '/\bS5E[0-9]{4}[A-Z]?\b/i',
            '/\bS5P[0-9]{4}[A-Z]?\b/i',
            '/\bHi[0-9]{3,5}[A-Z0-9]*\b/i',
            '/\bKIRIN[0-9]{3,4}[A-Z]?\b/i',
            '/\bSC[0-9]{4}[A-Z0-9]*\b/i',
            '/\bAPL[0-9][A-Z][0-9]{2}\b/i',
            '/\bZ[0-9]{4}[A-Z]?\b/i',
        ];
        
        foreach ($chipPatterns as $pattern) {
            if (preg_match($pattern, $cpuString, $matches)) {
                return strtoupper($matches[0]);
            }
        }
        
        $marketingPatterns = [
            '/Snapdragon\s+[0-9]+\s+Gen\s+[0-9]+\+?/i',
            '/Snapdragon\s+[0-9]+[A-Za-z+]*/i',
            '/Dimensity\s+[0-9]+[A-Za-z+]*/i',
            '/Helio\s+[A-Z][0-9]+[A-Za-z]*/i',
            '/Kirin\s+[0-9]+[A-Za-z+ ]*/i',
            '/Exynos\s+[0-9]+[A-Za-z]*/i',
            '/Tensor\s+G?[0-9]+/i',
            '/A[0-9]+\s+(?:Bionic|chip|Chip)/i',
            '/Unisoc\s+T[0-9]+/i',
        ];
        
        foreach ($marketingPatterns as $pattern) {
            if (preg_match($pattern, $cpuString, $matches)) {
                return trim($matches[0]);
            }
        }
        
        return null;
    }
    
    private function isCpuChipPattern($code) {
        $prefixes = ['SM', 'MT', 'MSM', 'APQ', 'SDM', 'S5E', 'S5P', 'Hi', 'SC', 'PXA', 'APL', 'KIRIN'];
        foreach ($prefixes as $prefix) {
            if (stripos($code, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }
    
    private function extractGpuName($gpuString, $cpuString = null) {
        if ($gpuString && trim($gpuString)) {
            $cleaned = preg_replace('/,\s*20[0-9]{2}\s*$/', '', trim($gpuString));
            $cleaned = preg_replace('/\s+GPU\s*$/i', '', $cleaned);
            $cleaned = preg_replace('/\s+/', ' ', $cleaned);
            return $cleaned;
        }
        
        if (!$cpuString) return null;
        
        $gpuPatterns = [
            '/Qualcomm\s+Adreno\s+[0-9]+[A-Za-z]*/i',
            '/Adreno\s+[0-9]+[A-Za-z]*/i',
            '/Arm\s+Mali[-\s][A-Z][0-9]+(?:\s+MC[0-9]+)?/i',
            '/ARM\s+Mali[-\s][A-Z][0-9]+(?:\s+MC[0-9]+)?/i',
            '/Samsung\s+(?:Xclipse|S3C)[A-Za-z0-9\s]+/i',
            '/Apple\s+GPU/i',
            '/PowerVR\s+[A-Z0-9]+(?:\s+XE)?/i',
            '/NVIDIA\s+(?:Tegra|GeForce)\s+[A-Z0-9]+/i',
            '/Intel\s+(?:HD|UHD|Iris)\s+[A-Za-z0-9 ]+/i',
            '/Vivante\s+GC[0-9]+/i',
        ];
        
        foreach ($gpuPatterns as $pattern) {
            if (preg_match($pattern, $cpuString, $matches)) {
                $result = trim($matches[0]);
                $result = preg_replace('/\s+GPU\s*$/i', '', $result);
                return trim($result);
            }
        }
        
        return null;
    }
    
    private function extractPlatform($specs) {
        $cpu = $specs['CPU'] ?? $specs['cpu'] ?? '';
        
        if (stripos($cpu, 'Snapdragon') !== false) {
            if (preg_match('/Snapdragon\s+(\d+)/', $cpu, $matches)) {
                return 'msm' . $matches[1];
            }
        }
        
        if (stripos($cpu, 'Exynos') !== false) {
            if (preg_match('/Exynos\s+(\d{4})/', $cpu, $matches)) {
                return 'exynos' . $matches[1];
            }
        }
        
        if (stripos($cpu, 'MediaTek') !== false) {
            if (preg_match('/MT(\d{4})/', $cpu, $matches)) {
                return 'mt' . $matches[1];
            }
        }
        
        if (stripos($cpu, 'UNISOC') !== false) {
            if (preg_match('/([A-Z]{2}\d{4}[A-Z]?)/', $cpu, $matches)) {
                return strtolower($matches[1]);
            }
        }
        
        return 'unknown';
    }
    
    private function generateBaseband($brand, $modelCode) {
        return $modelCode . 'BB_' . rand(100, 999);
    }
    
    private function generateRadioVersion($brand, $modelCode) {
        if ($brand === 'Samsung') {
            $modelPrefix = preg_replace('/^SM-/', '', $modelCode);
            $modelPrefix = preg_replace('/[^A-Z0-9]/', '', $modelPrefix);
            $modelPrefix = substr($modelPrefix, 0, 5);
            
            $regions = ['XXU', 'XXS', 'XXN', 'KSU', 'DSU', 'OXM', 'DBT', 'XSG'];
            $region = $regions[array_rand($regions)];
            
            $version = rand(1, 9);
            
            $buildSuffix = chr(65 + rand(0, 25)) . 
                          chr(65 + rand(0, 25)) . 
                          chr(65 + rand(0, 25)) . 
                          rand(1, 9);
            
            return $modelPrefix . $region . $version . $buildSuffix;
        }
        
        if ($brand === 'Xiaomi') {
            return 'V' . rand(10, 14) . '.' . rand(0, 9) . '.' . rand(1, 31) . '.0.RUXMIXM';
        }
        
        if ($brand === 'Google') {
            return 'g5123b-' . rand(100000, 999999) . '-' . date('ymd') . '-RC' . rand(1, 9);
        }
        
        return rand(1, 9) . '.' . rand(10, 99) . '.' . rand(10, 99);
    }
    
    /**
     * UUID v4 (RFC 4122)
     */
    private function generateUUIDv4() {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        $hex     = bin2hex($data);
        return sprintf('%s-%s-%s-%s-%s',
            substr($hex,  0, 8),
            substr($hex,  8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }

    /**
     * Generate all Group 1 + Group 2 device identifiers.
     * Can be called standalone (idinfo.php) or is embedded in generate().
     *
     * @param string|null $brand  e.g. "Samsung", "Xiaomi"
     * @param string|null $oemId  e.g. "M236BLBHXME"
     */
    public function generateDeviceIDs($brand = null, $oemId = null) {
        $oui     = $this->getBrandOUI($brand);
        $btMac   = $oui . ':' . $this->randomHexByteMac() . ':' . $this->randomHexByteMac() . ':' . $this->randomHexByteMac();
        $wifiMac = $oui . ':' . $this->randomHexByteMac() . ':' . $this->randomHexByteMac() . ':' . $this->randomHexByteMac();

        return [
            'AndroidID'               => bin2hex(random_bytes(8)),
            'BuildSerialNumber'       => $this->generateSerialNumber($brand, $oemId),
            'BluetoothMACAddress'     => $btMac,
            'WiFiMACAddress'          => $wifiMac,
            'WiFiSSID'                => $this->generateWiFiSSID($brand),
            'GoogleAdvertisingID'     => $this->generateUUIDv4(),
            'GoogleServiceFramework'  => bin2hex(random_bytes(8)),
            'ClientUUID'              => $this->generateUUIDv4(),
            'OAID'                    => $this->generateUUIDv4(),
        ];
    }

    /**
     * Brand-specific serial number format (ro.boot.serialno)
     *
     * Samsung  : R[2 digits][letter][7 alphanum]      e.g. R58M7A1B2C3D  (11)
     * Xiaomi   : [15 uppercase alphanum]
     * Huawei   : [3-letter prefix][12 hex uppercase]  e.g. CNA0A1B2C3D4E5
     * Google   : [2 uppercase letters][10 alphanum]   e.g. FA6B7C8D9E0F
     * Sony     : CB[13 alphanum]
     * Motorola : ZY[10 alphanum]
     * OnePlus  : [2 letters][13 alphanum]
     * Oppo/Realme : [3 letters][11 alphanum]
     * Vivo     : [2 letters][13 alphanum]
     * Nokia    : [14 alphanum]
     * Lenovo/Asus : [16 alphanum]
     * Default  : 16-char uppercase hex
     */
    private function generateSerialNumber($brand = null, $oemId = null) {
        switch ($brand) {
            case 'Samsung':
                $prefix = 'R' . random_int(3, 9) . random_int(0, 9) . chr(65 + random_int(0, 25));
                return $prefix . $this->randomAlphanumStr(7);

            case 'Xiaomi':
                return $this->randomAlphanumStr(15);

            case 'Huawei':
                $prefixes = ['EZA', 'CNA', 'CLT', 'HLN', 'VOG', 'ANA'];
                return $prefixes[array_rand($prefixes)] . strtoupper(bin2hex(random_bytes(6)));

            case 'Google':
                $letters = chr(65 + random_int(0, 25)) . chr(65 + random_int(0, 25));
                return $letters . $this->randomAlphanumStr(10);

            case 'Sony':
                return 'CB' . $this->randomAlphanumStr(13);

            case 'Motorola':
                return 'ZY' . $this->randomAlphanumStr(10);

            case 'OnePlus':
                $letters = chr(65 + random_int(0, 25)) . chr(65 + random_int(0, 25));
                return $letters . $this->randomAlphanumStr(13);

            case 'Oppo':
            case 'Realme':
                $letters = chr(65 + random_int(0, 25)) . chr(65 + random_int(0, 25)) . chr(65 + random_int(0, 25));
                return $letters . $this->randomAlphanumStr(11);

            case 'Vivo':
                $letters = chr(65 + random_int(0, 25)) . chr(65 + random_int(0, 25));
                return $letters . $this->randomAlphanumStr(13);

            case 'Nokia':
                return $this->randomAlphanumStr(14);

            case 'HTC':
                return strtoupper(bin2hex(random_bytes(8)));

            case 'Lenovo':
            case 'Asus':
                return $this->randomAlphanumStr(16);

            default:
                if ($oemId && strlen($oemId) >= 6) {
                    $clean = strtoupper(preg_replace('/[^A-Z0-9]/', '', $oemId));
                    return substr($clean, 0, 6) . $this->randomAlphanumStr(10);
                }
                return strtoupper(bin2hex(random_bytes(8)));
        }
    }

    private function randomHexByteMac() {
        return strtoupper(bin2hex(random_bytes(1)));
    }

    private function randomAlphanumStr($len) {
        $pool   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';
        for ($i = 0; $i < $len; $i++) {
            $result .= $pool[random_int(0, 35)];
        }
        return $result;
    }

    private function getBrandOUI($brand) {
        $brandOUIs = [
            'Samsung'  => ['A0:07:98', '8C:71:F8', 'E0:CB:1D', '5C:3A:45', '40:0E:85',
                           'AC:5F:3E', 'B4:3A:28', '08:D4:0C', 'CC:07:AB', 'D8:57:EF'],
            'Xiaomi'   => ['34:80:B3', '2C:DB:07', 'AC:F7:F3', '28:E3:1F', '8C:BE:BE',
                           '64:A2:F9', 'F4:F5:DB', '58:44:98', '20:47:DA'],
            'Huawei'   => ['04:F9:38', '14:9D:09', '28:31:52', 'AC:E2:15', '68:26:08',
                           'A0:86:C6', '54:89:98', '04:B1:67', '9C:28:EF'],
            'OnePlus'  => ['AC:B3:13', '94:65:2D', '8C:8D:28', 'A4:C1:38'],
            'Google'   => ['A4:C3:F0', 'B4:F1:DA', '3C:28:6D', 'F4:F5:DB', '54:60:09'],
            'Oppo'     => ['C0:EE:FB', '14:55:95', '0C:1D:AF', 'DC:44:27', 'A4:50:46'],
            'Realme'   => ['C0:EE:FB', '14:55:95', '0C:1D:AF', 'DC:44:27'],
            'Vivo'     => ['B8:D7:AF', '10:2C:6B', '58:B3:FC', 'A4:77:33'],
            'Sony'     => ['30:17:C8', '80:4E:70', 'BC:F5:AC', '4C:BC:98'],
            'Motorola' => ['00:22:A4', 'AC:37:43', 'E8:D8:D1', '04:B1:67', 'DC:D9:16'],
            'LG'       => ['00:1E:75', 'A8:9C:ED', 'C0:97:27', '14:A3:64'],
            'HTC'      => ['18:87:96', '00:EE:BD', 'AC:BD:D9', '64:A7:69'],
            'Lenovo'   => ['DC:53:60', '00:90:4C', 'E4:54:E8', '70:C9:4E'],
            'Asus'     => ['00:17:31', 'AC:22:0B', '10:7B:44', '74:D4:35'],
            'Nokia'    => ['C4:AC:59', '68:54:5A', '20:6B:E7', '04:69:F8'],
            'Nothing'  => ['C4:AC:59', '68:54:5A'],
            'TCL'      => ['14:55:95', 'DC:44:27', '00:00:4C'],
            'Sharp'    => ['00:1F:D6', '98:F4:AB'],
            'Meizu'    => ['88:C9:D0', 'AC:1F:74'],
            'ZTE'      => ['28:5F:DB', '8C:A9:82', 'BC:14:EF'],
        ];

        if ($brand && isset($brandOUIs[$brand])) {
            $list = $brandOUIs[$brand];
            return $list[random_int(0, count($list) - 1)];
        }

        $all = array_merge(...array_values($brandOUIs));
        return $all[random_int(0, count($all) - 1)];
    }

    private function generateWiFiSSID($brand) {
        $brandPrefixes = [
            'Samsung'  => ['SAMSUNG-', 'Galaxy_'],
            'Xiaomi'   => ['Xiaomi_', 'Redmi_', 'Mi_', 'POCO_'],
            'Huawei'   => ['HUAWEI-', 'Honor_'],
            'OnePlus'  => ['OnePlus_'],
            'Google'   => ['Pixel_'],
            'Oppo'     => ['OPPO-', 'Find_'],
            'Realme'   => ['realme_'],
            'Vivo'     => ['Vivo_', 'iQOO_'],
            'Sony'     => ['Xperia_'],
            'Motorola' => ['Moto_', 'motorola_'],
            'LG'       => ['LG-'],
            'Nokia'    => ['Nokia_'],
            'Asus'     => ['ASUS_', 'ZenFone_'],
            'Nothing'  => ['Nothing_'],
            'TCL'      => ['TCL_'],
        ];

        if ($brand && isset($brandPrefixes[$brand])) {
            $list   = $brandPrefixes[$brand];
            $prefix = $list[random_int(0, count($list) - 1)];
        } else {
            $fallback = ['AndroidAP_', 'Hotspot_', 'Phone_', 'Mobile_'];
            $prefix   = $fallback[random_int(0, count($fallback) - 1)];
        }

        return $prefix . strtoupper(bin2hex(random_bytes(2)));
    }
    
    private function generateBootloader($brand, $modelCode) {
        return $modelCode . 'U' . rand(1, 9) . chr(65 + rand(0, 25)) . chr(65 + rand(0, 25)) . rand(1, 9);
    }
    
    private function extractChipset($specs) {
        $cpu = $specs['cpu'] ?? '';
        
        if (preg_match('/(Snapdragon|Exynos|Dimensity|MediaTek)\s*(\d+)/i', $cpu, $matches)) {
            return $matches[1] . ' ' . $matches[2];
        }
        
        return 'Unknown';
    }
    
    private function extractResolution($specs) {
        $resolutionStr = $specs['Resolution'] ?? $specs['resolution'] ?? '';
        
        if (preg_match('/(\d+)\s*[xX×]\s*(\d+)/', $resolutionStr, $matches)) {
            return [
                'width' => (int)$matches[1],
                'height' => (int)$matches[2]
            ];
        }
        
        return [
            'width' => 1080,
            'height' => 2400
        ];
    }
    
    private function extractCpuCores($cpuString) {
        if (!$cpuString) return 8;
        
        if (preg_match('/\b(deca|10)[-\s]?core\b/i', $cpuString)) {
            return 10;
        }
        
        if (preg_match('/\b(octa|8)[-\s]?core\b/i', $cpuString)) {
            return 8;
        }
        
        if (preg_match('/\b(hexa|6)[-\s]?core\b/i', $cpuString)) {
            return 6;
        }
        
        if (preg_match('/\b(quad|4)[-\s]?core\b/i', $cpuString)) {
            return 4;
        }
        
        if (preg_match('/\b(dual|2)[-\s]?core\b/i', $cpuString)) {
            return 2;
        }
        
        return 8;
    }
    
    private function extractMemoryTotal($specs) {
        $ramStr = $specs['RAM Capacity (converted)'] ?? 
                  $specs['ram_capacity_converted'] ?? 
                  $specs['RAM'] ?? 
                  $specs['ram'] ?? '';
        
        if (preg_match('/(\d+)\s*(GB|GiB|MB|MiB)/i', $ramStr, $matches)) {
            $value = (int)$matches[1];
            $unit = strtoupper($matches[2]);
            
            if ($unit === 'GB' || $unit === 'GIB') {
                return $value * 1024;
            } elseif ($unit === 'MB' || $unit === 'MIB') {
                return $value;
            }
        }
        
        return 4096;
    }
    
    private function generateBluetoothName($brand, $codename, $specs) {
        $oemId = $specs['OEM ID'] ?? $specs['oem_id'] ?? '';
        
        if ($codename && $codename !== 'generic') {
            $parts = preg_split('/\s+/', trim($codename));
            
            if (count($parts) >= 2) {
                $name = implode(' ', array_slice($parts, 1));
                
                if ($brand === 'Samsung' && !stripos($name, 'galaxy')) {
                    return 'Galaxy ' . ucfirst($name);
                }
                
                return ucfirst($name);
            }
            
            return ucfirst($codename);
        }
        
        if ($oemId) {
            return strtoupper($oemId);
        }
        
        return $brand . ' Device';
    }
    
    private function generateUserAgent($modelCode, $androidVersion, $buildId) {
        $chromeVersion = rand(118, 122) . '.0.' . rand(6000, 6200) . '.' . rand(100, 200);
        
        return "Mozilla/5.0 (Linux; Android {$androidVersion}; {$modelCode} Build/{$buildId}) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/{$chromeVersion} Mobile Safari/537.36";
    }
    
    private function extractBatteryCapacity($specs) {
        if (isset($specs['Nominal Battery Capacity'])) {
            if (preg_match('/(\d+)\s*mAh/', $specs['Nominal Battery Capacity'], $matches)) {
                return (int)$matches[1];
            }
        }
        
        if (isset($specs['nominal_battery_capacity'])) {
            if (preg_match('/(\d+)\s*mAh/', $specs['nominal_battery_capacity'], $matches)) {
                return (int)$matches[1];
            }
        }
        
        return rand(3000, 5000);
    }
}
