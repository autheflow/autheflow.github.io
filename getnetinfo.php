<?php
require_once 'api/config.php';

$licenseKey      = $_GET['License'] ?? $_GET['license'] ?? '';
$networkOperator = trim($_GET['network_operator'] ?? '');
$countryName     = trim($_GET['country_name'] ?? '');

if (empty($licenseKey)) {
    sendResponse(['success' => false, 'error' => 'License key is required'], 400);
}

if (empty($networkOperator)) {
    sendResponse(['success' => false, 'error' => 'Parameter "network_operator" is required'], 400);
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

    // ── Find operator — collect all matches by name first ─────────────────────
    $matches = [];
    foreach ($operators as $op) {
        if (strcasecmp($op['network_operator'], $networkOperator) === 0) {
            $matches[] = $op;
        }
    }

    if (empty($matches)) {
        sendResponse(['success' => false, 'error' => 'Operator "' . $networkOperator . '" not found'], 404);
    }

    // If multiple countries have the same operator name, require country_name
    if (count($matches) > 1 && empty($countryName)) {
        $countries = array_map(fn($m) => [
            'country_code' => $m['country_code'],
            'country_name' => $m['country_name'],
        ], $matches);

        sendResponse([
            'success' => false,
            'error'   => 'Operator "' . $networkOperator . '" exists in multiple countries. Please specify country_name.',
            'hint'    => 'Add &country_name=Vietnam to your request',
            'matches' => $countries,
        ], 409);
    }

    // Filter by country_name if provided (case-insensitive)
    $found = null;
    if (!empty($countryName)) {
        foreach ($matches as $op) {
            if (strcasecmp($op['country_name'], $countryName) === 0) {
                $found = $op;
                break;
            }
        }

        if (!$found) {
            $countries = array_map(fn($m) => [
                'country_code' => $m['country_code'],
                'country_name' => $m['country_name'],
            ], $matches);

            sendResponse([
                'success'      => false,
                'error'        => 'Operator "' . $networkOperator . '" not found in country "' . $countryName . '"',
                'available_in' => $countries,
            ], 404);
        }
    } else {
        $found = $matches[0];
    }

    $mcc         = $found['mcc'];
    $mnc         = $found['mnc'];
    $countryCode = $found['country_code'];

    // ── Generate Group 3 carrier IDs ──────────────────────────────────────────
    $imsi            = generateIMSI($mcc, $mnc);
    $iccid           = generateICCID($countryCode, $mnc);
    $imei            = generateIMEI();
    $phoneNumber     = generatePhoneNumber($countryCode);

    sendResponse([
        'success'  => true,
        'operator' => [
            'country_code'     => $found['country_code'],
            'country_name'     => $found['country_name'],
            'network_operator' => $found['network_operator'],
            'operator'         => $found['operator'],
            'mcc'              => $mcc,
            'mnc'              => $mnc,
        ],
        'ids' => [
            'IMEI'            => $imei,
            'SIMSerialNumber' => $iccid,
            'SubscriberID'    => $imsi,
            'PhoneNumber'     => $phoneNumber,
        ],
    ], 200);

} catch (PDOException $e) {
    sendResponse(['success' => false, 'error' => 'Database error'], 500);
} catch (Exception $e) {
    sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
}

// ═══════════════════════════════════════════════════════════════════════════════
// Luhn helpers
// ═══════════════════════════════════════════════════════════════════════════════

function luhnSum($digits) {
    $sum = 0;
    $alt = false;
    for ($i = strlen($digits) - 1; $i >= 0; $i--) {
        $n = intval($digits[$i]);
        if ($alt) {
            $n *= 2;
            if ($n > 9) $n -= 9;
        }
        $sum += $n;
        $alt  = !$alt;
    }
    return $sum;
}

function luhnCheckDigit($base) {
    $sum = luhnSum($base . '0');
    return ($sum % 10 === 0) ? 0 : (10 - ($sum % 10));
}

