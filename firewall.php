<?php
// --- Config ---
$useAllowlist = false; // Set false for blocklist mode
$allowedCountries = ['US', 'CA'];
$blockedCountries = ['RU', 'CN', 'IR', 'KP', 'SY', 'CU'];

$blockedBots = [
  'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
  'yandex', 'sogou', 'exabot', 'facebot', 'ia_archiver'
];

// --- Bot User-Agent Filtering ---
$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
foreach ($blockedBots as $bot) {
  if (strpos($ua, $bot) !== false) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access Denied: Bot Detected");
  }
}

// --- Get IP Address ---
$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? null;

// --- Get Country (Cloudflare first, fallback to ipwho.is) ---
$countryCode = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null;

if (!$countryCode) {
  $response = @file_get_contents("https://ipwho.is/{$ip}?fields=country_code");
  $json = @json_decode($response);
  if (isset($json->country_code)) {
    $countryCode = strtoupper($json->country_code);
  }
}

// --- Failsafe Block If Country Cannot Be Determined ---
if (!$countryCode) {
  header("HTTP/1.1 403 Forbidden");
  exit("Access Denied: Unable to determine region");
}

// --- Geo Block/Allow Logic ---
if (
  ($useAllowlist && !in_array($countryCode, $allowedCountries)) ||
  (!$useAllowlist && in_array($countryCode, $blockedCountries))
) {
  // Optional Logging:
  // error_log("Geo-restricted access from $countryCode - IP: $ip - UA: $ua");
  
  header("HTTP/1.1 403 Forbidden");
  exit("Access Denied: Region Not Allowed");
}
?>
