<?php
// backend/ErrorHandler.php

class SecurityException extends Exception
{
    protected $userMessage;
    public function __construct($message, $code = 0, $userMessage = null, ?Exception $previous = null)
    {
        $this->userMessage = $userMessage;
        parent::__construct($message, $code, $previous);
    }
    public function getUserMessage()
    {
        return $this->userMessage;
    }
}

class ValidationException extends Exception
{
    protected $userMessage;
    public function __construct($message, $code = 0, $userMessage = null, ?Exception $previous = null)
    {
        $this->userMessage = $userMessage;
        parent::__construct($message, $code, $previous);
    }
    public function getUserMessage()
    {
        return $this->userMessage;
    }
}

class DatabaseException extends Exception
{
    protected $userMessage;
    public function __construct($message, $code = 0, $userMessage = null, ?Exception $previous = null)
    {
        $this->userMessage = $userMessage;
        parent::__construct($message, $code, $previous);
    }
    public function getUserMessage()
    {
        return $this->userMessage;
    }
}

class ErrorResponse
{
    private $code;
    private $message;
    private $userMessage;
    private $details;
    private $timestamp;

    public function __construct($code, $message, $userMessage = null, $details = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->userMessage = $userMessage ?? $this->getDefaultUserMessage($code);
        $this->details = $details;
        $this->timestamp = date('Y-m-d H:i:s');
    }

    public function toJSON()
    {
        return json_encode([
            'success' => false,
            'error' => [
                'code' => $this->code,
                'message' => $this->message,
                'userMessage' => $this->userMessage,
                'details' => $this->details,
                'timestamp' => $this->timestamp
            ]
        ], JSON_UNESCAPED_UNICODE);
    }

    private function getDefaultUserMessage($code)
    {
        $messages = [
            'DB_ERROR' => 'データベースエラーが発生しました。しばらく後に再度お試しください。',
            'VALIDATION_ERROR' => '入力値が正しくありません。',
            'UNAUTHORIZED' => 'ログインが必要です。',
            'FORBIDDEN' => 'このアクションを実行する権限がありません。',
            'NOT_FOUND' => 'リソースが見つかりません。',
            'SERVER_ERROR' => 'サーバーエラーが発生しました。管理者に連絡してください。',
            'CSRF_ERROR' => 'セッションの有効期限が切れたか、不正なリクエストです。ページを再読み込みしてください。',
        ];
        return $messages[$code] ?? '予期しないエラーが発生しました。';
    }
}
