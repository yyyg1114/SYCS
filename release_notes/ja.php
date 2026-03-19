<?php

/**
 * Release Notes - Japanese Content
 * 
 * This file is included by release_notes.php
 */
?>

<!-- ===== v1.2.19 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.19</span>
        <span class="release-title">アナログ/デジタル時計ウィジェットの追加</span>
        <span class="release-date">2026-03-19</span>
    </div>
    <div class="release-body">
        <!-- 新機能 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新機能 (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">⌚</span>
                    <div>
                        <strong>時計ウィジェットの実装</strong>
                        <span class="detail">
                            ダッシュボードにアナログ時計とデジタル時計を切り替え可能なウィジェットを追加しました。日付表示と滑らかな秒針の動きに対応しています。
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
