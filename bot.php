<?php
require_once "config.php";
/*
 * ZAKAZ BOTI — MAXSUS LITSENZIYA
 * Muallif: Mister Murod Primov xmpa (c) 2026
 * Muallif nomini o‘chirish yoki o‘zgartirish QAT'IYAN TAQIQLANADI
 * To‘liq shartlar: LICENSE faylini ko‘ring
 */
$update = json_decode(file_get_contents("php://input"), true);
if (!$update) exit;

// ====================
// MESSAGE
// ====================
if (isset($update['message'])) {
    $msg = $update['message'];
    $chat_id    = $msg['chat']['id'];
    $user_id    = $msg['from']['id'] ?? $chat_id;
    $text       = trim($msg['text'] ?? '');

    $first_name = $msg['from']['first_name'] ?? '';
    $last_name  = $msg['from']['last_name']  ?? '';
    $username   = $msg['from']['username']   ?? '';

    $users = readJson(USERS_FILE);
    if (!in_array($user_id, $users)) {
        $users[] = $user_id;
        writeJson(USERS_FILE, $users);
    }

    if ($text === "/start") {
        $welcome = "Assalomu alaykum, $first_name 👋\n\n"
                 . "📦 Zakaz berish uchun tugmani bosing\n"
                 . "yoki shunchaki mahsulot nomini, SAP kodini yoki qisqa kodini yozib izlang";
        tg("sendMessage", [
            "chat_id" => $chat_id,
            "text" => $welcome,
            "reply_markup" => json_encode([
                "keyboard" => [
                    [["text" => "📦 Zakaz berish"]]
                ],
                "resize_keyboard" => true
            ])
        ]);
        exit;
    }

    if ($text === "📦 Zakaz berish") {
        $products = readJson(PRODUCTS_FILE);

        if (count($products) > 60) {
            tg("sendMessage", [
                "chat_id" => $chat_id,
                "text" => "Mahsulotlar soni ko‘p (".count($products)."+)\n\n"
                        . "Izlash uchun shunchaki shu chatda mahsulot nomini, SAP yoki qisqa kodini yozing\n"
                        . "masalan: qosh, yoni, stol, SPRGP",
                "reply_markup" => json_encode([
                    "inline_keyboard" => [
                        [["text" => "🔍 Mahsulot izlash", "switch_inline_query_current_chat" => ""]]
                    ]
                ])
            ]);
            exit;
        }

        $buttons = [];
        foreach ($products as $p) {
            $buttons[] = [[
                "text" => $p['name'],
                "callback_data" => "product_" . $p['id']
            ]];
        }

        tg("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "Mahsulotni tanlang:",
            "reply_markup" => json_encode(["inline_keyboard" => $buttons])
        ]);
        exit;
    }

    $tmp_file = __DIR__ . "/tmp_user_$user_id.json";
    if (file_exists($tmp_file)) {
        $data = json_decode(file_get_contents($tmp_file), true);
        $step = $data['step'] ?? 'quantity';

        if ($step === 'quantity') {
            if (!is_numeric($text)) {
                tg("sendMessage", [
                    "chat_id" => $chat_id,
                    "text" => "❌ Iltimos faqat raqam kiriting (masalan: 3, 12, 50)"
                ]);
                exit;
            }

            $qty = (int)$text;
            $data['quantity'] = $qty;
            $data['step'] = 'color';
            file_put_contents($tmp_file, json_encode($data, JSON_UNESCAPED_UNICODE));

            tg("sendMessage", [
                "chat_id" => $chat_id,
                "text" => "Miqdor: $qty ta\n\n"
                        . "Rangini kiriting (masalan: BEJ, BEL, KRA, SER, KOR, CHR)\n"
                        . "Agar rang kerak bo‘lmasa yoki bo‘sh qoldirmoqchi bo‘lsangiz — shunchaki Enter bosing."
            ]);
            exit;
        }

        if ($step === 'color') {
            $color = trim($text);
            $product = $data['product'];
            $qty     = $data['quantity'] ?? 0;

            @unlink($tmp_file);

            $date = date("d.m.Y");
            $time = date("H:i");

            $user_info = trim("$first_name $last_name");
            if ($username) $user_info .= "\n@$username";

            $order_text = "📦 <b>YANGI ZAKAZ</b>  ⏰ $time\n\n"
                        . "👤 <b>Buyurtmachi:</b>\n$user_info\n\n"
                        . "🧾 <b>Mahsulot:</b> {$product['name']}\n";

            if (!empty($product['ai'])) {
                $order_text .= "🔤 <b>Qisqa nom:</b> {$product['ai']}\n";
            }

            $order_text .= "🏷 <b>SAP kod:</b> <code>" . ($product['sap'] ?? '—') . "</code>\n\n"
                        . "🔢 <b>Miqdor:</b> $qty ta\n";

            if ($color !== '') {
                $order_text .= "🎨 <b>Rang:</b> $color\n";
            }

            $order_text .= "\n📅 <b>Sana:</b> $date";

            $admins = readJson(ADMINS_FILE);
            foreach ($admins as $admin_id) {
                tg("sendMessage", [
                    "chat_id" => $admin_id,
                    "text" => $order_text,
                    "parse_mode" => "HTML"
                ]);
            }

            tg("sendMessage", [
                "chat_id" => GROUP_ID,
                "text" => $order_text,
                "parse_mode" => "HTML"
            ]);
            
            $confirm = "✅ Zakazingiz qabul qilindi!\n";
            if ($color) $confirm .= "🎨 Rang: $color\n";
            $confirm .= "Guruhdan sizga kod yuboriladi.";

            tg("sendMessage", [
                "chat_id" => $chat_id,
                "text" => $confirm
            ]);

            exit;
        }
    }
}

