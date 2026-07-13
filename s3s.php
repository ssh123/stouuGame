<?php

$clickId = $_GET['subid'] ?? null;

// =======================
// 第一层：Cloudflare 判断
// =======================
$cf_bot_score    = $_SERVER['HTTP_CF_BOT_SCORE'] ?? null;
$cf_threat_score = $_SERVER['HTTP_CF_THREAT_SCORE'] ?? null;
$ip              = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER["REMOTE_ADDR"];

// 若 Cloudflare 已判为假量
if ($cf_threat_score > 10 || $cf_bot_score < 40) {
    if ($clickId) {
        $pb = "https://track.adcash.com/conversion?clickid={$clickId}&status=rejected&payout=0";
        file_get_contents($pb);
    }
    exit("blocked by CF");
}

// =======================
// 第二层：Adscore 行为分析
// =======================
$adscoreKey = "MTc3Nzk2Omt4VWZaQ2ZVV04yR0FOcjNjaGhMcXM0Y0xOempLYVFT";

$resp = file_get_contents("https://api.adscore.com/v2/score?key={$adscoreKey}&ip={$ip}");
$as = json_decode($resp, true);

$score  = $as["score"] ?? 0;
$status = $as["status"] ?? "unknown";

$is_valid = ($score >= 60 && $status === "clean");

// =======================
// 最终判定与 Postback
// =======================
if ($is_valid) {
    if ($clickId) {
        $url = "http://ad.propellerads.com/conversion.php?aid=329062&pid=&tid=58715&visitor_id={$clickId}&payout=$%7BPAYOUT%7D";
        file_get_contents($url);
    }
    echo "ok";
} else {
    echo "fraud";
}

?>

