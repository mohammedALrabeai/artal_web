<?php

use Dotenv\Dotenv;
use Google\Client;

require __DIR__.'/vendor/autoload.php';

// تحميل ملف .env إذا لم يكن المشروع Laravel
if (! function_exists('env')) {
    if (file_exists(__DIR__.'/.env')) {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    }

    function env($key, $default = null)
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}

$client = new Client;
$client->setClientId(env('GOOGLE_DRIVE_CLIENT_ID'));
$client->setClientSecret(env('GOOGLE_DRIVE_CLIENT_SECRET'));
$client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
$client->setAccessType('offline');
$client->setPrompt('consent');
$client->addScope([
    'https://www.googleapis.com/auth/drive.file',
]);

$authUrl = $client->createAuthUrl();

echo "افتح هذا الرابط في المتصفح:\n$authUrl\n";
echo "ثم أدخل رمز التأكيد:\n";

$authCode = trim(fgets(STDIN));

$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

echo "Refresh Token:\n".($accessToken['refresh_token'] ?? 'لا يوجد')."\n\n";
echo "الوصول الكامل:\n";
print_r($accessToken);
