<?php

/**
 * Release Notes - Japanese Content
 * 
 * This file is included by release_notes.php
 */
?>

<!-- ===== v2.2.26 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.26</span>
        <span class="release-title">AES-256-GCM 上位、AEAD 認証付下位、上位狐撃性</span>
        <span class="release-date">2026-08-18</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> セキュリティ強化
            </div>
            <ul>
                <li>
                    <span class="icon">🔐</span>
                    <div>
                        <strong>AES-256-GCM (AEAD) 暗号化の導入</strong>
                        <span class="detail">AES-256-CBC から AES-256-GCM に上位し、認証付暗号で改ざん検出を可能にし、データ一計性を確保しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">✓</span>
                    <div>
                        <strong>レガシー CBC データとの後方互換性</strong>
                        <span class="detail">バージョンマーカー (v2:: プレフィックス GCM 用、レガシー CBC は接頭辞なし) を使用して既存暗号化データを自動移行できる構成を実装しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔑</span>
                    <div>
                        <strong>ENCRYPTION_KEY 環境変数設定</strong>
                        <span class="detail">.env.example に ENCRYPTION_KEY を追加し、安全なキー生成方法のドキュメンテーションを追加しました (推奨 64文字以上の 16 進数)。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.25 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.25</span>
        <span class="release-title">ホワイトリストベース SVG サニタイゼーション、XXE 対策強化とセキュリティ硬化</span>
        <span class="release-date">2026-08-18</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> セキュリティと堅牢性
            </div>
            <ul>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>ホワイトリストベース SVG タグと属性フィルタリング</strong>
                        <span class="detail">SVG サニタイゼーション方式をブラックリストからホワイトリストに変更し、許可される安全なタグ (svg, path, circle, text など) と属性を明示的に指定してバイパス攻撃を防止しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔒</span>
                    <div>
                        <strong>XXE (XML 外部エンティティ) 対策の強化</strong>
                        <span class="detail">PHP &lt; 8.0 (libxml_disable_entity_loader 使用) と PHP 8+ (LIBXML_NONET フラグ) の両方でエンティティ読み込みを防止し、外部リソースアクセスをブロックしました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔗</span>
                    <div>
                        <strong>href と xlink:href の検証厳格化</strong>
                        <span class="detail">SVG リンクは内部参照 (#) または空の値のみを許可するようになり、外部 URL やプロトコルベースの攻撃をブロックします。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.24 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.24</span>
        <span class="release-title">メッセージ選択モード、複数削除、会話管理の強化</span>
        <span class="release-date">2026-08-09</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 &amp; 強化
            </div>
            <ul>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>メッセージ選択モード</strong>
                        <span class="detail">チャット内で複数の自分のメッセージをチェックボックスで選択できる新しいモードを追加し、Shift 選択にも対応しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🗑️</span>
                    <div>
                        <strong>複数メッセージ削除</strong>
                        <span class="detail">選択したメッセージを一括で削除できるようになり、確認プロンプト付きで会話の整理をより簡単にしました。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI &amp; 生産性
            </div>
            <ul>
                <li>
                    <span class="icon">✅</span>
                    <div>
                        <strong>選択ツールバーとアクションコントロール</strong>
                        <span class="detail">すべて選択、選択解除、キャンセルを備えたコンテキストアクションバーを追加し、複数メッセージの処理をスムーズにしました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.23 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.23</span>
        <span class="release-title">Google OAuth 認証統合、環境変数 (.env) 管理の導入、セキュリティ強化</span>
        <span class="release-date">2026-07-29</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 &amp; 強化 (New Features &amp; Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">🔑</span>
                    <div>
                        <strong>Google OAuth 2.0 統合とソーシャルログイン対応</strong>
                        <span class="detail">Google クライアント ID / シークレットを用いた Google OAuth 2.0 認証フローを統合し、安全かつスムーズなソーシャルログインに対応しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚙️</span>
                    <div>
                        <strong>環境変数 (.env) ローダーの設定</strong>
                        <span class="detail">API キーや各種認証鍵などの機密情報を安全に管理するため EnvLoader を導入し、設定管理の利便性とセキュリティを向上させました。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> セキュリティ &amp; 認証 (Security &amp; Auth)
            </div>
            <ul>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>ログイン試行制限とセッション保護</strong>
                        <span class="detail">ブルートフォース攻撃を防止するログインレートリミットを最適化し、ログイン成功時のセッション再生成（Session Fixation 対策）を徹底しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.22 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.22</span>
        <span class="release-title">データベース高速化、テーマ洗練、CSS ビルドキャッシュ</span>
        <span class="release-date">2026-07-22</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 &amp; 強化 (New Features &amp; Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">🗄️</span>
                    <div>
                        <strong>永続的 DB 接続とインデックス管理</strong>
                        <span class="detail">MySQL を永続接続に変更し、メッセージ、ダイレクトメッセージ、シグナリングテーブルのインデックスを自動管理してクエリ性能を向上させました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>期限切れ DM のクリーンアップ</strong>
                        <span class="detail">バックエンドに期限切れダイレクトメッセージの削除処理を追加し、データベースの健全性とストレージ管理を強化しました。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI &amp; デザイン (UI &amp; Design)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>テーマシステムとモーダルの磨き上げ</strong>
                        <span class="detail">ライト / ダーク / ナイトテーマをグローバルに統合し、プロフィールモーダルやオーバーレイのスタイルをさらに磨きました。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> インフラ &amp; ユーティリティ (Infrastructure &amp; Utilities)
            </div>
            <ul>
                <li>
                    <span class="icon">📦</span>
                    <div>
                        <strong>CSS ビルドキャッシュと高速再生成</strong>
                        <span class="detail">CSS バンドルの再生成を制御するキャッシュフラグとディレクトリを導入し、不要なビルドを回避してアセット生成を高速化しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.21 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.21</span>
        <span class="release-title">オプティミスティック UI、メッセージリプライ機能、CSS アーキテクチャの刷新</span>
        <span class="release-date">2026-07-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 &amp; 強化 (New Features &amp; Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>オプティミスティック UI とメッセージの送信状態管理</strong>
                        <span class="detail">メッセージが即座にチャットに表示され、送信中は 💬 インジケーターが表示されます。送信失敗時は復旧可能なエラーバナーが表示され、再試行機能で信頼性が向上しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">💬</span>
                    <div>
                        <strong>メッセージリプライとメンション機能</strong>
                        <span class="detail">特定メッセージへのリプライ機能を実装し、リプライ対象の追跡、コンテキスト表示、会話中の添付ファイル管理に対応しました。より良いスレッド形式の会話が可能になります。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>添付ファイル処理と API の強化</strong>
                        <span class="detail">バックエンド API を強化し、送信メッセージの添付ファイルパスを正確に追跡・返却することで、ファイルがチャット履歴に確実に保存・表示されるようになりました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>セキュリティと表示機能のライブラリ統合</strong>
                        <span class="detail">Marked.js（Markdown レンダリング）、DOMPurify（XSS 対策）、Highlight.js（コード構文ハイライト）を統合し、安全で豊かなテキスト表示に対応しました。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI &amp; デザイン (UI &amp; Design)
            </div>
            <ul>
                <li>
                    <span class="icon">🎯</span>
                    <div>
                        <strong>アカウント削除ページの再設計</strong>
                        <span class="detail">アカウント削除ページを完全に刷新し、スタイルの向上、視覚階層の改善、パスワード確認フローのセキュリティ強化を実施しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">📦</span>
                    <div>
                        <strong>CSS バンドル最適化とアーキテクチャの整理</strong>
                        <span class="detail">delete_account.php のインラインスタイルを外部 CSS に抽出し、build_css.php による自動ミニ化・バンドル化を実装。bundle.min.css で最適化され、保守性とパフォーマンスが向上しました。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> インフラ &amp; ユーティリティ (Infrastructure &amp; Utilities)
            </div>
            <ul>
                <li>
                    <span class="icon">⚙️</span>
                    <div>
                        <strong>バックエンドのクリーンアップ、インデックス、および CSS ビルド自動化</strong>
                        <span class="detail">期限切れメッセージのクリーンアップとクエリインデックス管理をバックエンドに追加し、CSS ビルド自動化、オプティミスティック UI メッセージ状態、ユーティリティ改善を継続しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.20 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.20</span>
        <span class="release-title">認証処理の強化、フレンドリクエスト UX、およびチャット UI の磨き上げ</span>
        <span class="release-date">2026-06-28</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 &amp; 強化 (New Features &amp; Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>認証フローの強化</strong>
                        <span class="detail">ログインとサインアップ処理に try/catch を導入し、ロックアウト検出とエラーパスを安定化させて信頼性を向上させました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">👥</span>
                    <div>
                        <strong>フレンドリクエストとソーシャル操作</strong>
                        <span class="detail">ユーザー検索、申請送信、保留リクエスト、ブロックユーザー管理を追加し、フレンド関係の構築をよりスムーズにしました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">✨</span>
                    <div>
                        <strong>チャット UI とお気に入りの改善</strong>
                        <span class="detail">チャット検索表示、グループメンバー選択、favorites パネルを安全な DOM 更新と明確な空状態表示に改善しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔧</span>
                    <div>
                        <strong>パスワードリセット UX の向上</strong>
                        <span class="detail">リセットページのメッセージと復旧フローを改善し、より分かりやすい操作体験を提供します。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.19 ===== -->
<article class="release">
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 &amp; 強化 (New Features &amp; Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">👥</span>
                    <div>
                        <strong>フレンド機能の拡張</strong>
                        <span class="detail">送信、承認、拒否のフレンドリクエスト API を追加し、保留中リクエストの処理と DM ハブ内のフレンドリスト更新を実装しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧑‍💻</span>
                    <div>
                        <strong>ユーザープロフィールの改善</strong>
                        <span class="detail">ユーザープロフィールモーダルに「フレンド申請」・保留中リクエスト・ブロックユーザー管理を追加し、より直接的なソーシャル操作を可能にしました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">📸</span>
                    <div>
                        <strong>メディアアップロードプレビューの追加</strong>
                        <span class="detail">ドラッグ＆ドロップに対応した専用アップロードモーダルを追加し、ファイルプレビューとメタデータ表示で添付体験を改善しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>オンラインユーザー更新とグループチャット同期</strong>
                        <span class="detail">オンラインユーザー一覧の 30 秒自動更新を有効化し、初期読み込み時にグループチャット判定を復元して正しいサイドバー状態を保持します。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.18 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.18</span>
        <span class="release-title">ページ遷移アニメーションの実装・</span>
        <span class="release-date">2026-06-08</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI &amp; デザイン (UI &amp; Design)
            </div>
            <ul>
                <li>
                    <span class="icon">🎬</span>
                    <div>
                        <strong>View Transitions API によるページ遷移アニメーションの実装</strong>
                        <span class="detail">CSS の <code>@view-transition</code> と <code>::view-transition-old/new</code> を活用し、index.html・about.html・privacy_policy.html・login.php・signup.php・reset_password.php 間のページ遷移にスムーズなフェード＆スライドアニメーションを適用しました。ネイティブアプリ並みの快適な画面切り替えを実現しています。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.17 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.17</span>
        <span class="release-title">アニメーションの洗練・透明感の最適化・カラー視認性の全面向上</span>
        <span class="release-date">2026-06-07</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI &amp; デザイン (UI &amp; Design)
            </div>
            <ul>
                <li>
                    <span class="icon">✨</span>
                    <div>
                        <strong>アニメーションの滑らか化</strong>
                        <span class="detail">各種ボタン・モーダル・ウィジェットのトランジションを細かく見直し、より自然でなめらかな動きに調整しました。過剰な動きを排除しつつ、操作に対するフィードバックの質を高めています。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>透明感（グラスモーフィズム）の最適化と配色の視認性向上</strong>
                        <span class="detail">透明・ぼかし効果が強すぎて読みにくかった箇所を調整し、可読性と操作性を両立。カラーのコントラストと明るさを全体的に改善し、より分かりやすく洗練されたビジュアルに刷新しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.16 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.16</span>
        <span class="release-title">通知システムの SSE + Web Push ハイブリッド構成への移行</span>
        <span class="release-date">2026-06-07</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 &amp; 強化 (New Features &amp; Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">🔔</span>
                    <div>
                        <strong>SSE + Web Push ハイブリッド通知の実装</strong>
                        <span class="detail">従来の通知配信方式を全面刷新し、Server-Sent Events (SSE) とブラウザ標準の Web Push を組み合わせたハイブリッドアーキテクチャを採用。アプリがバックグラウンドにある場合でも確実に通知が届き、リアルタイム性とバッテリー効率を両立しています。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>通知の到達率と信頼性の向上</strong>
                        <span class="detail">接続断・ネットワーク不安定時のフォールバック処理を強化し、通知の取りこぼしを大幅に低減しました。サービスワーカーとの連携を最適化し、プッシュ通知の応答速度も向上しています。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.15 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.15</span>
        <span class="release-title">モダンWeb標準に基づいたダイアログの強化とプレミアムアニメーションの導入</span>
        <span class="release-date">2026-05-23</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 & 強化 (New Features & Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">✨</span>
                    <div>
                        <strong>宣言的クローズ (Light-dismiss) のサポート</strong>
                        <span class="detail">各種ダイアログ（設定、ギャラリー、ブロックリスト、ショートカット等）に <code>closedby="any"</code> 属性を追加。JSを使わずに背景（外側）をクリックするだけでスムーズに閉じられるモダンUXを実現しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>グローバルなJSフォールバックの実装</strong>
                        <span class="detail">Safariなど一部の <code>closedby</code> 未対応ブラウザでも背景クリックでダイアログが正しく閉じるよう、イベントデリゲーションを利用した軽量なフォールバックロジックをJS初期化時に追加しました。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI & デザイン (UI & Design)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>@starting-styleを用いたプレミアムアニメーション</strong>
                        <span class="detail">CSSの <code>@starting-style</code>、<code>transition-behavior: allow-discrete</code>、<code>overlay</code> プロパティを使用し、モーダルの開閉時における双方向の滑らかなフェード＆拡大縮小アニメーションをピュアCSSで実装。バックドロップ（背景）のぼかし効果も滑らかに遷移します。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">♿</span>
                    <div>
                        <strong>視覚的アクセシビリティ対応 (Reduced Motion)</strong>
                        <span class="detail">OSやブラウザでアニメーションの軽減設定 (<code>prefers-reduced-motion</code>) が有効な環境において、自動的に余分な動き（拡大縮小）を排除し、シンプルなフェードイン・フェードアウトにフォールバックするよう配慮しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.14 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.14</span>
        <span class="release-title">PWA 強化、通知モジュールの刷新、法的ドキュメントの整備</span>
        <span class="release-date">2026-05-09</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 & 強化 (New Features & Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">🔔</span>
                    <div>
                        <strong>通知システムのモジュール化</strong>
                        <span class="detail">通知機能を独立したモジュール (`notifications.js`) に分離し、プッシュ通知の安定性と拡張性を向上させました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">📄</span>
                    <div>
                        <strong>「SYCS について」と「プライバシーポリシー」の追加</strong>
                        <span class="detail">プロジェクトの詳細を紹介する About ページと、法的遵守のためのプライバシーポリシーページを新規公開しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🌐</span>
                    <div>
                        <strong>多言語対応の強化 (i18n)</strong>
                        <span class="detail">ロケールファイル (`en.json`, `ja.json`) を更新し、新機能に対応した翻訳を追加しました。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> PWA & 安定性 (PWA & Stability)
            </div>
            <ul>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>サービスワーカーの最適化</strong>
                        <span class="detail">キャッシュ戦略を刷新し、モジュール化された最新の CSS 群とメインスクリプトを事前キャッシュ対象に追加。オフライン時の読み込み速度と信頼性が大幅に向上しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>API ハンドラの堅牢化</strong>
                        <span class="detail">バックエンドの `Handler.php` におけるエラー処理と入出力の整合性をさらに強化しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.13 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.13</span>
        <span class="release-title">プライバシーポリシーページ、Aboutページ、利用規約ページの追加</span>
        <span class="release-date">2026-05-07</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能(New Feature)
            </div>
            <ul>
                <li>
                    <span class="icon">📝</span>
                    <div>
                        <strong>プライバシーポリシーページ、Aboutページ、利用規約ページの追加</strong>
                        <span class="detail">プライバシーポリシーページ、Aboutページ、利用規約ページを追加しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.12 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.12</span>
        <span class="release-title">カラーテーマ追加</span>
        <span class="release-date">2026-05-06</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI & デザイン (UI & Design)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>ナイトテーマカラーの追加</strong>
                        <span class="detail">ナイトテーマのカラーを追加しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.11 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.11</span>
        <span class="release-title">ユーザープロフィール設定の動作変更</span>
        <span class="release-date">2026-05-06</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> ユーザープロフィール設定システム改修
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>ユーザープロフィール設定モーダルの動作変更</strong>
                        <span class="detail">ユーザープロフィール設定モーダルの動作を変更しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.10 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.10</span>
        <span class="release-title">ランディングページのスタイル改修</span>
        <span class="release-date">2026-05-06</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI & デザイン (UI & Design)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>専用デザインシステム (landing.css)</strong>
                        <span class="detail">ランディングページ専用のスタイルシートを作成し、滑らかなアニメーション、Outfit/Inter フォントによる美麗なタイポグラフィ、そして一貫性のあるカラーパレットを実装しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.9 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.9</span>
        <span class="release-title">ランディングページ追加</span>
        <span class="release-date">2026-05-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 (New Feature)
            </div>
            <ul>
                <li>
                    <span class="icon">✨</span>
                    <div>
                        <strong>ランディングページ追加</strong>
                        <span class="detail">SYCSの魅力を最大限に伝えるための、モダンで洗練されたランディングページを新規公開しました。2パネル構成のレイアウトを採用し、プロジェクトの各機能を美しく紹介しています。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>専用デザインシステム (landing.css)</strong>
                        <span class="detail">ランディングページ専用のスタイルシートを作成し、滑らかなアニメーション、Outfit/Inter フォントによる美麗なタイポグラフィ、そして一貫性のあるカラーパレットを実装しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.8 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.8</span>
        <span class="release-title">APIの整合性向上とサービスワーカーの信頼性向上</span>
        <span class="release-date">2026-05-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 安定性 & 修正 (Stability & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">⚙️</span>
                    <div>
                        <strong>API レスポンスの整合性向上</strong>
                        <span class="detail">API リクエスト処理後に確実に実行を終了させることで、意図しない HTML が JSON レスポンスに混入する問題を修正しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔌</span>
                    <div>
                        <strong>サービスワーカーのキャッシュ処理を最適化</strong>
                        <span class="detail">HTTP/HTTPS プロトコルのみをキャッシュ対象とするよう制限し、ブラウザ拡張機能などに起因するエラーを防止しました。オフライン時の動作がより安定します。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.9 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.9</span>
        <span class="release-title">プレミアムランディングページの導入</span>
        <span class="release-date">2026-05-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 (New Feature)
            </div>
            <ul>
                <li>
                    <span class="icon">✨</span>
                    <div>
                        <strong>次世代ランディングページ (index.html) の導入</strong>
                        <span class="detail">SYCS の魅力を最大限に伝えるための、モダンで洗練されたランディングページを新規公開しました。2パネル構成のレイアウトを採用し、プロジェクトの各機能を美しく紹介しています。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>専用デザインシステム (landing.css)</strong>
                        <span class="detail">ランディングページ専用のスタイルシートを作成し、滑らかなアニメーション、Outfit/Inter フォントによる美麗なタイポグラフィ、そして一貫性のあるカラーパレットを実装しました。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善点 (Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">🚀</span>
                    <div>
                        <strong>レスポンシブ対応の強化</strong>
                        <span class="detail">PC からスマートフォンまで、あらゆるデバイスでプロジェクトの機能紹介が美しく表示されるよう最適化されています。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.7 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.7</span>
        <span class="release-title">ステータス選択 UI の刷新と視覚効果の改善</span>
        <span class="release-date">2026-05-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI & デザイン (UI & Design)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>ステータス選択ボックスのモダン化</strong>
                        <span class="detail">ステータス選択ドロップダウンのデザインを全面的に刷新しました。カスタムアイコンの導入、グラスモーフィズム（背景ぼかし）の適用、洗練されたホバー/フォーカスエフェクトにより、操作性と視認性が向上しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">✨</span>
                    <div>
                        <strong>視覚フィードバックの強化</strong>
                        <span class="detail">各種インタラクティブ要素のトランジションやシャドウを微調整し、よりプレミアムな使用感を実現しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.5 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.5</span>
        <span class="release-title">バックエンドの堅牢化とセキュリティ強化</span>
        <span class="release-date">2026-05-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> セキュリティ & 安定性 (Security & Stability)
            </div>
            <ul>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>API ハンドラのセキュリティ強化</strong>
                        <span class="detail">API 入力処理を専用のラッパーメソッドに統合し、グローバル変数への直接アクセスを排除することで、インジェクション攻撃などのリスクを低減しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔒</span>
                    <div>
                        <strong>プリペアドステートメントの適用拡大</strong>
                        <span class="detail">メッセージのピン留め解除や削除などの処理において、より一貫してプリペアドステートメントを使用するように改善し、SQL インジェクション対策を強化しました。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 修正 & 改善 (Fixes & Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">🐞</span>
                    <div>
                        <strong>お気に入り機能の読み込み不具合を修正</strong>
                        <span class="detail">一部の環境でお気に入り一覧が正しく表示されない問題を、イベントリスナーの最適化によって解決しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🏗️</span>
                    <div>
                        <strong>コードの品質向上</strong>
                        <span class="detail">バックエンドコードに型ヒントを導入し、エラーハンドリングを改善することで、システムの信頼性を高めました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.4 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.4</span>
        <span class="release-title">お気に入り管理機能の強化と UI の洗練</span>
        <span class="release-date">2026-05-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">⭐</span>
                    <div>
                        <strong>お気に入り管理ページの追加</strong>
                        <span class="detail">お気に入りに登録したスレッドを一覧で確認・管理できる専用ページを追加しました。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善 & UI (Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">📱</span>
                    <div>
                        <strong>ヘッダーコンポーネントの共通化</strong>
                        <span class="detail">各ページで一貫した操作を提供するため、ヘッダーを共通コンポーネント化しました。スレッド内での検索、添付ファイル一覧、ピン留めメッセージへのアクセスがよりスムーズになります。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🌍</span>
                    <div>
                        <strong>多言語対応の強化</strong>
                        <span class="detail">お気に入り機能に関連する翻訳リソース（日・英・中）を拡充しました。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 修正 & 内部改善 (Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">🔧</span>
                    <div>
                        <strong>API 通信の最適化</strong>
                        <span class="detail">お気に入り操作の API 通信を JSON 形式に統一し、安全性を向上させました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🐞</span>
                    <div>
                        <strong>DM ヘッダー表示の修正</strong>
                        <span class="detail">ダイレクトメッセージ画面で相手のユーザー名が正しく表示されない問題を修正しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.3 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.3</span>
        <span class="release-title">チャット機能の全面的な修正と強化</span>
        <span class="release-date">2026-05-03</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-red"></span> バグ修正 (Bug Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">📌</span>
                    <div>
                        <strong>ピン留め機能の完全な修正</strong>
                        <span class="detail">メッセージのピン留め・解除ボタンが正しく動作しなかった問題を解消。ピン留め後にメッセージ一覧が自動更新されるようになり、ピン留め一覧モーダルもスレッドIDを正しく渡してAPIを呼ぶよう修正しました。また発信者名・日時も表示されクリックで当該メッセージへジャンプできるようになりました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">😊</span>
                    <div>
                        <strong>リアクション機能の修正</strong>
                        <span class="detail">絵文字リアクションピッカーが表示されなかった問題を修正。フローティングな絵文字ピッカー（👍❤️😂 など10種類）が表示され、選択するとリアクションのトグルが正しく機能するようになりました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">✏️</span>
                    <div>
                        <strong>メッセージ編集・削除の修正</strong>
                        <span class="detail">編集ボタンがインライン編集エリアを表示するように修正。削除ボタンも確認ダイアログ付きで正しく動作するよう修正しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">↩️</span>
                    <div>
                        <strong>返信機能の修正</strong>
                        <span class="detail"><code>reply_to_id</code> がAPIに送信されていなかった問題を修正。バックエンドでも <code>reply_to_id</code> をINSERT文に正しく保存するよう改善しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔍</span>
                    <div>
                        <strong>メッセージ検索・添付ファイル一覧の修正</strong>
                        <span class="detail">検索クエリのAPIパス二重化バグを修正。添付ファイルギャラリーでフィールド名不一致（<code>item.path</code> → <code>item.attachment_path</code>）によって画像が表示されなかった問題を解消しました。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善 (Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">⌨️</span>
                    <div>
                        <strong>キーボードショートカットの実装</strong>
                        <span class="detail"><code>Alt+P</code> でピン留めメッセージ一覧、<code>/</code> で検索フォーカス、<code>Alt+Shift+?</code> でショートカット一覧を表示できるようになりました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🗄️</span>
                    <div>
                        <strong>メッセージ取得APIの強化</strong>
                        <span class="detail"><code>getMessages</code> APIでリアクション・返信元ユーザー名・オンラインステータスを一括取得するよう改善。不要な追加リクエストを排除しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔗</span>
                    <div>
                        <strong>状態管理の改善</strong>
                        <span class="detail">スレッド切り替え時に <code>window.SYCS_CONFIG.currentThreadId</code> を同期更新するよう修正。各モジュールが常に正しいスレッドIDを参照できるようになりました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.0 ===== -->

<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.0</span>
        <span class="release-title">大規模なアーキテクチャの刷新とパフォーマンス向上</span>
        <span class="release-date">2026-05-02</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 安定性 & セキュリティ (Stability & Security)
            </div>
            <ul>
                <li>
                    <span class="icon">🏗️</span>
                    <div>
                        <strong>バックエンドAPIの分離と堅牢化</strong>
                        <span class="detail">APIのルーティングとデータベース初期化ロジックを専用のハンドラクラス（<code>Handler.php</code>、<code>db_init.php</code>）に抽出し、バックエンドの保守性とセキュリティを大幅に向上させました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚙️</span>
                    <div>
                        <strong>フロントエンドのモジュール化</strong>
                        <span class="detail">巨大化していた <code>index.js</code> を ES6 モジュール（<code>api.js</code>、<code>chat.js</code>、<code>ui.js</code> など）に分割し、コードの保守性と読み込みパフォーマンスを最適化しました。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善 & リファクタリング (Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>CSSのコンポーネント化</strong>
                        <span class="detail">単一の <code>style.css</code> を論理的なモジュール（<code>layout.css</code>、<code>components.css</code>、<code>modals.css</code> など）に分割・再構築し、UIの拡張性を高めました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧱</span>
                    <div>
                        <strong>UIテンプレートのコンポーネント化</strong>
                        <span class="detail"><code>index.php</code> に集中していたHTML構造を <code>sidebar.php</code> や <code>modals.php</code> などのインクルードファイルに分割し、UIの一貫性と開発効率を向上させました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.38 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.38</span>
        <span class="release-title">UI/UX の洗練と多言語対応の拡充</span>
        <span class="release-date">2026-04-25</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善 & 修正 (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>ピン留めメッセージのアイコン刷新</strong>
                        <span class="detail">絵文字から専用の SVG アイコン (`pin.svg`) に変更し、UI の一貫性とプロフェッショナルな外観を向上させました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">👤</span>
                    <div>
                        <strong>プロフィール編集機能の強化</strong>
                        <span class="detail">レイアウト選択やバナー画像設定のラベルを多言語対応（i18n）化し、コード内のハードコードされたテキストを排除しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🌍</span>
                    <div>
                        <strong>多言語リソースの拡充 (JA/EN/ZH)</strong>
                        <span class="detail">時計ウィジェットの表示切替（デジタル/アナログ）や ToDo リストの操作用テキストを追加し、すべての対応言語で一貫したユーザー体験を提供します。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>フロントエンドコードのクリーンアップ</strong>
                        <span class="detail">テンプレートファイル内のインラインテキストを言語定数に置き換え、保守性を高めました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.36 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.36</span>
        <span class="release-title">レンダリングエンジンのセキュリティ強化 (XSS 対策)</span>
        <span class="release-date">2026-04-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 安定性 & セキュリティ (Stability & Security)
            </div>
            <ul>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>描画ロジックの刷新による XSS 対策</strong>
                        <span class="detail">`innerHTML` の使用を大幅に削減し、`createElement` と `textContent` を使用した安全な DOM 生成方式に移行しました。これにより、悪意のあるスクリプトの混入を物理的に防ぎます。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔍</span>
                    <div>
                        <strong>escapeHTML 関数の最適化</strong>
                        <span class="detail">特殊文字の処理をより確実に行うようロジックを改善し、データの整合性と安全性を高めました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>ウィジェット表示の安定化</strong>
                        <span class="detail">通知リスト、ファイル一覧、ToDo リストのレンダリングを近代的な手法に統一しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.35 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.35</span>
        <span class="release-title">UI/UX デザインの微調整とモーダルの視認性向上</span>
        <span class="release-date">2026-04-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善 & 修正 (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>モーダルウィンドウのサイズと背景色の最適化</strong>
                        <span class="detail">`group-creation-modal` や `keyboard-shortcuts-modal` の背景色およびサイズを調整し、視認性と操作性を向上させました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔘</span>
                    <div>
                        <strong>ボタンの角丸と幅の調整による操作性向上</strong>
                        <span class="detail">プライベートボタンやチャットヘッダー内のボタンの `border-radius` と `width` を微調整し、よりモダンで使いやすいデザインに更新しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.34 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.34</span>
        <span class="release-title">Markdown レンダリングエンジンのセキュリティ強化 (XSS対策)</span>
        <span class="release-date">2026-04-01</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 安定性 & セキュリティ (Stability & Security)
            </div>
            <ul>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>レンダリング方式の刷新による XSS 対策</strong>
                        <span class="detail">`innerHTML` を完全に廃止し、`DocumentFragment` と `createTextNode` を使用して DOM ノードを直接生成する方式に移行しました。これにより、悪意のあるスクリプトの実行を物理的に遮断し、安全なチャット体験を提供します。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔍</span>
                    <div>
                        <strong>フォーマット適用の厳格化</strong>
                        <span class="detail">コードブロック内での二重フォーマットの適用を防止するロジックを導入。コードの可読性を損なうことなく、確実なレンダリングを実現しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>メッセージ表示の最適化</strong>
                        <span class="detail">`replaceChildren` メソッドを採用し、最新のブラウザ標準に則った高速で安全なコンテンツ更新を実装しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.33 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.33</span>
        <span class="release-title">Markdown レンダリングとコードシンタックスハイライトへの対応</span>
        <span class="release-date">2026-03-30</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">📝</span>
                    <div>
                        <strong>Markdown / リッチテキスト レンダリング</strong>
                        <span class="detail">太字 (**bold**)、斜体 (*italic*)、下線 (__underline__)、打ち消し線 (~~strike~~)、および引用 (blockquote) に対応しました。チャット内での柔軟な表現が可能になります。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">💻</span>
                    <div>
                        <strong>コードブロック シンタックスハイライト (Highlight.js)</strong>
                        <span class="detail">`highlight.js` を導入し、複数言語のコードブロックに対してシンタックスハイライトを適用しました。開発者間のコード共有がより見やすくなります。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔗</span>
                    <div>
                        <strong>スマートなメッセージフォーマット</strong>
                        <span class="detail">URL の自動リンク化やメンション処理の堅牢性を向上させ、メッセージの視認性を高めました。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善 (Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>UI デザインの微調整</strong>
                        <span class="detail">PWA インストールボタンのコントラストを改善し、視認性を高めました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.28 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.28</span>
        <span class="release-title">WebRTC 接続の安定化と通知システムの堅牢化</span>
        <span class="release-date">2026-03-27</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善 & 修正 (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">🎥</span>
                    <div>
                        <strong>WebRTC シグナリングの安定性向上</strong>
                        <span class="detail">ICE candidate の保留キューを実装しました。これにより、ビデオ会議の接続確立時に candidate が取りこぼされる問題を解消し、より確実な接続を可能にしました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>通知エンジンのエラーハンドリング強化</strong>
                        <span class="detail">リアルタイム通知およびプッシュ通知の送信処理に HTTP ステータスコードチェックを導入しました。バックエンド連携の失敗を正確に検知し、ログに出力します。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔐</span>
                    <div>
                        <strong>設定の堅牢化とコード整理</strong>
                        <span class="detail">シークレットキーの参照ロジックを最適化し、環境設定の不備に対処しました。また、不要なファイル読み込みを削除しパフォーマンスを向上させました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.27 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.27</span>
        <span class="release-title">アナログ時計ウィジェットの大幅な機能強化</span>
        <span class="release-date">2026-03-22</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 新機能 (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">⌚</span>
                    <div>
                        <strong>アナログ時計のスイープ運針とサブダイヤルの実装</strong>
                        <span class="detail"><code>requestAnimationFrame</code> を採用し、秒針のスムーズな動きを実現しました。また、24時間計、曜日計、独立秒計のサブダイヤルを実機能として実装し、より本格的な時計体験を提供します。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.26 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.26</span>
        <span class="release-title">システム基盤の強化と多言語対応の最適化</span>
        <span class="release-date">2026-03-21</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善 & 修正 (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">🔐</span>
                    <div>
                        <strong>セッション・Cookie管理の堅牢化</strong>
                        <span class="detail">バックエンドのセッションとCookie処理を刷新し、セキュリティと接続の安定性を大幅に向上させました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🌍</span>
                    <div>
                        <strong>多言語対応 (i18n) プロセスの最適化</strong>
                        <span class="detail">言語切り替えロジックを改善し、よりスムーズなユーザー体験を提供します。また、中国語（簡体字）リソースを全面的に更新しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>フロントエンドのクリーンアップ</strong>
                        <span class="detail">メイン画面の構造を整理し、不要なコードを削除することでパフォーマンスを最適化しました。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.23 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.23</span>
        <span class="release-title">アナログ時計ウィジェットの追加</span>
        <span class="release-date">2026-03-20</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 新機能 (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">⌚</span>
                    <div>
                        <strong>アナログ時計ウィジェットの追加</strong>
                        <span class="detail">
                            ホーム画面にアナログ時計ウィジェットを追加しました。時刻表示、日付表示、曜日表示、秒針表示、サブダイヤル（12時、3時、6時、9時位置）を備えた高機能な時計です。
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.20 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.20</span>
        <span class="release-title">WebRTC シグナリングの Socket.IO 移行と高速化</span>
        <span class="release-date">2026-03-18</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善 & 修正 (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>WebRTC シグナリングのリアルタイム化</strong>
                        <span class="detail">
                            ビデオ会議のシグナリングを従来の HTTP ポーリングから Socket.IO による双方向通信に移行しました。これにより、接続の遅延が大幅に短縮され、サーバー負荷も軽減されました。
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.19 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.19</span>
        <span class="release-title">ログイン画面のUI調整と微細なクリーンアップ</span>
        <span class="release-date">2026-03-18</span>
    </div>
    <div class="release-body">

        <!-- UI/UX 向上 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> UI/UX 向上 (UI/UX)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>ログインカードのレイアウト調整</strong>
                        <span class="detail">
                            ログイン画面のカード最大幅を <code>500px</code> に拡大し、よりゆとりのあるモダンなレイアウトに調整しました。
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>スタイルシートのクリーンアップ</strong>
                        <span class="detail">
                            <code>style-index.css</code> 内の冗長な背景グラデーション指定を削除し、コードの保守性を向上させました。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

    </div>
</article>

<!-- ===== v1.2.18 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.18</span>
        <span class="release-title">モバイル表示の最適化とUIのクリーンアップ</span>
        <span class="release-date">2026-03-14</span>
    </div>
    <div class="release-body">

        <!-- UI/UX 向上 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> UI/UX 向上 (UI/UX)
            </div>
            <ul>
                <li>
                    <span class="icon">📱</span>
                    <div>
                        <strong>モバイルレスポンシブの強化</strong>
                        <span class="detail">
                            スマートフォン表示において、ヘッダー要素の整理や不要なボタン（ビデオ、ピン留め等）の非表示化を行い、チャット画面の視認性を向上させました。
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>レイアウトのクリーンアップ</strong>
                        <span class="detail">
                            CSSの整理（<code>flex-grow</code>の適正化等）およびログイン画面のレスポンシブ対応を強化し、各デバイスで一貫した操作感を実現しました。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

    </div>
</article>

<!-- ===== v1.2.16 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.16</span>
        <span class="release-title">サーバーサイドの堅牢化と冗長なコードの整理</span>
        <span class="release-date">2026-03-11</span>
    </div>
    <div class="release-body">

        <!-- 安定性 & 信頼性 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 安定性 & 信頼性 (Stability & Reliability)
            </div>
            <ul>
                <li>
                    <span class="icon">🔐</span>
                    <div>
                        <strong>リアルタイムサーバーの認証同期の改善</strong>
                        <span class="detail">
                            <code>index.php</code> および <code>server.js</code> において、<code>REALTIME_SECRET_KEY</code> の読み込みフローを改善。
                            環境変数 <code>SECRET_KEY</code> へのフォールバック処理と、未設定時のエラーハンドリングを追加し、システム間の認証同期の確実性を高めました。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

        <!-- 改善 & リファクタリング -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善 & リファクタリング (Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>フロントエンドのエントリポイント最適化</strong>
                        <span class="detail">
                            <code>index.php</code> において、既に読み込み済みの冗長な <code>require_once</code> 命令を削除し、内部構造を整理しました。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

    </div>
</article>

<!-- ===== v1.2.14 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.14</span>
        <span class="release-title">多言語対応 (i18n) の導入とリリースノートの動的化</span>
        <span class="release-date">2026-03-10</span>
    </div>
    <div class="release-body">

        <!-- 新機能 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">🌍</span>
                    <div>
                        <strong>多言語対応 (i18n) 基盤の導入 (<code>I18n.php</code>)</strong>
                        <span class="detail">
                            システム全体に多言語対応基盤を導入し、日本語と英語の切り替えをサポートしました。
                            言語設定はセッションおよびクッキーにより永続化され、ログイン画面やメイン画面からワンクリックで切り替え可能です。
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🚀</span>
                    <div>
                        <strong>リリースノートの動的システム化 (PHP)</strong>
                        <span class="detail">
                            従来の静的な HTML 形式から、PHP ベースの動的なシステムへ刷新。
                            閲覧者の言語設定に合わせて、日本語と英語の内容が自動的に出し分けられるようになりました。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

        <!-- UI/UX 向上 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> UI/UX 向上 (UI/UX)
            </div>
            <ul>
                <li>
                    <span class="icon">🔘</span>
                    <div>
                        <strong>言語セレクターの実装</strong>
                        <span class="detail">
                            ログイン、新規登録、メイン画面のヘッダーに言語切り替え用のセレクターを設置。
                            シームレスな体験を提供するためのインターフェースを強化しました。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

    </div>
</article>

<!-- ===== v1.2.13 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.13</span>
        <span class="release-title">ビデオ会議機能の安定性向上と UI 改善</span>
        <span class="release-date">2026-03-09</span>
    </div>
    <div class="release-body">

        <!-- 改善 & 修正 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善 & 修正 (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">🎥</span>
                    <div>
                        <strong>WebRTC ビデオ会議の安定性向上</strong>
                        <span class="detail">
                            <code>webrtc.js</code> および <code>meetings.php</code> において、ストリーム取得処理を堅牢化。トラック単体での
                            MediaStream 生成に対応し、接続確立時のビデオ表示の確実性を高めました。
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">📱</span>
                    <div>
                        <strong>モバイルブラウザでの再生互換性改善</strong>
                        <span class="detail">
                            ビデオ要素に <code>playsinline</code> 属性を明示的に付与し、iOS
                            等のモバイルブラウザでビデオが強制全画面化されず、インラインで正常に再生されるように修正しました。
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧱</span>
                    <div>
                        <strong>ビデオグリッドのレイアウト一貫性向上</strong>
                        <span class="detail">
                            <code>index.php</code> と <code>meetings.php</code> の間でビデオ表示用クラス名を
                            <code>video-grid</code> に統合し、どちらの画面でも一貫したグリッド表示が行われるように改善しました。
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">📡</span>
                    <div>
                        <strong>シグナリング同期の改善</strong>
                        <span class="detail">
                            シグナリング処理に非同期制御を導入し、複数の同時参加者がいる環境下での接続確立フローをよりスムーズにしました。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

    </div>
</article>

<!-- ===== v1.2.12 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.12</span>
        <span class="release-title">レート制限キャッシュの自動クリーンアップと容量制限</span>
        <span class="release-date">2026-03-06</span>
    </div>
    <div class="release-body">

        <!-- 改善 & リファクタリング -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善 & リファクタリング (Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>レート制限キャッシュの自動クリーンアップ</strong>
                        <span class="detail">
                            <code>FileRateLimiter.php</code>
                            において、1時間ごとの自動クリーンアッププロセスを実装。期限切れのデータを定期的に削除し、さらにキャッシュディレクトリの合計サイズが100MBを超過した場合は古いファイルから順次削除して80MB程度まで縮小する仕組みを追加しました。これにより、ディスク容量の圧迫を防ぎます。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

    </div>
</article>

<!-- ===== v1.2.11 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.11</span>
        <span class="release-title">レート制限の導入によるシステム保護機能の強化</span>
        <span class="release-date">2026-03-06</span>
    </div>
    <div class="release-body">

        <!-- セキュリティ & 安定性 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> セキュリティ & 安定性 (Security & Stability)
            </div>
            <ul>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>API レート制限 (Rate Limiting) の実装</strong>
                        <span class="detail">
                            バックエンドに新たに <code>RateLimiter.php</code> (Redisベース) およびフォールバック用の
                            <code>FileRateLimiter.php</code> を追加しました。位置情報送信 API (<code>update_location</code>)
                            に対して、IPアドレスおよびユーザーIDごとのリクエスト上限を設けることで、システムへの負荷集中や不正なスパム送信を防止し、安定したサービス提供を実現します。
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">⏱️</span>
                    <div>
                        <strong>クライアント側でのリクエスト制御最適化</strong>
                        <span class="detail">
                            フロントエンドの位置情報取得処理 (<code>locate.js</code>) において、最小更新間隔 (5秒) を強制する仕組みを導入。無駄な API
                            リクエストの発生をクライアント側でも抑制し、サーバーとクライアント双方のリソース消費を最適化しました。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

    </div>
</article>

<!-- ===== v1.2.10 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.10</span>
        <span class="release-title">テーマ対応の強化と UI 洗練</span>
        <span class="release-date">2026-03-05</span>
    </div>
    <div class="release-body">

        <!-- UI/UX 向上 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> UI/UX 向上 (UI/UX Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>CSS 変数による一貫したテーマ対応</strong>
                        <span class="detail">
                            <code>style-index.css</code> および <code>style.css</code>
                            において、文字色や背景色のハードコード指定を排除しました。
                            <code>--text-primary</code> や <code>--text-secondary</code> などの CSS
                            変数を全面的に適用し、ダークテーマ・ライトテーマ切り替え時の視認性とデザインの一貫性を大幅に向上させました。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

    </div>
</article>

<!-- ===== v1.2.9 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.9</span>
        <span class="release-title">外部サービス連携の安定性向上とロガーの強化</span>
        <span class="release-date">2026-03-05</span>
    </div>
    <div class="release-body">

        <!-- 安定性 & 信頼性 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 安定性 & 信頼性 (Stability & Reliability)
            </div>
            <ul>
                <li>
                    <span class="icon">🔄</span>
                    <div>
                        <strong>外部API通信のリトライ機構 (<code>RetryHandler.php</code>)</strong>
                        <span class="detail">
                            Outlook、Discord、Google などの外部サービス連携において、一時的なネットワークエラーやサーバーエラーが発生した際に自動で再試行する機能を実装。
                            指数バックオフアルゴリズムを採用し、サーバー負荷を抑えつつ連携の成功率を高めました。
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>ファイルベースのキャッシュシステム (<code>Cache.php</code>)</strong>
                        <span class="detail">
                            外部APIから取得したユーザー情報などをキャッシュする仕組みを導入。
                            不要なAPIリクエストを削減し、ページ読み込み速度の向上とAPIレートリミット回避を実現しました。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

        <!-- 改善 & リファクタリング -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善 & リファクタリング (Improvements & Refactoring)
            </div>
            <ul>
                <li>
                    <span class="icon">📝</span>
                    <div>
                        <strong>ログの自動ローテーション機能</strong>
                        <span class="detail">
                            <code>Logger.php</code> にファイルサイズベースのログローテーション機能を追加。
                            古いログを自動でバックアップ・クリーンアップし、ディスク容量の圧迫を防ぐことで長期運用時の安全性を向上させました。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

    </div>
</article>

<!-- ===== v1.2.8 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.8</span>
        <span class="release-title">エラーハンドリング基盤の新設とロギングシステムの実装</span>
        <span class="release-date">2026-03-05</span>
    </div>
    <div class="release-body">

        <!-- 新機能 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">🏗️</span>
                    <div>
                        <strong>バックエンドエラーハンドリング基盤ের整備 (<code>ErrorHandler.php</code>)</strong>
                        <span class="detail">
                            <code>SecurityException</code>、<code>ValidationException</code>、<code>DatabaseException</code>
                            などの型付き例外クラスと、
                            JSON 形式の統一エラーレスポンスを生成する <code>ErrorResponse</code> クラスを新設。
                            エラー原因の種別を明確にし、適切なメッセージをユーザーに返せる堅牢な基盤を構築しました。
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">📋</span>
                    <div>
                        <strong>サーバーサイドロガーの実装 (<code>Logger.php</code>)</strong>
                        <span class="detail">
                            <code>DEBUG</code>〜<code>CRITICAL</code> の5段階ログレベルをサポートする <code>Logger</code>
                            クラスを新設。
                            ログレベルごとに <code>logs/</code> 配下の個別ファイルへ出力し、問題発生時の追跡とデバッグを容易にします。
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔌</span>
                    <div>
                        <strong>フロントエンドAPIクライアントの整備 (<code>errorHandler.js</code>)</strong>
                        <span class="detail">
                            タイムアウト検知、ネットワークエラー自動判定、CSRF トークン自動付与、構造化エラーメッセージ表示を備えた
                            <code>APIClient</code> クラスを新設。フロントエンドのAPI通信品質と信頼性を向上させました。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

    </div>
</article>
