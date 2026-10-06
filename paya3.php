<?php
error_reporting(0);
date_default_timezone_set('Asia/Jakarta');

// Captura a lista enviada pelo painel
$lista = $_GET['lista'] ?? null;
$cards = [];

if (!empty($lista)) {
    $lista = trim($lista);
    $lista = str_replace([" ", ":", ";", ",", "=>", "-", "/", "|||", "\r", "\n"], "|", $lista);
    $lista = preg_replace('/\|+/', '|', $lista);
    
    $tmp = explode("|", $lista);
    for ($i = 0; $i < count($tmp); $i += 4) {
        if (isset($tmp[$i + 3])) {
            $cc = trim($tmp[$i]);
            $mes = trim($tmp[$i+1]);
            $ano = trim($tmp[$i+2]);
            $cvv = trim($tmp[$i+3]);
            if (strlen($cc) >= 15 && strlen($cvv) >= 3) {
                $cards[] = "$cc|$mes|$ano|$cvv";
            }
        }
    }
    
    if (!empty($cards)) {
        file_put_contents('db.txt', implode("\n", $cards) . "\n");
    }
}

if (empty($cards) && file_exists('db.txt')) {
    $cards = file('db.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

if (empty($cards)) {
    die();
}

$current_card = array_shift($cards);
$separa = explode("|", $current_card);
$cc = $separa[0];
$mes = $separa[1];
$ano = $separa[2];
$cvv = $separa[3];

if (!empty($cards)) {
    file_put_contents('db.txt', implode("\n", $cards) . "\n");
} else {
    if (file_exists('db.txt')) unlink('db.txt');
}

// Funções de Apoio para BIN
function GetStr($string, $start, $end) {
    $str = explode($start, $string);
    if (!isset($str[1])) return '';
    $str = explode($end, $str[1]);
    return $str[0];
}

$bin_info = "N/A";
$bin_number = substr($cc, 0, 6);

if (file_exists('bins.json')) {
    $json_str = file_get_contents('bins.json');
    $bins = json_decode($json_str, true);
    if (isset($bins[$bin_number])) {
        $a = json_encode($bins[$bin_number]);
        $bandeira = GetStr($a, 'bandeira":"', '"');
        $nivel    = GetStr($a, 'level":"', '"');
        $bank     = GetStr($a, 'banco":"', '"');
        $pais     = GetStr($a, 'pais":"', '"');
        $bin_info = trim("$bandeira $nivel $bank $pais");
    }
} 
if ($bin_info == "N/A" && file_exists('bins.csv')) {
    $contents = file_get_contents('bins.csv');
    $pattern = preg_quote($bin_number, '/');
    $pattern = "/^.*$pattern.*\$/m";
    if (preg_match_all($pattern, $contents, $matches)) {
        $encontrada = implode("\n", $matches[0]);
        $pieces = explode(";", $encontrada);
        $bin_info = trim("$pieces[1] $pieces[2] $pieces[3] $pieces[4] $pieces[5]");
    }
}

// --- SIMULAÇÃO ALEATÓRIA DE RESULTADOS ---
$sorteio = rand(1, 100);
$is_aprovada = ($sorteio <= 35); // 35% de chance de aprovação simulada

if ($is_aprovada) {
    $es = "Aproved";
    $msg_output = "CHARGED 1$ SUCCESSFULLY 🟢"; 
    $code = "CHARGED 1$ SUCCESSFULLY 🟢";
    echo '<span class="text-success">Aproved</span> ✅<br>'.$current_card.'<br>['.$bin_info.']<br>'.$msg_output.'<br>@wzzin_santos';
} else {
    $es = "Reproved";
    $erros_possiveis = ["Transaction declined [ INSUFFICIENT_FUNDS ]", "Transaction declined [ ISSUER_DECLINE ]", "Transaction declined [ INVALID_SECURITY_CODE ]"];
    $msg_output = $erros_possiveis[array_rand($erros_possiveis)];
    $code = $msg_output;
    echo '<span class="text-danger">Reprovada</span><br>'.$current_card.'<br>['.$bin_info.']<br>'.$msg_output.'<br>@wzzin_santos';
}

ob_flush();
sleep(2); // Pequeno delay simulado
?>
