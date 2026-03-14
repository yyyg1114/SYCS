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
            return RetryHandler::callJsonApi(
                "https://discord.com/api/oauth2/token",
                'POST',
                $postData,
                ['Content-Type: application/x-www-form-urlencoded'],
                ['provider' => 'discord', 'action' => 'exchangeCode']
            );
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
            $result = RetryHandler::callJsonApi(
                "https://discord.com/api/users/@me",
                'GET',
                [],
                ['Authorization: Bearer ' . $accessToken],
                ['provider' => 'discord', 'action' => 'getUserInfo']
            );
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
