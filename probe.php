<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
require __DIR__ . '/../includes/suprema_api.php';
$jars = glob(spx_dir() . '/spx_*.txt') ?: [];
if (!$jars) {
    echo "sessoes=0\n";
    @unlink(__FILE__);
    exit;
}
usort($jars, static function ($a, $b) {
    return filemtime($b) <=> filemtime($a);
});
$token = basename($jars[0], '.txt');
$aberto = spx_abrir_jogo($token, 'aviator-2', false);
echo 'abrir=' . ($aberto['ok'] ? '1' : '0') . ' http=' . $aberto['http_code'] . "\n";
if (!$aberto['ok']) {
    echo 'msg=' . preg_replace('/\s+/', ' ', $aberto['message']) . "\n";
    @unlink(__FILE__);
    exit;
}
$link = $aberto['link'];
$host = strtolower((string) parse_url($link, PHP_URL_HOST));
echo 'host=' . $host . "\n";
$ch = curl_init($link);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_TIMEOUT => 25,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
    CURLOPT_HTTPHEADER => [
        'Accept: text/html',
        'Origin: https://supremapix.app',
        'Referer: https://supremapix.app/',
    ],
]);
$b = curl_exec($ch);
$e = curl_error($ch);
$st = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);
$body = is_string($b) ? substr($b, $hs) : '';
$invalid = stripos($body, 'is invalid') !== false ? 1 : 0;
$meta = stripos($body, 'gameMetadata') !== false ? 1 : 0;
$title = 'none';
if (preg_match('/<title>([^<]+)/i', $body, $m)) {
    $title = trim($m[1]);
}
echo 'alea=' . $st . ' err=' . $e . ' len=' . strlen($body) . ' invalid=' . $invalid . ' meta=' . $meta . ' title=' . $title . "\n";
@unlink(__FILE__);
