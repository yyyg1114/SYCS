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

<p style="text-align: center; color: var(--muted); padding: 20px;">
    Older release notes are currently only available in Japanese.
</p>
