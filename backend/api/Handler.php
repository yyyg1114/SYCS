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

            switch ($action) {
                // ---- User ----
                case 'update_profile':
                    (new UserHandler(...$args))->updateProfile();
                    break;
                case 'push_subscribe':
                    (new UserHandler(...$args))->pushSubscribe();
                    break;
                case 'update_status':
                    (new UserHandler(...$args))->updateStatus();
                    break;
                case 'get_user_status':
                    (new UserHandler(...$args))->getUserStatus();
                    break;
                case 'get_user_profile':
                    (new UserHandler(...$args))->getUserProfile();
                    break;
                case 'get_friends_statuses':
                    (new UserHandler(...$args))->getFriendsStatuses();
                    break;
                case 'get_all_users':
                    (new UserHandler(...$args))->getAllUsers();
                    break;
                case 'search_users':
                    (new UserHandler(...$args))->searchUsers();
                    break;
                case 'get_online_users':
                    (new UserHandler(...$args))->getOnlineUsers();
                    break;
                case 'get_my_files':
                    (new UserHandler(...$args))->getMyFiles();
                    break;
                case 'toggle_mute':
                    (new UserHandler(...$args))->toggleMute();
                    break;
                case 'get_mute_statuses':
                    (new UserHandler(...$args))->getMuteStatuses();
                    break;
                case 'set_lang':
                    (new UserHandler(...$args))->setLang();
                    break;

                // ---- Messages ----
                case 'get_messages':
                    (new MessageHandler(...$args))->getMessages();
                    break;
                case 'send_message':
                    (new MessageHandler(...$args))->sendMessage();
                    break;
                case 'edit_message':
                    (new MessageHandler(...$args))->editMessage();
                    break;
                case 'delete_message':
                    (new MessageHandler(...$args))->deleteMessage();
                    break;
                case 'delete_messages':
                    (new MessageHandler(...$args))->deleteMessages();
                    break;
                case 'toggle_reaction':
                    (new MessageHandler(...$args))->toggleReaction();
                    break;
                case 'toggle_pin':
                    (new MessageHandler(...$args))->togglePin();
                    break;
                case 'search_messages':
                    (new MessageHandler(...$args))->searchMessages();
                    break;
                case 'getPinnedMessages':
                case 'get_pinned_messages':
                    (new MessageHandler(...$args))->getPinnedMessages();
                    break;
                case 'get_attachments':
                    (new MessageHandler(...$args))->getAttachments();
                    break;
                case 'update_typing_status':
                    (new MessageHandler(...$args))->updateTypingStatus();
                    break;
                case 'get_typing_users':
                    (new MessageHandler(...$args))->getTypingUsers();
                    break;

                // ---- Direct Messages ----
                case 'get_direct_messages':
                    (new DirectMessageHandler(...$args))->getDirectMessages();
                    break;
                case 'send_direct_message':
                    (new DirectMessageHandler(...$args))->sendDirectMessage();
                    break;
                case 'mark_dms_as_read':
                    (new DirectMessageHandler(...$args))->markDmsAsRead();
                    break;
                case 'get_dm_partners':
                    (new DirectMessageHandler(...$args))->getDmPartners();
                    break;
                case 'get_unread_dm_counts':
                    (new DirectMessageHandler(...$args))->getUnreadDmCounts();
                    break;

                // ---- Threads ----
                case 'get_threads':
                    (new ThreadHandler(...$args))->getThreads();
                    break;
                case 'create_thread':
                    (new ThreadHandler(...$args))->createThread();
                    break;
                case 'edit_thread':
                case 'update_thread':
                    (new ThreadHandler(...$args))->editThread();
                    break;
                case 'delete_thread':
                    (new ThreadHandler(...$args))->deleteThread();
                    break;
                case 'set_last_thread':
                    (new ThreadHandler(...$args))->setLastThread();
                    break;
                case 'toggle_favorite':
                    (new ThreadHandler(...$args))->toggleFavorite();
                    break;
                case 'get_favorites':
                    (new ThreadHandler(...$args))->getFavorites();
                    break;
                case 'check_favorite':
                    (new ThreadHandler(...$args))->checkFavorite();
                    break;

                // ---- Group ----
                case 'create_group_thread':
                    (new GroupHandler(...$args))->createGroupThread();
                    break;
                case 'get_group_threads':
                    (new GroupHandler(...$args))->getGroupThreads();
                    break;
                case 'get_group_messages':
                    (new GroupHandler(...$args))->getGroupMessages();
                    break;

                // ---- Friends ----
                case 'request_friend':
                    (new FriendHandler(...$args))->requestFriend();
                    break;
                case 'send_friend_request':
                    (new FriendHandler(...$args))->sendFriendRequestAction();
                    break;
                case 'accept_friend':
                    (new FriendHandler(...$args))->acceptFriend();
                    break;
                case 'get_friend_requests':
                case 'get_pending_requests':
                    (new FriendHandler(...$args))->getFriendRequests();
                    break;
                case 'handle_friend_request':
                    (new FriendHandler(...$args))->handleFriendRequestAction();
                    break;
                case 'get_friends':
                    (new FriendHandler(...$args))->getFriends();
                    break;
                case 'block_user':
                    (new FriendHandler(...$args))->blockUser();
                    break;
                case 'unblock_user':
                    (new FriendHandler(...$args))->unblockUser();
                    break;
                case 'get_blocked_users':
                    (new FriendHandler(...$args))->getBlockedUsers();
                    break;

                // ---- Location ----
                case 'update_location':
                    (new LocationHandler(...$args))->updateLocation();
                    break;
                case 'get_user_locations':
                    (new LocationHandler(...$args))->getUserLocations();
                    break;

                // ---- Meeting / Signaling ----
                case 'join_meeting':
                    (new MeetingHandler(...$args))->joinMeeting();
                    break;
                case 'send_signaling':
                    (new MeetingHandler(...$args))->sendSignaling();
                    break;
                case 'get_signaling':
                    (new MeetingHandler(...$args))->getSignaling();
                    break;

                // ---- SSE ----
                case 'sse':
                    (new SseHandler(...$args))->streamEvents();
                    break;

                default:
                    echo json_encode(['error' => 'Unknown action: ' . $action]);
                    break;
            }
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
