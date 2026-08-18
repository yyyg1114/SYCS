<?php

/**
 * Release Notes - Japanese Content
 * 
 * This file is included by release_notes.php
 */
?>

<!-- ===== v2.2.27 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.27</span>
        <span class="release-title">セッションセキュリティ: リスクベース検証、定期再生成、モバイル対応</span>
        <span class="release-date">2026-08-18</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> セキュリティと信頼性
            </div>
            <ul>
                <li>
                    <span class="icon">🔐</span>
                    <div>
                        <strong>リスクベースセッション検証 (IP アドレス緩和)</strong>
                        <span class="detail">モバイルユーザーと VPN ユーザーを除外していた厳格な IP 検証を廃止し、User-Agent の変化のみをリスク判定対象とし、ソフト警告で対応するよう改善しました。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔄</span>
                    <div>
                        <strong>定期的なセッション ID 再生成 (15分間隔)</strong>
                        <span class="detail">セッションを 15 分ごとに再生成し、セッション固定攻撃を低減し、ハイジャック被害時間窓を縮小します。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚠️</span>
                    <div>
                        <strong>User-Agent 変化検知とログ記録</strong>
                        <span class="detail">User-Agent が変わった場合 (ブラウザ切り替え)、警告ログを記録して再生成を推奨し、ユーザーフローを損なわないようにします。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>


