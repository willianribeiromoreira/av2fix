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
    echo 'msg=' . preg_replace('/\s+/', ' ', (string) $aberto['message']) . "\n";
    @unlink(__FILE__);
    exit;
}
$link = $aberto['link'];
echo 'host=' . strtolower((string) parse_url($link, PHP_URL_HOST)) . "\n";
$ch = curl_init($link);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => false,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_TIMEOUT => 25,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    CURLOPT_HTTPHEADER => [
        'Accept: text/html',
        'Origin: https://supremapix.app',
        'Referer: https://supremapix.app/',
    ],
]);
$body = curl_exec($ch);
$e = curl_error($ch);
$st = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);
$body = is_string($body) ? $body : '';
$limpo = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $body);
$texto = trim(preg_replace('/\s+/', ' ', strip_tags((string) $limpo)));
$texto = preg_replace('#https?://\S+#', '[url]', (string) $texto);
echo "status=$st err=$e len=" . strlen($body) . "\n";
foreach (['invalid', 'domain', 'referer', 'referrer', 'blocked', 'session', 'contact', 'manager'] as $p) {
    $pos = stripos($texto, $p);
    echo $p . '=' . ($pos === false ? '0' : '1') . "\n";
    if ($pos !== false) {
        echo 'trecho=' . substr($texto, max(0, $pos - 80), 220) . "\n";
    }
}
echo "fim=" . substr($texto, -300) . "\n";
@unlink(__FILE__);
