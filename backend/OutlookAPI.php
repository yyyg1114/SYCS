<?php
// backend/OutlookAPI.php
require_once __DIR__ . '/outlook_config.php';
require_once __DIR__ . '/RetryHandler.php';
require_once __DIR__ . '/Cache.php';

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

    /**
     * 認可コードをアクセストークンに交換する（リトライ付き）
     *
     * @param string $code 認可コード
     * @return array|null トークン情報、失敗時は null
     */
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

        try {
            return RetryHandler::execute(function () use ($url, $data) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                $response = RetryHandler::curlExec($ch, 1);
                curl_close($ch);

                $decoded = json_decode($response, true);
                if (empty($decoded) || isset($decoded['error'])) {
                    throw new \RuntimeException("Outlook token exchange failed: " . ($decoded['error'] ?? 'unknown'));
                }
                return $decoded;
            }, 3, 500, ['provider' => 'outlook', 'action' => 'exchangeCode']);
        } catch (\Throwable $e) {
            error_log("OutlookAPI::exchangeCode 失敗: " . $e->getMessage());
            return null;
        }
    }

    /**
     * アクセストークンでユーザー情報を取得する（リトライ + キャッシュ付き）
     *
     * @param string $accessToken アクセストークン
     * @return array|null ユーザー情報、失敗時は null
     */
    public static function getUserInfo($accessToken)
    {
        $cache = new Cache();
        $cacheKey = 'outlook_userinfo_' . hash('sha256', $accessToken);

        // キャッシュから取得を試みる
        $cached = $cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $result = RetryHandler::execute(function () use ($accessToken) {
                $url = "https://graph.microsoft.com/v1.0/me";
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $accessToken"]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                $response = RetryHandler::curlExec($ch, 1);
                curl_close($ch);

                $decoded = json_decode($response, true);
                if (empty($decoded) || isset($decoded['error'])) {
                    throw new \RuntimeException("Outlook userinfo failed: " . json_encode($decoded['error'] ?? 'unknown'));
                }
                return $decoded;
            }, 3, 500, ['provider' => 'outlook', 'action' => 'getUserInfo']);
        } catch (\Throwable $e) {
            error_log("OutlookAPI::getUserInfo 失敗: " . $e->getMessage());
            return null;
        }

        // 5分間キャッシュ
        if ($result !== null) {
            $cache->set($cacheKey, $result, 300);
        }

        return $result;
    }
}
