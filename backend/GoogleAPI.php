<?php
// backend/GoogleAPI.php
require_once __DIR__ . '/google_config.php';
require_once __DIR__ . '/RetryHandler.php';
require_once __DIR__ . '/Cache.php';

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

    /**
     * 認可コードをアクセストークンに交換する（リトライ付き）
     *
     * @param string $code 認可コード
     * @return array|null トークン情報、失敗時は null
     */
    public static function exchangeCode($code)
    {
        $postData = [
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => getGoogleRedirectUri(),
        ];

        try {
            return RetryHandler::execute(function () use ($postData) {
                $ch = curl_init("https://oauth2.googleapis.com/token");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                $response = RetryHandler::curlExec($ch, 1); // curlExec内でも1回リトライ
                curl_close($ch);

                $decoded = json_decode($response, true);
                if (empty($decoded) || isset($decoded['error'])) {
                    throw new \RuntimeException("Google token exchange failed: " . ($decoded['error'] ?? 'unknown'));
                }
                return $decoded;
            }, 3, 500, ['provider' => 'google', 'action' => 'exchangeCode']);
        } catch (\Throwable $e) {
            error_log("GoogleAPI::exchangeCode 失敗: " . $e->getMessage());
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
        $cacheKey = 'google_userinfo_' . hash('sha256', $accessToken);

        // キャッシュから取得を試みる
        $cached = $cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $result = RetryHandler::execute(function () use ($accessToken) {
                $ch = curl_init("https://www.googleapis.com/oauth2/v3/userinfo");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                $response = RetryHandler::curlExec($ch, 1);
                curl_close($ch);

                $decoded = json_decode($response, true);
                if (empty($decoded) || isset($decoded['error'])) {
                    throw new \RuntimeException("Google userinfo failed: " . json_encode($decoded['error'] ?? 'unknown'));
                }
                return $decoded;
            }, 3, 500, ['provider' => 'google', 'action' => 'getUserInfo']);
        } catch (\Throwable $e) {
            error_log("GoogleAPI::getUserInfo 失敗: " . $e->getMessage());
            return null;
        }

        // 5分間キャッシュ
        if ($result !== null) {
            $cache->set($cacheKey, $result, 300);
        }

        return $result;
    }
}
