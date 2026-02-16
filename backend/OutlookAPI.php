<?php
// backend/OutlookAPI.php
require_once __DIR__ . '/outlook_config.php';

class OutlookAPI
{
    public static function getAuthorizeUrl($state)
    {
        $params = [
            'client_id' => OUTLOOK_CLIENT_ID,
            'redirect_uri' => getOutlookRedirectUri(),
            'response_type' => 'code',
            'state' => $state,
            'scope' => 'openid profile email User.Read'
        ];
        return "https://login.microsoftonline.com/common/oauth2/v2.0/authorize?" . http_build_query($params);
    }

    public static function exchangeCode($code)
    {
        $url = "https://login.microsoftonline.com/common/oauth2/v2.0/token";
        $data = [
            'client_id' => OUTLOOK_CLIENT_ID,
            'client_secret' => OUTLOOK_CLIENT_SECRET,
            'code' => $code,
            'redirect_uri' => getOutlookRedirectUri(),
            'grant_type' => 'authorization_code'
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return json_decode($result, true);
    }

    public static function getUserInfo($accessToken)
    {
        $url = "https://graph.microsoft.com/v1.0/me";
        $options = [
            'http' => [
                'header' => "Authorization: Bearer $accessToken\r\n"
            ]
        ];
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return json_decode($result, true);
    }
}
