# SYCS Chat App Walkthrough

モダンな技術スタック (Vite + Vue 3) と PHP によるバックエンド API を用いて、チャットアプリケーションを構築しました。
破壊的な変更を避けるため、プロジェクトルートに `frontend/`, `backend/`, [sql/](file:///c:/Users/pkyg6/%E3%82%A6%E3%82%A7%E3%83%96%E9%96%8B%E7%99%BA/VSCode/SYCS_suchgamer_ver_Vue/sql/init.sql) フォルダを新規作成し、その中でSPAとして動作する構成としました。

## 実装内容

1. **Frontend (Vite + Vue 3)**
   - `frontend/` ディレクトリ内にプロジェクトを構築しました。
   - `vue-router` によるルーティング（Login, Signup, Chat画面）
   - `pinia` による認証状態のグローバル管理
   - プレミアム感のあるグラスモーフィズムデザイン ([frontend/src/assets/style.css](file:///c:/Users/pkyg6/%E3%82%A6%E3%82%A7%E3%83%96%E9%96%8B%E7%99%BA/VSCode/SYCS_suchgamer_ver_Vue/frontend/src/assets/style.css), [chat.css](file:///c:/Users/pkyg6/%E3%82%A6%E3%82%A7%E3%83%96%E9%96%8B%E7%99%BA/VSCode/SYCS_suchgamer_ver_Vue/frontend/src/assets/chat.css))
2. **Backend (PHP API)**
   - オリジナルのセッションロジックを活用しつつ、RESTful API レイヤー (`backend/api/`) へと進化させました。
   - `login.php`, `signup.php`, `threads.php`, `messages.php`, `check_auth.php`, `logout.php` といったエンドポイントを提供します。
   - Vite のプロキシ機能 (`vite.config.js`) により、CORSの課題を回避し統合開発が可能です。

3. **Database (SQL)**
   - `sql/init.sql` を準備しました。これを用いて `sycs_chat` データベースと `users`, `threads`, `messages` テーブルを作成できます。

## 動作確認 (Manual Verification)

ローカル環境で動作を確認するには、以下の手順を実行してください。

### 1. データベースのセットアップ

`sql/init.sql` をお使いの MySQL サーバー（XAMPP など）で実行し、データベースを構築します。
必要に応じて、`backend/db.php` の環境変数（もしくはデフォルト値）を、ご自身のDB認証情報（Host, User, Pass）に設定してください。

### 2. PHP バックエンドサーバーの起動

新しくターミナルを開き、`backend` ディレクトリでPHPのビルトインサーバーを起動します。（ポート8000）

```bash
cd backend
php -S localhost:8000
```

### 3. Vite 開発サーバーの起動

別のターミナルを開き、`frontend` ディレクトリで開発サーバーを起動します。

```bash
cd frontend
npm run dev
```

起動後、ブラウザで `http://localhost:5173` にアクセスします。

### 今回のデザインについて

オリジナル（jQuery + バニラ CSS）から、モダンな「ダークモード＋グラスモーフィズム（すりガラス効果）」をベースとしたプレミアムなUIに生まれ変わりました。アニメーションとレスポンシブなサイドバーが直感的なユーザー体験をもたらします。
