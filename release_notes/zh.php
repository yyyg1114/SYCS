<?php

/**
 * Release Notes - Chinese Content
 * 
 * This file is included by release_notes.php
 */
?>

<!-- ===== v1.2.34 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.34</span>
        <span class="release-title">增强 Markdown 渲染引擎的安全性 (彻底防御 XSS)</span>
        <span class="release-date">2026-04-01</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 稳定性与安全性 (Stability & Security)
            </div>
            <ul>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>全新渲染方式，彻底防御 XSS</strong>
                        <span class="detail">全面废除 `innerHTML`，转而采用 `DocumentFragment` 和 `createTextNode` 直接生成 DOM 节点。这一改进在物理层面上切断了恶意脚本执行的可能性，为您提供一个安全可靠的聊天环境。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔍</span>
                    <div>
                        <strong>严谨的格式化逻辑</strong>
                        <span class="detail">引入 `inProtectedTag` 判定机制，确保 Markdown 格式化规则不会影响代码块 (`code`) 和预格式化标签 (`pre`) 内部的内容，完美呈现源代码。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>性能优化与标准对齐</strong>
                        <span class="detail">采用现代化的 `replaceChildren` 方法更新页面内容，遵循最新浏览器标准，实现更快速、更安全的 UI 渲染。</span>
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
        <span class="release-title">支持 Markdown 渲染和代码高亮</span>
        <span class="release-date">2026-03-30</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新功能 (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">📝</span>
                    <div>
                        <strong>Markdown / 富文本渲染</strong>
                        <span class="detail">支持粗体 (**bold**)、斜体 (*italic*)、下划线 (__underline__)、删除线 (~~strike~~) 和引用 (blockquote)，让您的消息表达更灵活生动。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">💻</span>
                    <div>
                        <strong>代码块语法高亮 (Highlight.js)</strong>
                        <span class="detail">集成了 `highlight.js`，为多种编程语言提供精美的代码块高亮显示，显著提升开发者间的代码可读性。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔗</span>
                    <div>
                        <strong>更智能的消息格式化</strong>
                        <span class="detail">改进了 URL 自动链接和提及 (Mentions) 处理的可靠性，提供了更清晰、直观的聊天界面。</span>
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
                        <strong>UI 体验优化</strong>
                        <span class="detail">优化了 PWA 安装按钮的对比度，提高了可视性和整体界面美感。</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.28 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge canvas-badge">v1.2.28</span>
        <span class="release-title">WebRTC 连接稳定性与通知系统强化</span>
        <span class="release-date">2026-03-27</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善与修正 (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">🎥</span>
                    <div>
                        <strong>提高 WebRTC 信令稳定性</strong>
                        <span class="detail">实现了 ICE candidate 保留队列。这解决了视频会议连接过程中 candidate 丢失的问题，确保连接更加可靠。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>强化通知引擎错误处理</strong>
                        <span class="detail">在实时通知和推送通知处理中引入了 HTTP 状态码检查。能够更准确地检测后端协作故障并记录日志。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔐</span>
                    <div>
                        <strong>优化安全逻辑与代码整理</strong>
                        <span class="detail">优化了密钥引用逻辑，增强了环境配置缺失时的鲁棒性。同时删除了冗余的文件引用。</span>
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
        <span class="release-title">模拟时钟小部件重大增强</span>
        <span class="release-date">2026-03-22</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 新功能 (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">⌚</span>
                    <div>
                        <strong>平滑秒针与功能性子表盘</strong>
                        <span class="detail">采用 <code>requestAnimationFrame</code> 实现了秒针的平滑扫秒。此外，24小时计、星期计和独立秒计的子表盘现在已完全实现功能。</span>
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
        <span class="release-title">系统基础强化与多语言支持优化</span>
        <span class="release-date">2026-03-21</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善与修正 (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">🔐</span>
                    <div>
                        <strong>强化会话与 Cookie 管理</strong>
                        <span class="detail">刷新了后端会话和 Cookie 处理，大幅提升了安全性和连接稳定性。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🌍</span>
                    <div>
                        <strong>多语言支持 (i18n) 流程优化</strong>
                        <span class="detail">改善了语言切换逻辑，提供更顺畅的用户体验。此外，全面更新了简体中文资源。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>前端清理与优化</strong>
                        <span class="detail">通过整理主界面结构并删除冗余代码，优化了整体性能。</span>
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
        <span class="release-title">模拟时钟小部件的添加</span>
        <span class="release-date">2026-03-20</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 新功能 (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">⌚</span>
                    <div>
                        <strong>模拟时钟小部件的添加</strong>
                        <span class="detail">
                            在主屏幕上添加了模拟时钟小部件。这是一个功能齐全的时钟，具有时间显示、日期显示、星期显示、秒针显示以及子表盘（位于12点、3点、6点和9点位置）。
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
        <span class="release-title">登录界面 UI 调整与细微清理</span>
        <span class="release-date">2026-03-18</span>
    </div>
    <div class="release-body">

        <!-- UI/UX 提升 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> UI/UX 提升 (UI/UX)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>登录卡片布局调整</strong>
                        <span class="detail">
                            将登录界面的卡片最大宽度扩大至 <code>500px</code>，使整体布局更加从容、现代。
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>样式表清理</strong>
                        <span class="detail">
                            删除了 <code>style-index.css</code> 中冗余的背景渐变指定，提高了代码的可维护性。
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
        <span class="release-title">移动端显示优化与 UI 清理</span>
        <span class="release-date">2026-03-14</span>
    </div>
    <div class="release-body">

        <!-- UI/UX 提升 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> UI/UX 提升 (UI/UX)
            </div>
            <ul>
                <li>
                    <span class="icon">📱</span>
                    <div>
                        <strong>加强移动端响应式设计</strong>
                        <span class="detail">
                            优化了智能手机显示下的页眉元素，隐藏了非必要按钮（视频、置顶等），提升了聊天界面的可视性。
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>布局清理</strong>
                        <span class="detail">
                            整理了 CSS（如 <code>flex-grow</code> 的优化）并加强了登录界面的响应式支持，实现了各设备间一致的操作体验。
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
        <span class="release-title">服务端加固与冗余代码整理</span>
        <span class="release-date">2026-03-11</span>
    </div>
    <div class="release-body">

        <!-- 稳定性与可靠性 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 稳定性与可靠性 (Stability & Reliability)
            </div>
            <ul>
                <li>
                    <span class="icon">🔐</span>
                    <div>
                        <strong>改进实时服务器认证同步</strong>
                        <span class="detail">
                            改进了 <code>index.php</code> 和 <code>server.js</code> 中 <code>REALTIME_SECRET_KEY</code> 的加载流程。
                            添加了 <code>SECRET_KEY</code> 的备选处理和缺失时的错误处理，提高了系统间认证同步的可靠性。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

        <!-- 改进与重构 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改进与重构 (Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>前端入口点优化</strong>
                        <span class="detail">
                            删除了 <code>index.php</code> 中已加载的冗余 <code>require_once</code> 命令，整理了内部结构。
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
        <span class="release-title">引入多语言支持 (i18n) 与动态更新日志</span>
        <span class="release-date">2026-03-10</span>
    </div>
    <div class="release-body">

        <!-- 新功能 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新功能 (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">🌍</span>
                    <div>
                        <strong>引入多语言支持 (i18n) 基底 (<code>I18n.php</code>)</strong>
                        <span class="detail">
                            在整个系统中引入了多语言支持基底，支持日语和英语的切换。
                            语言设置通过 Session 和 Cookie 持久化，支持从登录界面或主界面一键切换。
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🚀</span>
                    <div>
                        <strong>更新日志系统化 (PHP)</strong>
                        <span class="detail">
                            从传统的静态 HTML 格式刷新为基于 PHP 的动态系统。
                            现在可以根据访问者的语言设置，自动显示日语、英语或中文内容。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

        <!-- UI/UX 提升 -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> UI/UX 提升 (UI/UX)
            </div>
            <ul>
                <li>
                    <span class="icon">🔘</span>
                    <div>
                        <strong>实现语言选择器</strong>
                        <span class="detail">
                            在登录、注册和主界面的页眉处设置了语言切换选择器。
                            强化了界面以提供无缝体验。
                        </span>
                    </div>
                </li>
            </ul>
        </div>

    </div>
</article>

<p style="text-align: center; color: var(--muted); padding: 20px;">
    更早的更新日志目前仅提供日语版本。
</p>
