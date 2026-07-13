<?php
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 0);

/* ===============================================
   Cloudflare + Turnstile + Adscore 三层防刷模板
   可直接部署 (index.php)
   =============================================== */

// ----------------------
// 配置区域
// ----------------------
$AD_SCORE_KEY   = "MTc3ODg2OmFtQkpRU3F2V1U5VGh1WVFyVEJMdXZVczZYN3ROVlpO";
$TURNSTILE_KEY  = "0x4AAAAAACB97kkQOTQD4VzrzHj4m5M-IbI";
$ADCASH_POSTBACK = "http://ad.propellerads.com/conversion.php?aid=329062&pid=&tid=58715&visitor_id=";
$PAYOUT_APPROVED = 0.2;   // 可调整
$PAYOUT_REJECTED = 0.0;

// ----------------------
// Cloudflare 信息读取
// ----------------------
$cf_bot_score    = $_SERVER['HTTP_CF_BOT_SCORE'] ?? null;
$cf_bot_score = is_string($cf_bot_score) ? intval($cf_bot_score) : null;

$cf_threat_score = intval($_SERVER['HTTP_CF_THREAT_SCORE'] ?? 0);
$real_ip         = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
$clickId         = $_GET['subid'] ?? null;

// ----------------------
// 第一层：Cloudflare 过滤
// ----------------------
if ($cf_threat_score > 10 || ($cf_bot_score && $cf_bot_score < 40)) {
    if ($clickId)
        file_get_contents("$ADCASH_POSTBACK?clickid={$clickId}&status=rejected&payout={$PAYOUT_REJECTED}");

    http_response_code(403);
    exit("<h3>Access Blocked (CF Layer)</h3>");
}

// ----------------------
// 第二层：Turnstile 验证
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['cf-turnstile-response'] ?? null;

    if (!$token) die("Turnstile token missing");

    $ch = curl_init("https://challenges.cloudflare.com/turnstile/v0/siteverify");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'secret'  => $TURNSTILE_KEY,
        'response'=> $token,
        'remoteip'=> $real_ip
    ]);
    $ret = curl_exec($ch);
    curl_close($ch);

    $ts = json_decode($ret, true);

    if (empty($ts['success'])) {
        die("<h3>Human Verification Failed</h3>");
    }
    $pass = true; 
    if ($pass) {
        if ($clickId)
        file_get_contents("$ADCASH_POSTBACK{$clickId}&status=approved&payout={$PAYOUT_APPROVED}");
	header("Location: https://stouu.com/index.html");
        exit;
    } else {
        if ($clickId)
            file_get_contents("$ADCASH_POSTBACK?{$clickId}&status=rejected&payout={$PAYOUT_REJECTED}");
        echo "<h3> Fraudulent Traffic</h3>";
        exit;
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Secure Landing</title>
<script async src="https://api.adscore.com/adscore.js"></script>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    <style>
        /* 页面全屏居中 */
        body {
	    margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;

            background-image: url("https://stouu.com/bg.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;

            font-family: Arial, sans-serif;
        }

        /* 内容容器 */
        .box {
            padding: 30px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.85);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            text-align: center;
            z-index: 2;
        }

        /* 透明 delay 层 */
        .delay-layer {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.35); /* 半透明黑色 */
            display: none; /* 默认隐藏 */
            justify-content: center;
            align-items: center;
            z-index: 999;
            color: #fff;
            font-size: 22px;
            backdrop-filter: blur(2px); /* 毛玻璃效果，可选 */
        }
    </style>
</head>
<body>
<script src="//c.adsco.re" type="text/javascript"></script>
<script type="text/javascript">
AdscoreInit("QqhHBQAAAAAAExD_FZSEvMLDsCVLeUq7_xIWK4E", {
callback: function(result) {
 console.log(result.signature);
 document.getElementById("adscore_token").value = result.signature;
},
onerror: function(){
 alert("Disable Adblock!");
}
});
</script>

<div class="delay-layer" id="delayLayer">
</div>

<div class="box">
    <h2>Human Verification</h2>
    <form method="post" action="" onsubmit="return showDelay();">
	<input type="hidden" id="adscore_token" name="adscore_token">
        <div class="cf-turnstile" data-sitekey="0x4AAAAAACB97uKzzVaTd0eF"></div>
        <br>
        <button type="submit">Continue</button>
    </form>
</div>

<script>

function showDelay() {
    document.getElementById('delayLayer').style.display = 'flex';
}
</script>

</body>
</html>

