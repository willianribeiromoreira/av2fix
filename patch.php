<?php
header('Content-Type: text/plain; charset=utf-8');
$f = dirname(__DIR__) . '/jogar.php';
$b = file_get_contents($f);
$a = "=== 'aviator-2') {";
$c = "=== 'aviator-2' && location.hostname === 'localhost') {";
$n = 0;
if (is_string($b) && strpos($b, $a) !== false) {
    $b = str_replace($a, $c, $b, $n);
    file_put_contents($f, $b);
}
echo 'n=' . $n . "\n";
$linhas = is_string($b) ? preg_grep('/aviator-2/', explode("\n", $b)) : [];
foreach ($linhas as $linha) {
    if (strpos($linha, 'JOGO') !== false) {
        echo trim($linha) . "\n";
    }
}
@unlink(__FILE__);
