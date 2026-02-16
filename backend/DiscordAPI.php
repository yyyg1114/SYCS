<?php
// backend/DiscordAPI.php
require_once __DIR__ . '/discord_config.php';

class DiscordAPI
{
    public static function getAuthorizeUrl($state)
    {
        $params = [
            'client_id' => DISCORD_CLIENT_ID,
            'redirect_uri' => getDiscordRedirectUri(),
            'response_type' => 'code',
            'scope' => 'identify email',
            'state' => $state
        ];
        return "https://discord.com/api/oauth2/authorize?" . http_build_query($params);
    }

    public static function exchangeCode($code)
    {
        $postData = [
            'client_id' => DISCORD_CLIENT_ID,
            'client_secret' => DISCORD_CLIENT_SECRET,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => getDiscordRedirectUri(),
        ];

        $ch = curl_init("https://discord.com/api/oauth2/token");
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
        $ch = curl_init("https://discord.com/api/users/@me");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}