// ═══════════════════════════════════════════════════════════════════════════════
// IMSI — MCC(3) + MNC(2-3) + MSIN = 15 digits total
// ═══════════════════════════════════════════════════════════════════════════════
function generateIMSI($mcc, $mnc) {
    $base      = $mcc . $mnc;
    $remaining = 15 - strlen($base);

    if ($remaining < 1) {
        $remaining = 1;
    }

    $msin = '';
    for ($i = 0; $i < $remaining; $i++) {
        $msin .= random_int(0, 9);
    }

    return $base . $msin;
}

// ═══════════════════════════════════════════════════════════════════════════════
// ICCID (SIM Serial Number)
// Format: 89 + country_calling_code + MNC(padded 2) + subscriber + Luhn = 20 digits
// ═══════════════════════════════════════════════════════════════════════════════
function generateICCID($countryCode, $mnc) {
    $callingCode = getCallingCode($countryCode);

    $prefix    = '89' . ltrim($callingCode, '+');
    $mncPadded = str_pad($mnc, 2, '0', STR_PAD_LEFT);
    $base      = $prefix . $mncPadded;

    $remaining = 19 - strlen($base);
    if ($remaining < 1) $remaining = 1;

    $subscriber = '';
    for ($i = 0; $i < $remaining; $i++) {
        $subscriber .= random_int(0, 9);
    }

    $base .= $subscriber;
    return $base . luhnCheckDigit($base);
}

// ═══════════════════════════════════════════════════════════════════════════════
// IMEI — TAC(8) + serial(6) + Luhn check = 15 digits
// Uses real TAC codes from Android 9-era devices
// ═══════════════════════════════════════════════════════════════════════════════
function generateIMEI() {
    $tacs = [
        '35299209', '35780109', '35388209', '35199509',
        '86780903', '86498903', '86357703',
        '35785008', '35785108', '35920208',
        '86614904', '86868103',
        '35404108', '35403908',
        '35982108',
        '35250210',
        '35916807',
        '35347608',
        '86958803',
        '35847807',
    ];

    $tac    = $tacs[array_rand($tacs)];
    $serial = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $base   = $tac . $serial;

    return $base . luhnCheckDigit($base);
}

// ═══════════════════════════════════════════════════════════════════════════════
// Phone Number — country calling code + subscriber digits
// ═══════════════════════════════════════════════════════════════════════════════
function generatePhoneNumber($countryCode) {
    $callingCode = getCallingCode($countryCode);
    $format      = getPhoneFormat($countryCode);

    $digits = '';
    for ($i = 0; $i < $format['length']; $i++) {
        if ($i === 0 && isset($format['first_digit'])) {
            $digits .= $format['first_digit'][array_rand($format['first_digit'])];
        } else {
            $digits .= random_int(0, 9);
        }
    }

    return $callingCode . $digits;
}

