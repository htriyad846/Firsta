<?php

function getDatabase() {
    static $pdo;

    if ($pdo) return $pdo;

    $host = DB_HOST;
    $user = DB_USER;
    $pass = DB_PASS;
    $name = DB_NAME;

    if (!$host || !$user || !$pass || !$name) {
        error_log("[!] Database credentials missing in config.");
        return null;
    }

    try {
        $dsn = "pgsql:host=$host;dbname=$name";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3,
        ]);
        return $pdo;
    } catch (Exception $e) {
        error_log("[!] PostgreSQL error: " . $e->getMessage());
        return null;
    }
}

function saveToDatabase($data) {
    $db = getDatabase();
    if (!$db) return false;

    $sql = "INSERT INTO logs (
        session_id, ip, location, lat, lon, accuracy,
        battery, charging, theme, device, browser,
        referrer, user_agent, created_at
    ) VALUES (
        :session_id, :ip, :location, :lat, :lon, :accuracy,
        :battery, :charging, :theme, :device, :browser,
        :referrer, :user_agent, NOW()
    )";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':session_id' => $data['session_id'],
            ':ip'         => $data['ip'],
            ':location'   => $data['location'],
            ':lat'        => $data['lat'],
            ':lon'        => $data['lon'],
            ':accuracy'   => $data['accuracy'],
            ':battery'    => $data['battery'],
            ':charging'   => $data['charging'],
            ':theme'      => $data['theme'],
            ':device'     => $data['device'],
            ':browser'    => $data['browser'],
            ':referrer'   => $data['referrer'],
            ':user_agent' => $data['user_agent'],
        ]);
        return true;
    } catch (Exception $e) {
        error_log("[!] DB insert error: " . $e->getMessage());
        return false;
    }
}