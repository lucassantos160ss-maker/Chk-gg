<?php
error_reporting(0);
header('Content-Type: text/html; charset=UTF-8');

$lista = $_GET['lista'] ?? $_POST['lista'] ?? '';
if (empty($lista)) {
    echo "AGUARDANDO_CARGA...";
    exit;
}

$parts = explode("|", trim($lista));
$cc = $parts[0] ?? '4066699999999999';
$mes = $parts[1] ?? '01';
$ano = $parts[2] ?? '2034';
$cvv = $parts[3] ?? '123';

$cartao_formatado = "$cc|$mes|$ano|$cvv";
$bin_info = "VISA PLATINUM BANCO DO BRASIL BRA CREDIT";

// Sorteio: 40% de chance de aprovar (Live falsa) e 60% de reprovar
$sorteio = rand(1, 100);

if ($sorteio <= 40) {
    echo '<span class="text-success">Aproved</span> ✅ ➔ ' . $cartao_formatado . ' ➔ [' . $bin_info . '] ➔ CHARGED 1$ SUCCESSFULLY 🟢 ➔ @wzzin_center';
} else {
    echo '<span class="text-danger">Reprovada</span> ❌ ➔ ' . $cartao_formatado . ' ➔ [' . $bin_info . '] ➔ Transaction declined [ INSUFFICIENT_FUNDS ] ➔ @wzzin_center';
}
?>