// ====================
// CALLBACK QUERY
// ====================
if (isset($update['callback_query'])) {
    $cb = $update['callback_query'];
    $chat_id = $cb['message']['chat']['id'];
    $user_id = $cb['from']['id'];
    $data    = $cb['data'];

    if (strpos($data, "product_") === 0) {
        $id = (int)str_replace("product_", "", $data);
        $products = readJson(PRODUCTS_FILE);

        foreach ($products as $p) {
            if ($p['id'] == $id) {
                $tmp_file = __DIR__ . "/tmp_user_$user_id.json";
                file_put_contents($tmp_file, json_encode([
                    "product" => $p,
                    "step"    => "quantity"
                ], JSON_UNESCAPED_UNICODE));

                tg("sendMessage", [
                    "chat_id" => $chat_id,
                    "text" => "Miqdorini kiriting (faqat raqam):"
                ]);
                break;
            }
        }
    }

    tg("answerCallbackQuery", ["callback_query_id" => $cb['id']]);
}

// ====================
// INLINE QUERY
// ====================
if (isset($update['inline_query'])) {
    $iq = $update['inline_query'];
    $qid   = $iq['id'];
    $from  = $iq['from']['id'];
    $query = trim($iq['query']);

    $results = [];

    if (strlen($query) < 2) {
        tg("answerInlineQuery", [
            "inline_query_id" => $qid,
            "results" => json_encode($results),
            "cache_time" => 300
        ]);
        exit;
    }

    $products = readJson(PRODUCTS_FILE);
    $found = 0;
    $max = 20;

    foreach ($products as $p) {
        $n  = mb_strtolower($p['name'] ?? '');
        $s  = mb_strtolower($p['sap']  ?? '');
        $a  = mb_strtolower($p['ai']   ?? '');
        $q  = mb_strtolower($query);

        if (
            strpos($n, $q) !== false ||
            strpos($s, $q) !== false ||
            strpos($a, $q) !== false
        ) {
            $title = $p['name'];
            if (!empty($p['sap'])) $title .= " • " . $p['sap'];
            if (!empty($p['ai'])) $title .= " (" . $p['ai'] . ")";

            $results[] = [
                "type" => "article",
                "id" => (string)$p['id'],
                "title" => $title,
                "description" => ($p['ai'] ?? '—') . " • Miqdor va rang kiritish uchun bosing",
                "input_message_content" => [
                    "message_text" => "<b>Tanlandi:</b> " . htmlspecialchars($p['name']) . "\n"
                                    . "SAP: <code>" . ($p['sap'] ?? '—') . "</code>\n"
                                    . "Qisqa kod: " . ($p['ai'] ?? '—') . "\n\n"
                                    . "Miqdorini yozing:",
                    "parse_mode" => "HTML"
                ],
                "reply_markup" => [
                    "inline_keyboard" => [[
                        ["text" => "✅ Tanlash", "callback_data" => "product_" . $p['id']]
                    ]]
                ]
            ];

            $found++;
            if ($found >= $max) break;
        }
    }

    tg("answerInlineQuery", [
        "inline_query_id" => $qid,
        "results" => json_encode($results, JSON_UNESCAPED_UNICODE),
        "cache_time" => 1800,
        "is_personal" => true
    ]);
}
