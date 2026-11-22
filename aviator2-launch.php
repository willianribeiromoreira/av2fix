<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/suprema_api.php';

function av2_erro(int $status, string $mensagem): void
{
    http_response_code($status >= 400 ? $status : 502);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Frame-Options: SAMEORIGIN');
    $texto = htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><title>Aviator 2</title></head>'
        . '<body data-erro="1" style="margin:0;background:#000;color:#fff;font:16px/1.4 Poppins,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:24px"><p>'
        . $texto . '</p></body></html>';
    exit;
}

/**
 * @return array{status:int, erro:string, corpo:string}
 */
function av2_alea(string $link, bool $ssl, bool $v4): array
{
    $ch = curl_init($link);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml',
            'Accept-Language: pt-BR,pt;q=0.9',
            'Origin: https://supremapix.app',
            'Referer: https://supremapix.app/',
        ],
        CURLOPT_SSL_VERIFYPEER => $ssl,
        CURLOPT_SSL_VERIFYHOST => $ssl ? 2 : 0,
    ];
    if ($v4) {
        $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
    }
    $ca = spx_ca();
    if ($ssl && $ca) {
        $opts[CURLOPT_CAINFO] = $ca;
    }
    curl_setopt_array($ch, $opts);
    $bruto = curl_exec($ch);
    $erro = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $tamanho = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    return [
        'status' => $status,
        'erro' => $erro,
        'corpo' => is_string($bruto) ? substr($bruto, $tamanho) : '',
    ];
}

function av2_util(array $resposta): bool
{
    $corpo = $resposta['corpo'];
    if ($resposta['status'] !== 200 || $corpo === '' || stripos($corpo, 'is invalid') !== false) {
        return false;
    }
    return stripos($corpo, 'gameMetadata') !== false || stripos($corpo, '<title>Aviator</title>') !== false;
}

$token = trim((string) ($_GET['token'] ?? ''));
$mobile = (($_GET['device'] ?? '') === 'mobile');
if (!spx_token_valido($token)) {
    av2_erro(401, 'Sua sessão na casa expirou. Volte e entre de novo.');
}

$aberto = spx_abrir_jogo($token, 'aviator-2', $mobile);
$link = $aberto['link'];
$host = strtolower((string) parse_url($link, PHP_URL_HOST));
if (!$aberto['ok'] || $link === '' || $host !== 'play.aleaplay.com') {
    $mensagem = $aberto['message'] !== '' ? $aberto['message'] : 'Não foi possível abrir o jogo.';
    av2_erro($aberto['http_code'] >= 400 ? $aberto['http_code'] : 502, $mensagem);
}

$resposta = av2_alea($link, true, true);
if (!av2_util($resposta)) {
    $resposta = av2_alea($link, false, true);
}
if (!av2_util($resposta)) {
    $resposta = av2_alea($link, false, false);
}
if (!av2_util($resposta)) {
    av2_erro(502, 'Não foi possível abrir o jogo.');
}

$corpo = $resposta['corpo'];
if (stripos($corpo, '<base ') === false) {
    $base = '<base href="https://play.aleaplay.com/">';
    $trocado = preg_replace('/<head\b[^>]*>/i', '$0' . $base, $corpo, 1);
    $corpo = is_string($trocado) ? $trocado : ($base . $corpo);
}

http_response_code(200);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: SAMEORIGIN');
echo $corpo;
