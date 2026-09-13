<?php

/**
 * SYCS API Handler (Router)
 *
 * このファイルはルーターとして機能する。
 * 各アクションは handlers/ 以下の専用クラスに委譲される。
 * クラス名・ファイル名は変更なし → index.php / dm.php の修正不要。
 */

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/BaseHandler.php';
require_once __DIR__ . '/handlers/UserHandler.php';
require_once __DIR__ . '/handlers/MessageHandler.php';
require_once __DIR__ . '/handlers/DirectMessageHandler.php';
require_once __DIR__ . '/handlers/ThreadHandler.php';
require_once __DIR__ . '/handlers/GroupHandler.php';
require_once __DIR__ . '/handlers/FriendHandler.php';
require_once __DIR__ . '/handlers/LocationHandler.php';
require_once __DIR__ . '/handlers/MeetingHandler.php';
require_once __DIR__ . '/handlers/SseHandler.php';

class ApiHandler
{
    private $mysqli;
    private $userId;
    private $csrfToken;

    public function __construct($mysqli, $userId, $csrfToken)
    {
        $this->mysqli    = $mysqli;
        $this->userId    = $userId;
        $this->csrfToken = $csrfToken;
    }

    /**
     * 全アクションのルーティング
     * 各ハンドラクラスのインスタンスを生成して委譲する
     */
    public function handle($action): void
    {
        try {
            $args = [$this->mysqli, $this->userId, $this->csrfToken];

            $routes = [
                // ---- User ----
                'update_profile' => [UserHandler::class, 'updateProfile'],
                'push_subscribe' => [UserHandler::class, 'pushSubscribe'],
                'update_status' => [UserHandler::class, 'updateStatus'],
                'get_user_status' => [UserHandler::class, 'getUserStatus'],
                'get_user_profile' => [UserHandler::class, 'getUserProfile'],
                'get_friends_statuses' => [UserHandler::class, 'getFriendsStatuses'],
                'get_all_users' => [UserHandler::class, 'getAllUsers'],
                'search_users' => [UserHandler::class, 'searchUsers'],
                'get_online_users' => [UserHandler::class, 'getOnlineUsers'],
                'get_my_files' => [UserHandler::class, 'getMyFiles'],
                'toggle_mute' => [UserHandler::class, 'toggleMute'],
                'get_mute_statuses' => [UserHandler::class, 'getMuteStatuses'],
                'set_lang' => [UserHandler::class, 'setLang'],

                // ---- Messages ----
                'get_messages' => [MessageHandler::class, 'getMessages'],
                'send_message' => [MessageHandler::class, 'sendMessage'],
                'edit_message' => [MessageHandler::class, 'editMessage'],
                'delete_message' => [MessageHandler::class, 'deleteMessage'],
                'delete_messages' => [MessageHandler::class, 'deleteMessages'],
                'toggle_reaction' => [MessageHandler::class, 'toggleReaction'],
                'toggle_pin' => [MessageHandler::class, 'togglePin'],
                'search_messages' => [MessageHandler::class, 'searchMessages'],
                'getPinnedMessages' => [MessageHandler::class, 'getPinnedMessages'],
                'get_pinned_messages' => [MessageHandler::class, 'getPinnedMessages'],
                'get_attachments' => [MessageHandler::class, 'getAttachments'],
                'update_typing_status' => [MessageHandler::class, 'updateTypingStatus'],
                'get_typing_users' => [MessageHandler::class, 'getTypingUsers'],

                // ---- Direct Messages ----
                'get_direct_messages' => [DirectMessageHandler::class, 'getDirectMessages'],
                'send_direct_message' => [DirectMessageHandler::class, 'sendDirectMessage'],
                'mark_dms_as_read' => [DirectMessageHandler::class, 'markDmsAsRead'],
                'get_dm_partners' => [DirectMessageHandler::class, 'getDmPartners'],
                'get_unread_dm_counts' => [DirectMessageHandler::class, 'getUnreadDmCounts'],

                // ---- Threads ----
                'get_threads' => [ThreadHandler::class, 'getThreads'],
                'create_thread' => [ThreadHandler::class, 'createThread'],
                'edit_thread' => [ThreadHandler::class, 'editThread'],
                'update_thread' => [ThreadHandler::class, 'editThread'],
                'delete_thread' => [ThreadHandler::class, 'deleteThread'],
                'set_last_thread' => [ThreadHandler::class, 'setLastThread'],
                'toggle_favorite' => [ThreadHandler::class, 'toggleFavorite'],
                'get_favorites' => [ThreadHandler::class, 'getFavorites'],
                'check_favorite' => [ThreadHandler::class, 'checkFavorite'],

                // ---- Group ----
                'create_group_thread' => [GroupHandler::class, 'createGroupThread'],
                'get_group_threads' => [GroupHandler::class, 'getGroupThreads'],
                'get_group_messages' => [GroupHandler::class, 'getGroupMessages'],

                // ---- Friends ----
                'request_friend' => [FriendHandler::class, 'requestFriend'],
                'send_friend_request' => [FriendHandler::class, 'sendFriendRequestAction'],
                'accept_friend' => [FriendHandler::class, 'acceptFriend'],
                'get_friend_requests' => [FriendHandler::class, 'getFriendRequests'],
                'get_pending_requests' => [FriendHandler::class, 'getFriendRequests'],
                'handle_friend_request' => [FriendHandler::class, 'handleFriendRequestAction'],
                'get_friends' => [FriendHandler::class, 'getFriends'],
                'block_user' => [FriendHandler::class, 'blockUser'],
                'unblock_user' => [FriendHandler::class, 'unblockUser'],
                'get_blocked_users' => [FriendHandler::class, 'getBlockedUsers'],

                // ---- Location ----
                'update_location' => [LocationHandler::class, 'updateLocation'],
                'get_user_locations' => [LocationHandler::class, 'getUserLocations'],

                // ---- Meeting / Signaling ----
                'join_meeting' => [MeetingHandler::class, 'joinMeeting'],
                'send_signaling' => [MeetingHandler::class, 'sendSignaling'],
                'get_signaling' => [MeetingHandler::class, 'getSignaling'],

                // ---- SSE ----
                'sse' => [SseHandler::class, 'streamEvents'],
            ];

            if (!isset($routes[$action])) {
                echo json_encode(['error' => 'Unknown action: ' . $action]);
                return;
            }

            [$handlerClass, $method] = $routes[$action];
            $handler = new $handlerClass(...$args);
            $handler->$method();
        } catch (\Exception $e) {
            $code = 500;
            if ($e->getMessage() === 'Invalid CSRF Token') {
                $code = 403;
            }
            http_response_code($code);
            echo json_encode(['success' => false, 'error' => 'API Error: ' . $e->getMessage()]);
        }
    }
}
