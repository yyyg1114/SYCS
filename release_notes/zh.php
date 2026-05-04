<?php

/**
 * Release Notes - Chinese Content
 * 
 * This file is included by release_notes.php
 */
?>


<!-- ===== v2.2.8 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.8</span>
        <span class="release-title">API的整合性提高与服务工作线程的可靠性</span>
        <span class="release-date">2026-05-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 稳定性与修复 (Stability & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">⚙️</span>
                    <div>
                        <strong>提高 API 响应完整性</strong>
                        <span class="detail">确保在处理 API 请求后立即退出进程，防止多余的 HTML 片段干扰 JSON 响应。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔌</span>
                    <div>
                        <strong>优化服务工作线程 (Service Worker) 缓存</strong>
                        <span class="detail">限制仅对 HTTP/HTTPS 协议进行缓存，有效避免了由浏览器扩展或内部协议引起的加载错误。</span>
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
        <span class="release-title">状态选择器 UI 革新与视觉细节优化</span>
        <span class="release-date">2026-05-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI 与设计 (UI & Design)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>现代化状态选择框</strong>
                        <span class="detail">全面重新设计了状态选择下拉框。引入了自定义图标、毛玻璃效果（背景模糊）以及更精致的悬停和聚焦状态，显著提升了视觉一致性和操作体验。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">✨</span>
                    <div>
                        <strong>视觉反馈强化</strong>
                        <span class="detail">优化了交互元素的过渡动画和阴影效果，使整个界面更加流畅且富有质质感。</span>
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
        <span class="release-title">后端加固与安全性增强</span>
        <span class="release-date">2026-05-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 安全性与稳定性 (Security & Stability)
            </div>
            <ul>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>API 处理器加固</strong>
                        <span class="detail">重构了 API 输入处理逻辑，使用安全的包装方法代替直接访问全局变量，有效降低了潜在的安全风险。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔒</span>
                    <div>
                        <strong>扩大预处理语句的使用范围</strong>
                        <span class="detail">在消息置顶和删除等关键操作中，更加一致地使用预处理语句，进一步强化了对 SQL 注入的防护。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 修复与改进 (Fixes & Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">🐞</span>
                    <div>
                        <strong>修复收藏夹加载问题</strong>
                        <span class="detail">通过优化事件监听机制，解决了收藏列表在某些情况下无法正确显示的问题。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🏗️</span>
                    <div>
                        <strong>提升代码质量</strong>
                        <span class="detail">在后端代码中引入了类型提示并完善了错误处理机制，增强了系统的可靠性。</span>
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
        <span class="release-title">增强收藏管理与 UI 细节优化</span>
        <span class="release-date">2026-05-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> 新功能 (New Features)
            </div>
            <ul>
                <li>
                    <span class="icon">⭐</span>
                    <div>
                        <strong>新增收藏管理页面</strong>
                        <span class="detail">增加了专门的页面，用于集中查看和管理您收藏的所有讨论串。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改进与 UI (Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">📱</span>
                    <div>
                        <strong>统一页眉组件</strong>
                        <span class="detail">将页眉逻辑整合为公共组件，提升了搜索、附件库和置顶消息访问的便捷性。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🌍</span>
                    <div>
                        <strong>多语言支持增强</strong>
                        <span class="detail">扩展了与收藏功能相关的日、英、中翻译资源。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 修复与内部改进 (Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">🔧</span>
                    <div>
                        <strong>API 通信优化</strong>
                        <span class="detail">收藏操作的 API 统一改用 JSON 负载，提升了安全性。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🐞</span>
                    <div>
                        <strong>私信页眉显示修复</strong>
                        <span class="detail">修复了私信界面中对方用户名显示不正确的 bug。</span>
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
        <span class="release-title">聊天功能全面修正与强化</span>
        <span class="release-date">2026-05-03</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-red"></span> 缺陷修复 (Bug Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">📌</span>
                    <div>
                        <strong>完全修复消息置顶功能</strong>
                        <span class="detail">解决了置顶/取消置顶按钮无效的问题。现在置顶后消息列表会自动刷新。置顶消息模态框现在能正确传递频道 ID 调用 API，并显示发送者、时间戳及内容，点击即可平滑滚动并高亮该消息。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">😊</span>
                    <div>
                        <strong>修复表情回应功能</strong>
                        <span class="detail">修复了点击回应按钮时不显示表情选择器的问题。现在会弹出悬浮表情面板（包含 👍❤️😂 等 10 种常用表情），选择后即可正确切换回应状态。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">✏️</span>
                    <div>
                        <strong>修复消息编辑与删除</strong>
                        <span class="detail">修正了编辑按钮，现在会直接在消息内显示行内编辑框。删除按钮也已修复，现在会弹出确认对话框并正确执行删除操作。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">↩️</span>
                    <div>
                        <strong>修复回复功能</strong>
                        <span class="detail">解决了回复时 <code>reply_to_id</code> 未发送至 API 的问题。后端 <code>sendMessage</code> 处理程序也已更新，以确保回复 ID 正确存入数据库。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔍</span>
                    <div>
                        <strong>修复搜索与附件画廊</strong>
                        <span class="detail">修复了搜索功能中 API 路径重复的错误。解决了附件画廊中因字段名不匹配（<code>item.path</code> → <code>item.attachment_path</code>）导致图片无法显示的问题。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 功能改进 (Improvements)
            </div>
            <ul>
                <li>
                    <span class="icon">⌨️</span>
                    <div>
                        <strong>实现键盘快捷键</strong>
                        <span class="detail">新增支持 <code>Alt+P</code>（查看置顶消息）、<code>/</code>（聚焦搜索框）以及 <code>Alt+Shift+?</code>（显示快捷键列表）。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🗄️</span>
                    <div>
                        <strong>强化消息获取 API</strong>
                        <span class="detail">改进了 <code>getMessages</code> API，现在可以在单次请求中获取表情回应、回复源用户名及在线状态，减少了冗余的后续请求。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔗</span>
                    <div>
                        <strong>改进状态管理</strong>
                        <span class="detail">切换频道时会同步更新 <code>window.SYCS_CONFIG.currentThreadId</code>，确保所有模块始终引用正确的活动频道。</span>
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
        <span class="release-title">大规模架构革新与性能优化</span>
        <span class="release-date">2026-05-02</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> 稳定性与安全性 (Stability & Security)
            </div>
            <ul>
                <li>
                    <span class="icon">🏗️</span>
                    <div>
                        <strong>后端 API 解耦与加固</strong>
                        <span class="detail">将 API 路由和数据库初始化逻辑提取至专用的处理类 (<code>Handler.php</code>, <code>db_init.php</code>) 中，大幅提升了后端的代码可维护性和系统安全性。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚙️</span>
                    <div>
                        <strong>前端模块化改造</strong>
                        <span class="detail">将庞大的 <code>index.js</code> 拆分为多个 ES6 模块 (如 <code>api.js</code>, <code>chat.js</code>, <code>ui.js</code> 等)，优化了代码的可维护性和页面加载性能。</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善与重构 (Improvements & Refactoring)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>CSS 组件化重构</strong>
                        <span class="detail">将单一的 <code>style.css</code> 拆分并重构为多个逻辑模块 (<code>layout.css</code>, <code>components.css</code>, <code>modals.css</code> 等)，增强了 UI 的可扩展性。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧱</span>
                    <div>
                        <strong>UI 模板组件化</strong>
                        <span class="detail">将 <code>index.php</code> 中集中的 HTML 结构拆分为 <code>sidebar.php</code> 和 <code>modals.php</code> 等包含文件，提高了 UI 的一致性和开发效率。</span>
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
        <span class="release-title">UI/UX 优化与多语言支持扩充</span>
        <span class="release-date">2026-04-25</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善与修正 (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>置顶消息图标更新</strong>
                        <span class="detail">将表情符号图标替换为专用的 SVG 图标 (`pin.svg`)，提升了 UI 的一致性与专业感。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">👤</span>
                    <div>
                        <strong>个人资料设置功能强化</strong>
                        <span class="detail">为个人资料布局选择和横幅图片设置添加了多语言支持，排除了前端模板中的硬编码文本。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🌍</span>
                    <div>
                        <strong>多语言资源扩充 (JA/EN/ZH)</strong>
                        <span class="detail">新增了时钟组件显示切换（数字/指针）和任务列表操作的翻译，为所有支持语言提供一致的用户体验。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>前端代码清理</strong>
                        <span class="detail">将模板文件中的内联日语文本替换为动态多语言调用，提高了代码的可维护性。</span>
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
        <span class="release-title">增强渲染引擎安全性 (防御 XSS)</span>
        <span class="release-date">2026-04-05</span>
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
                        <strong>刷新渲染逻辑以防御 XSS</strong>
                        <span class="detail">大幅减少了 `innerHTML` 的使用，转而采用 `createElement` 和 `textContent` 进行安全的 DOM 操作。这在物理层面上杜绝了恶意脚本执行的可能性。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔍</span>
                    <div>
                        <strong>优化 escapeHTML 函数</strong>
                        <span class="detail">改进了特殊字符的处理逻辑，提高了数据的一致性与安全性。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>统一组件渲染方式</strong>
                        <span class="detail">将通知列表、文件列表及任务列表的渲染方式统一为更现代化、更安全的标准。</span>
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
        <span class="release-title">UI/UX 设计微调与模态框视认性提升</span>
        <span class="release-date">2026-04-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> 改善与修正 (Improvements & Fixes)
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>优化模态框尺寸与背景色</strong>
                        <span class="detail">调整了 `group-creation-modal` 和 `keyboard-shortcuts-modal` 的背景色及尺寸，提升了视认性与操作便捷性。</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔘</span>
                    <div>
                        <strong>按钮圆角与宽度优化</strong>
                        <span class="detail">微调了主要按钮及聊天顶栏按钮的 `border-radius` 与 `width`，使整体设计更趋现代且更易于使用。</span>
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
