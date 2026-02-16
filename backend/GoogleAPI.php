<?php
// backend/GoogleAPI.php
require_once __DIR__ . '/google_config.php';

class GoogleAPI
{
    public static function getAuthorizeUrl($state)
    {
        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => getGoogleRedirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'select_account'
        ];
        return "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query($params);
    }

    public static function exchangeCode($code)
    {
        $postData = [
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => getGoogleRedirectUri(),
        ];

        $ch = curl_init("https://oauth2.googleapis.com/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    public static function getUserInfo($accessToken)
    {
        $ch = curl_init("https://www.googleapis.com/oauth2/v3/userinfo");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}