// ── International calling codes (ISO alpha-2 → +XX) ──────────────────────────
function getCallingCode($countryCode) {
    $codes = [
        'AD' => '+376', 'AE' => '+971', 'AF' => '+93',  'AL' => '+355',
        'AM' => '+374', 'AO' => '+244', 'AR' => '+54',  'AT' => '+43',
        'AU' => '+61',  'AZ' => '+994', 'BA' => '+387', 'BB' => '+1246',
        'BD' => '+880', 'BE' => '+32',  'BF' => '+226', 'BG' => '+359',
        'BH' => '+973', 'BI' => '+257', 'BJ' => '+229', 'BN' => '+673',
        'BO' => '+591', 'BR' => '+55',  'BT' => '+975', 'BW' => '+267',
        'BY' => '+375', 'BZ' => '+501', 'CA' => '+1',   'CD' => '+243',
        'CF' => '+236', 'CG' => '+242', 'CH' => '+41',  'CI' => '+225',
        'CL' => '+56',  'CM' => '+237', 'CN' => '+86',  'CO' => '+57',
        'CR' => '+506', 'CU' => '+53',  'CV' => '+238', 'CY' => '+357',
        'CZ' => '+420', 'DE' => '+49',  'DJ' => '+253', 'DK' => '+45',
        'DZ' => '+213', 'EC' => '+593', 'EE' => '+372', 'EG' => '+20',
        'ER' => '+291', 'ES' => '+34',  'ET' => '+251', 'FI' => '+358',
        'FJ' => '+679', 'FR' => '+33',  'GA' => '+241', 'GB' => '+44',
        'GE' => '+995', 'GH' => '+233', 'GM' => '+220', 'GN' => '+224',
        'GQ' => '+240', 'GR' => '+30',  'GT' => '+502', 'GW' => '+245',
        'GY' => '+592', 'HK' => '+852', 'HN' => '+504', 'HR' => '+385',
        'HT' => '+509', 'HU' => '+36',  'ID' => '+62',  'IE' => '+353',
        'IL' => '+972', 'IN' => '+91',  'IQ' => '+964', 'IR' => '+98',
        'IS' => '+354', 'IT' => '+39',  'JM' => '+1876','JO' => '+962',
        'JP' => '+81',  'KE' => '+254', 'KG' => '+996', 'KH' => '+855',
        'KI' => '+686', 'KM' => '+269', 'KN' => '+1869','KP' => '+850',
        'KR' => '+82',  'KW' => '+965', 'KZ' => '+7',   'LA' => '+856',
        'LB' => '+961', 'LC' => '+1758','LI' => '+423', 'LK' => '+94',
        'LR' => '+231', 'LS' => '+266', 'LT' => '+370', 'LU' => '+352',
        'LV' => '+371', 'LY' => '+218', 'MA' => '+212', 'MC' => '+377',
        'MD' => '+373', 'ME' => '+382', 'MG' => '+261', 'MH' => '+692',
        'MK' => '+389', 'ML' => '+223', 'MM' => '+95',  'MN' => '+976',
        'MO' => '+853', 'MR' => '+222', 'MT' => '+356', 'MU' => '+230',
        'MV' => '+960', 'MW' => '+265', 'MX' => '+52',  'MY' => '+60',
        'MZ' => '+258', 'NA' => '+264', 'NE' => '+227', 'NG' => '+234',
        'NI' => '+505', 'NL' => '+31',  'NO' => '+47',  'NP' => '+977',
        'NR' => '+674', 'NZ' => '+64',  'OM' => '+968', 'PA' => '+507',
        'PE' => '+51',  'PG' => '+675', 'PH' => '+63',  'PK' => '+92',
        'PL' => '+48',  'PT' => '+351', 'PW' => '+680', 'PY' => '+595',
        'QA' => '+974', 'RO' => '+40',  'RS' => '+381', 'RU' => '+7',
        'RW' => '+250', 'SA' => '+966', 'SB' => '+677', 'SC' => '+248',
        'SD' => '+249', 'SE' => '+46',  'SG' => '+65',  'SI' => '+386',
        'SK' => '+421', 'SL' => '+232', 'SM' => '+378', 'SN' => '+221',
        'SO' => '+252', 'SR' => '+597', 'ST' => '+239', 'SV' => '+503',
        'SY' => '+963', 'SZ' => '+268', 'TD' => '+235', 'TG' => '+228',
        'TH' => '+66',  'TJ' => '+992', 'TL' => '+670', 'TM' => '+993',
        'TN' => '+216', 'TO' => '+676', 'TR' => '+90',  'TT' => '+1868',
        'TV' => '+688', 'TW' => '+886', 'TZ' => '+255', 'UA' => '+380',
        'UG' => '+256', 'US' => '+1',   'UY' => '+598', 'UZ' => '+998',
        'VA' => '+39',  'VC' => '+1784','VE' => '+58',  'VN' => '+84',
        'VU' => '+678', 'WS' => '+685', 'YE' => '+967', 'ZA' => '+27',
        'ZM' => '+260', 'ZW' => '+263',
    ];

    return $codes[$countryCode] ?? '+1';
}

