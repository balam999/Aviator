<?php
define('BOT_TOKEN', '8926202846:AAHNDwk5WVucCyUi0D3K3fjumwKFiX1lbRo');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('IMGBB_API_KEY', 'D9a99c755d68f9072ad1f5ff8e630e4e');

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

$chat_id = $update["message"]["chat"]["id"] ?? null;
$text = $update["message"]["text"] ?? "";
$photos = $update["message"]["photo"] ?? null;

if (!$chat_id) {
    exit;
}

if ($text === "/start" || $text === "📸 ছবি আপলোড করুন") {
    $keyboard = [
        "keyboard" => [
            [
                ["text" => "📸 ছবি আপলোড করুন"]
            ]
        ],
        "resize_keyboard" => true,
        "is_persistent" => true
    ];

    $postData = [
        'chat_id' => $chat_id,
        'text' => "স্বাগতম! নিচের '📸 ছবি আপলোড করুন' বাটনে ক্লিক করুন অথবা সরাসরি গ্যালারি থেকে একটি ছবি সেন্ড করুন:",
        'reply_markup' => json_encode($keyboard)
    ];

    $ch = curl_init(API_URL . "sendMessage");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}
elseif (!empty($photos)) {
    file_get_contents(API_URL . "sendMessage?chat_id=" . $chat_id . "&text=" . urlencode("⏳ ছবি আপলোড হচ্ছে, দয়া করে অপেক্ষা করুন..."));

    $file_id = end($photos)['file_id'];
    $file_info_json = file_get_contents(API_URL . "getFile?file_id=" . $file_id);
    $file_info = json_decode($file_info_json, true);

    if (isset($file_info['result']['file_path'])) {
        $file_path = $file_info['result']['file_path'];
        $download_url = "https://api.telegram.org/file/bot" . BOT_TOKEN . "/" . $file_path;

        $image_data = file_get_contents($download_url);

        if ($image_data) {
            $imgbb_url = "https://api.imgbb.com/1/upload";
            $post_fields = [
                'key' => IMGBB_API_KEY,
                'image' => base64_encode($image_data)
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $imgbb_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);

            if (isset($result['success']) && $result['success'] == true) {
                $image_url = $result['data']['url'];
                $reply_text = "✅ আপনার ছবি সফলভাবে আপলোড হয়েছে!\n\n🔗 ছবির লিংক:\n" . $image_url;
                file_get_contents(API_URL . "sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($reply_text));
            } else {
                file_get_contents(API_URL . "sendMessage?chat_id=" . $chat_id . "&text=" . urlencode("❌ ImgBB আপলোড ব্যর্থ হয়েছে।"));
            }
        } else {
            file_get_contents(API_URL . "sendMessage?chat_id=" . $chat_id . "&text=" . urlencode("❌ টেলিগ্রাম থেকে ছবি ডাউনলোড করা যায়নি।"));
        }
    }
}
?>
