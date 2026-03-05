<?php
// backend/DiscordAPI.php
require_once __DIR__ . '/discord_config.php';
require_once __DIR__ . '/RetryHandler.php';
require_once __DIR__ . '/Cache.php';

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

    /**
     * 認可コードをアクセストークンに交換する（リトライ付き）
     *
     * @param string $code 認可コード
     * @return array|null トークン情報、失敗時は null
     */
    public static function exchangeCode($code)
    {
        $postData = [
            'client_id' => DISCORD_CLIENT_ID,
            'client_secret' => DISCORD_CLIENT_SECRET,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => getDiscordRedirectUri(),
        ];

        try {
            return RetryHandler::execute(function () use ($postData) {
                $ch = curl_init("https://discord.com/api/oauth2/token");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                $response = RetryHandler::curlExec($ch, 1);
                curl_close($ch);

                $decoded = json_decode($response, true);
                if (empty($decoded) || isset($decoded['error'])) {
                    throw new \RuntimeException("Discord token exchange failed: " . ($decoded['error'] ?? 'unknown'));
                }
                return $decoded;
            }, 3, 500, ['provider' => 'discord', 'action' => 'exchangeCode']);
        } catch (\Throwable $e) {
            error_log("DiscordAPI::exchangeCode 失敗: " . $e->getMessage());
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
        $cacheKey = 'discord_userinfo_' . hash('sha256', $accessToken);

        // キャッシュから取得を試みる
        $cached = $cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $result = RetryHandler::execute(function () use ($accessToken) {
                $ch = curl_init("https://discord.com/api/users/@me");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                $response = RetryHandler::curlExec($ch, 1);
                curl_close($ch);

                $decoded = json_decode($response, true);
                if (empty($decoded) || isset($decoded['code'])) {
                    // Discord APIはエラー時 'code' フィールドを返す
                    throw new \RuntimeException("Discord userinfo failed: " . json_encode($decoded));
                }
                return $decoded;
            }, 3, 500, ['provider' => 'discord', 'action' => 'getUserInfo']);
        } catch (\Throwable $e) {
            error_log("DiscordAPI::getUserInfo 失敗: " . $e->getMessage());
            return null;
        }

        // 5分間キャッシュ
        if ($result !== null) {
            $cache->set($cacheKey, $result, 300);
        }

        return $result;
    }
}
