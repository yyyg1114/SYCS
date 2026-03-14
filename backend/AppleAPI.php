<?php
// backend/AppleAPI.php
require_once __DIR__ . '/apple_config.php';

require_once __DIR__ . '/RetryHandler.php';

class AppleAPI
{
    public static function getAuthorizeUrl($state)
    {
        $params = [
            'client_id' => APPLE_CLIENT_ID,
            'redirect_uri' => getAppleRedirectUri(),
            'response_type' => 'code id_token',
            'state' => $state,
            'scope' => 'name email',
            'response_mode' => 'form_post'
        ];
        return "https://appleid.apple.com/auth/authorize?" . http_build_query($params);
    }

    /**
     * 認可コードをアクセストークンに交換する
     * 
     * 注意: Apple OAuth2 では client_secret として自署した JWT が必要です。
     * 本タスクではリトライと統一構造の実装を優先し、JWT生成ロジックは別途必要です。
     */
    public static function exchangeCode($code)
    {
        // TODO: Generate JWT client_secret using private key
        $clientSecret = 'PLACEHOLDER_JWT'; 

        $postData = [
            'client_id' => APPLE_CLIENT_ID,
            'client_secret' => $clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => getAppleRedirectUri(),
        ];

        try {
            return RetryHandler::callJsonApi(
                "https://appleid.apple.com/auth/token",
                'POST',
                $postData,
                ['Content-Type: application/x-www-form-urlencoded'],
                ['provider' => 'apple', 'action' => 'exchangeCode']
            );
        } catch (\Throwable $e) {
            error_log("AppleAPI::exchangeCode 失敗: " . $e->getMessage());
            return null;
        }
    }

    /**
     * IDトークンをデコードしてユーザー情報を取得する
     */
    public static function getUserInfo($idToken)
    {
        // Apple API の場合、userInfo エンドポイントはなく、id_token をパースするのが一般的です。
        // ここでは統一構造のためにプレースホルダを維持します。
        try {
            // 本来は JWT Library で $idToken をパースし、'sub', 'email' 等を取得する
            return ['id_token' => $idToken]; 
        } catch (\Throwable $e) {
            error_log("AppleAPI::getUserInfo 失敗: " . $e->getMessage());
            return null;
        }
    }
}