<!-- ===== v2.2.26 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.26</span>
        <span class="release-title">AES-256-GCM 荳贋ｽ阪、EAD 隱崎ｨｼ莉倅ｸ倶ｽ阪∽ｸ贋ｽ咲巨謦・ｧ</span>
        <span class="release-date">2026-08-18</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 繧ｻ繧ｭ繝･繝ｪ繝・ぅ蠑ｷ蛹・
            </div>
            <ul>
                <li>
                    <span class="icon">柏</span>
                    <div>
                        <strong>AES-256-GCM (AEAD) 證怜捷蛹悶・蟆主・</strong>
                        <span class="detail">AES-256-CBC 縺九ｉ AES-256-GCM 縺ｫ荳贋ｽ阪＠縲∬ｪ崎ｨｼ莉俶囓蜿ｷ縺ｧ謾ｹ縺悶ｓ讀懷・繧貞庄閭ｽ縺ｫ縺励√ョ繝ｼ繧ｿ荳險域ｧ繧堤｢ｺ菫昴＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">笨・/span>
                    <div>
                        <strong>繝ｬ繧ｬ繧ｷ繝ｼ CBC 繝・・繧ｿ縺ｨ縺ｮ蠕梧婿莠呈鋤諤ｧ</strong>
                        <span class="detail">繝舌・繧ｸ繝ｧ繝ｳ繝槭・繧ｫ繝ｼ (v2:: 繝励Ξ繝輔ぅ繝・け繧ｹ GCM 逕ｨ縲√Ξ繧ｬ繧ｷ繝ｼ CBC 縺ｯ謗･鬆ｭ霎槭↑縺・ 繧剃ｽｿ逕ｨ縺励※譌｢蟄俶囓蜿ｷ蛹悶ョ繝ｼ繧ｿ繧定・蜍慕ｧｻ陦後〒縺阪ｋ讒区・繧貞ｮ溯｣・＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">泊</span>
                    <div>
                        <strong>ENCRYPTION_KEY 迺ｰ蠅・､画焚險ｭ螳・/strong>
                        <span class="detail">.env.example 縺ｫ ENCRYPTION_KEY 繧定ｿｽ蜉縺励∝ｮ牙・縺ｪ繧ｭ繝ｼ逕滓・譁ｹ豕輔・繝峨く繝･繝｡繝ｳ繝・・繧ｷ繝ｧ繝ｳ繧定ｿｽ蜉縺励∪縺励◆ (謗ｨ螂ｨ 64譁・ｭ嶺ｻ･荳翫・ 16 騾ｲ謨ｰ)縲・/span>
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
        <span class="release-title">繝帙Ρ繧､繝医Μ繧ｹ繝医・繝ｼ繧ｹ SVG 繧ｵ繝九ち繧､繧ｼ繝ｼ繧ｷ繝ｧ繝ｳ縲々XE 蟇ｾ遲門ｼｷ蛹悶→繧ｻ繧ｭ繝･繝ｪ繝・ぅ遑ｬ蛹・/span>
        <span class="release-date">2026-08-18</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 繧ｻ繧ｭ繝･繝ｪ繝・ぅ縺ｨ蝣・欧諤ｧ
            </div>
            <ul>
                <li>
                    <span class="icon">孱・・/span>
                    <div>
                        <strong>繝帙Ρ繧､繝医Μ繧ｹ繝医・繝ｼ繧ｹ SVG 繧ｿ繧ｰ縺ｨ螻樊ｧ繝輔ぅ繝ｫ繧ｿ繝ｪ繝ｳ繧ｰ</strong>
                        <span class="detail">SVG 繧ｵ繝九ち繧､繧ｼ繝ｼ繧ｷ繝ｧ繝ｳ譁ｹ蠑上ｒ繝悶Λ繝・け繝ｪ繧ｹ繝医°繧峨・繝ｯ繧､繝医Μ繧ｹ繝医↓螟画峩縺励∬ｨｱ蜿ｯ縺輔ｌ繧句ｮ牙・縺ｪ繧ｿ繧ｰ (svg, path, circle, text 縺ｪ縺ｩ) 縺ｨ螻樊ｧ繧呈・遉ｺ逧・↓謖・ｮ壹＠縺ｦ繝舌う繝代せ謾ｻ謦・ｒ髦ｲ豁｢縺励∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">白</span>
                    <div>
                        <strong>XXE (XML 螟夜Κ繧ｨ繝ｳ繝・ぅ繝・ぅ) 蟇ｾ遲悶・蠑ｷ蛹・/strong>
                        <span class="detail">PHP &lt; 8.0 (libxml_disable_entity_loader 菴ｿ逕ｨ) 縺ｨ PHP 8+ (LIBXML_NONET 繝輔Λ繧ｰ) 縺ｮ荳｡譁ｹ縺ｧ繧ｨ繝ｳ繝・ぅ繝・ぅ隱ｭ縺ｿ霎ｼ縺ｿ繧帝亟豁｢縺励∝､夜Κ繝ｪ繧ｽ繝ｼ繧ｹ繧｢繧ｯ繧ｻ繧ｹ繧偵ヶ繝ｭ繝・け縺励∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">迫</span>
                    <div>
                        <strong>href 縺ｨ xlink:href 縺ｮ讀懆ｨｼ蜴ｳ譬ｼ蛹・/strong>
                        <span class="detail">SVG 繝ｪ繝ｳ繧ｯ縺ｯ蜀・Κ蜿ら・ (#) 縺ｾ縺溘・遨ｺ縺ｮ蛟､縺ｮ縺ｿ繧定ｨｱ蜿ｯ縺吶ｋ繧医≧縺ｫ縺ｪ繧翫∝､夜Κ URL 繧・・繝ｭ繝医さ繝ｫ繝吶・繧ｹ縺ｮ謾ｻ謦・ｒ繝悶Ο繝・け縺励∪縺吶・/span>
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
        <span class="release-title">繝｡繝・そ繝ｼ繧ｸ驕ｸ謚槭Δ繝ｼ繝峨∬､・焚蜑企勁縲∽ｼ夊ｩｱ邂｡逅・・蠑ｷ蛹・/span>
        <span class="release-date">2026-08-09</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 譁ｰ讖溯・ &amp; 蠑ｷ蛹・
            </div>
            <ul>
                <li>
                    <span class="icon">ｧｹ</span>
                    <div>
                        <strong>繝｡繝・そ繝ｼ繧ｸ驕ｸ謚槭Δ繝ｼ繝・/strong>
                        <span class="detail">繝√Ε繝・ヨ蜀・〒隍・焚縺ｮ閾ｪ蛻・・繝｡繝・そ繝ｼ繧ｸ繧偵メ繧ｧ繝・け繝懊ャ繧ｯ繧ｹ縺ｧ驕ｸ謚槭〒縺阪ｋ譁ｰ縺励＞繝｢繝ｼ繝峨ｒ霑ｽ蜉縺励ヾhift 驕ｸ謚槭↓繧ょｯｾ蠢懊＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">卵・・/span>
                    <div>
                        <strong>隍・焚繝｡繝・そ繝ｼ繧ｸ蜑企勁</strong>
                        <span class="detail">驕ｸ謚槭＠縺溘Γ繝・そ繝ｼ繧ｸ繧剃ｸ諡ｬ縺ｧ蜑企勁縺ｧ縺阪ｋ繧医≧縺ｫ縺ｪ繧翫∫｢ｺ隱阪・繝ｭ繝ｳ繝励ヨ莉倥″縺ｧ莨夊ｩｱ縺ｮ謨ｴ逅・ｒ繧医ｊ邁｡蜊倥↓縺励∪縺励◆縲・/span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI &amp; 逕溽肇諤ｧ
            </div>
            <ul>
                <li>
                    <span class="icon">笨・/span>
                    <div>
                        <strong>驕ｸ謚槭ヤ繝ｼ繝ｫ繝舌・縺ｨ繧｢繧ｯ繧ｷ繝ｧ繝ｳ繧ｳ繝ｳ繝医Ο繝ｼ繝ｫ</strong>
                        <span class="detail">縺吶∋縺ｦ驕ｸ謚槭・∈謚櫁ｧ｣髯､縲√く繝｣繝ｳ繧ｻ繝ｫ繧貞ｙ縺医◆繧ｳ繝ｳ繝・く繧ｹ繝医い繧ｯ繧ｷ繝ｧ繝ｳ繝舌・繧定ｿｽ蜉縺励∬､・焚繝｡繝・そ繝ｼ繧ｸ縺ｮ蜃ｦ逅・ｒ繧ｹ繝繝ｼ繧ｺ縺ｫ縺励∪縺励◆縲・/span>
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
        <span class="release-title">Google OAuth 隱崎ｨｼ邨ｱ蜷医∫腸蠅・､画焚 (.env) 邂｡逅・・蟆主・縲√そ繧ｭ繝･繝ｪ繝・ぅ蠑ｷ蛹・/span>
        <span class="release-date">2026-07-29</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 譁ｰ讖溯・ &amp; 蠑ｷ蛹・(New Features &amp; Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">泊</span>
                    <div>
                        <strong>Google OAuth 2.0 邨ｱ蜷医→繧ｽ繝ｼ繧ｷ繝｣繝ｫ繝ｭ繧ｰ繧､繝ｳ蟇ｾ蠢・/strong>
                        <span class="detail">Google 繧ｯ繝ｩ繧､繧｢繝ｳ繝・ID / 繧ｷ繝ｼ繧ｯ繝ｬ繝・ヨ繧堤畑縺・◆ Google OAuth 2.0 隱崎ｨｼ繝輔Ο繝ｼ繧堤ｵｱ蜷医＠縲∝ｮ牙・縺九▽繧ｹ繝繝ｼ繧ｺ縺ｪ繧ｽ繝ｼ繧ｷ繝｣繝ｫ繝ｭ繧ｰ繧､繝ｳ縺ｫ蟇ｾ蠢懊＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">笞呻ｸ・/span>
                    <div>
                        <strong>迺ｰ蠅・､画焚 (.env) 繝ｭ繝ｼ繝繝ｼ縺ｮ險ｭ螳・/strong>
                        <span class="detail">API 繧ｭ繝ｼ繧・推遞ｮ隱崎ｨｼ骰ｵ縺ｪ縺ｩ縺ｮ讖溷ｯ・ュ蝣ｱ繧貞ｮ牙・縺ｫ邂｡逅・☆繧九◆繧・EnvLoader 繧貞ｰ主・縺励∬ｨｭ螳夂ｮ｡逅・・蛻ｩ萓ｿ諤ｧ縺ｨ繧ｻ繧ｭ繝･繝ｪ繝・ぅ繧貞髄荳翫＆縺帙∪縺励◆縲・/span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 繧ｻ繧ｭ繝･繝ｪ繝・ぅ &amp; 隱崎ｨｼ (Security &amp; Auth)
            </div>
            <ul>
                <li>
                    <span class="icon">孱・・/span>
                    <div>
                        <strong>繝ｭ繧ｰ繧､繝ｳ隧ｦ陦悟宛髯舌→繧ｻ繝・す繝ｧ繝ｳ菫晁ｭｷ</strong>
                        <span class="detail">繝悶Ν繝ｼ繝医ヵ繧ｩ繝ｼ繧ｹ謾ｻ謦・ｒ髦ｲ豁｢縺吶ｋ繝ｭ繧ｰ繧､繝ｳ繝ｬ繝ｼ繝医Μ繝溘ャ繝医ｒ譛驕ｩ蛹悶＠縲√Ο繧ｰ繧､繝ｳ謌仙粥譎ゅ・繧ｻ繝・す繝ｧ繝ｳ蜀咲函謌撰ｼ・ession Fixation 蟇ｾ遲厄ｼ峨ｒ蠕ｹ蠎輔＠縺ｾ縺励◆縲・/span>
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
        <span class="release-title">繝・・繧ｿ繝吶・繧ｹ鬮倬溷喧縲√ユ繝ｼ繝樊ｴ礼ｷｴ縲，SS 繝薙Ν繝峨く繝｣繝・す繝･</span>
        <span class="release-date">2026-07-22</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 譁ｰ讖溯・ &amp; 蠑ｷ蛹・(New Features &amp; Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">淀・・/span>
                    <div>
                        <strong>豌ｸ邯夂噪 DB 謗･邯壹→繧､繝ｳ繝・ャ繧ｯ繧ｹ邂｡逅・/strong>
                        <span class="detail">MySQL 繧呈ｰｸ邯壽磁邯壹↓螟画峩縺励√Γ繝・そ繝ｼ繧ｸ縲√ム繧､繝ｬ繧ｯ繝医Γ繝・そ繝ｼ繧ｸ縲√す繧ｰ繝翫Μ繝ｳ繧ｰ繝・・繝悶Ν縺ｮ繧､繝ｳ繝・ャ繧ｯ繧ｹ繧定・蜍慕ｮ｡逅・＠縺ｦ繧ｯ繧ｨ繝ｪ諤ｧ閭ｽ繧貞髄荳翫＆縺帙∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">ｧｹ</span>
                    <div>
                        <strong>譛滄剞蛻・ｌ DM 縺ｮ繧ｯ繝ｪ繝ｼ繝ｳ繧｢繝・・</strong>
                        <span class="detail">繝舌ャ繧ｯ繧ｨ繝ｳ繝峨↓譛滄剞蛻・ｌ繝繧､繝ｬ繧ｯ繝医Γ繝・そ繝ｼ繧ｸ縺ｮ蜑企勁蜃ｦ逅・ｒ霑ｽ蜉縺励√ョ繝ｼ繧ｿ繝吶・繧ｹ縺ｮ蛛･蜈ｨ諤ｧ縺ｨ繧ｹ繝医Ξ繝ｼ繧ｸ邂｡逅・ｒ蠑ｷ蛹悶＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI &amp; 繝・じ繧､繝ｳ (UI &amp; Design)
            </div>
            <ul>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>繝・・繝槭す繧ｹ繝・Β縺ｨ繝｢繝ｼ繝繝ｫ縺ｮ逎ｨ縺堺ｸ翫￡</strong>
                        <span class="detail">繝ｩ繧､繝・/ 繝繝ｼ繧ｯ / 繝翫う繝医ユ繝ｼ繝槭ｒ繧ｰ繝ｭ繝ｼ繝舌Ν縺ｫ邨ｱ蜷医＠縲√・繝ｭ繝輔ぅ繝ｼ繝ｫ繝｢繝ｼ繝繝ｫ繧・が繝ｼ繝舌・繝ｬ繧､縺ｮ繧ｹ繧ｿ繧､繝ｫ繧偵＆繧峨↓逎ｨ縺阪∪縺励◆縲・/span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 繧､繝ｳ繝輔Λ &amp; 繝ｦ繝ｼ繝・ぅ繝ｪ繝・ぅ (Infrastructure &amp; Utilities)
            </div>
            <ul>
                <li>
                    <span class="icon">逃</span>
                    <div>
                        <strong>CSS 繝薙Ν繝峨く繝｣繝・す繝･縺ｨ鬮倬溷・逕滓・</strong>
                        <span class="detail">CSS 繝舌Φ繝峨Ν縺ｮ蜀咲函謌舌ｒ蛻ｶ蠕｡縺吶ｋ繧ｭ繝｣繝・す繝･繝輔Λ繧ｰ縺ｨ繝・ぅ繝ｬ繧ｯ繝医Μ繧貞ｰ主・縺励∽ｸ崎ｦ√↑繝薙Ν繝峨ｒ蝗樣∩縺励※繧｢繧ｻ繝・ヨ逕滓・繧帝ｫ倬溷喧縺励∪縺励◆縲・/span>
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
        <span class="release-title">繧ｪ繝励ユ繧｣繝溘せ繝・ぅ繝・け UI縲√Γ繝・そ繝ｼ繧ｸ繝ｪ繝励Λ繧､讖溯・縲，SS 繧｢繝ｼ繧ｭ繝・け繝√Ε縺ｮ蛻ｷ譁ｰ</span>
        <span class="release-date">2026-07-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 譁ｰ讖溯・ &amp; 蠑ｷ蛹・(New Features &amp; Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">笞｡</span>
                    <div>
                        <strong>繧ｪ繝励ユ繧｣繝溘せ繝・ぅ繝・け UI 縺ｨ繝｡繝・そ繝ｼ繧ｸ縺ｮ騾∽ｿ｡迥ｶ諷狗ｮ｡逅・/strong>
                        <span class="detail">繝｡繝・そ繝ｼ繧ｸ縺悟叉蠎ｧ縺ｫ繝√Ε繝・ヨ縺ｫ陦ｨ遉ｺ縺輔ｌ縲・∽ｿ｡荳ｭ縺ｯ 町 繧､繝ｳ繧ｸ繧ｱ繝ｼ繧ｿ繝ｼ縺瑚｡ｨ遉ｺ縺輔ｌ縺ｾ縺吶る∽ｿ｡螟ｱ謨玲凾縺ｯ蠕ｩ譌ｧ蜿ｯ閭ｽ縺ｪ繧ｨ繝ｩ繝ｼ繝舌リ繝ｼ縺瑚｡ｨ遉ｺ縺輔ｌ縲∝・隧ｦ陦梧ｩ溯・縺ｧ菫｡鬆ｼ諤ｧ縺悟髄荳翫＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">町</span>
                    <div>
                        <strong>繝｡繝・そ繝ｼ繧ｸ繝ｪ繝励Λ繧､縺ｨ繝｡繝ｳ繧ｷ繝ｧ繝ｳ讖溯・</strong>
                        <span class="detail">迚ｹ螳壹Γ繝・そ繝ｼ繧ｸ縺ｸ縺ｮ繝ｪ繝励Λ繧､讖溯・繧貞ｮ溯｣・＠縲√Μ繝励Λ繧､蟇ｾ雎｡縺ｮ霑ｽ霍｡縲√さ繝ｳ繝・く繧ｹ繝郁｡ｨ遉ｺ縲∽ｼ夊ｩｱ荳ｭ縺ｮ豺ｻ莉倥ヵ繧｡繧､繝ｫ邂｡逅・↓蟇ｾ蠢懊＠縺ｾ縺励◆縲ゅｈ繧願憶縺・せ繝ｬ繝・ラ蠖｢蠑上・莨夊ｩｱ縺悟庄閭ｽ縺ｫ縺ｪ繧翫∪縺吶・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">孱・・/span>
                    <div>
                        <strong>豺ｻ莉倥ヵ繧｡繧､繝ｫ蜃ｦ逅・→ API 縺ｮ蠑ｷ蛹・/strong>
                        <span class="detail">繝舌ャ繧ｯ繧ｨ繝ｳ繝・API 繧貞ｼｷ蛹悶＠縲・∽ｿ｡繝｡繝・そ繝ｼ繧ｸ縺ｮ豺ｻ莉倥ヵ繧｡繧､繝ｫ繝代せ繧呈ｭ｣遒ｺ縺ｫ霑ｽ霍｡繝ｻ霑泌唆縺吶ｋ縺薙→縺ｧ縲√ヵ繧｡繧､繝ｫ縺後メ繝｣繝・ヨ螻･豁ｴ縺ｫ遒ｺ螳溘↓菫晏ｭ倥・陦ｨ遉ｺ縺輔ｌ繧九ｈ縺・↓縺ｪ繧翫∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>繧ｻ繧ｭ繝･繝ｪ繝・ぅ縺ｨ陦ｨ遉ｺ讖溯・縺ｮ繝ｩ繧､繝悶Λ繝ｪ邨ｱ蜷・/strong>
                        <span class="detail">Marked.js・・arkdown 繝ｬ繝ｳ繝繝ｪ繝ｳ繧ｰ・峨．OMPurify・・SS 蟇ｾ遲厄ｼ峨？ighlight.js・医さ繝ｼ繝画ｧ区枚繝上う繝ｩ繧､繝茨ｼ峨ｒ邨ｱ蜷医＠縲∝ｮ牙・縺ｧ雎翫°縺ｪ繝・く繧ｹ繝郁｡ｨ遉ｺ縺ｫ蟇ｾ蠢懊＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI &amp; 繝・じ繧､繝ｳ (UI &amp; Design)
            </div>
            <ul>
                <li>
                    <span class="icon">識</span>
                    <div>
                        <strong>繧｢繧ｫ繧ｦ繝ｳ繝亥炎髯､繝壹・繧ｸ縺ｮ蜀崎ｨｭ險・/strong>
                        <span class="detail">繧｢繧ｫ繧ｦ繝ｳ繝亥炎髯､繝壹・繧ｸ繧貞ｮ悟・縺ｫ蛻ｷ譁ｰ縺励√せ繧ｿ繧､繝ｫ縺ｮ蜷台ｸ翫∬ｦ冶ｦ夐嚴螻､縺ｮ謾ｹ蝟・√ヱ繧ｹ繝ｯ繝ｼ繝臥｢ｺ隱阪ヵ繝ｭ繝ｼ縺ｮ繧ｻ繧ｭ繝･繝ｪ繝・ぅ蠑ｷ蛹悶ｒ螳滓命縺励∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">逃</span>
                    <div>
                        <strong>CSS 繝舌Φ繝峨Ν譛驕ｩ蛹悶→繧｢繝ｼ繧ｭ繝・け繝√Ε縺ｮ謨ｴ逅・/strong>
                        <span class="detail">delete_account.php 縺ｮ繧､繝ｳ繝ｩ繧､繝ｳ繧ｹ繧ｿ繧､繝ｫ繧貞､夜Κ CSS 縺ｫ謚ｽ蜃ｺ縺励｜uild_css.php 縺ｫ繧医ｋ閾ｪ蜍輔Α繝句喧繝ｻ繝舌Φ繝峨Ν蛹悶ｒ螳溯｣・Ｃundle.min.css 縺ｧ譛驕ｩ蛹悶＆繧後∽ｿ晏ｮ域ｧ縺ｨ繝代ヵ繧ｩ繝ｼ繝槭Φ繧ｹ縺悟髄荳翫＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 繧､繝ｳ繝輔Λ &amp; 繝ｦ繝ｼ繝・ぅ繝ｪ繝・ぅ (Infrastructure &amp; Utilities)
            </div>
            <ul>
                <li>
                    <span class="icon">笞呻ｸ・/span>
                    <div>
                        <strong>繝舌ャ繧ｯ繧ｨ繝ｳ繝峨・繧ｯ繝ｪ繝ｼ繝ｳ繧｢繝・・縲√う繝ｳ繝・ャ繧ｯ繧ｹ縲√♀繧医・ CSS 繝薙Ν繝芽・蜍募喧</strong>
                        <span class="detail">譛滄剞蛻・ｌ繝｡繝・そ繝ｼ繧ｸ縺ｮ繧ｯ繝ｪ繝ｼ繝ｳ繧｢繝・・縺ｨ繧ｯ繧ｨ繝ｪ繧､繝ｳ繝・ャ繧ｯ繧ｹ邂｡逅・ｒ繝舌ャ繧ｯ繧ｨ繝ｳ繝峨↓霑ｽ蜉縺励，SS 繝薙Ν繝芽・蜍募喧縲√が繝励ユ繧｣繝溘せ繝・ぅ繝・け UI 繝｡繝・そ繝ｼ繧ｸ迥ｶ諷九√Θ繝ｼ繝・ぅ繝ｪ繝・ぅ謾ｹ蝟・ｒ邯咏ｶ壹＠縺ｾ縺励◆縲・/span>
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
        <span class="release-title">隱崎ｨｼ蜃ｦ逅・・蠑ｷ蛹悶√ヵ繝ｬ繝ｳ繝峨Μ繧ｯ繧ｨ繧ｹ繝・UX縲√♀繧医・繝√Ε繝・ヨ UI 縺ｮ逎ｨ縺堺ｸ翫￡</span>
        <span class="release-date">2026-06-28</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 譁ｰ讖溯・ &amp; 蠑ｷ蛹・(New Features &amp; Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">孱・・/span>
                    <div>
                        <strong>隱崎ｨｼ繝輔Ο繝ｼ縺ｮ蠑ｷ蛹・/strong>
                        <span class="detail">繝ｭ繧ｰ繧､繝ｳ縺ｨ繧ｵ繧､繝ｳ繧｢繝・・蜃ｦ逅・↓ try/catch 繧貞ｰ主・縺励√Ο繝・け繧｢繧ｦ繝域､懷・縺ｨ繧ｨ繝ｩ繝ｼ繝代せ繧貞ｮ牙ｮ壼喧縺輔○縺ｦ菫｡鬆ｼ諤ｧ繧貞髄荳翫＆縺帙∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">則</span>
                    <div>
                        <strong>繝輔Ξ繝ｳ繝峨Μ繧ｯ繧ｨ繧ｹ繝医→繧ｽ繝ｼ繧ｷ繝｣繝ｫ謫堺ｽ・/strong>
                        <span class="detail">繝ｦ繝ｼ繧ｶ繝ｼ讀懃ｴ｢縲∫筏隲矩∽ｿ｡縲∽ｿ晉蕗繝ｪ繧ｯ繧ｨ繧ｹ繝医√ヶ繝ｭ繝・け繝ｦ繝ｼ繧ｶ繝ｼ邂｡逅・ｒ霑ｽ蜉縺励√ヵ繝ｬ繝ｳ繝蛾未菫ゅ・讒狗ｯ峨ｒ繧医ｊ繧ｹ繝繝ｼ繧ｺ縺ｫ縺励∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">笨ｨ</span>
                    <div>
                        <strong>繝√Ε繝・ヨ UI 縺ｨ縺頑ｰ励↓蜈･繧翫・謾ｹ蝟・/strong>
                        <span class="detail">繝√Ε繝・ヨ讀懃ｴ｢陦ｨ遉ｺ縲√げ繝ｫ繝ｼ繝励Γ繝ｳ繝舌・驕ｸ謚槭’avorites 繝代ロ繝ｫ繧貞ｮ牙・縺ｪ DOM 譖ｴ譁ｰ縺ｨ譏守｢ｺ縺ｪ遨ｺ迥ｶ諷玖｡ｨ遉ｺ縺ｫ謾ｹ蝟・＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">肌</span>
                    <div>
                        <strong>繝代せ繝ｯ繝ｼ繝峨Μ繧ｻ繝・ヨ UX 縺ｮ蜷台ｸ・/strong>
                        <span class="detail">繝ｪ繧ｻ繝・ヨ繝壹・繧ｸ縺ｮ繝｡繝・そ繝ｼ繧ｸ縺ｨ蠕ｩ譌ｧ繝輔Ο繝ｼ繧呈隼蝟・＠縲√ｈ繧雁・縺九ｊ繧・☆縺・桃菴應ｽ馴ｨ薙ｒ謠蝉ｾ帙＠縺ｾ縺吶・/span>
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
                <span class="dot dot-green"></span> 譁ｰ讖溯・ &amp; 蠑ｷ蛹・(New Features &amp; Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">則</span>
                    <div>
                        <strong>繝輔Ξ繝ｳ繝画ｩ溯・縺ｮ諡｡蠑ｵ</strong>
                        <span class="detail">騾∽ｿ｡縲∵価隱阪∵拠蜷ｦ縺ｮ繝輔Ξ繝ｳ繝峨Μ繧ｯ繧ｨ繧ｹ繝・API 繧定ｿｽ蜉縺励∽ｿ晉蕗荳ｭ繝ｪ繧ｯ繧ｨ繧ｹ繝医・蜃ｦ逅・→ DM 繝上ヶ蜀・・繝輔Ξ繝ｳ繝峨Μ繧ｹ繝域峩譁ｰ繧貞ｮ溯｣・＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">ｧ鯛昨汳ｻ</span>
                    <div>
                        <strong>繝ｦ繝ｼ繧ｶ繝ｼ繝励Ο繝輔ぅ繝ｼ繝ｫ縺ｮ謾ｹ蝟・/strong>
                        <span class="detail">繝ｦ繝ｼ繧ｶ繝ｼ繝励Ο繝輔ぅ繝ｼ繝ｫ繝｢繝ｼ繝繝ｫ縺ｫ縲後ヵ繝ｬ繝ｳ繝臥筏隲九阪・菫晉蕗荳ｭ繝ｪ繧ｯ繧ｨ繧ｹ繝医・繝悶Ο繝・け繝ｦ繝ｼ繧ｶ繝ｼ邂｡逅・ｒ霑ｽ蜉縺励√ｈ繧顔峩謗･逧・↑繧ｽ繝ｼ繧ｷ繝｣繝ｫ謫堺ｽ懊ｒ蜿ｯ閭ｽ縺ｫ縺励∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">萄</span>
                    <div>
                        <strong>繝｡繝・ぅ繧｢繧｢繝・・繝ｭ繝ｼ繝峨・繝ｬ繝薙Η繝ｼ縺ｮ霑ｽ蜉</strong>
                        <span class="detail">繝峨Λ繝・げ・・ラ繝ｭ繝・・縺ｫ蟇ｾ蠢懊＠縺溷ｰら畑繧｢繝・・繝ｭ繝ｼ繝峨Δ繝ｼ繝繝ｫ繧定ｿｽ蜉縺励√ヵ繧｡繧､繝ｫ繝励Ξ繝薙Η繝ｼ縺ｨ繝｡繧ｿ繝・・繧ｿ陦ｨ遉ｺ縺ｧ豺ｻ莉倅ｽ馴ｨ薙ｒ謾ｹ蝟・＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">笞｡</span>
                    <div>
                        <strong>繧ｪ繝ｳ繝ｩ繧､繝ｳ繝ｦ繝ｼ繧ｶ繝ｼ譖ｴ譁ｰ縺ｨ繧ｰ繝ｫ繝ｼ繝励メ繝｣繝・ヨ蜷梧悄</strong>
                        <span class="detail">繧ｪ繝ｳ繝ｩ繧､繝ｳ繝ｦ繝ｼ繧ｶ繝ｼ荳隕ｧ縺ｮ 30 遘定・蜍墓峩譁ｰ繧呈怏蜉ｹ蛹悶＠縲∝・譛溯ｪｭ縺ｿ霎ｼ縺ｿ譎ゅ↓繧ｰ繝ｫ繝ｼ繝励メ繝｣繝・ヨ蛻､螳壹ｒ蠕ｩ蜈・＠縺ｦ豁｣縺励＞繧ｵ繧､繝峨ヰ繝ｼ迥ｶ諷九ｒ菫晄戟縺励∪縺吶・/span>
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
        <span class="release-title">繝壹・繧ｸ驕ｷ遘ｻ繧｢繝九Γ繝ｼ繧ｷ繝ｧ繝ｳ縺ｮ螳溯｣・・</span>
        <span class="release-date">2026-06-08</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI &amp; 繝・じ繧､繝ｳ (UI &amp; Design)
            </div>
            <ul>
                <li>
                    <span class="icon">汐</span>
                    <div>
                        <strong>View Transitions API 縺ｫ繧医ｋ繝壹・繧ｸ驕ｷ遘ｻ繧｢繝九Γ繝ｼ繧ｷ繝ｧ繝ｳ縺ｮ螳溯｣・/strong>
                        <span class="detail">CSS 縺ｮ <code>@view-transition</code> 縺ｨ <code>::view-transition-old/new</code> 繧呈ｴｻ逕ｨ縺励（ndex.html繝ｻabout.html繝ｻprivacy_policy.html繝ｻlogin.php繝ｻsignup.php繝ｻreset_password.php 髢薙・繝壹・繧ｸ驕ｷ遘ｻ縺ｫ繧ｹ繝繝ｼ繧ｺ縺ｪ繝輔ぉ繝ｼ繝会ｼ・せ繝ｩ繧､繝峨い繝九Γ繝ｼ繧ｷ繝ｧ繝ｳ繧帝←逕ｨ縺励∪縺励◆縲ゅロ繧､繝・ぅ繝悶い繝励Μ荳ｦ縺ｿ縺ｮ蠢ｫ驕ｩ縺ｪ逕ｻ髱｢蛻・ｊ譖ｿ縺医ｒ螳溽樟縺励※縺・∪縺吶・/span>
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
        <span class="release-title">繧｢繝九Γ繝ｼ繧ｷ繝ｧ繝ｳ縺ｮ豢礼ｷｴ繝ｻ騾乗・諢溘・譛驕ｩ蛹悶・繧ｫ繝ｩ繝ｼ隕冶ｪ肴ｧ縺ｮ蜈ｨ髱｢蜷台ｸ・/span>
        <span class="release-date">2026-06-07</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI &amp; 繝・じ繧､繝ｳ (UI &amp; Design)
            </div>
            <ul>
                <li>
                    <span class="icon">笨ｨ</span>
                    <div>
                        <strong>繧｢繝九Γ繝ｼ繧ｷ繝ｧ繝ｳ縺ｮ貊代ｉ縺句喧</strong>
                        <span class="detail">蜷・ｨｮ繝懊ち繝ｳ繝ｻ繝｢繝ｼ繝繝ｫ繝ｻ繧ｦ繧｣繧ｸ繧ｧ繝・ヨ縺ｮ繝医Λ繝ｳ繧ｸ繧ｷ繝ｧ繝ｳ繧堤ｴｰ縺九￥隕狗峩縺励√ｈ繧願・辟ｶ縺ｧ縺ｪ繧√ｉ縺九↑蜍輔″縺ｫ隱ｿ謨ｴ縺励∪縺励◆縲る℃蜑ｰ縺ｪ蜍輔″繧呈賜髯､縺励▽縺､縲∵桃菴懊↓蟇ｾ縺吶ｋ繝輔ぅ繝ｼ繝峨ヰ繝・け縺ｮ雉ｪ繧帝ｫ倥ａ縺ｦ縺・∪縺吶・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>騾乗・諢滂ｼ医げ繝ｩ繧ｹ繝｢繝ｼ繝輔ぅ繧ｺ繝・峨・譛驕ｩ蛹悶→驟崎牡縺ｮ隕冶ｪ肴ｧ蜷台ｸ・/strong>
                        <span class="detail">騾乗・繝ｻ縺ｼ縺九＠蜉ｹ譫懊′蠑ｷ縺吶℃縺ｦ隱ｭ縺ｿ縺ｫ縺上°縺｣縺溽ｮ・園繧定ｪｿ謨ｴ縺励∝庄隱ｭ諤ｧ縺ｨ謫堺ｽ懈ｧ繧剃ｸ｡遶九ゅき繝ｩ繝ｼ縺ｮ繧ｳ繝ｳ繝医Λ繧ｹ繝医→譏弱ｋ縺輔ｒ蜈ｨ菴鍋噪縺ｫ謾ｹ蝟・＠縲√ｈ繧雁・縺九ｊ繧・☆縺乗ｴ礼ｷｴ縺輔ｌ縺溘ン繧ｸ繝･繧｢繝ｫ縺ｫ蛻ｷ譁ｰ縺励∪縺励◆縲・/span>
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
        <span class="release-title">騾夂衍繧ｷ繧ｹ繝・Β縺ｮ SSE + Web Push 繝上う繝悶Μ繝・ラ讒区・縺ｸ縺ｮ遘ｻ陦・/span>
        <span class="release-date">2026-06-07</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 譁ｰ讖溯・ &amp; 蠑ｷ蛹・(New Features &amp; Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">粕</span>
                    <div>
                        <strong>SSE + Web Push 繝上う繝悶Μ繝・ラ騾夂衍縺ｮ螳溯｣・/strong>
                        <span class="detail">蠕捺擂縺ｮ騾夂衍驟堺ｿ｡譁ｹ蠑上ｒ蜈ｨ髱｢蛻ｷ譁ｰ縺励ヾerver-Sent Events (SSE) 縺ｨ繝悶Λ繧ｦ繧ｶ讓呎ｺ悶・ Web Push 繧堤ｵ・∩蜷医ｏ縺帙◆繝上う繝悶Μ繝・ラ繧｢繝ｼ繧ｭ繝・け繝√Ε繧呈治逕ｨ縲ゅい繝励Μ縺後ヰ繝・け繧ｰ繝ｩ繧ｦ繝ｳ繝峨↓縺ゅｋ蝣ｴ蜷医〒繧ら｢ｺ螳溘↓騾夂衍縺悟ｱ翫″縲√Μ繧｢繝ｫ繧ｿ繧､繝諤ｧ縺ｨ繝舌ャ繝・Μ繝ｼ蜉ｹ邇・ｒ荳｡遶九＠縺ｦ縺・∪縺吶・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">笞｡</span>
                    <div>
                        <strong>騾夂衍縺ｮ蛻ｰ驕皮紫縺ｨ菫｡鬆ｼ諤ｧ縺ｮ蜷台ｸ・/strong>
                        <span class="detail">謗･邯壽妙繝ｻ繝阪ャ繝医Ρ繝ｼ繧ｯ荳榊ｮ牙ｮ壽凾縺ｮ繝輔か繝ｼ繝ｫ繝舌ャ繧ｯ蜃ｦ逅・ｒ蠑ｷ蛹悶＠縲・夂衍縺ｮ蜿悶ｊ縺薙⊂縺励ｒ螟ｧ蟷・↓菴取ｸ帙＠縺ｾ縺励◆縲ゅし繝ｼ繝薙せ繝ｯ繝ｼ繧ｫ繝ｼ縺ｨ縺ｮ騾｣謳ｺ繧呈怙驕ｩ蛹悶＠縲√・繝・す繝･騾夂衍縺ｮ蠢懃ｭ秘溷ｺｦ繧ょ髄荳翫＠縺ｦ縺・∪縺吶・/span>
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
        <span class="release-title">繝｢繝繝ｳWeb讓呎ｺ悶↓蝓ｺ縺･縺・◆繝繧､繧｢繝ｭ繧ｰ縺ｮ蠑ｷ蛹悶→繝励Ξ繝溘い繝繧｢繝九Γ繝ｼ繧ｷ繝ｧ繝ｳ縺ｮ蟆主・</span>
        <span class="release-date">2026-05-23</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 譁ｰ讖溯・ & 蠑ｷ蛹・(New Features & Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">笨ｨ</span>
                    <div>
                        <strong>螳｣險逧・け繝ｭ繝ｼ繧ｺ (Light-dismiss) 縺ｮ繧ｵ繝昴・繝・/strong>
                        <span class="detail">蜷・ｨｮ繝繧､繧｢繝ｭ繧ｰ・郁ｨｭ螳壹√ぐ繝｣繝ｩ繝ｪ繝ｼ縲√ヶ繝ｭ繝・け繝ｪ繧ｹ繝医√す繝ｧ繝ｼ繝医き繝・ヨ遲会ｼ峨↓ <code>closedby="any"</code> 螻樊ｧ繧定ｿｽ蜉縲・S繧剃ｽｿ繧上★縺ｫ閭梧勹・亥､門・・峨ｒ繧ｯ繝ｪ繝・け縺吶ｋ縺縺代〒繧ｹ繝繝ｼ繧ｺ縺ｫ髢峨§繧峨ｌ繧九Δ繝繝ｳUX繧貞ｮ溽樟縺励∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">孱・・/span>
                    <div>
                        <strong>繧ｰ繝ｭ繝ｼ繝舌Ν縺ｪJS繝輔か繝ｼ繝ｫ繝舌ャ繧ｯ縺ｮ螳溯｣・/strong>
                        <span class="detail">Safari縺ｪ縺ｩ荳驛ｨ縺ｮ <code>closedby</code> 譛ｪ蟇ｾ蠢懊ヶ繝ｩ繧ｦ繧ｶ縺ｧ繧りレ譎ｯ繧ｯ繝ｪ繝・け縺ｧ繝繧､繧｢繝ｭ繧ｰ縺梧ｭ｣縺励￥髢峨§繧九ｈ縺・√う繝吶Φ繝医ョ繝ｪ繧ｲ繝ｼ繧ｷ繝ｧ繝ｳ繧貞茜逕ｨ縺励◆霆ｽ驥上↑繝輔か繝ｼ繝ｫ繝舌ャ繧ｯ繝ｭ繧ｸ繝・け繧谷S蛻晄悄蛹匁凾縺ｫ霑ｽ蜉縺励∪縺励◆縲・/span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI & 繝・じ繧､繝ｳ (UI & Design)
            </div>
            <ul>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>@starting-style繧堤畑縺・◆繝励Ξ繝溘い繝繧｢繝九Γ繝ｼ繧ｷ繝ｧ繝ｳ</strong>
                        <span class="detail">CSS縺ｮ <code>@starting-style</code>縲・code>transition-behavior: allow-discrete</code>縲・code>overlay</code> 繝励Ο繝代ユ繧｣繧剃ｽｿ逕ｨ縺励√Δ繝ｼ繝繝ｫ縺ｮ髢矩哩譎ゅ↓縺翫￠繧句曙譁ｹ蜷代・貊代ｉ縺九↑繝輔ぉ繝ｼ繝会ｼ・僑螟ｧ邵ｮ蟆上い繝九Γ繝ｼ繧ｷ繝ｧ繝ｳ繧偵ヴ繝･繧｢CSS縺ｧ螳溯｣・ゅヰ繝・け繝峨Ο繝・・・郁レ譎ｯ・峨・縺ｼ縺九＠蜉ｹ譫懊ｂ貊代ｉ縺九↓驕ｷ遘ｻ縺励∪縺吶・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">笙ｿ</span>
                    <div>
                        <strong>隕冶ｦ夂噪繧｢繧ｯ繧ｻ繧ｷ繝薙Μ繝・ぅ蟇ｾ蠢・(Reduced Motion)</strong>
                        <span class="detail">OS繧・ヶ繝ｩ繧ｦ繧ｶ縺ｧ繧｢繝九Γ繝ｼ繧ｷ繝ｧ繝ｳ縺ｮ霆ｽ貂幄ｨｭ螳・(<code>prefers-reduced-motion</code>) 縺梧怏蜉ｹ縺ｪ迺ｰ蠅・↓縺翫＞縺ｦ縲∬・蜍慕噪縺ｫ菴吝・縺ｪ蜍輔″・域僑螟ｧ邵ｮ蟆擾ｼ峨ｒ謗帝勁縺励√す繝ｳ繝励Ν縺ｪ繝輔ぉ繝ｼ繝峨う繝ｳ繝ｻ繝輔ぉ繝ｼ繝峨い繧ｦ繝医↓繝輔か繝ｼ繝ｫ繝舌ャ繧ｯ縺吶ｋ繧医≧驟肴・縺励∪縺励◆縲・/span>
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
        <span class="release-title">PWA 蠑ｷ蛹悶・夂衍繝｢繧ｸ繝･繝ｼ繝ｫ縺ｮ蛻ｷ譁ｰ縲∵ｳ慕噪繝峨く繝･繝｡繝ｳ繝医・謨ｴ蛯・/span>
        <span class="release-date">2026-05-09</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 譁ｰ讖溯・ & 蠑ｷ蛹・(New Features & Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">粕</span>
                    <div>
                        <strong>騾夂衍繧ｷ繧ｹ繝・Β縺ｮ繝｢繧ｸ繝･繝ｼ繝ｫ蛹・/strong>
                        <span class="detail">騾夂衍讖溯・繧堤峡遶九＠縺溘Δ繧ｸ繝･繝ｼ繝ｫ (`notifications.js`) 縺ｫ蛻・屬縺励√・繝・す繝･騾夂衍縺ｮ螳牙ｮ壽ｧ縺ｨ諡｡蠑ｵ諤ｧ繧貞髄荳翫＆縺帙∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">塘</span>
                    <div>
                        <strong>縲郡YCS 縺ｫ縺､縺・※縲阪→縲後・繝ｩ繧､繝舌す繝ｼ繝昴Μ繧ｷ繝ｼ縲阪・霑ｽ蜉</strong>
                        <span class="detail">繝励Ο繧ｸ繧ｧ繧ｯ繝医・隧ｳ邏ｰ繧堤ｴｹ莉九☆繧・About 繝壹・繧ｸ縺ｨ縲∵ｳ慕噪驕ｵ螳医・縺溘ａ縺ｮ繝励Λ繧､繝舌す繝ｼ繝昴Μ繧ｷ繝ｼ繝壹・繧ｸ繧呈眠隕丞・髢九＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">倹</span>
                    <div>
                        <strong>螟夊ｨ隱槫ｯｾ蠢懊・蠑ｷ蛹・(i18n)</strong>
                        <span class="detail">繝ｭ繧ｱ繝ｼ繝ｫ繝輔ぃ繧､繝ｫ (`en.json`, `ja.json`) 繧呈峩譁ｰ縺励∵眠讖溯・縺ｫ蟇ｾ蠢懊＠縺溽ｿｻ險ｳ繧定ｿｽ蜉縺励∪縺励◆縲・/span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> PWA & 螳牙ｮ壽ｧ (PWA & Stability)
            </div>
            <ul>
                <li>
                    <span class="icon">笞｡</span>
                    <div>
                        <strong>繧ｵ繝ｼ繝薙せ繝ｯ繝ｼ繧ｫ繝ｼ縺ｮ譛驕ｩ蛹・/strong>
                        <span class="detail">繧ｭ繝｣繝・す繝･謌ｦ逡･繧貞姐譁ｰ縺励√Δ繧ｸ繝･繝ｼ繝ｫ蛹悶＆繧後◆譛譁ｰ縺ｮ CSS 鄒､縺ｨ繝｡繧､繝ｳ繧ｹ繧ｯ繝ｪ繝励ヨ繧剃ｺ句燕繧ｭ繝｣繝・す繝･蟇ｾ雎｡縺ｫ霑ｽ蜉縲ゅが繝輔Λ繧､繝ｳ譎ゅ・隱ｭ縺ｿ霎ｼ縺ｿ騾溷ｺｦ縺ｨ菫｡鬆ｼ諤ｧ縺悟､ｧ蟷・↓蜷台ｸ翫＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">孱・・/span>
                    <div>
                        <strong>API 繝上Φ繝峨Λ縺ｮ蝣・欧蛹・/strong>
                        <span class="detail">繝舌ャ繧ｯ繧ｨ繝ｳ繝峨・ `Handler.php` 縺ｫ縺翫￠繧九お繝ｩ繝ｼ蜃ｦ逅・→蜈･蜃ｺ蜉帙・謨ｴ蜷域ｧ繧偵＆繧峨↓蠑ｷ蛹悶＠縺ｾ縺励◆縲・/span>
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
        <span class="release-title">繝励Λ繧､繝舌す繝ｼ繝昴Μ繧ｷ繝ｼ繝壹・繧ｸ縲、bout繝壹・繧ｸ縲∝茜逕ｨ隕冗ｴ・・繝ｼ繧ｸ縺ｮ霑ｽ蜉</span>
        <span class="release-date">2026-05-07</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 譁ｰ讖溯・(New Feature)
            </div>
            <ul>
                <li>
                    <span class="icon">統</span>
                    <div>
                        <strong>繝励Λ繧､繝舌す繝ｼ繝昴Μ繧ｷ繝ｼ繝壹・繧ｸ縲、bout繝壹・繧ｸ縲∝茜逕ｨ隕冗ｴ・・繝ｼ繧ｸ縺ｮ霑ｽ蜉</strong>
                        <span class="detail">繝励Λ繧､繝舌す繝ｼ繝昴Μ繧ｷ繝ｼ繝壹・繧ｸ縲、bout繝壹・繧ｸ縲∝茜逕ｨ隕冗ｴ・・繝ｼ繧ｸ繧定ｿｽ蜉縺励∪縺励◆縲・/span>
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
        <span class="release-title">繧ｫ繝ｩ繝ｼ繝・・繝櫁ｿｽ蜉</span>
        <span class="release-date">2026-05-06</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI & 繝・じ繧､繝ｳ (UI & Design)
            </div>
            <ul>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>繝翫う繝医ユ繝ｼ繝槭き繝ｩ繝ｼ縺ｮ霑ｽ蜉</strong>
                        <span class="detail">繝翫う繝医ユ繝ｼ繝槭・繧ｫ繝ｩ繝ｼ繧定ｿｽ蜉縺励∪縺励◆縲・/span>
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
        <span class="release-title">繝ｦ繝ｼ繧ｶ繝ｼ繝励Ο繝輔ぅ繝ｼ繝ｫ險ｭ螳壹・蜍穂ｽ懷､画峩</span>
        <span class="release-date">2026-05-06</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 繝ｦ繝ｼ繧ｶ繝ｼ繝励Ο繝輔ぅ繝ｼ繝ｫ險ｭ螳壹す繧ｹ繝・Β謾ｹ菫ｮ
            </div>
            <ul>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>繝ｦ繝ｼ繧ｶ繝ｼ繝励Ο繝輔ぅ繝ｼ繝ｫ險ｭ螳壹Δ繝ｼ繝繝ｫ縺ｮ蜍穂ｽ懷､画峩</strong>
                        <span class="detail">繝ｦ繝ｼ繧ｶ繝ｼ繝励Ο繝輔ぅ繝ｼ繝ｫ險ｭ螳壹Δ繝ｼ繝繝ｫ縺ｮ蜍穂ｽ懊ｒ螟画峩縺励∪縺励◆縲・/span>
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
        <span class="release-title">繝ｩ繝ｳ繝・ぅ繝ｳ繧ｰ繝壹・繧ｸ縺ｮ繧ｹ繧ｿ繧､繝ｫ謾ｹ菫ｮ</span>
        <span class="release-date">2026-05-06</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI & 繝・じ繧､繝ｳ (UI & Design)
            </div>
            <ul>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>蟆ら畑繝・じ繧､繝ｳ繧ｷ繧ｹ繝・Β (landing.css)</strong>
                        <span class="detail">繝ｩ繝ｳ繝・ぅ繝ｳ繧ｰ繝壹・繧ｸ蟆ら畑縺ｮ繧ｹ繧ｿ繧､繝ｫ繧ｷ繝ｼ繝医ｒ菴懈・縺励∵ｻ代ｉ縺九↑繧｢繝九Γ繝ｼ繧ｷ繝ｧ繝ｳ縲＾utfit/Inter 繝輔か繝ｳ繝医↓繧医ｋ鄒朱ｺ励↑繧ｿ繧､繝昴げ繝ｩ繝輔ぅ縲√◎縺励※荳雋ｫ諤ｧ縺ｮ縺ゅｋ繧ｫ繝ｩ繝ｼ繝代Ξ繝・ヨ繧貞ｮ溯｣・＠縺ｾ縺励◆縲・/span>
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
        <span class="release-title">繝ｩ繝ｳ繝・ぅ繝ｳ繧ｰ繝壹・繧ｸ霑ｽ蜉</span>
        <span class="release-date">2026-05-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 譁ｰ讖溯・ (New Feature)
            </div>
            <ul>
                <li>
                    <span class="icon">笨ｨ</span>
                    <div>
                        <strong>繝ｩ繝ｳ繝・ぅ繝ｳ繧ｰ繝壹・繧ｸ霑ｽ蜉</strong>
                        <span class="detail">SYCS縺ｮ鬲・鴨繧呈怙螟ｧ髯舌↓莨昴∴繧九◆繧√・縲√Δ繝繝ｳ縺ｧ豢礼ｷｴ縺輔ｌ縺溘Λ繝ｳ繝・ぅ繝ｳ繧ｰ繝壹・繧ｸ繧呈眠隕丞・髢九＠縺ｾ縺励◆縲・繝代ロ繝ｫ讒区・縺ｮ繝ｬ繧､繧｢繧ｦ繝医ｒ謗｡逕ｨ縺励√・繝ｭ繧ｸ繧ｧ繧ｯ繝医・蜷・ｩ溯・繧堤ｾ弱＠縺冗ｴｹ莉九＠縺ｦ縺・∪縺吶・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>蟆ら畑繝・じ繧､繝ｳ繧ｷ繧ｹ繝・Β (landing.css)</strong>
                        <span class="detail">繝ｩ繝ｳ繝・ぅ繝ｳ繧ｰ繝壹・繧ｸ蟆ら畑縺ｮ繧ｹ繧ｿ繧､繝ｫ繧ｷ繝ｼ繝医ｒ菴懈・縺励∵ｻ代ｉ縺九↑繧｢繝九Γ繝ｼ繧ｷ繝ｧ繝ｳ縲＾utfit/Inter 繝輔か繝ｳ繝医↓繧医ｋ鄒朱ｺ励↑繧ｿ繧､繝昴げ繝ｩ繝輔ぅ縲√◎縺励※荳雋ｫ諤ｧ縺ｮ縺ゅｋ繧ｫ繝ｩ繝ｼ繝代Ξ繝・ヨ繧貞ｮ溯｣・＠縺ｾ縺励◆縲・/span>
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
        <span class="release-title">API縺ｮ謨ｴ蜷域ｧ蜷台ｸ翫→繧ｵ繝ｼ繝薙せ繝ｯ繝ｼ繧ｫ繝ｼ縺ｮ菫｡鬆ｼ諤ｧ蜷台ｸ・/span>
        <span class="release-date">2026-05-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 螳牙ｮ壽ｧ & 菫ｮ豁｣ (Stability & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">笞呻ｸ・/span>
                    <div>
                        <strong>API 繝ｬ繧ｹ繝昴Φ繧ｹ縺ｮ謨ｴ蜷域ｧ蜷台ｸ・/strong>
                        <span class="detail">API 繝ｪ繧ｯ繧ｨ繧ｹ繝亥・逅・ｾ後↓遒ｺ螳溘↓螳溯｡後ｒ邨ゆｺ・＆縺帙ｋ縺薙→縺ｧ縲∵э蝗ｳ縺励↑縺・HTML 縺・JSON 繝ｬ繧ｹ繝昴Φ繧ｹ縺ｫ豺ｷ蜈･縺吶ｋ蝠城｡後ｒ菫ｮ豁｣縺励∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">伯</span>
                    <div>
                        <strong>繧ｵ繝ｼ繝薙せ繝ｯ繝ｼ繧ｫ繝ｼ縺ｮ繧ｭ繝｣繝・す繝･蜃ｦ逅・ｒ譛驕ｩ蛹・/strong>
                        <span class="detail">HTTP/HTTPS 繝励Ο繝医さ繝ｫ縺ｮ縺ｿ繧偵く繝｣繝・す繝･蟇ｾ雎｡縺ｨ縺吶ｋ繧医≧蛻ｶ髯舌＠縲√ヶ繝ｩ繧ｦ繧ｶ諡｡蠑ｵ讖溯・縺ｪ縺ｩ縺ｫ襍ｷ蝗縺吶ｋ繧ｨ繝ｩ繝ｼ繧帝亟豁｢縺励∪縺励◆縲ゅが繝輔Λ繧､繝ｳ譎ゅ・蜍穂ｽ懊′繧医ｊ螳牙ｮ壹＠縺ｾ縺吶・/span>
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
        <span class="release-title">繝励Ξ繝溘い繝繝ｩ繝ｳ繝・ぅ繝ｳ繧ｰ繝壹・繧ｸ縺ｮ蟆主・</span>
        <span class="release-date">2026-05-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 譁ｰ讖溯・ (New Feature)
            </div>
            <ul>
                <li>
                    <span class="icon">笨ｨ</span>
                    <div>
                        <strong>谺｡荳紋ｻ｣繝ｩ繝ｳ繝・ぅ繝ｳ繧ｰ繝壹・繧ｸ (index.html) 縺ｮ蟆主・</strong>
                        <span class="detail">SYCS 縺ｮ鬲・鴨繧呈怙螟ｧ髯舌↓莨昴∴繧九◆繧√・縲√Δ繝繝ｳ縺ｧ豢礼ｷｴ縺輔ｌ縺溘Λ繝ｳ繝・ぅ繝ｳ繧ｰ繝壹・繧ｸ繧呈眠隕丞・髢九＠縺ｾ縺励◆縲・繝代ロ繝ｫ讒区・縺ｮ繝ｬ繧､繧｢繧ｦ繝医ｒ謗｡逕ｨ縺励√・繝ｭ繧ｸ繧ｧ繧ｯ繝医・蜷・ｩ溯・繧堤ｾ弱＠縺冗ｴｹ莉九＠縺ｦ縺・∪縺吶・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>蟆ら畑繝・じ繧､繝ｳ繧ｷ繧ｹ繝・Β (landing.css)</strong>
                        <span class="detail">繝ｩ繝ｳ繝・ぅ繝ｳ繧ｰ繝壹・繧ｸ蟆ら畑縺ｮ繧ｹ繧ｿ繧､繝ｫ繧ｷ繝ｼ繝医ｒ菴懈・縺励∵ｻ代ｉ縺九↑繧｢繝九Γ繝ｼ繧ｷ繝ｧ繝ｳ縲＾utfit/Inter 繝輔か繝ｳ繝医↓繧医ｋ鄒朱ｺ励↑繧ｿ繧､繝昴げ繝ｩ繝輔ぅ縲√◎縺励※荳雋ｫ諤ｧ縺ｮ縺ゅｋ繧ｫ繝ｩ繝ｼ繝代Ξ繝・ヨ繧貞ｮ溯｣・＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 謾ｹ蝟・せ (Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">噫</span>
                    <div>
                        <strong>繝ｬ繧ｹ繝昴Φ繧ｷ繝門ｯｾ蠢懊・蠑ｷ蛹・/strong>
                        <span class="detail">PC 縺九ｉ繧ｹ繝槭・繝医ヵ繧ｩ繝ｳ縺ｾ縺ｧ縲√≠繧峨ｆ繧九ョ繝舌う繧ｹ縺ｧ繝励Ο繧ｸ繧ｧ繧ｯ繝医・讖溯・邏ｹ莉九′鄒弱＠縺剰｡ｨ遉ｺ縺輔ｌ繧九ｈ縺・怙驕ｩ蛹悶＆繧後※縺・∪縺吶・/span>
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
        <span class="release-title">繧ｹ繝・・繧ｿ繧ｹ驕ｸ謚・UI 縺ｮ蛻ｷ譁ｰ縺ｨ隕冶ｦ壼柑譫懊・謾ｹ蝟・/span>
        <span class="release-date">2026-05-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI & 繝・じ繧､繝ｳ (UI & Design)
            </div>
            <ul>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>繧ｹ繝・・繧ｿ繧ｹ驕ｸ謚槭・繝・け繧ｹ縺ｮ繝｢繝繝ｳ蛹・/strong>
                        <span class="detail">繧ｹ繝・・繧ｿ繧ｹ驕ｸ謚槭ラ繝ｭ繝・・繝繧ｦ繝ｳ縺ｮ繝・じ繧､繝ｳ繧貞・髱｢逧・↓蛻ｷ譁ｰ縺励∪縺励◆縲ゅき繧ｹ繧ｿ繝繧｢繧､繧ｳ繝ｳ縺ｮ蟆主・縲√げ繝ｩ繧ｹ繝｢繝ｼ繝輔ぅ繧ｺ繝・郁レ譎ｯ縺ｼ縺九＠・峨・驕ｩ逕ｨ縲∵ｴ礼ｷｴ縺輔ｌ縺溘・繝舌・/繝輔か繝ｼ繧ｫ繧ｹ繧ｨ繝輔ぉ繧ｯ繝医↓繧医ｊ縲∵桃菴懈ｧ縺ｨ隕冶ｪ肴ｧ縺悟髄荳翫＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">笨ｨ</span>
                    <div>
                        <strong>隕冶ｦ壹ヵ繧｣繝ｼ繝峨ヰ繝・け縺ｮ蠑ｷ蛹・/strong>
                        <span class="detail">蜷・ｨｮ繧､繝ｳ繧ｿ繝ｩ繧ｯ繝・ぅ繝冶ｦ∫ｴ縺ｮ繝医Λ繝ｳ繧ｸ繧ｷ繝ｧ繝ｳ繧・す繝｣繝峨え繧貞ｾｮ隱ｿ謨ｴ縺励√ｈ繧翫・繝ｬ繝溘い繝縺ｪ菴ｿ逕ｨ諢溘ｒ螳溽樟縺励∪縺励◆縲・/span>
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
        <span class="release-title">繝舌ャ繧ｯ繧ｨ繝ｳ繝峨・蝣・欧蛹悶→繧ｻ繧ｭ繝･繝ｪ繝・ぅ蠑ｷ蛹・/span>
        <span class="release-date">2026-05-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 繧ｻ繧ｭ繝･繝ｪ繝・ぅ & 螳牙ｮ壽ｧ (Security & Stability)
            </div>
            <ul>
                <li>
                    <span class="icon">孱・・/span>
                    <div>
                        <strong>API 繝上Φ繝峨Λ縺ｮ繧ｻ繧ｭ繝･繝ｪ繝・ぅ蠑ｷ蛹・/strong>
                        <span class="detail">API 蜈･蜉帛・逅・ｒ蟆ら畑縺ｮ繝ｩ繝・ヱ繝ｼ繝｡繧ｽ繝・ラ縺ｫ邨ｱ蜷医＠縲√げ繝ｭ繝ｼ繝舌Ν螟画焚縺ｸ縺ｮ逶ｴ謗･繧｢繧ｯ繧ｻ繧ｹ繧呈賜髯､縺吶ｋ縺薙→縺ｧ縲√う繝ｳ繧ｸ繧ｧ繧ｯ繧ｷ繝ｧ繝ｳ謾ｻ謦・↑縺ｩ縺ｮ繝ｪ繧ｹ繧ｯ繧剃ｽ取ｸ帙＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">白</span>
                    <div>
                        <strong>繝励Μ繝壹い繝峨せ繝・・繝医Γ繝ｳ繝医・驕ｩ逕ｨ諡｡螟ｧ</strong>
                        <span class="detail">繝｡繝・そ繝ｼ繧ｸ縺ｮ繝斐Φ逡吶ａ隗｣髯､繧・炎髯､縺ｪ縺ｩ縺ｮ蜃ｦ逅・↓縺翫＞縺ｦ縲√ｈ繧贋ｸ雋ｫ縺励※繝励Μ繝壹い繝峨せ繝・・繝医Γ繝ｳ繝医ｒ菴ｿ逕ｨ縺吶ｋ繧医≧縺ｫ謾ｹ蝟・＠縲ヾQL 繧､繝ｳ繧ｸ繧ｧ繧ｯ繧ｷ繝ｧ繝ｳ蟇ｾ遲悶ｒ蠑ｷ蛹悶＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 菫ｮ豁｣ & 謾ｹ蝟・(Fixes & Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">裾</span>
                    <div>
                        <strong>縺頑ｰ励↓蜈･繧頑ｩ溯・縺ｮ隱ｭ縺ｿ霎ｼ縺ｿ荳榊・蜷医ｒ菫ｮ豁｣</strong>
                        <span class="detail">荳驛ｨ縺ｮ迺ｰ蠅・〒縺頑ｰ励↓蜈･繧贋ｸ隕ｧ縺梧ｭ｣縺励￥陦ｨ遉ｺ縺輔ｌ縺ｪ縺・撫鬘後ｒ縲√う繝吶Φ繝医Μ繧ｹ繝翫・縺ｮ譛驕ｩ蛹悶↓繧医▲縺ｦ隗｣豎ｺ縺励∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">女・・/span>
                    <div>
                        <strong>繧ｳ繝ｼ繝峨・蜩∬ｳｪ蜷台ｸ・/strong>
                        <span class="detail">繝舌ャ繧ｯ繧ｨ繝ｳ繝峨さ繝ｼ繝峨↓蝙九ヲ繝ｳ繝医ｒ蟆主・縺励√お繝ｩ繝ｼ繝上Φ繝峨Μ繝ｳ繧ｰ繧呈隼蝟・☆繧九％縺ｨ縺ｧ縲√す繧ｹ繝・Β縺ｮ菫｡鬆ｼ諤ｧ繧帝ｫ倥ａ縺ｾ縺励◆縲・/span>
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
        <span class="release-title">縺頑ｰ励↓蜈･繧顔ｮ｡逅・ｩ溯・縺ｮ蠑ｷ蛹悶→ UI 縺ｮ豢礼ｷｴ</span>
        <span class="release-date">2026-05-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 譁ｰ讖溯・ (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">箝・/span>
                    <div>
                        <strong>縺頑ｰ励↓蜈･繧顔ｮ｡逅・・繝ｼ繧ｸ縺ｮ霑ｽ蜉</strong>
                        <span class="detail">縺頑ｰ励↓蜈･繧翫↓逋ｻ骭ｲ縺励◆繧ｹ繝ｬ繝・ラ繧剃ｸ隕ｧ縺ｧ遒ｺ隱阪・邂｡逅・〒縺阪ｋ蟆ら畑繝壹・繧ｸ繧定ｿｽ蜉縺励∪縺励◆縲・/span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 謾ｹ蝟・& UI (Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">導</span>
                    <div>
                        <strong>繝倥ャ繝繝ｼ繧ｳ繝ｳ繝昴・繝阪Φ繝医・蜈ｱ騾壼喧</strong>
                        <span class="detail">蜷・・繝ｼ繧ｸ縺ｧ荳雋ｫ縺励◆謫堺ｽ懊ｒ謠蝉ｾ帙☆繧九◆繧√√・繝・ム繝ｼ繧貞・騾壹さ繝ｳ繝昴・繝阪Φ繝亥喧縺励∪縺励◆縲ゅせ繝ｬ繝・ラ蜀・〒縺ｮ讀懃ｴ｢縲∵ｷｻ莉倥ヵ繧｡繧､繝ｫ荳隕ｧ縲√ヴ繝ｳ逡吶ａ繝｡繝・そ繝ｼ繧ｸ縺ｸ縺ｮ繧｢繧ｯ繧ｻ繧ｹ縺後ｈ繧翫せ繝繝ｼ繧ｺ縺ｫ縺ｪ繧翫∪縺吶・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">訣</span>
                    <div>
                        <strong>螟夊ｨ隱槫ｯｾ蠢懊・蠑ｷ蛹・/strong>
                        <span class="detail">縺頑ｰ励↓蜈･繧頑ｩ溯・縺ｫ髢｢騾｣縺吶ｋ鄙ｻ險ｳ繝ｪ繧ｽ繝ｼ繧ｹ・域律繝ｻ闍ｱ繝ｻ荳ｭ・峨ｒ諡｡蜈・＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 菫ｮ豁｣ & 蜀・Κ謾ｹ蝟・(Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">肌</span>
                    <div>
                        <strong>API 騾壻ｿ｡縺ｮ譛驕ｩ蛹・/strong>
                        <span class="detail">縺頑ｰ励↓蜈･繧頑桃菴懊・ API 騾壻ｿ｡繧・JSON 蠖｢蠑上↓邨ｱ荳縺励∝ｮ牙・諤ｧ繧貞髄荳翫＆縺帙∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">裾</span>
                    <div>
                        <strong>DM 繝倥ャ繝繝ｼ陦ｨ遉ｺ縺ｮ菫ｮ豁｣</strong>
                        <span class="detail">繝繧､繝ｬ繧ｯ繝医Γ繝・そ繝ｼ繧ｸ逕ｻ髱｢縺ｧ逶ｸ謇九・繝ｦ繝ｼ繧ｶ繝ｼ蜷阪′豁｣縺励￥陦ｨ遉ｺ縺輔ｌ縺ｪ縺・撫鬘後ｒ菫ｮ豁｣縺励∪縺励◆縲・/span>
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
        <span class="release-title">繝√Ε繝・ヨ讖溯・縺ｮ蜈ｨ髱｢逧・↑菫ｮ豁｣縺ｨ蠑ｷ蛹・/span>
        <span class="release-date">2026-05-03</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-red"></span> 繝舌げ菫ｮ豁｣ (Bug Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">東</span>
                    <div>
                        <strong>繝斐Φ逡吶ａ讖溯・縺ｮ螳悟・縺ｪ菫ｮ豁｣</strong>
                        <span class="detail">繝｡繝・そ繝ｼ繧ｸ縺ｮ繝斐Φ逡吶ａ繝ｻ隗｣髯､繝懊ち繝ｳ縺梧ｭ｣縺励￥蜍穂ｽ懊＠縺ｪ縺九▲縺溷撫鬘後ｒ隗｣豸医ゅヴ繝ｳ逡吶ａ蠕後↓繝｡繝・そ繝ｼ繧ｸ荳隕ｧ縺瑚・蜍墓峩譁ｰ縺輔ｌ繧九ｈ縺・↓縺ｪ繧翫√ヴ繝ｳ逡吶ａ荳隕ｧ繝｢繝ｼ繝繝ｫ繧ゅせ繝ｬ繝・ラID繧呈ｭ｣縺励￥貂｡縺励※API繧貞他縺ｶ繧医≧菫ｮ豁｣縺励∪縺励◆縲ゅ∪縺溽匱菫｡閠・錐繝ｻ譌･譎ゅｂ陦ｨ遉ｺ縺輔ｌ繧ｯ繝ｪ繝・け縺ｧ蠖楢ｩｲ繝｡繝・そ繝ｼ繧ｸ縺ｸ繧ｸ繝｣繝ｳ繝励〒縺阪ｋ繧医≧縺ｫ縺ｪ繧翫∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">・</span>
                    <div>
                        <strong>繝ｪ繧｢繧ｯ繧ｷ繝ｧ繝ｳ讖溯・縺ｮ菫ｮ豁｣</strong>
                        <span class="detail">邨ｵ譁・ｭ励Μ繧｢繧ｯ繧ｷ繝ｧ繝ｳ繝斐ャ繧ｫ繝ｼ縺瑚｡ｨ遉ｺ縺輔ｌ縺ｪ縺九▲縺溷撫鬘後ｒ菫ｮ豁｣縲ゅヵ繝ｭ繝ｼ繝・ぅ繝ｳ繧ｰ縺ｪ邨ｵ譁・ｭ励ヴ繝・き繝ｼ・芋汨坂擘・条沽・縺ｪ縺ｩ10遞ｮ鬘橸ｼ峨′陦ｨ遉ｺ縺輔ｌ縲・∈謚槭☆繧九→繝ｪ繧｢繧ｯ繧ｷ繝ｧ繝ｳ縺ｮ繝医げ繝ｫ縺梧ｭ｣縺励￥讖溯・縺吶ｋ繧医≧縺ｫ縺ｪ繧翫∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">笨擾ｸ・/span>
                    <div>
                        <strong>繝｡繝・そ繝ｼ繧ｸ邱ｨ髮・・蜑企勁縺ｮ菫ｮ豁｣</strong>
                        <span class="detail">邱ｨ髮・・繧ｿ繝ｳ縺後う繝ｳ繝ｩ繧､繝ｳ邱ｨ髮・お繝ｪ繧｢繧定｡ｨ遉ｺ縺吶ｋ繧医≧縺ｫ菫ｮ豁｣縲ょ炎髯､繝懊ち繝ｳ繧ら｢ｺ隱阪ム繧､繧｢繝ｭ繧ｰ莉倥″縺ｧ豁｣縺励￥蜍穂ｽ懊☆繧九ｈ縺・ｿｮ豁｣縺励∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">竊ｩ・・/span>
                    <div>
                        <strong>霑比ｿ｡讖溯・縺ｮ菫ｮ豁｣</strong>
                        <span class="detail"><code>reply_to_id</code> 縺窟PI縺ｫ騾∽ｿ｡縺輔ｌ縺ｦ縺・↑縺九▲縺溷撫鬘後ｒ菫ｮ豁｣縲ゅヰ繝・け繧ｨ繝ｳ繝峨〒繧・<code>reply_to_id</code> 繧棚NSERT譁・↓豁｣縺励￥菫晏ｭ倥☆繧九ｈ縺・隼蝟・＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">剥</span>
                    <div>
                        <strong>繝｡繝・そ繝ｼ繧ｸ讀懃ｴ｢繝ｻ豺ｻ莉倥ヵ繧｡繧､繝ｫ荳隕ｧ縺ｮ菫ｮ豁｣</strong>
                        <span class="detail">讀懃ｴ｢繧ｯ繧ｨ繝ｪ縺ｮAPI繝代せ莠碁㍾蛹悶ヰ繧ｰ繧剃ｿｮ豁｣縲よｷｻ莉倥ヵ繧｡繧､繝ｫ繧ｮ繝｣繝ｩ繝ｪ繝ｼ縺ｧ繝輔ぅ繝ｼ繝ｫ繝牙錐荳堺ｸ閾ｴ・・code>item.path</code> 竊・<code>item.attachment_path</code>・峨↓繧医▲縺ｦ逕ｻ蜒上′陦ｨ遉ｺ縺輔ｌ縺ｪ縺九▲縺溷撫鬘後ｒ隗｣豸医＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 謾ｹ蝟・(Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">竚ｨ・・/span>
                    <div>
                        <strong>繧ｭ繝ｼ繝懊・繝峨す繝ｧ繝ｼ繝医き繝・ヨ縺ｮ螳溯｣・/strong>
                        <span class="detail"><code>Alt+P</code> 縺ｧ繝斐Φ逡吶ａ繝｡繝・そ繝ｼ繧ｸ荳隕ｧ縲・code>/</code> 縺ｧ讀懃ｴ｢繝輔か繝ｼ繧ｫ繧ｹ縲・code>Alt+Shift+?</code> 縺ｧ繧ｷ繝ｧ繝ｼ繝医き繝・ヨ荳隕ｧ繧定｡ｨ遉ｺ縺ｧ縺阪ｋ繧医≧縺ｫ縺ｪ繧翫∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">淀・・/span>
                    <div>
                        <strong>繝｡繝・そ繝ｼ繧ｸ蜿門ｾ輸PI縺ｮ蠑ｷ蛹・/strong>
                        <span class="detail"><code>getMessages</code> API縺ｧ繝ｪ繧｢繧ｯ繧ｷ繝ｧ繝ｳ繝ｻ霑比ｿ｡蜈・Θ繝ｼ繧ｶ繝ｼ蜷阪・繧ｪ繝ｳ繝ｩ繧､繝ｳ繧ｹ繝・・繧ｿ繧ｹ繧剃ｸ諡ｬ蜿門ｾ励☆繧九ｈ縺・隼蝟・ゆｸ崎ｦ√↑霑ｽ蜉繝ｪ繧ｯ繧ｨ繧ｹ繝医ｒ謗帝勁縺励∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">迫</span>
                    <div>
                        <strong>迥ｶ諷狗ｮ｡逅・・謾ｹ蝟・/strong>
                        <span class="detail">繧ｹ繝ｬ繝・ラ蛻・ｊ譖ｿ縺域凾縺ｫ <code>window.SYCS_CONFIG.currentThreadId</code> 繧貞酔譛滓峩譁ｰ縺吶ｋ繧医≧菫ｮ豁｣縲ょ推繝｢繧ｸ繝･繝ｼ繝ｫ縺悟ｸｸ縺ｫ豁｣縺励＞繧ｹ繝ｬ繝・ラID繧貞盾辣ｧ縺ｧ縺阪ｋ繧医≧縺ｫ縺ｪ繧翫∪縺励◆縲・/span>
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
        <span class="release-title">螟ｧ隕乗ｨ｡縺ｪ繧｢繝ｼ繧ｭ繝・け繝√Ε縺ｮ蛻ｷ譁ｰ縺ｨ繝代ヵ繧ｩ繝ｼ繝槭Φ繧ｹ蜷台ｸ・/span>
        <span class="release-date">2026-05-02</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 螳牙ｮ壽ｧ & 繧ｻ繧ｭ繝･繝ｪ繝・ぅ (Stability & Security)
            </div>
            <ul>
                <li>
                    <span class="icon">女・・/span>
                    <div>
                        <strong>繝舌ャ繧ｯ繧ｨ繝ｳ繝陰PI縺ｮ蛻・屬縺ｨ蝣・欧蛹・/strong>
                        <span class="detail">API縺ｮ繝ｫ繝ｼ繝・ぅ繝ｳ繧ｰ縺ｨ繝・・繧ｿ繝吶・繧ｹ蛻晄悄蛹悶Ο繧ｸ繝・け繧貞ｰら畑縺ｮ繝上Φ繝峨Λ繧ｯ繝ｩ繧ｹ・・code>Handler.php</code>縲・code>db_init.php</code>・峨↓謚ｽ蜃ｺ縺励√ヰ繝・け繧ｨ繝ｳ繝峨・菫晏ｮ域ｧ縺ｨ繧ｻ繧ｭ繝･繝ｪ繝・ぅ繧貞､ｧ蟷・↓蜷台ｸ翫＆縺帙∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">笞呻ｸ・/span>
                    <div>
                        <strong>繝輔Ο繝ｳ繝医お繝ｳ繝峨・繝｢繧ｸ繝･繝ｼ繝ｫ蛹・/strong>
                        <span class="detail">蟾ｨ螟ｧ蛹悶＠縺ｦ縺・◆ <code>index.js</code> 繧・ES6 繝｢繧ｸ繝･繝ｼ繝ｫ・・code>api.js</code>縲・code>chat.js</code>縲・code>ui.js</code> 縺ｪ縺ｩ・峨↓蛻・牡縺励√さ繝ｼ繝峨・菫晏ｮ域ｧ縺ｨ隱ｭ縺ｿ霎ｼ縺ｿ繝代ヵ繧ｩ繝ｼ繝槭Φ繧ｹ繧呈怙驕ｩ蛹悶＠縺ｾ縺励◆縲・/span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 謾ｹ蝟・& 繝ｪ繝輔ぃ繧ｯ繧ｿ繝ｪ繝ｳ繧ｰ (Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>CSS縺ｮ繧ｳ繝ｳ繝昴・繝阪Φ繝亥喧</strong>
                        <span class="detail">蜊倅ｸ縺ｮ <code>style.css</code> 繧定ｫ也炊逧・↑繝｢繧ｸ繝･繝ｼ繝ｫ・・code>layout.css</code>縲・code>components.css</code>縲・code>modals.css</code> 縺ｪ縺ｩ・峨↓蛻・牡繝ｻ蜀肴ｧ狗ｯ峨＠縲ゞI縺ｮ諡｡蠑ｵ諤ｧ繧帝ｫ倥ａ縺ｾ縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">ｧｱ</span>
                    <div>
                        <strong>UI繝・Φ繝励Ξ繝ｼ繝医・繧ｳ繝ｳ繝昴・繝阪Φ繝亥喧</strong>
                        <span class="detail"><code>index.php</code> 縺ｫ髮・ｸｭ縺励※縺・◆HTML讒矩繧・<code>sidebar.php</code> 繧・<code>modals.php</code> 縺ｪ縺ｩ縺ｮ繧､繝ｳ繧ｯ繝ｫ繝ｼ繝峨ヵ繧｡繧､繝ｫ縺ｫ蛻・牡縺励ゞI縺ｮ荳雋ｫ諤ｧ縺ｨ髢狗匱蜉ｹ邇・ｒ蜷台ｸ翫＆縺帙∪縺励◆縲・/span>
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
        <span class="release-title">UI/UX 縺ｮ豢礼ｷｴ縺ｨ螟夊ｨ隱槫ｯｾ蠢懊・諡｡蜈・/span>
        <span class="release-date">2026-04-25</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 謾ｹ蝟・& 菫ｮ豁｣ (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>繝斐Φ逡吶ａ繝｡繝・そ繝ｼ繧ｸ縺ｮ繧｢繧､繧ｳ繝ｳ蛻ｷ譁ｰ</strong>
                        <span class="detail">邨ｵ譁・ｭ励°繧牙ｰら畑縺ｮ SVG 繧｢繧､繧ｳ繝ｳ (`pin.svg`) 縺ｫ螟画峩縺励ゞI 縺ｮ荳雋ｫ諤ｧ縺ｨ繝励Ο繝輔ぉ繝・す繝ｧ繝翫Ν縺ｪ螟冶ｦｳ繧貞髄荳翫＆縺帙∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">側</span>
                    <div>
                        <strong>繝励Ο繝輔ぅ繝ｼ繝ｫ邱ｨ髮・ｩ溯・縺ｮ蠑ｷ蛹・/strong>
                        <span class="detail">繝ｬ繧､繧｢繧ｦ繝磯∈謚槭ｄ繝舌リ繝ｼ逕ｻ蜒剰ｨｭ螳壹・繝ｩ繝吶Ν繧貞､夊ｨ隱槫ｯｾ蠢懶ｼ・18n・牙喧縺励√さ繝ｼ繝牙・縺ｮ繝上・繝峨さ繝ｼ繝峨＆繧後◆繝・く繧ｹ繝医ｒ謗帝勁縺励∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">訣</span>
                    <div>
                        <strong>螟夊ｨ隱槭Μ繧ｽ繝ｼ繧ｹ縺ｮ諡｡蜈・(JA/EN/ZH)</strong>
                        <span class="detail">譎りｨ医え繧｣繧ｸ繧ｧ繝・ヨ縺ｮ陦ｨ遉ｺ蛻・崛・医ョ繧ｸ繧ｿ繝ｫ/繧｢繝翫Ο繧ｰ・峨ｄ ToDo 繝ｪ繧ｹ繝医・謫堺ｽ懃畑繝・く繧ｹ繝医ｒ霑ｽ蜉縺励√☆縺ｹ縺ｦ縺ｮ蟇ｾ蠢懆ｨ隱槭〒荳雋ｫ縺励◆繝ｦ繝ｼ繧ｶ繝ｼ菴馴ｨ薙ｒ謠蝉ｾ帙＠縺ｾ縺吶・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">ｧｹ</span>
                    <div>
                        <strong>繝輔Ο繝ｳ繝医お繝ｳ繝峨さ繝ｼ繝峨・繧ｯ繝ｪ繝ｼ繝ｳ繧｢繝・・</strong>
                        <span class="detail">繝・Φ繝励Ξ繝ｼ繝医ヵ繧｡繧､繝ｫ蜀・・繧､繝ｳ繝ｩ繧､繝ｳ繝・く繧ｹ繝医ｒ險隱槫ｮ壽焚縺ｫ鄂ｮ縺肴鋤縺医∽ｿ晏ｮ域ｧ繧帝ｫ倥ａ縺ｾ縺励◆縲・/span>
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
        <span class="release-title">繝ｬ繝ｳ繝繝ｪ繝ｳ繧ｰ繧ｨ繝ｳ繧ｸ繝ｳ縺ｮ繧ｻ繧ｭ繝･繝ｪ繝・ぅ蠑ｷ蛹・(XSS 蟇ｾ遲・</span>
        <span class="release-date">2026-04-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 螳牙ｮ壽ｧ & 繧ｻ繧ｭ繝･繝ｪ繝・ぅ (Stability & Security)
            </div>
            <ul>
                <li>
                    <span class="icon">孱・・/span>
                    <div>
                        <strong>謠冗判繝ｭ繧ｸ繝・け縺ｮ蛻ｷ譁ｰ縺ｫ繧医ｋ XSS 蟇ｾ遲・/strong>
                        <span class="detail">`innerHTML` 縺ｮ菴ｿ逕ｨ繧貞､ｧ蟷・↓蜑頑ｸ帙＠縲～createElement` 縺ｨ `textContent` 繧剃ｽｿ逕ｨ縺励◆螳牙・縺ｪ DOM 逕滓・譁ｹ蠑上↓遘ｻ陦後＠縺ｾ縺励◆縲ゅ％繧後↓繧医ｊ縲∵が諢上・縺ゅｋ繧ｹ繧ｯ繝ｪ繝励ヨ縺ｮ豺ｷ蜈･繧堤黄逅・噪縺ｫ髦ｲ縺弱∪縺吶・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">剥</span>
                    <div>
                        <strong>escapeHTML 髢｢謨ｰ縺ｮ譛驕ｩ蛹・/strong>
                        <span class="detail">迚ｹ谿頑枚蟄励・蜃ｦ逅・ｒ繧医ｊ遒ｺ螳溘↓陦後≧繧医≧繝ｭ繧ｸ繝・け繧呈隼蝟・＠縲√ョ繝ｼ繧ｿ縺ｮ謨ｴ蜷域ｧ縺ｨ螳牙・諤ｧ繧帝ｫ倥ａ縺ｾ縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">笞｡</span>
                    <div>
                        <strong>繧ｦ繧｣繧ｸ繧ｧ繝・ヨ陦ｨ遉ｺ縺ｮ螳牙ｮ壼喧</strong>
                        <span class="detail">騾夂衍繝ｪ繧ｹ繝医√ヵ繧｡繧､繝ｫ荳隕ｧ縲ゝoDo 繝ｪ繧ｹ繝医・繝ｬ繝ｳ繝繝ｪ繝ｳ繧ｰ繧定ｿ台ｻ｣逧・↑謇区ｳ輔↓邨ｱ荳縺励∪縺励◆縲・/span>
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
        <span class="release-title">UI/UX 繝・じ繧､繝ｳ縺ｮ蠕ｮ隱ｿ謨ｴ縺ｨ繝｢繝ｼ繝繝ｫ縺ｮ隕冶ｪ肴ｧ蜷台ｸ・/span>
        <span class="release-date">2026-04-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 謾ｹ蝟・& 菫ｮ豁｣ (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>繝｢繝ｼ繝繝ｫ繧ｦ繧｣繝ｳ繝峨え縺ｮ繧ｵ繧､繧ｺ縺ｨ閭梧勹濶ｲ縺ｮ譛驕ｩ蛹・/strong>
                        <span class="detail">`group-creation-modal` 繧・`keyboard-shortcuts-modal` 縺ｮ閭梧勹濶ｲ縺翫ｈ縺ｳ繧ｵ繧､繧ｺ繧定ｪｿ謨ｴ縺励∬ｦ冶ｪ肴ｧ縺ｨ謫堺ｽ懈ｧ繧貞髄荳翫＆縺帙∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">曝</span>
                    <div>
                        <strong>繝懊ち繝ｳ縺ｮ隗剃ｸｸ縺ｨ蟷・・隱ｿ謨ｴ縺ｫ繧医ｋ謫堺ｽ懈ｧ蜷台ｸ・/strong>
                        <span class="detail">繝励Λ繧､繝吶・繝医・繧ｿ繝ｳ繧・メ繝｣繝・ヨ繝倥ャ繝繝ｼ蜀・・繝懊ち繝ｳ縺ｮ `border-radius` 縺ｨ `width` 繧貞ｾｮ隱ｿ謨ｴ縺励√ｈ繧翫Δ繝繝ｳ縺ｧ菴ｿ縺・ｄ縺吶＞繝・じ繧､繝ｳ縺ｫ譖ｴ譁ｰ縺励∪縺励◆縲・/span>
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
        <span class="release-title">Markdown 繝ｬ繝ｳ繝繝ｪ繝ｳ繧ｰ繧ｨ繝ｳ繧ｸ繝ｳ縺ｮ繧ｻ繧ｭ繝･繝ｪ繝・ぅ蠑ｷ蛹・(XSS蟇ｾ遲・</span>
        <span class="release-date">2026-04-01</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 螳牙ｮ壽ｧ & 繧ｻ繧ｭ繝･繝ｪ繝・ぅ (Stability & Security)
            </div>
            <ul>
                <li>
                    <span class="icon">孱・・/span>
                    <div>
                        <strong>繝ｬ繝ｳ繝繝ｪ繝ｳ繧ｰ譁ｹ蠑上・蛻ｷ譁ｰ縺ｫ繧医ｋ XSS 蟇ｾ遲・/strong>
                        <span class="detail">`innerHTML` 繧貞ｮ悟・縺ｫ蟒・ｭ｢縺励～DocumentFragment` 縺ｨ `createTextNode` 繧剃ｽｿ逕ｨ縺励※ DOM 繝弱・繝峨ｒ逶ｴ謗･逕滓・縺吶ｋ譁ｹ蠑上↓遘ｻ陦後＠縺ｾ縺励◆縲ゅ％繧後↓繧医ｊ縲∵が諢上・縺ゅｋ繧ｹ繧ｯ繝ｪ繝励ヨ縺ｮ螳溯｡後ｒ迚ｩ逅・噪縺ｫ驕ｮ譁ｭ縺励∝ｮ牙・縺ｪ繝√Ε繝・ヨ菴馴ｨ薙ｒ謠蝉ｾ帙＠縺ｾ縺吶・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">剥</span>
                    <div>
                        <strong>繝輔か繝ｼ繝槭ャ繝磯←逕ｨ縺ｮ蜴ｳ譬ｼ蛹・/strong>
                        <span class="detail">繧ｳ繝ｼ繝峨ヶ繝ｭ繝・け蜀・〒縺ｮ莠碁㍾繝輔か繝ｼ繝槭ャ繝医・驕ｩ逕ｨ繧帝亟豁｢縺吶ｋ繝ｭ繧ｸ繝・け繧貞ｰ主・縲ゅさ繝ｼ繝峨・蜿ｯ隱ｭ諤ｧ繧呈錐縺ｪ縺・％縺ｨ縺ｪ縺上∫｢ｺ螳溘↑繝ｬ繝ｳ繝繝ｪ繝ｳ繧ｰ繧貞ｮ溽樟縺励∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">笞｡</span>
                    <div>
                        <strong>繝｡繝・そ繝ｼ繧ｸ陦ｨ遉ｺ縺ｮ譛驕ｩ蛹・/strong>
                        <span class="detail">`replaceChildren` 繝｡繧ｽ繝・ラ繧呈治逕ｨ縺励∵怙譁ｰ縺ｮ繝悶Λ繧ｦ繧ｶ讓呎ｺ悶↓蜑・▲縺滄ｫ倬溘〒螳牙・縺ｪ繧ｳ繝ｳ繝・Φ繝・峩譁ｰ繧貞ｮ溯｣・＠縺ｾ縺励◆縲・/span>
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
        <span class="release-title">Markdown 繝ｬ繝ｳ繝繝ｪ繝ｳ繧ｰ縺ｨ繧ｳ繝ｼ繝峨す繝ｳ繧ｿ繝・け繧ｹ繝上う繝ｩ繧､繝医∈縺ｮ蟇ｾ蠢・/span>
        <span class="release-date">2026-03-30</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 譁ｰ讖溯・ (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">統</span>
                    <div>
                        <strong>Markdown / 繝ｪ繝・メ繝・く繧ｹ繝・繝ｬ繝ｳ繝繝ｪ繝ｳ繧ｰ</strong>
                        <span class="detail">螟ｪ蟄・(**bold**)縲∵万菴・(*italic*)縲∽ｸ狗ｷ・(__underline__)縲∵遠縺｡豸医＠邱・(~~strike~~)縲√♀繧医・蠑慕畑 (blockquote) 縺ｫ蟇ｾ蠢懊＠縺ｾ縺励◆縲ゅメ繝｣繝・ヨ蜀・〒縺ｮ譟碑ｻ溘↑陦ｨ迴ｾ縺悟庄閭ｽ縺ｫ縺ｪ繧翫∪縺吶・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">捗</span>
                    <div>
                        <strong>繧ｳ繝ｼ繝峨ヶ繝ｭ繝・け 繧ｷ繝ｳ繧ｿ繝・け繧ｹ繝上う繝ｩ繧､繝・(Highlight.js)</strong>
                        <span class="detail">`highlight.js` 繧貞ｰ主・縺励∬､・焚險隱槭・繧ｳ繝ｼ繝峨ヶ繝ｭ繝・け縺ｫ蟇ｾ縺励※繧ｷ繝ｳ繧ｿ繝・け繧ｹ繝上う繝ｩ繧､繝医ｒ驕ｩ逕ｨ縺励∪縺励◆縲る幕逋ｺ閠・俣縺ｮ繧ｳ繝ｼ繝牙・譛峨′繧医ｊ隕九ｄ縺吶￥縺ｪ繧翫∪縺吶・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">迫</span>
                    <div>
                        <strong>繧ｹ繝槭・繝医↑繝｡繝・そ繝ｼ繧ｸ繝輔か繝ｼ繝槭ャ繝・/strong>
                        <span class="detail">URL 縺ｮ閾ｪ蜍輔Μ繝ｳ繧ｯ蛹悶ｄ繝｡繝ｳ繧ｷ繝ｧ繝ｳ蜃ｦ逅・・蝣・欧諤ｧ繧貞髄荳翫＆縺帙√Γ繝・そ繝ｼ繧ｸ縺ｮ隕冶ｪ肴ｧ繧帝ｫ倥ａ縺ｾ縺励◆縲・/span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 謾ｹ蝟・(Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>UI 繝・じ繧､繝ｳ縺ｮ蠕ｮ隱ｿ謨ｴ</strong>
                        <span class="detail">PWA 繧､繝ｳ繧ｹ繝医・繝ｫ繝懊ち繝ｳ縺ｮ繧ｳ繝ｳ繝医Λ繧ｹ繝医ｒ謾ｹ蝟・＠縲∬ｦ冶ｪ肴ｧ繧帝ｫ倥ａ縺ｾ縺励◆縲・/span>
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
        <span class="release-title">WebRTC 謗･邯壹・螳牙ｮ壼喧縺ｨ騾夂衍繧ｷ繧ｹ繝・Β縺ｮ蝣・欧蛹・/span>
        <span class="release-date">2026-03-27</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 謾ｹ蝟・& 菫ｮ豁｣ (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">磁</span>
                    <div>
                        <strong>WebRTC 繧ｷ繧ｰ繝翫Μ繝ｳ繧ｰ縺ｮ螳牙ｮ壽ｧ蜷台ｸ・/strong>
                        <span class="detail">ICE candidate 縺ｮ菫晉蕗繧ｭ繝･繝ｼ繧貞ｮ溯｣・＠縺ｾ縺励◆縲ゅ％繧後↓繧医ｊ縲√ン繝・が莨夊ｭｰ縺ｮ謗･邯夂｢ｺ遶区凾縺ｫ candidate 縺悟叙繧翫％縺ｼ縺輔ｌ繧句撫鬘後ｒ隗｣豸医＠縲√ｈ繧顔｢ｺ螳溘↑謗･邯壹ｒ蜿ｯ閭ｽ縺ｫ縺励∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">孱・・/span>
                    <div>
                        <strong>騾夂衍繧ｨ繝ｳ繧ｸ繝ｳ縺ｮ繧ｨ繝ｩ繝ｼ繝上Φ繝峨Μ繝ｳ繧ｰ蠑ｷ蛹・/strong>
                        <span class="detail">繝ｪ繧｢繝ｫ繧ｿ繧､繝騾夂衍縺翫ｈ縺ｳ繝励ャ繧ｷ繝･騾夂衍縺ｮ騾∽ｿ｡蜃ｦ逅・↓ HTTP 繧ｹ繝・・繧ｿ繧ｹ繧ｳ繝ｼ繝峨メ繧ｧ繝・け繧貞ｰ主・縺励∪縺励◆縲ゅヰ繝・け繧ｨ繝ｳ繝蛾｣謳ｺ縺ｮ螟ｱ謨励ｒ豁｣遒ｺ縺ｫ讀懃衍縺励√Ο繧ｰ縺ｫ蜃ｺ蜉帙＠縺ｾ縺吶・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">柏</span>
                    <div>
                        <strong>險ｭ螳壹・蝣・欧蛹悶→繧ｳ繝ｼ繝画紛逅・/strong>
                        <span class="detail">繧ｷ繝ｼ繧ｯ繝ｬ繝・ヨ繧ｭ繝ｼ縺ｮ蜿ら・繝ｭ繧ｸ繝・け繧呈怙驕ｩ蛹悶＠縲∫腸蠅・ｨｭ螳壹・荳榊ｙ縺ｫ蟇ｾ蜃ｦ縺励∪縺励◆縲ゅ∪縺溘∽ｸ崎ｦ√↑繝輔ぃ繧､繝ｫ隱ｭ縺ｿ霎ｼ縺ｿ繧貞炎髯､縺励ヱ繝輔か繝ｼ繝槭Φ繧ｹ繧貞髄荳翫＆縺帙∪縺励◆縲・/span>
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
        <span class="release-title">繧｢繝翫Ο繧ｰ譎りｨ医え繧｣繧ｸ繧ｧ繝・ヨ縺ｮ螟ｧ蟷・↑讖溯・蠑ｷ蛹・/span>
        <span class="release-date">2026-03-22</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 譁ｰ讖溯・ (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">竚・/span>
                    <div>
                        <strong>繧｢繝翫Ο繧ｰ譎りｨ医・繧ｹ繧､繝ｼ繝鈴°驥昴→繧ｵ繝悶ム繧､繝､繝ｫ縺ｮ螳溯｣・/strong>
                        <span class="detail"><code>requestAnimationFrame</code> 繧呈治逕ｨ縺励∫ｧ帝・縺ｮ繧ｹ繝繝ｼ繧ｺ縺ｪ蜍輔″繧貞ｮ溽樟縺励∪縺励◆縲ゅ∪縺溘・4譎る俣險医∵屆譌･險医∫峡遶狗ｧ定ｨ医・繧ｵ繝悶ム繧､繝､繝ｫ繧貞ｮ滓ｩ溯・縺ｨ縺励※螳溯｣・＠縲√ｈ繧頑悽譬ｼ逧・↑譎りｨ井ｽ馴ｨ薙ｒ謠蝉ｾ帙＠縺ｾ縺吶・/span>
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
        <span class="release-title">繧ｷ繧ｹ繝・Β蝓ｺ逶､縺ｮ蠑ｷ蛹悶→螟夊ｨ隱槫ｯｾ蠢懊・譛驕ｩ蛹・/span>
        <span class="release-date">2026-03-21</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 謾ｹ蝟・& 菫ｮ豁｣ (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">柏</span>
                    <div>
                        <strong>繧ｻ繝・す繝ｧ繝ｳ繝ｻCookie邂｡逅・・蝣・欧蛹・/strong>
                        <span class="detail">繝舌ャ繧ｯ繧ｨ繝ｳ繝峨・繧ｻ繝・す繝ｧ繝ｳ縺ｨCookie蜃ｦ逅・ｒ蛻ｷ譁ｰ縺励√そ繧ｭ繝･繝ｪ繝・ぅ縺ｨ謗･邯壹・螳牙ｮ壽ｧ繧貞､ｧ蟷・↓蜷台ｸ翫＆縺帙∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">訣</span>
                    <div>
                        <strong>螟夊ｨ隱槫ｯｾ蠢・(i18n) 繝励Ο繧ｻ繧ｹ縺ｮ譛驕ｩ蛹・/strong>
                        <span class="detail">險隱槫・繧頑崛縺医Ο繧ｸ繝・け繧呈隼蝟・＠縲√ｈ繧翫せ繝繝ｼ繧ｺ縺ｪ繝ｦ繝ｼ繧ｶ繝ｼ菴馴ｨ薙ｒ謠蝉ｾ帙＠縺ｾ縺吶ゅ∪縺溘∽ｸｭ蝗ｽ隱橸ｼ育ｰ｡菴灘ｭ暦ｼ峨Μ繧ｽ繝ｼ繧ｹ繧貞・髱｢逧・↓譖ｴ譁ｰ縺励∪縺励◆縲・/span>
                    </div>
                </li>
                <li>
                    <span class="icon">ｧｹ</span>
                    <div>
                        <strong>繝輔Ο繝ｳ繝医お繝ｳ繝峨・繧ｯ繝ｪ繝ｼ繝ｳ繧｢繝・・</strong>
                        <span class="detail">繝｡繧､繝ｳ逕ｻ髱｢縺ｮ讒矩繧呈紛逅・＠縲∽ｸ崎ｦ√↑繧ｳ繝ｼ繝峨ｒ蜑企勁縺吶ｋ縺薙→縺ｧ繝代ヵ繧ｩ繝ｼ繝槭Φ繧ｹ繧呈怙驕ｩ蛹悶＠縺ｾ縺励◆縲・/span>
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
        <span class="release-title">繧｢繝翫Ο繧ｰ譎りｨ医え繧｣繧ｸ繧ｧ繝・ヨ縺ｮ霑ｽ蜉</span>
        <span class="release-date">2026-03-20</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 譁ｰ讖溯・ (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">竚・/span>
                    <div>
                        <strong>繧｢繝翫Ο繧ｰ譎りｨ医え繧｣繧ｸ繧ｧ繝・ヨ縺ｮ霑ｽ蜉</strong>
                        <span class="detail">
                            繝帙・繝逕ｻ髱｢縺ｫ繧｢繝翫Ο繧ｰ譎りｨ医え繧｣繧ｸ繧ｧ繝・ヨ繧定ｿｽ蜉縺励∪縺励◆縲よ凾蛻ｻ陦ｨ遉ｺ縲∵律莉倩｡ｨ遉ｺ縲∵屆譌･陦ｨ遉ｺ縲∫ｧ帝・陦ｨ遉ｺ縲√し繝悶ム繧､繝､繝ｫ・・2譎ゅ・譎ゅ・譎ゅ・譎ゆｽ咲ｽｮ・峨ｒ蛯吶∴縺滄ｫ俶ｩ溯・縺ｪ譎りｨ医〒縺吶・
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
        <span class="release-title">WebRTC 繧ｷ繧ｰ繝翫Μ繝ｳ繧ｰ縺ｮ Socket.IO 遘ｻ陦後→鬮倬溷喧</span>
        <span class="release-date">2026-03-18</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 謾ｹ蝟・& 菫ｮ豁｣ (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">笞｡</span>
                    <div>
                        <strong>WebRTC 繧ｷ繧ｰ繝翫Μ繝ｳ繧ｰ縺ｮ繝ｪ繧｢繝ｫ繧ｿ繧､繝蛹・/strong>
                        <span class="detail">
                            繝薙ョ繧ｪ莨夊ｭｰ縺ｮ繧ｷ繧ｰ繝翫Μ繝ｳ繧ｰ繧貞ｾ捺擂縺ｮ HTTP 繝昴・繝ｪ繝ｳ繧ｰ縺九ｉ Socket.IO 縺ｫ繧医ｋ蜿梧婿蜷鷹壻ｿ｡縺ｫ遘ｻ陦後＠縺ｾ縺励◆縲ゅ％繧後↓繧医ｊ縲∵磁邯壹・驕・ｻｶ縺悟､ｧ蟷・↓遏ｭ邵ｮ縺輔ｌ縲√し繝ｼ繝舌・雋闕ｷ繧りｻｽ貂帙＆繧後∪縺励◆縲・
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
        <span class="release-title">繝ｭ繧ｰ繧､繝ｳ逕ｻ髱｢縺ｮUI隱ｿ謨ｴ縺ｨ蠕ｮ邏ｰ縺ｪ繧ｯ繝ｪ繝ｼ繝ｳ繧｢繝・・</span>
        <span class="release-date">2026-03-18</span>
    </div>
    <div class="release-body">

        <!-- UI/UX 蜷台ｸ・-->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> UI/UX 蜷台ｸ・(UI/UX)
            </div>
            <ul>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>繝ｭ繧ｰ繧､繝ｳ繧ｫ繝ｼ繝峨・繝ｬ繧､繧｢繧ｦ繝郁ｪｿ謨ｴ</strong>
                        <span class="detail">
                            繝ｭ繧ｰ繧､繝ｳ逕ｻ髱｢縺ｮ繧ｫ繝ｼ繝画怙螟ｧ蟷・ｒ <code>500px</code> 縺ｫ諡｡螟ｧ縺励√ｈ繧翫ｆ縺ｨ繧翫・縺ゅｋ繝｢繝繝ｳ縺ｪ繝ｬ繧､繧｢繧ｦ繝医↓隱ｿ謨ｴ縺励∪縺励◆縲・
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">ｧｹ</span>
                    <div>
                        <strong>繧ｹ繧ｿ繧､繝ｫ繧ｷ繝ｼ繝医・繧ｯ繝ｪ繝ｼ繝ｳ繧｢繝・・</strong>
                        <span class="detail">
                            <code>style-index.css</code> 蜀・・蜀鈴聞縺ｪ閭梧勹繧ｰ繝ｩ繝・・繧ｷ繝ｧ繝ｳ謖・ｮ壹ｒ蜑企勁縺励√さ繝ｼ繝峨・菫晏ｮ域ｧ繧貞髄荳翫＆縺帙∪縺励◆縲・
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
        <span class="release-title">繝｢繝舌う繝ｫ陦ｨ遉ｺ縺ｮ譛驕ｩ蛹悶→UI縺ｮ繧ｯ繝ｪ繝ｼ繝ｳ繧｢繝・・</span>
        <span class="release-date">2026-03-14</span>
    </div>
    <div class="release-body">

        <!-- UI/UX 蜷台ｸ・-->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> UI/UX 蜷台ｸ・(UI/UX)
            </div>
            <ul>
                <li>
                    <span class="icon">導</span>
                    <div>
                        <strong>繝｢繝舌う繝ｫ繝ｬ繧ｹ繝昴Φ繧ｷ繝悶・蠑ｷ蛹・/strong>
                        <span class="detail">
                            繧ｹ繝槭・繝医ヵ繧ｩ繝ｳ陦ｨ遉ｺ縺ｫ縺翫＞縺ｦ縲√・繝・ム繝ｼ隕∫ｴ縺ｮ謨ｴ逅・ｄ荳崎ｦ√↑繝懊ち繝ｳ・医ン繝・が縲√ヴ繝ｳ逡吶ａ遲会ｼ峨・髱櫁｡ｨ遉ｺ蛹悶ｒ陦後＞縲√メ繝｣繝・ヨ逕ｻ髱｢縺ｮ隕冶ｪ肴ｧ繧貞髄荳翫＆縺帙∪縺励◆縲・
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">ｧｹ</span>
                    <div>
                        <strong>繝ｬ繧､繧｢繧ｦ繝医・繧ｯ繝ｪ繝ｼ繝ｳ繧｢繝・・</strong>
                        <span class="detail">
                            CSS縺ｮ謨ｴ逅・ｼ・code>flex-grow</code>縺ｮ驕ｩ豁｣蛹也ｭ会ｼ峨♀繧医・繝ｭ繧ｰ繧､繝ｳ逕ｻ髱｢縺ｮ繝ｬ繧ｹ繝昴Φ繧ｷ繝門ｯｾ蠢懊ｒ蠑ｷ蛹悶＠縲∝推繝・ヰ繧､繧ｹ縺ｧ荳雋ｫ縺励◆謫堺ｽ懈─繧貞ｮ溽樟縺励∪縺励◆縲・
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
        <span class="release-title">繧ｵ繝ｼ繝舌・繧ｵ繧､繝峨・蝣・欧蛹悶→蜀鈴聞縺ｪ繧ｳ繝ｼ繝峨・謨ｴ逅・/span>
        <span class="release-date">2026-03-11</span>
    </div>
    <div class="release-body">

        <!-- 螳牙ｮ壽ｧ & 菫｡鬆ｼ諤ｧ -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 螳牙ｮ壽ｧ & 菫｡鬆ｼ諤ｧ (Stability & Reliability)
            </div>
            <ul>
                <li>
                    <span class="icon">柏</span>
                    <div>
                        <strong>繝ｪ繧｢繝ｫ繧ｿ繧､繝繧ｵ繝ｼ繝舌・縺ｮ隱崎ｨｼ蜷梧悄縺ｮ謾ｹ蝟・/strong>
                        <span class="detail">
                            <code>index.php</code> 縺翫ｈ縺ｳ <code>server.js</code> 縺ｫ縺翫＞縺ｦ縲・code>REALTIME_SECRET_KEY</code> 縺ｮ隱ｭ縺ｿ霎ｼ縺ｿ繝輔Ο繝ｼ繧呈隼蝟・・
                            迺ｰ蠅・､画焚 <code>SECRET_KEY</code> 縺ｸ縺ｮ繝輔か繝ｼ繝ｫ繝舌ャ繧ｯ蜃ｦ逅・→縲∵悴險ｭ螳壽凾縺ｮ繧ｨ繝ｩ繝ｼ繝上Φ繝峨Μ繝ｳ繧ｰ繧定ｿｽ蜉縺励√す繧ｹ繝・Β髢薙・隱崎ｨｼ蜷梧悄縺ｮ遒ｺ螳滓ｧ繧帝ｫ倥ａ縺ｾ縺励◆縲・
                        </span>
                    </div>
                </li>
            </ul>
        </div>

        <!-- 謾ｹ蝟・& 繝ｪ繝輔ぃ繧ｯ繧ｿ繝ｪ繝ｳ繧ｰ -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 謾ｹ蝟・& 繝ｪ繝輔ぃ繧ｯ繧ｿ繝ｪ繝ｳ繧ｰ (Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">ｧｹ</span>
                    <div>
                        <strong>繝輔Ο繝ｳ繝医お繝ｳ繝峨・繧ｨ繝ｳ繝医Μ繝昴う繝ｳ繝域怙驕ｩ蛹・/strong>
                        <span class="detail">
                            <code>index.php</code> 縺ｫ縺翫＞縺ｦ縲∵里縺ｫ隱ｭ縺ｿ霎ｼ縺ｿ貂医∩縺ｮ蜀鈴聞縺ｪ <code>require_once</code> 蜻ｽ莉､繧貞炎髯､縺励∝・驛ｨ讒矩繧呈紛逅・＠縺ｾ縺励◆縲・
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
        <span class="release-title">螟夊ｨ隱槫ｯｾ蠢・(i18n) 縺ｮ蟆主・縺ｨ繝ｪ繝ｪ繝ｼ繧ｹ繝弱・繝医・蜍慕噪蛹・/span>
        <span class="release-date">2026-03-10</span>
    </div>
    <div class="release-body">

        <!-- 譁ｰ讖溯・ -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 譁ｰ讖溯・ (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">訣</span>
                    <div>
                        <strong>螟夊ｨ隱槫ｯｾ蠢・(i18n) 蝓ｺ逶､縺ｮ蟆主・ (<code>I18n.php</code>)</strong>
                        <span class="detail">
                            繧ｷ繧ｹ繝・Β蜈ｨ菴薙↓螟夊ｨ隱槫ｯｾ蠢懷渕逶､繧貞ｰ主・縺励∵律譛ｬ隱槭→闍ｱ隱槭・蛻・ｊ譖ｿ縺医ｒ繧ｵ繝昴・繝医＠縺ｾ縺励◆縲・
                            險隱櫁ｨｭ螳壹・繧ｻ繝・す繝ｧ繝ｳ縺翫ｈ縺ｳ繧ｯ繝・く繝ｼ縺ｫ繧医ｊ豌ｸ邯壼喧縺輔ｌ縲√Ο繧ｰ繧､繝ｳ逕ｻ髱｢繧・Γ繧､繝ｳ逕ｻ髱｢縺九ｉ繝ｯ繝ｳ繧ｯ繝ｪ繝・け縺ｧ蛻・ｊ譖ｿ縺亥庄閭ｽ縺ｧ縺吶・
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">噫</span>
                    <div>
                        <strong>繝ｪ繝ｪ繝ｼ繧ｹ繝弱・繝医・蜍慕噪繧ｷ繧ｹ繝・Β蛹・(PHP)</strong>
                        <span class="detail">
                            蠕捺擂縺ｮ髱咏噪縺ｪ HTML 蠖｢蠑上°繧峨￣HP 繝吶・繧ｹ縺ｮ蜍慕噪縺ｪ繧ｷ繧ｹ繝・Β縺ｸ蛻ｷ譁ｰ縲・
                            髢ｲ隕ｧ閠・・險隱櫁ｨｭ螳壹↓蜷医ｏ縺帙※縲∵律譛ｬ隱槭→闍ｱ隱槭・蜀・ｮｹ縺瑚・蜍慕噪縺ｫ蜃ｺ縺怜・縺代ｉ繧後ｋ繧医≧縺ｫ縺ｪ繧翫∪縺励◆縲・
                        </span>
                    </div>
                </li>
            </ul>
        </div>

        <!-- UI/UX 蜷台ｸ・-->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> UI/UX 蜷台ｸ・(UI/UX)
            </div>
            <ul>
                <li>
                    <span class="icon">曝</span>
                    <div>
                        <strong>險隱槭そ繝ｬ繧ｯ繧ｿ繝ｼ縺ｮ螳溯｣・/strong>
                        <span class="detail">
                            繝ｭ繧ｰ繧､繝ｳ縲∵眠隕冗匳骭ｲ縲√Γ繧､繝ｳ逕ｻ髱｢縺ｮ繝倥ャ繝繝ｼ縺ｫ險隱槫・繧頑崛縺育畑縺ｮ繧ｻ繝ｬ繧ｯ繧ｿ繝ｼ繧定ｨｭ鄂ｮ縲・
                            繧ｷ繝ｼ繝繝ｬ繧ｹ縺ｪ菴馴ｨ薙ｒ謠蝉ｾ帙☆繧九◆繧√・繧､繝ｳ繧ｿ繝ｼ繝輔ぉ繝ｼ繧ｹ繧貞ｼｷ蛹悶＠縺ｾ縺励◆縲・
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
        <span class="release-title">繝薙ョ繧ｪ莨夊ｭｰ讖溯・縺ｮ螳牙ｮ壽ｧ蜷台ｸ翫→ UI 謾ｹ蝟・/span>
        <span class="release-date">2026-03-09</span>
    </div>
    <div class="release-body">

        <!-- 謾ｹ蝟・& 菫ｮ豁｣ -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 謾ｹ蝟・& 菫ｮ豁｣ (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">磁</span>
                    <div>
                        <strong>WebRTC 繝薙ョ繧ｪ莨夊ｭｰ縺ｮ螳牙ｮ壽ｧ蜷台ｸ・/strong>
                        <span class="detail">
                            <code>webrtc.js</code> 縺翫ｈ縺ｳ <code>meetings.php</code> 縺ｫ縺翫＞縺ｦ縲√せ繝医Μ繝ｼ繝蜿門ｾ怜・逅・ｒ蝣・欧蛹悶ゅヨ繝ｩ繝・け蜊倅ｽ薙〒縺ｮ
                            MediaStream 逕滓・縺ｫ蟇ｾ蠢懊＠縲∵磁邯夂｢ｺ遶区凾縺ｮ繝薙ョ繧ｪ陦ｨ遉ｺ縺ｮ遒ｺ螳滓ｧ繧帝ｫ倥ａ縺ｾ縺励◆縲・
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">導</span>
                    <div>
                        <strong>繝｢繝舌う繝ｫ繝悶Λ繧ｦ繧ｶ縺ｧ縺ｮ蜀咲函莠呈鋤諤ｧ謾ｹ蝟・/strong>
                        <span class="detail">
                            繝薙ョ繧ｪ隕∫ｴ縺ｫ <code>playsinline</code> 螻樊ｧ繧呈・遉ｺ逧・↓莉倅ｸ弱＠縲（OS
                            遲峨・繝｢繝舌う繝ｫ繝悶Λ繧ｦ繧ｶ縺ｧ繝薙ョ繧ｪ縺悟ｼｷ蛻ｶ蜈ｨ逕ｻ髱｢蛹悶＆繧後★縲√う繝ｳ繝ｩ繧､繝ｳ縺ｧ豁｣蟶ｸ縺ｫ蜀咲函縺輔ｌ繧九ｈ縺・↓菫ｮ豁｣縺励∪縺励◆縲・
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">ｧｱ</span>
                    <div>
                        <strong>繝薙ョ繧ｪ繧ｰ繝ｪ繝・ラ縺ｮ繝ｬ繧､繧｢繧ｦ繝井ｸ雋ｫ諤ｧ蜷台ｸ・/strong>
                        <span class="detail">
                            <code>index.php</code> 縺ｨ <code>meetings.php</code> 縺ｮ髢薙〒繝薙ョ繧ｪ陦ｨ遉ｺ逕ｨ繧ｯ繝ｩ繧ｹ蜷阪ｒ
                            <code>video-grid</code> 縺ｫ邨ｱ蜷医＠縲√←縺｡繧峨・逕ｻ髱｢縺ｧ繧ゆｸ雋ｫ縺励◆繧ｰ繝ｪ繝・ラ陦ｨ遉ｺ縺瑚｡後ｏ繧後ｋ繧医≧縺ｫ謾ｹ蝟・＠縺ｾ縺励◆縲・
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">藤</span>
                    <div>
                        <strong>繧ｷ繧ｰ繝翫Μ繝ｳ繧ｰ蜷梧悄縺ｮ謾ｹ蝟・/strong>
                        <span class="detail">
                            繧ｷ繧ｰ繝翫Μ繝ｳ繧ｰ蜃ｦ逅・↓髱槫酔譛溷宛蠕｡繧貞ｰ主・縺励∬､・焚縺ｮ蜷梧凾蜿ょ刈閠・′縺・ｋ迺ｰ蠅・ｸ九〒縺ｮ謗･邯夂｢ｺ遶九ヵ繝ｭ繝ｼ繧偵ｈ繧翫せ繝繝ｼ繧ｺ縺ｫ縺励∪縺励◆縲・
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
        <span class="release-title">繝ｬ繝ｼ繝亥宛髯舌く繝｣繝・す繝･縺ｮ閾ｪ蜍輔け繝ｪ繝ｼ繝ｳ繧｢繝・・縺ｨ螳ｹ驥丞宛髯・/span>
        <span class="release-date">2026-03-06</span>
    </div>
    <div class="release-body">

        <!-- 謾ｹ蝟・& 繝ｪ繝輔ぃ繧ｯ繧ｿ繝ｪ繝ｳ繧ｰ -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 謾ｹ蝟・& 繝ｪ繝輔ぃ繧ｯ繧ｿ繝ｪ繝ｳ繧ｰ (Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">ｧｹ</span>
                    <div>
                        <strong>繝ｬ繝ｼ繝亥宛髯舌く繝｣繝・す繝･縺ｮ閾ｪ蜍輔け繝ｪ繝ｼ繝ｳ繧｢繝・・</strong>
                        <span class="detail">
                            <code>FileRateLimiter.php</code>
                            縺ｫ縺翫＞縺ｦ縲・譎る俣縺斐→縺ｮ閾ｪ蜍輔け繝ｪ繝ｼ繝ｳ繧｢繝・・繝励Ο繧ｻ繧ｹ繧貞ｮ溯｣・よ悄髯仙・繧後・繝・・繧ｿ繧貞ｮ壽悄逧・↓蜑企勁縺励√＆繧峨↓繧ｭ繝｣繝・す繝･繝・ぅ繝ｬ繧ｯ繝医Μ縺ｮ蜷郁ｨ医し繧､繧ｺ縺・00MB繧定ｶ・℃縺励◆蝣ｴ蜷医・蜿､縺・ヵ繧｡繧､繝ｫ縺九ｉ鬆・ｬ｡蜑企勁縺励※80MB遞句ｺｦ縺ｾ縺ｧ邵ｮ蟆上☆繧倶ｻ慕ｵ・∩繧定ｿｽ蜉縺励∪縺励◆縲ゅ％繧後↓繧医ｊ縲√ョ繧｣繧ｹ繧ｯ螳ｹ驥上・蝨ｧ霑ｫ繧帝亟縺弱∪縺吶・
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
        <span class="release-title">繝ｬ繝ｼ繝亥宛髯舌・蟆主・縺ｫ繧医ｋ繧ｷ繧ｹ繝・Β菫晁ｭｷ讖溯・縺ｮ蠑ｷ蛹・/span>
        <span class="release-date">2026-03-06</span>
    </div>
    <div class="release-body">

        <!-- 繧ｻ繧ｭ繝･繝ｪ繝・ぅ & 螳牙ｮ壽ｧ -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 繧ｻ繧ｭ繝･繝ｪ繝・ぅ & 螳牙ｮ壽ｧ (Security & Stability)
            </div>
            <ul>
                <li>
                    <span class="icon">孱・・/span>
                    <div>
                        <strong>API 繝ｬ繝ｼ繝亥宛髯・(Rate Limiting) 縺ｮ螳溯｣・/strong>
                        <span class="detail">
                            繝舌ャ繧ｯ繧ｨ繝ｳ繝峨↓譁ｰ縺溘↓ <code>RateLimiter.php</code> (Redis繝吶・繧ｹ) 縺翫ｈ縺ｳ繝輔か繝ｼ繝ｫ繝舌ャ繧ｯ逕ｨ縺ｮ
                            <code>FileRateLimiter.php</code> 繧定ｿｽ蜉縺励∪縺励◆縲ゆｽ咲ｽｮ諠・ｱ騾∽ｿ｡ API (<code>update_location</code>)
                            縺ｫ蟇ｾ縺励※縲！P繧｢繝峨Ξ繧ｹ縺翫ｈ縺ｳ繝ｦ繝ｼ繧ｶ繝ｼID縺斐→縺ｮ繝ｪ繧ｯ繧ｨ繧ｹ繝井ｸ企剞繧定ｨｭ縺代ｋ縺薙→縺ｧ縲√す繧ｹ繝・Β縺ｸ縺ｮ雋闕ｷ髮・ｸｭ繧・ｸ肴ｭ｣縺ｪ繧ｹ繝代Β騾∽ｿ｡繧帝亟豁｢縺励∝ｮ牙ｮ壹＠縺溘し繝ｼ繝薙せ謠蝉ｾ帙ｒ螳溽樟縺励∪縺吶・
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">竢ｱ・・/span>
                    <div>
                        <strong>繧ｯ繝ｩ繧､繧｢繝ｳ繝亥・縺ｧ縺ｮ繝ｪ繧ｯ繧ｨ繧ｹ繝亥宛蠕｡譛驕ｩ蛹・/strong>
                        <span class="detail">
                            繝輔Ο繝ｳ繝医お繝ｳ繝峨・菴咲ｽｮ諠・ｱ蜿門ｾ怜・逅・(<code>locate.js</code>) 縺ｫ縺翫＞縺ｦ縲∵怙蟆乗峩譁ｰ髢馴囈 (5遘・ 繧貞ｼｷ蛻ｶ縺吶ｋ莉慕ｵ・∩繧貞ｰ主・縲ら┌鬧・↑ API
                            繝ｪ繧ｯ繧ｨ繧ｹ繝医・逋ｺ逕溘ｒ繧ｯ繝ｩ繧､繧｢繝ｳ繝亥・縺ｧ繧よ椛蛻ｶ縺励√し繝ｼ繝舌・縺ｨ繧ｯ繝ｩ繧､繧｢繝ｳ繝亥曙譁ｹ縺ｮ繝ｪ繧ｽ繝ｼ繧ｹ豸郁ｲｻ繧呈怙驕ｩ蛹悶＠縺ｾ縺励◆縲・
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
        <span class="release-title">繝・・繝槫ｯｾ蠢懊・蠑ｷ蛹悶→ UI 豢礼ｷｴ</span>
        <span class="release-date">2026-03-05</span>
    </div>
    <div class="release-body">

        <!-- UI/UX 蜷台ｸ・-->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> UI/UX 蜷台ｸ・(UI/UX Enhancements)
            </div>
            <ul>
                <li>
                    <span class="icon">耳</span>
                    <div>
                        <strong>CSS 螟画焚縺ｫ繧医ｋ荳雋ｫ縺励◆繝・・繝槫ｯｾ蠢・/strong>
                        <span class="detail">
                            <code>style-index.css</code> 縺翫ｈ縺ｳ <code>style.css</code>
                            縺ｫ縺翫＞縺ｦ縲∵枚蟄苓牡繧・レ譎ｯ濶ｲ縺ｮ繝上・繝峨さ繝ｼ繝画欠螳壹ｒ謗帝勁縺励∪縺励◆縲・
                            <code>--text-primary</code> 繧・<code>--text-secondary</code> 縺ｪ縺ｩ縺ｮ CSS
                            螟画焚繧貞・髱｢逧・↓驕ｩ逕ｨ縺励√ム繝ｼ繧ｯ繝・・繝槭・繝ｩ繧､繝医ユ繝ｼ繝槫・繧頑崛縺域凾縺ｮ隕冶ｪ肴ｧ縺ｨ繝・じ繧､繝ｳ縺ｮ荳雋ｫ諤ｧ繧貞､ｧ蟷・↓蜷台ｸ翫＆縺帙∪縺励◆縲・
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
        <span class="release-title">螟夜Κ繧ｵ繝ｼ繝薙せ騾｣謳ｺ縺ｮ螳牙ｮ壽ｧ蜷台ｸ翫→繝ｭ繧ｬ繝ｼ縺ｮ蠑ｷ蛹・/span>
        <span class="release-date">2026-03-05</span>
    </div>
    <div class="release-body">

        <!-- 螳牙ｮ壽ｧ & 菫｡鬆ｼ諤ｧ -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 螳牙ｮ壽ｧ & 菫｡鬆ｼ諤ｧ (Stability & Reliability)
            </div>
            <ul>
                <li>
                    <span class="icon">売</span>
                    <div>
                        <strong>螟夜ΚAPI騾壻ｿ｡縺ｮ繝ｪ繝医Λ繧､讖滓ｧ・(<code>RetryHandler.php</code>)</strong>
                        <span class="detail">
                            Outlook縲．iscord縲；oogle 縺ｪ縺ｩ縺ｮ螟夜Κ繧ｵ繝ｼ繝薙せ騾｣謳ｺ縺ｫ縺翫＞縺ｦ縲∽ｸ譎ら噪縺ｪ繝阪ャ繝医Ρ繝ｼ繧ｯ繧ｨ繝ｩ繝ｼ繧・し繝ｼ繝舌・繧ｨ繝ｩ繝ｼ縺檎匱逕溘＠縺滄圀縺ｫ閾ｪ蜍輔〒蜀崎ｩｦ陦後☆繧区ｩ溯・繧貞ｮ溯｣・・
                            謖・焚繝舌ャ繧ｯ繧ｪ繝輔い繝ｫ繧ｴ繝ｪ繧ｺ繝繧呈治逕ｨ縺励√し繝ｼ繝舌・雋闕ｷ繧呈椛縺医▽縺､騾｣謳ｺ縺ｮ謌仙粥邇・ｒ鬮倥ａ縺ｾ縺励◆縲・
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">笞｡</span>
                    <div>
                        <strong>繝輔ぃ繧､繝ｫ繝吶・繧ｹ縺ｮ繧ｭ繝｣繝・す繝･繧ｷ繧ｹ繝・Β (<code>Cache.php</code>)</strong>
                        <span class="detail">
                            螟夜ΚAPI縺九ｉ蜿門ｾ励＠縺溘Θ繝ｼ繧ｶ繝ｼ諠・ｱ縺ｪ縺ｩ繧偵く繝｣繝・す繝･縺吶ｋ莉慕ｵ・∩繧貞ｰ主・縲・
                            荳崎ｦ√↑API繝ｪ繧ｯ繧ｨ繧ｹ繝医ｒ蜑頑ｸ帙＠縲√・繝ｼ繧ｸ隱ｭ縺ｿ霎ｼ縺ｿ騾溷ｺｦ縺ｮ蜷台ｸ翫→API繝ｬ繝ｼ繝医Μ繝溘ャ繝亥屓驕ｿ繧貞ｮ溽樟縺励∪縺励◆縲・
                        </span>
                    </div>
                </li>
            </ul>
        </div>

        <!-- 謾ｹ蝟・& 繝ｪ繝輔ぃ繧ｯ繧ｿ繝ｪ繝ｳ繧ｰ -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 謾ｹ蝟・& 繝ｪ繝輔ぃ繧ｯ繧ｿ繝ｪ繝ｳ繧ｰ (Improvements & Refactoring)
            </div>
            <ul>
                <li>
                    <span class="icon">統</span>
                    <div>
                        <strong>繝ｭ繧ｰ縺ｮ閾ｪ蜍輔Ο繝ｼ繝・・繧ｷ繝ｧ繝ｳ讖溯・</strong>
                        <span class="detail">
                            <code>Logger.php</code> 縺ｫ繝輔ぃ繧､繝ｫ繧ｵ繧､繧ｺ繝吶・繧ｹ縺ｮ繝ｭ繧ｰ繝ｭ繝ｼ繝・・繧ｷ繝ｧ繝ｳ讖溯・繧定ｿｽ蜉縲・
                            蜿､縺・Ο繧ｰ繧定・蜍輔〒繝舌ャ繧ｯ繧｢繝・・繝ｻ繧ｯ繝ｪ繝ｼ繝ｳ繧｢繝・・縺励√ョ繧｣繧ｹ繧ｯ螳ｹ驥上・蝨ｧ霑ｫ繧帝亟縺舌％縺ｨ縺ｧ髟ｷ譛滄°逕ｨ譎ゅ・螳牙・諤ｧ繧貞髄荳翫＆縺帙∪縺励◆縲・
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
        <span class="release-title">繧ｨ繝ｩ繝ｼ繝上Φ繝峨Μ繝ｳ繧ｰ蝓ｺ逶､縺ｮ譁ｰ險ｭ縺ｨ繝ｭ繧ｮ繝ｳ繧ｰ繧ｷ繧ｹ繝・Β縺ｮ螳溯｣・/span>
        <span class="release-date">2026-03-05</span>
    </div>
    <div class="release-body">

        <!-- 譁ｰ讖溯・ -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 譁ｰ讖溯・ (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">女・・/span>
                    <div>
                        <strong>繝舌ャ繧ｯ繧ｨ繝ｳ繝峨お繝ｩ繝ｼ繝上Φ繝峨Μ繝ｳ繧ｰ蝓ｺ逶､爰・ｦｰ謨ｴ蛯・(<code>ErrorHandler.php</code>)</strong>
                        <span class="detail">
                            <code>SecurityException</code>縲・code>ValidationException</code>縲・code>DatabaseException</code>
                            縺ｪ縺ｩ縺ｮ蝙倶ｻ倥″萓句､悶け繝ｩ繧ｹ縺ｨ縲・
                            JSON 蠖｢蠑上・邨ｱ荳繧ｨ繝ｩ繝ｼ繝ｬ繧ｹ繝昴Φ繧ｹ繧堤函謌舌☆繧・<code>ErrorResponse</code> 繧ｯ繝ｩ繧ｹ繧呈眠險ｭ縲・
                            繧ｨ繝ｩ繝ｼ蜴溷屏縺ｮ遞ｮ蛻･繧呈・遒ｺ縺ｫ縺励・←蛻・↑繝｡繝・そ繝ｼ繧ｸ繧偵Θ繝ｼ繧ｶ繝ｼ縺ｫ霑斐○繧句・欧縺ｪ蝓ｺ逶､繧呈ｧ狗ｯ峨＠縺ｾ縺励◆縲・
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">搭</span>
                    <div>
                        <strong>繧ｵ繝ｼ繝舌・繧ｵ繧､繝峨Ο繧ｬ繝ｼ縺ｮ螳溯｣・(<code>Logger.php</code>)</strong>
                        <span class="detail">
                            <code>DEBUG</code>縲・code>CRITICAL</code> 縺ｮ5谿ｵ髫弱Ο繧ｰ繝ｬ繝吶Ν繧偵し繝昴・繝医☆繧・<code>Logger</code>
                            繧ｯ繝ｩ繧ｹ繧呈眠險ｭ縲・
                            繝ｭ繧ｰ繝ｬ繝吶Ν縺斐→縺ｫ <code>logs/</code> 驟堺ｸ九・蛟句挨繝輔ぃ繧､繝ｫ縺ｸ蜃ｺ蜉帙＠縲∝撫鬘檎匱逕滓凾縺ｮ霑ｽ霍｡縺ｨ繝・ヰ繝・げ繧貞ｮｹ譏薙↓縺励∪縺吶・
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">伯</span>
                    <div>
                        <strong>繝輔Ο繝ｳ繝医お繝ｳ繝陰PI繧ｯ繝ｩ繧､繧｢繝ｳ繝医・謨ｴ蛯・(<code>errorHandler.js</code>)</strong>
                        <span class="detail">
                            繧ｿ繧､繝繧｢繧ｦ繝域､懃衍縲√ロ繝・ヨ繝ｯ繝ｼ繧ｯ繧ｨ繝ｩ繝ｼ閾ｪ蜍募愛螳壹，SRF 繝医・繧ｯ繝ｳ閾ｪ蜍穂ｻ倅ｸ弱∵ｧ矩蛹悶お繝ｩ繝ｼ繝｡繝・そ繝ｼ繧ｸ陦ｨ遉ｺ繧貞ｙ縺医◆
                            <code>APIClient</code> 繧ｯ繝ｩ繧ｹ繧呈眠險ｭ縲ゅヵ繝ｭ繝ｳ繝医お繝ｳ繝峨・API騾壻ｿ｡蜩∬ｳｪ縺ｨ菫｡鬆ｼ諤ｧ繧貞髄荳翫＆縺帙∪縺励◆縲・
                        </span>
                    </div>
                </li>
            </ul>
        </div>

    </div>
</article>

