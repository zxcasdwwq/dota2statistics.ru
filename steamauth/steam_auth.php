<?php

require __DIR__ . '/vendor/autoload.php';

session_start();

// Замените на свой URL
$redirectUri  = 'http://dota2statistics.ru/steamauth/steam_auth.php'; // Важно: URL должен совпадать с тем, который вы укажете в параметрах OpenID

// Steam OpenID URL
$steamOpenIdUrl = 'https://steamcommunity.com/openid/login';

// Если нет openid_mode, перенаправляем на Steam для авторизации
if (!isset($_GET['openid_mode'])) {

    // Генерируем параметры для запроса авторизации Steam
    $params = [
        'openid.ns'         => 'http://specs.openid.net/auth/2.0',
        'openid.mode'       => 'checkid_setup',
        'openid.return_to'  => $redirectUri,
        'openid.realm'      => (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'],
        'openid.identity'   => 'http://specs.openid.net/auth/2.0/identifier_select',
        'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select'
    ];

    // Создаем URL для перенаправления на Steam
    $authorizationUrl = $steamOpenIdUrl . '?' . http_build_query($params);

    // Перенаправляем пользователя на URL авторизации Steam
    header('Location: ' . $authorizationUrl);
    exit;

// Если Steam перенаправил обратно
} else {

    // ВАЖНО: Необходима проверка подписи OpenID (см. пример кода ниже)

    // Извлекаем SteamID из URL перенаправления
    $steamID64 = substr($_GET['openid_identity'], strlen("http://steamcommunity.com/openid/id/"));

    // Сохраняем SteamID в сессии
    $_SESSION['steamid'] = $steamID64;

    // Перенаправляем пользователя на главную страницу (или куда нужно)
    header('Location: index.php');  // Замените на свой URL
    exit;

}
/**
 * Verifies the signature returned by the OpenID provider.
 * @param  array $params The GET parameters returned by the provider.
 * @return bool Whether the signature is valid.
 */
function verifySignature(array $params)
{
    $signed = explode(',', $params['openid_signed']);
    $data = [];
    foreach ($signed as $item) {
        $data[$item] = $params['openid_' . str_replace('.', '_', $item)];
    }

    $data['openid.ns'] = 'http://specs.openid.net/auth/2.0';
    $data['openid.op_endpoint'] = $params['openid_op_endpoint'];
    $data['openid.return_to'] = $params['openid_return_to'];
    $data['openid.response_nonce'] = $params['openid_response_nonce'];
    $data['openid.assoc_handle'] = $params['openid_assoc_handle'];

    ksort($data);

    $signature = base64_decode($params['openid_sig']);

    $signData = '';
    foreach ($data as $key => $value) {
        $signData .= $key . ':' . $value . "\n";
    }

    // Steam Community Public Key - NEVER CHANGE THIS!
    $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
        "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDjzW6Ya+2mcrM1a9+uSjfrNrg8\n" .
        "Lrw/H/szi7k9l8zXjE52GfKsL65h7MCuJGT9M3Kuq2f7c+8h7Xp924o3j9j9\n" .
        "V9O8s0T6u6L1z9Ssi3XyXmEje+X6zYv4LpXwZ+Hj12nVQbQkGvC75mKR2f40\n" .
        "0Nl1oE4YxN+O65L3qTjG4XwIDAQAB\n" .
        "-----END PUBLIC KEY-----";

    $sslCheck = openssl_verify($signData, $signature, $publicKey, OPENSSL_ALGO_SHA1);
    if ($sslCheck === 1) {
        return true;
    }
    return false;

}
