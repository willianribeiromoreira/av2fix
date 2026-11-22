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
$host = strtolower((string) parse_url($link, PHP_URL_HOST));
echo 'host=' . $host . "\n";

$buscar = static function (string $link, array $headers) {
    $ch = curl_init($link);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $b = curl_exec($ch);
    $e = curl_error($ch);
    $st = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $body = is_string($b) ? substr($b, $hs) : '';
    $texto = trim(preg_replace('/\s+/', ' ', strip_tags($body)));
    $texto = preg_replace('#https?://\S+#', '[url]', $texto);
    return [$st, $e, strlen($body), substr($texto, 0, 280)];
};

[$st, $e, $len, $texto] = $buscar($link, [
    'Accept: text/html',
    'Origin: https://supremapix.app',
    'Referer: https://supremapix.app/',
]);
echo "simples status=$st err=$e len=$len\n$texto\n";

[$st2, $e2, $len2, $texto2] = $buscar($link, [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Accept-Language: pt-BR,pt;q=0.9',
    'Origin: https://supremapix.app',
    'Referer: https://supremapix.app/',
    'Upgrade-Insecure-Requests: 1',
    'Sec-Fetch-Dest: iframe',
    'Sec-Fetch-Mode: navigate',
    'Sec-Fetch-Site: cross-site',
]);
echo "navegador status=$st2 err=$e2 len=$len2\n$texto2\n";
@unlink(__FILE__);
