<!-- ===== v1.2.36 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.36</span>
        <span class="release-title">Security Hardening & Rendering Refactor (XSS Protection)</span>
        <span class="release-date">2026-04-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> Stability & Security
            </div>
            <ul>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>Modernized Rendering for XSS Prevention</strong>
                        <span class="detail">Significantly reduced the use of `innerHTML`, transitioning to secure DOM generation using `createElement` and `textContent`. This provides a robust barrier against cross-site scripting (XSS).</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔍</span>
                    <div>
                        <strong>Optimized escapeHTML Function</strong>
                        <span class="detail">Improved the replacement logic for special characters to ensure better data integrity and security.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>Unified Widget Rendering</strong>
                        <span class="detail">Standardized the rendering processes for notifications, file lists, and ToDo items using modern, safe DOM APIs.</span>
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
        <span class="release-title">UI/UX Refinement & Modal Visibility Improvement</span>
        <span class="release-date">2026-04-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> Improvements & Fixes
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>Optimized Modal Size & Background</strong>
                        <span class="detail">Refined the background colors and dimensions of modal windows like `group-creation-modal` and `keyboard-shortcuts-modal` for better visibility and usability.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔘</span>
                    <div>
                        <strong>Button Styling Polish</strong>
                        <span class="detail">Updated `border-radius` and `width` for primary buttons and chat header elements to provide a more modern and intuitive user experience.</span>
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
        <span class="release-title">Security Hardening for Markdown Rendering</span>
        <span class="release-date">2026-04-01</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> Stability & Security
            </div>
            <ul>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>Modernized Rendering for XSS Prevention</strong>
                        <span class="detail">Replaced `innerHTML` with a robust DOM-based rendering approach using `DocumentFragment` and `createTextNode`. This provides a complete physical barrier against cross-site scripting (XSS), ensuring a safe and secure chat experience.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔍</span>
                    <div>
                        <strong>Strict Formatting Logic</strong>
                        <span class="detail">Implemented `inProtectedTag` logic to prevent formatting rules from being applied inside code blocks and pre tags, ensuring correct rendering without breaking source code visibility.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>Performance Optimization</strong>
                        <span class="detail">Integrated the modern `replaceChildren` method for optimized, high-speed content replacement in sync with the latest browser standards.</span>
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
        <span class="release-title">Markdown & Syntax Highlighting Support</span>
        <span class="release-date">2026-03-30</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features
            </div>
            <ul>
                <li>
                    <span class="icon">📝</span>
                    <div>
                        <strong>Markdown & Rich Text Rendering</strong>
                        <span class="detail">Added support for Bold (**bold**), Italic (*italic*), Underline (__underline__), Strikethrough (~~strike~~), and Blockquotes. Enhance your chat experience with flexible formatting.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">💻</span>
                    <div>
                        <strong>Code Block Syntax Highlighting (Highlight.js)</strong>
                        <span class="detail">Integrated `highlight.js` for beautiful syntax highlighting in code blocks across multiple languages, making code sharing more readable for developers.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔗</span>
                    <div>
                        <strong>Smarter Message Formatting</strong>
                        <span class="detail">Enhanced URL auto-linking and improved mention reliability for a cleaner, more intuitive chat interface.</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> Improvements
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>UI Design Polish</strong>
                        <span class="detail">Improved contrast for the PWA install button to enhance visibility and overall aesthetics.</span>
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
        <span class="release-title">WebRTC Stability & Notification Engine Hardening</span>
        <span class="release-date">2026-03-27</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> Improvements & Fixes
            </div>
            <ul>
                <li>
                    <span class="icon">🎥</span>
                    <div>
                        <strong>Improved WebRTC Connection Reliability</strong>
                        <span class="detail">Implemented an ICE candidate queue to prevent signaling race conditions. This ensures consistent video conferencing connections by handling candidates only after the remote description is set.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>Enhanced Error Handling for Notifications</strong>
                        <span class="detail">Introduced HTTP status code verification for real-time and push notification delivery, enabling precise error detection and logging for backend integrations.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔐</span>
                    <div>
                        <strong>Security Logic Optimization</strong>
                        <span class="detail">Refined secret key fallback mechanisms and removed redundant file includes to improve system robustness and efficiency.</span>
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
        <span class="release-title">Major Analog Clock Widget Enhancement</span>
        <span class="release-date">2026-03-22</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> New Features
            </div>
            <ul>
                <li>
                    <span class="icon">⌚</span>
                    <div>
                        <strong>Sweep Seconds Hand & Functional Sub-dials</strong>
                        <span class="detail">Implemented <code>requestAnimationFrame</code> for smooth, sweeping second hand movement. Additionally, the 24-hour, Day of the Week, and constant seconds sub-dials are now fully functional.</span>
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
        <span class="release-title">System Infrastructure Hardening & i18n Optimization</span>
        <span class="release-date">2026-03-21</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> Improvements & Fixes
            </div>
            <ul>
                <li>
                    <span class="icon">🔐</span>
                    <div>
                        <strong>Enhanced Session & Cookie Management</strong>
                        <span class="detail">Refactored backend session and cookie handling for significantly improved security and connection stability.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🌍</span>
                    <div>
                        <strong>i18n Process Optimization</strong>
                        <span class="detail">Improved language switching logic and fully updated Simplified Chinese localized resources.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>Frontend Cleanup</strong>
                        <span class="detail">Optimized performance by reorganizing structures and removing redundant code in the main application.</span>
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
        <span class="release-title">Analog Clock Widget Added</span>
        <span class="release-date">2026-03-20</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> New Features
            </div>
            <ul>
                <li>
                    <span class="icon">⌚</span>
                    <div>
                        <strong>Analog Clock Widget Added</strong>
                        <span class="detail">
                            Added an analog clock widget to the home screen. This high-performance clock features time display, date display, day of the week display, second hand display, and sub-dials (at 12, 3, 6, and 9 o'clock positions).
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.20</span>
        <span class="release-title">WebRTC Signaling Migration to Socket.IO</span>
        <span class="release-date">2026-03-18</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> Improvements & Fixes
            </div>
            <ul>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>Real-time WebRTC Signaling</strong>
                        <span class="detail">
                            Migrated meeting signaling from HTTP polling to Socket.IO. This update significantly reduces connection latency and server overhead by enabling true bi-directional communication.
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
        <span class="release-title">Login UI Refinement & Style Cleanup</span>
        <span class="release-date">2026-03-18</span>
    </div>
    <div class="release-body">

        <!-- UI/UX Enhancements -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> UI/UX Enhancements
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>Login Card Layout Adjustment</strong>
                        <span class="detail">
                            Increased the maximum width of the login card to <code>500px</code> for a more balanced and modern appearance.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>CSS Refinement</strong>
                        <span class="detail">
                            Removed redundant background gradients in <code>style-index.css</code> to improve code clarity.
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
        <span class="release-title">Mobile Optimization & UI Cleanup</span>
        <span class="release-date">2026-03-14</span>
    </div>
    <div class="release-body">

        <!-- UI/UX -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> UI/UX Enhancements
            </div>
            <ul>
                <li>
                    <span class="icon">📱</span>
                    <div>
                        <strong>Mobile Responsiveness Hardening</strong>
                        <span class="detail">
                            Improved chat UI on mobile by hiding secondary icons and optimizing header space carefully.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>CSS Cleanup</strong>
                        <span class="detail">
                            Refined <code>flex-grow</code> properties and standardized login card layouts for better cross-device consistency.
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
        <span class="release-title">Backend Hardening & Code Cleanup</span>
        <span class="release-date">2026-03-11</span>
    </div>
    <div class="release-body">

        <!-- Stability & Reliability -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> Stability & Reliability
            </div>
            <ul>
                <li>
                    <span class="icon">🔐</span>
                    <div>
                        <strong>Improved Realtime Server Auth Synchronization</strong>
                        <span class="detail">
                            Refined the <code>REALTIME_SECRET_KEY</code> loading flow in both <code>index.php</code> and <code>server.js</code>.
                            Added fallback to <code>SECRET_KEY</code> and enhanced error logging for missing secrets, ensuring robust authentication between frontend and realtime services.
                        </span>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Improvements -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> Improvements & Refactoring
            </div>
            <ul>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>Frontend Entry Point Optimization</strong>
                        <span class="detail">
                            Cleaned up redundant <code>require_once</code> calls in <code>index.php</code> to improve code maintainability and structure.
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
        <span class="release-title">Multi-language (i18n) Support & Dynamic Release Notes</span>
        <span class="release-date">2026-03-10</span>
    </div>
    <div class="release-body">

        <!-- New Features -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features
            </div>
            <ul>
                <li>
                    <span class="icon">🌍</span>
                    <div>
                        <strong>Internationalization Framework (<code>I18n.php</code>)</strong>
                        <span class="detail">
                            Established a system-wide i18n infrastructure supporting Japanese and English.
                            Language preferences are persisted via sessions and cookies, allowing seamless switching from the UI.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🚀</span>
                    <div>
                        <strong>Dynamic Release Notes System (PHP)</strong>
                        <span class="detail">
                            Migrated from static HTML to a dynamic PHP-based system.
                            Content is now automatically served in the user's preferred language (JA/EN).
                        </span>
                    </div>
                </li>
            </ul>
        </div>

        <!-- UI/UX -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> UI/UX Enhancements
            </div>
            <ul>
                <li>
                    <span class="icon">🔘</span>
                    <div>
                        <strong>Language Selector Integration</strong>
                        <span class="detail">
                            Added intuitive language selectors to the login, signup, and main application headers.
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
        <span class="release-title">Improved Video Conferencing Stability & UI Enhancements</span>
        <span class="release-date">2026-03-09</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> Improvements & Fixes
            </div>
            <ul>
                <li>
                    <span class="icon">🎥</span>
                    <div>
                        <strong>Enhanced WebRTC Stability</strong>
                        <span class="detail">
                            Hardened stream acquisition in <code>webrtc.js</code> and <code>meetings.php</code>.
                            Added support for MediaStream generation from individual tracks, ensuring reliable video display upon connection.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">📱</span>
                    <div>
                        <strong>Mobile Browser Compatibility</strong>
                        <span class="detail">
                            Added <code>playsinline</code> attribute to video elements to prevent forced fullscreen on mobile browsers like iOS Safari.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧱</span>
                    <div>
                        <strong>Consistent Video Grid Layout</strong>
                        <span class="detail">
                            Unified video display classes to <code>video-grid</code> across <code>index.php</code> and <code>meetings.php</code> for a consistent experience.
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
        <span class="release-title">Rate Limit Cache Auto-Cleanup & Quota</span>
        <span class="release-date">2026-03-06</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> Improvements
            </div>
            <ul>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>Automatic Cache Cleanup</strong>
                        <span class="detail">
                            Implemented hourly cleanup in <code>FileRateLimiter.php</code>. Added a 100MB quota system that prunes old files down to 80MB to prevent disk space exhaustion.
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
        <span class="release-title">Enhanced System Protection via Rate Limiting</span>
        <span class="release-date">2026-03-06</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> Security & Stability
            </div>
            <ul>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>API Rate Limiting Implementation</strong>
                        <span class="detail">
                            Added <code>RateLimiter.php</code> (Redis-based) and <code>FileRateLimiter.php</code> (Fallback).
                            Introduced per-IP and per-User limits for the <code>update_location</code> API to prevent spam and system overload.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">⏱️</span>
                    <div>
                        <strong>Client-side Request Optimization</strong>
                        <span class="detail">
                            Enforced a 5-second minimum update interval in <code>locate.js</code> to reduce unnecessary API calls from the client side.
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
        <span class="release-title">Theme Support Enhancement & UI Refinement</span>
        <span class="release-date">2026-03-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> UI/UX Enhancements
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>Consistent Theming via CSS Variables</strong>
                        <span class="detail">
                            Removed hardcoded colors in <code>style-index.css</code> and <code>style.css</code>.
                            Fully implemented CSS variables like <code>--text-primary</code> and <code>--text-secondary</code> to ensure visual consistency and better visibility when switching between dark and light themes.
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
        <span class="release-title">External Service Integration Stability & Logger Enhancements</span>
        <span class="release-date">2026-03-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> Stability & Reliability
            </div>
            <ul>
                <li>
                    <span class="icon">🔄</span>
                    <div>
                        <strong>Retry Mechanism for External APIs (<code>RetryHandler.php</code>)</strong>
                        <span class="detail">
                            Implemented an automatic retry system for external service integrations (Outlook, Discord, Google).
                            Uses an exponential backoff algorithm to handle temporary network or server errors gracefully, increasing the overall success rate.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>File-based Caching System (<code>Cache.php</code>)</strong>
                        <span class="detail">
                            Introduced a caching mechanism for user data fetched from external APIs.
                            Reduces unnecessary API requests, improves page load speeds, and helps avoid hitting API rate limits.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> Improvements & Refactoring
            </div>
            <ul>
                <li>
                    <span class="icon">📝</span>
                    <div>
                        <strong>Automatic Log Rotation</strong>
                        <span class="detail">
                            Added file-size based log rotation to <code>Logger.php</code>.
                            Automatically backs up and cleans up old logs to prevent disk space exhaustion, ensuring safe long-term operation.
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
        <span class="release-title">Error Handling Foundation & Logging System</span>
        <span class="release-date">2026-03-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features
            </div>
            <ul>
                <li>
                    <span class="icon">🏗️</span>
                    <div>
                        <strong>Backend Error Handling Infrastructure (<code>ErrorHandler.php</code>)</strong>
                        <span class="detail">
                            Created typed exception classes (<code>SecurityException</code>, <code>ValidationException</code>, <code>DatabaseException</code>) and a unified <code>ErrorResponse</code> class for JSON responses.
                            Established a robust foundation for clear error reporting to users.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">📋</span>
                    <div>
                        <strong>Server-side Logger (<code>Logger.php</code>)</strong>
                        <span class="detail">
                            New <code>Logger</code> class supporting 5 log levels from <code>DEBUG</code> to <code>CRITICAL</code>.
                            Outputs to individual files in <code>logs/</code> for easier tracking and debugging.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔌</span>
                    <div>
                        <strong>Frontend API Client (<code>errorHandler.js</code>)</strong>
                        <span class="detail">
                            New <code>APIClient</code> class with timeout detection, automatic network error handling, CSRF token attachment, and structured error message displays.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.7 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.7</span>
        <span class="release-title">Notification UI Overhaul</span>
        <span class="release-date">2026-03-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> UI/UX Enhancements
            </div>
            <ul>
                <li>
                    <span class="icon">🔔</span>
                    <div>
                        <strong>Floating Notification Center</strong>
                        <span class="detail">
                            Moved the notification center to a Floating Action Button (FAB) at the bottom-right of the screen.
                            Features a polished floating animation and a new dropdown for intuitive notification checking.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🖼️</span>
                    <div>
                        <strong>Attachment Gallery Improvements</strong>
                        <span class="detail">
                            Enhanced the gallery for managing past attachments, now available in both threads and DMs with image previews and file type icons.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> Improvements & Fixes
            </div>
            <ul>
                <li>
                    <span class="icon">🛠️</span>
                    <div>
                        <strong>Notification Keyword Fix</strong>
                        <span class="detail">
                            Fixed issues where <code>notification_keywords</code> were not applied correctly or caused errors when empty, improving mention reliability.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.6 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.6</span>
        <span class="release-title">Profile UI Overhaul & Robustness Enhancements</span>
        <span class="release-date">2026-03-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> UI/UX Enhancements
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>Redesigned Profile Screen</strong>
                        <span class="detail">
                            Significantly improved profile editing and viewing modals with premium buttons, glassmorphism CSS, and new theme selection buttons for a modern UX.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> Robustness & Refactoring
            </div>
            <ul>
                <li>
                    <span class="icon">🛠️</span>
                    <div>
                        <strong>Secure PHP Templates</strong>
                        <span class="detail">
                            Hardened variable output in <code>index.php</code> using Null Coalescing operators (??) to eliminate runtime errors caused by undefined or incomplete user data.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🚀</span>
                    <div>
                        <strong>Signup Process Improvement</strong>
                        <span class="detail">
                            Integrated <code>EnvLoader</code> into <code>signup_process.php</code> for better backend configuration flexibility.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.5 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.5</span>
        <span class="release-title">Code Modularization & UI/UX Optimization</span>
        <span class="release-date">2026-03-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> Improvements & Refactoring
            </div>
            <ul>
                <li>
                    <span class="icon">📦</span>
                    <div>
                        <strong>Frontend Code Modularization</strong>
                        <span class="detail">
                            Extracted massive inline JS and CSS from <code>index.php</code> into external files like <code>frontend/js/index.js</code> and <code>frontend/css/style-index.css</code>, improving maintainability.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features & UI/UX
            </div>
            <ul>
                <li>
                    <span class="icon">🌐</span>
                    <div>
                        <strong>Connection Status Indicator</strong>
                        <span class="detail">
                            Added a real-time indicator at the top of the screen to notify users when they are offline.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">📲</span>
                    <div>
                        <strong>PWA Installation Experience</strong>
                        <span class="detail">
                            Redesigned the installation banner to harmonize with the page and integrated it across both thread and DM lists.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">⏳</span>
                    <div>
                        <strong>Refined Skeleton Loaders</strong>
                        <span class="detail">
                            Updated message skeleton loaders to a smoother "Discord-style" design to reduce perceived wait times.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.4 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.4</span>
        <span class="release-title">Location Feature Enhancements & Security Improvements</span>
        <span class="release-date">2026-03-03</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features & Improvements
            </div>
            <ul>
                <li>
                    <span class="icon">📍</span>
                    <div>
                        <strong>GPS Status Visualization</strong>
                        <span class="detail">
                            Added a real-time status display to track GPS acquisition (locating, success, error, etc.) directly in the UI.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>Location Script Optimization</strong>
                        <span class="detail">
                            Reorganized loading sequence and dependencies for <code>js/locate.js</code> to improve initial page performance.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-red"></span> Security Enhancements
            </div>
            <ul>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>CSRF Protection for Location API</strong>
                        <span class="detail">
                            Mandated CSRF token validation for <code>update_location</code> API requests.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> Bug Fixes
            </div>
            <ul>
                <li>
                    <span class="icon">🐛</span>
                    <div>
                        <strong>Location Table Consistency Fix</strong>
                        <span class="detail">
                            Fixed an issue where the API would error if the <code>user_locations</code> table was missing or incomplete.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.3 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.3</span>
        <span class="release-title">Bug Fixes & Stability Improvements</span>
        <span class="release-date">2026-03-01</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> Bug Fixes
            </div>
            <ul>
                <li>
                    <span class="icon">🐛</span>
                    <div>
                        <strong>DM Sending Issue Fixed</strong>
                        <span class="detail">
                            Resolved issues in the backend API and the frontend <code>sendDm</code> function, ensuring direct messages are sent and received correctly.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🛠️</span>
                    <div>
                        <strong>Token Verification Error Resolved</strong>
                        <span class="detail">
                            Fixed an "Exit Expression" error in <code>verify_token()</code>, stabilizing authentication.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.2 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.2</span>
        <span class="release-title">Notification Center Implementation</span>
        <span class="release-date">2026-03-01</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features
            </div>
            <ul>
                <li>
                    <span class="icon">🔔</span>
                    <div>
                        <strong>Notification Center</strong>
                        <span class="detail">
                            Added a bell icon to view mentions, DMs, and friend requests. Supports unread badges and real-time updates.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.2.0 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.0</span>
        <span class="release-title">Major Meeting Feature Update</span>
        <span class="release-date">2026-03-01</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features
            </div>
            <ul>
                <li>
                    <span class="icon">📹</span>
                    <div>
                        <strong>Independent Meeting Page</strong>
                        <span class="detail">
                            Introduced <code>meetings.php</code> with ID/password access, WebRTC P2P video, and screen sharing.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.1.12 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.1.12</span>
        <span class="release-title">Auth Modernization</span>
        <span class="release-date">2026-02-28</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-red"></span> Security
            </div>
            <ul>
                <li>
                    <span class="icon">🔐</span>
                    <div>
                        <strong>Updated Password Hashing</strong>
                        <span class="detail">
                            Migrated to <code>password_hash()</code> (BCRYPT) with automatic migration for existing users.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.1.0 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.1.0</span>
        <span class="release-title">PWA Support</span>
        <span class="release-date">2026-02-21</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features
            </div>
            <ul>
                <li>
                    <span class="icon">📱</span>
                    <div>
                        <strong>Progressive Web App Implementation</strong>
                        <span class="detail">
                            SYCS can now be added to the home screen. Includes <code>manifest.json</code>, Service Worker caching, and an offline fallback page.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.0.82 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.0.82</span>
        <span class="release-title">UX Power-up Update</span>
        <span class="release-date">2026-02-21</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features
            </div>
            <ul>
                <li>
                    <span class="icon">📌</span>
                    <div>
                        <strong>Pinned Messages List</strong>
                        <span class="detail">
                            View all pinned messages in a thread via a new header panel.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🌐</span>
                    <div>
                        <strong>Online Users List</strong>
                        <span class="detail">
                            Real-time sidebar list of online users sorted by status.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔴</span>
                    <div>
                        <strong>DM Unread Badges</strong>
                        <span class="detail">
                            Visual unread count markers in the DM sidebar tab.
                        </span>
                    </div>
                </li>
                <li>
                    <span class="icon">⌨️</span>
                    <div>
                        <strong>Keyboard Shortcuts</strong>
                        <span class="detail">
                            Added shortcuts for search (/), cancel-reply (Esc), and opening pinned messages (Alt+P).
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.0.80 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.0.80</span>
        <span class="release-title">Security: Environment Variable Migration</span>
        <span class="release-date">2026-02-20</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-red"></span> Security
            </div>
            <ul>
                <li>
                    <span class="icon">🔐</span>
                    <div>
                        <strong>Secrets Removal</strong>
                        <span class="detail">
                            Moved all hardcoded API keys and secrets to the <code>.env</code> file.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.0.73 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.0.73</span>
        <span class="release-title">File Upload Feature</span>
        <span class="release-date">2026-02-07</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features
            </div>
            <ul>
                <li>
                    <span class="icon">📎</span>
                    <div>
                        <strong>File & Media Sharing</strong>
                        <span class="detail">
                            Support for uploading and sharing images, videos, and documents with drag-and-drop and previews.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v1.0.0 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.0.0</span>
        <span class="release-title">🎉 Official Release</span>
        <span class="release-date">2026-01-13</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> Launch
            </div>
            <ul>
                <li>
                    <span class="icon">🎉</span>
                    <div>
                        <strong>SYCS v1.0.0 Launch</strong>
                        <span class="detail">
                            The first stable release featuring core chat, threads, DMs, and user authentication.
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>