// ── Phone number format per country ──────────────────────────────────────────
function getPhoneFormat($countryCode) {
    $formats = [
        'VN' => ['length' => 9,  'first_digit' => ['3','5','7','8','9']],
        'US' => ['length' => 10, 'first_digit' => ['2','3','4','5','6','7','8','9']],
        'CA' => ['length' => 10, 'first_digit' => ['2','3','4','5','6','7','8','9']],
        'GB' => ['length' => 10, 'first_digit' => ['7']],
        'CN' => ['length' => 11, 'first_digit' => ['1']],
        'IN' => ['length' => 10, 'first_digit' => ['6','7','8','9']],
        'JP' => ['length' => 10, 'first_digit' => ['7','8','9']],
        'KR' => ['length' => 10, 'first_digit' => ['1']],
        'AU' => ['length' => 9,  'first_digit' => ['4']],
        'DE' => ['length' => 10, 'first_digit' => ['1','2','3','4','5','6','7','8','9']],
        'FR' => ['length' => 9,  'first_digit' => ['6','7']],
        'IT' => ['length' => 10, 'first_digit' => ['3']],
        'ES' => ['length' => 9,  'first_digit' => ['6','7']],
        'BR' => ['length' => 11, 'first_digit' => ['9']],
        'MX' => ['length' => 10, 'first_digit' => ['1','2','3','4','5','6','7','8','9']],
        'RU' => ['length' => 10, 'first_digit' => ['9']],
        'ID' => ['length' => 11, 'first_digit' => ['8']],
        'PH' => ['length' => 10, 'first_digit' => ['9']],
        'MY' => ['length' => 9,  'first_digit' => ['1']],
        'TH' => ['length' => 9,  'first_digit' => ['6','8','9']],
        'SG' => ['length' => 8,  'first_digit' => ['8','9']],
        'HK' => ['length' => 8,  'first_digit' => ['5','6','9']],
        'TW' => ['length' => 9,  'first_digit' => ['9']],
        'PK' => ['length' => 10, 'first_digit' => ['3']],
        'BD' => ['length' => 10, 'first_digit' => ['1']],
        'NG' => ['length' => 10, 'first_digit' => ['7','8','9']],
        'ZA' => ['length' => 9,  'first_digit' => ['6','7','8']],
        'EG' => ['length' => 10, 'first_digit' => ['1']],
        'SA' => ['length' => 9,  'first_digit' => ['5']],
        'AE' => ['length' => 9,  'first_digit' => ['5']],
        'TR' => ['length' => 10, 'first_digit' => ['5']],
        'UA' => ['length' => 9,  'first_digit' => ['5','6','7','9']],
        'PL' => ['length' => 9,  'first_digit' => ['5','6','7','8','9']],
        'NL' => ['length' => 9,  'first_digit' => ['6']],
        'SE' => ['length' => 9,  'first_digit' => ['7']],
        'NO' => ['length' => 8,  'first_digit' => ['4','9']],
        'DK' => ['length' => 8,  'first_digit' => ['2','3','4','5','6','7','8','9']],
        'BE' => ['length' => 9,  'first_digit' => ['4','4']],
        'CH' => ['length' => 9,  'first_digit' => ['7','8']],
        'AT' => ['length' => 10, 'first_digit' => ['6','7']],
        'PT' => ['length' => 9,  'first_digit' => ['9']],
        'GR' => ['length' => 10, 'first_digit' => ['6','7']],
        'CZ' => ['length' => 9,  'first_digit' => ['6','7']],
        'RO' => ['length' => 9,  'first_digit' => ['7']],
        'HU' => ['length' => 9,  'first_digit' => ['2','3','4','5','6','7','8','9']],
        'AR' => ['length' => 10, 'first_digit' => ['1','2','3','4','5','6','7','8','9']],
        'CO' => ['length' => 10, 'first_digit' => ['3']],
        'KE' => ['length' => 9,  'first_digit' => ['7']],
        'GH' => ['length' => 9,  'first_digit' => ['2','5']],
        'TZ' => ['length' => 9,  'first_digit' => ['6','7']],
        'ET' => ['length' => 9,  'first_digit' => ['9']],
        'IR' => ['length' => 10, 'first_digit' => ['9']],
        'IQ' => ['length' => 10, 'first_digit' => ['7']],
    ];

    return $formats[$countryCode] ?? ['length' => 9, 'first_digit' => ['5','6','7','8','9']];
}
?>
