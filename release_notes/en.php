<!-- ===== v1.2.19 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v1.2.19</span>
        <span class="release-title">Analog & Digital Clock Widget Added</span>
        <span class="release-date">2026-03-19</span>
    </div>
    <div class="release-body">
        <!-- New Features -->
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features
            </div>
            <ul>
                <li>
                    <span class="icon">⌚</span>
                    <div>
                        <strong>Clock Widget Implementation</strong>
                        <span class="detail">
                            Added a toggleable analog and digital clock widget to the dashboard, featuring date display and smooth second-hand movement.
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
