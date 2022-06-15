<?php

namespace App;

use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class YandexConnector extends Model
{
  public static function getClientId() {
    return env('YANDEX_CLIENT_ID');
  }

  private static function getClientPassword() {
    return env('YANDEX_CLIENT_PASSWORD');
  }

  public static function clientHasToken($clientId) {
    return Cache::has('YToken:'.$clientId.':'.env('YANDEX_CLIENT_ID'));
  }

  public static function clientSetToken($clientId, $token, $expires) {
    return Cache::put('YToken:'.$clientId.':'.env('YANDEX_CLIENT_ID'), $token, $expires);
  }

  public static function clientGetToken($clientId) {
    return Cache::get('YToken:'.$clientId.':'.env('YANDEX_CLIENT_ID'));
  }

  public static function clientForgetToken($clientId) {
    return Cache::forget('YToken:'.$clientId.':'.env('YANDEX_CLIENT_ID'));
  }

  public static function codeToToken($clientId, $code) {

    try {
      $client = new Client();
      $response = $client->request('POST', 'https://oauth.yandex.ru/token',
        self::addProxy([
          'form_params' => [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => self::getClientId(),
            'client_secret' => self::getClientPassword(),
          ]
        ]));

      $data = json_decode($response->getBody()->getContents(), true);
      self::clientSetToken($clientId, $data['access_token'], $data['expires_in']);
      return ['success' => true, 'message' => 'Вы успешно авторизованы. Пожалуйста закройте вкладку и вернитесь на страницу аудитории.'];
    } catch (\Exception $e) {
      return ['success' => false, 'message' => $e->getMessage()];
    }
  }

  private static function generateBody(array $data, $disposition = '--------------------------5b2a52c5c90f668a') {
    $body = $disposition . "\r\n";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"data.csv\"\r\n";
    $body .= "Content-Type: application/octet-stream\r\n";
    foreach ($data as $mac) {
      $body .= "\r\n$mac";
    }
    $body .= "\r\n" . $disposition . "--\r\n";

    return $body;
  }

  public static function send($clientId, $type, $url, $data) {
    $body = '';
    $headers = [ 'Authorization' => 'Bearer ' . self::clientGetToken($clientId) ];

    try {
      if ($type === 'POST') {
        $body = self::generateBody($data);
        $headers['Content-Type'] = 'multipart/form-data; boundary=------------------------5b2a52c5c90f668a';
        $headers['Context-Length'] = strlen($body);
      }

      $client = new Client();
      $response = $client->request($type, $url,
        self::addProxy([
          'headers' => $headers,
          'body' => $body
        ]));
    } catch (\Exception $e) {
      Log::error('ERROR in YandexConnector send function: ' . $e->getMessage());
      return ['error' => $e->getMessage()];
    }

    return json_decode($response->getBody()->getContents(), true);
  }

  public static function sendJSON($clientId, $type, $url, $data) {
    $headers = [ 'Authorization' => 'Bearer ' . self::clientGetToken($clientId) ];

    try {
      $client = new Client();
      $response = $client->request($type, $url,
        self::addProxy([
          'headers' => $headers,
          'json' => $data
        ]));
    } catch (\Exception $e) {
      Log::error('ERROR in YandexConnector send function: ' . $e->getMessage());
      return ['error' => $e->getMessage()];
    }

    return json_decode($response->getBody()->getContents(), true);
  }

  private static function addProxy($data) {
    if( App::environment() != 'production') {
      $data['proxy'] = 'http://46.21.249.120:8888';
    }
    return $data;
  }
}
