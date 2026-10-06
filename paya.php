<?php
// ============================================================
// TRAVA DE SEGURANÇA (WOLF_SECURITY)
// ============================================================
session_start();

if (!isset($_SESSION['user_key'])) {
    http_response_code(403);
    die("Reprovada - ACESSO NÃO AUTORIZADO");
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Sao_Paulo');

// Funções de Apoio
function multiexplode($string) {
    $delimiters = ["|", ";", ":", "/", "»", "«", ">", "<", " "];
    $one = str_replace($delimiters, $delimiters[0], $string);
    return explode($delimiters[0], $one);
}

// Captura da Lista
$lista = $_GET['lista'] ?? '';
if (empty($lista)) exit("AGUARDANDO_CARGA...");

$dados = multiexplode($lista);
$cc = trim($dados[0] ?? '');
$mes = trim($dados[1] ?? '');
$ano = trim($dados[2] ?? '');
$cvv = trim($dados[3] ?? '');

// Simulação de informações de BIN aleatórias/genéricas baseadas no cartão
$bancos = ["NU PAGAMENTOS S.A.", "MERCADO PAGO", "BANCO BRADESCO S.A.", "BANCO ITAU S.A.", "CAIXA ECONOMICA FEDERAL"];
$paises = ["BRA", "USA", "CAN"];
$niveis = ["GOLD", "PLATINUM", "STANDARD", "BLACK"];
$bandeiras = ["VISA", "MASTERCARD"];

$bandeira = (strpos($cc, '4') === 0) ? "VISA" : "MASTERCARD";
$banco_aleatorio = $bancos[array_rand($bancos)];
$pais_aleatorio = $paises[array_rand($paises)];
$nivel_aleatorio = $niveis[array_rand($niveis)];

$bin_info = strtoupper("$bandeira $nivel_aleatorio $banco_aleatorio $pais_aleatorio CREDIT");

// Simulação de resultado aleatório (Ex: 30% de chance de aprovar, 70% reprovar)
$sorteio = rand(1, 100);
$is_aprovada = ($sorteio <= 30); // 30% aprovadas

if ($is_aprovada) {
    $status_code = "Y";
    echo "Aprovada ✅ " . $cc . "|" . $mes . "|" . $ano . "|" . $cvv . " ➔ [BIN: " . $bin_info . "] ➔ STATUS: [" . $status_code . "] - APROVADO COM SUCESSO ➔ @wzzin_center<br>";
} else {
    $msgs_erro = [
        "Transacao Recusada pelo emissor",
        "Saldo Insuficiente",
        "CVV Invalido",
        "Cartao Vencido",
        "Bloqueio de Seguranca"
    ];
    $msg_erro = $msgs_erro[array_rand($msgs_erro)];
    echo "Reprovada ❌ " . $cc . "|" . $mes . "|" . $ano . "|" . $cvv . " ➔ [BIN: " . $bin_info . "] ➔ [" . $msg_erro . "] ➔ @wzzin_center<br>";
}

// Pequeno delay simulado para dar o efeito visual na tela
usleep(300000); // 0.3 segundos
?>
