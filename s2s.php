<?php

$clickId = $_GET['subid'] ?? null;

// 1) 调用 Adscore API
$adscoreApiKey = 'MTc3Nzk2Omt4VWZaQ2ZVV04yR0FOcjNjaGhMcXM0Y0xOempLYVFT';
$ip = $_SERVER['REMOTE_ADDR'];

$resp = file_get_contents("https://api.adscore.com/v2/score?key={$adscoreApiKey}&ip={$ip}");
$data = json_decode($resp, true);

$score = $data['score'] ?? 0;
$status = $data['status'] ?? 'unknown';

// 2) 判断是否是有效流量
$is_valid = ($score > 60 && $status === 'clean');

// 3) 若有效，回传给 AdCash
if ($is_valid && $clickId) {
    $postback = "https://track.adcash.com/conversion?clickid={$clickId}&status=approved&payout=0.2";
    file_get_contents($postback);
} else {
    // 过滤假量 可回传 rejected 或不回传
    if ($clickId) {
        $postback = "https://track.adcash.com/conversion?clickid={$clickId}&status=rejected&payout=0";
        file_get_contents($postback);
    }
}

?>

