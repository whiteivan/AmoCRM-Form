<?php

define('TOKEN_FILE', __DIR__ . '\vault\token_info.json');

use AmoCRM\OAuth2\Client\Provider\AmoCRM;

include_once __DIR__ . '\..\vendor\autoload.php';
include_once __DIR__ . '\vault\vault.php';

const LOG_FILE = __DIR__ . '/log.txt';

function logMessage($message)
{
    file_put_contents(LOG_FILE, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

session_start();

function handleAmoCRMAuth($authorizationCode)
{
    $provider = new AmoCRM([
        'clientId' => INTEGRATION_ID,
        'clientSecret' => SECRET,
        'redirectUri' => REDIRECT_URI,
    ]);
    $provider->setBaseDomain('beloivanenkodanil.amocrm.ru');
    try {
        if (file_exists(TOKEN_FILE)) {
            $access_token = getToken();

            if ($access_token->hasExpired()) {
                $access_token = $provider->getAccessToken(new League\OAuth2\Client\Grant\RefreshToken(), [
                    'refresh_token' => $access_token->getRefreshToken(),
                ]);
                saveToken($access_token);
            }
        } else {
            try {
                $access_token = $provider->getAccessToken(new League\OAuth2\Client\Grant\AuthorizationCode(), [
                    'code' => $authorizationCode,
                ]);
            }catch (Exception $e) {
                var_dump($e->getMessage());
                die('Ошибка получения токена.');
            }

            saveToken($access_token);
        }
    } catch (Exception $e) {
        return 'Ошибка: ' . $e->getMessage();
    }
}

function saveToken($access_token)
{
    $data = [
        'accessToken' => $access_token->getToken(),
        'refreshToken' => $access_token->getRefreshToken(),
        'expires' => $access_token->getExpires(),
        'baseDomain' => $access_token->getValues()['baseDomain'],
    ];
    if (!is_dir(dirname(TOKEN_FILE))) {
        mkdir(dirname(TOKEN_FILE), 0755, true);
    }
    file_put_contents(TOKEN_FILE, json_encode($data));
}

function getToken()
{
    $token_path = __DIR__ . '/vault/token_info.json';
    logMessage('Попытка загрузить токен из файла: ' . $token_path);

    if (!file_exists($token_path)) {
        logMessage('Ошибка: файл токена не найден!');
        die('Файл с токеном не найден!');
    }

    $token_data = json_decode(file_get_contents($token_path), true);
    logMessage('Токен успешно загружен');
    return new \League\OAuth2\Client\Token\AccessToken($token_data);
}