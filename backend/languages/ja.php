<?php
return [
    'login' => [
        'title' => 'ログイン',
        'username' => 'ユーザー名',
        'password' => 'パスワード',
        'submit' => 'ログイン',
        'or' => 'または',
        'google' => 'Googleでログイン',
        'discord' => 'Discordでログイン',
        'apple' => 'Appleでログイン (無効化中)',
        'outlook' => 'Outlookでログイン (無効化中)',
        'forgot_password' => 'パスワードを忘れましたか？',
        'no_account' => 'アカウントをお持ちでないですか？',
        'signup' => '新規登録',
        'error_rate_limit' => 'ログイン試行回数の上限（{max}回）に達しました。{min}分後に再試行してください。',
        'error_not_verified' => 'メールアドレスの本登録が完了していません。',
        'error_invalid' => 'ユーザー名またはパスワードが正しくありません。',
    ],
    'signup' => [
        'title' => 'アカウント作成',
        'email' => 'メールアドレス',
        'username' => 'ユーザー名',
        'password' => 'パスワード',
        'submit' => '新規登録',
        'has_account' => '既にアカウントをお持ちですか？',
        'login' => 'ログイン',
        'error_csrf' => '不正なリクエストです (CSRF Token Mismatch)',
        'error_exists' => 'このメールアドレスまたはユーザー名は既に使用されています',
        'success_pending' => '仮登録が完了しました。届いたメール内のリンクをクリックして本登録を完了してください。',
        'error_failed' => '登録に失敗しました: ',
        'redirecting' => '3秒後に自動で移動します',
    ],
    'common' => [
        'lang_ja' => '日本語',
        'lang_en' => 'English',
    ]
];
