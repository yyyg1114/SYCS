<!-- ===== v2.2.24 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.24</span>
        <span class="release-title">Message Selection Mode, Batch Delete, and Enhanced Conversation Controls</span>
        <span class="release-date">2026-08-09</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features &amp; Enhancements
            </div>
            <ul>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>Message selection mode</strong>
                        <span class="detail">Added a new selection mode for choosing multiple own messages in the chat view with checkbox controls and shift-select support.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🗑️</span>
                    <div>
                        <strong>Batch message deletion</strong>
                        <span class="detail">Enabled deleting multiple selected messages at once with a confirmation prompt for faster conversation cleanup.</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI &amp; Productivity
            </div>
            <ul>
                <li>
                    <span class="icon">✅</span>
                    <div>
                        <strong>Selection toolbar and action controls</strong>
                        <span class="detail">Added a contextual action bar with select all, deselect all, and cancel controls for smoother multi-message workflows.</span>
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
        <span class="release-title">Google OAuth Integration, Environment Variable (.env) Support &amp; Security Enhancements</span>
        <span class="release-date">2026-07-29</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features &amp; Enhancements
            </div>
            <ul>
                <li>
                    <span class="icon">🔑</span>
                    <div>
                        <strong>Google OAuth 2.0 Integration &amp; Social Login</strong>
                        <span class="detail">Integrated Google OAuth 2.0 authentication flow with client ID and secret support for secure, seamless social login.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚙️</span>
                    <div>
                        <strong>Environment Variable (.env) Loader</strong>
                        <span class="detail">Introduced EnvLoader utility to manage API credentials and sensitive configurations safely across environments.</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> Security &amp; Auth
            </div>
            <ul>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>Login Rate Limiting &amp; Session Protection</strong>
                        <span class="detail">Optimized login attempt rate limiting to prevent brute-force attacks and ensured session ID regeneration upon successful authentication.</span>
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
        <span class="release-title">Database Performance, Theme Refinement, and CSS Build Caching</span>
        <span class="release-date">2026-07-22</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features &amp; Enhancements
            </div>
            <ul>
                <li>
                    <span class="icon">🗄️</span>
                    <div>
                        <strong>Persistent database connections and smart indexing</strong>
                        <span class="detail">Switched MySQL to persistent connections and added background index maintenance for message, direct message, and signaling tables to improve query performance.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>Expired direct message cleanup</strong>
                        <span class="detail">Added a backend cleanup function to remove stale direct messages on a schedule, keeping the database healthy and storage usage under control.</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI &amp; Design
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>Theme system and modal polish</strong>
                        <span class="detail">Extended theme toggling to support light, dark, and night modes globally while refining profile modal and overlay styling for a more cohesive UI.</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> Infrastructure &amp; Utilities
            </div>
            <ul>
                <li>
                    <span class="icon">📦</span>
                    <div>
                        <strong>CSS build cache and faster asset regeneration</strong>
                        <span class="detail">Introduced a CSS build flag and cache directory to skip unnecessary bundle rebuilds and speed up frontend asset generation.</span>
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
        <span class="release-title">Optimistic UI, Message Reply Support, and CSS Architecture Refactor</span>
        <span class="release-date">2026-07-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features &amp; Enhancements
            </div>
            <ul>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>Optimistic UI with message pending and failure states</strong>
                        <span class="detail">Messages now appear instantly in the chat with a pending indicator (💬), providing immediate visual feedback. If delivery fails, users see a recoverable error banner with a retry option, improving perceived responsiveness and trust.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">💬</span>
                    <div>
                        <strong>Message reply and mention system</strong>
                        <span class="detail">Added full support for replying to specific messages, including tracking reply targets, displaying reply context, and managing attachment state during conversations for better threaded discussions.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>Attachment handling and API improvements</strong>
                        <span class="detail">Enhanced backend API to properly track and return attachment paths for sent messages, ensuring files persist and display correctly in the chat history.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>Library integration for security and markup</strong>
                        <span class="detail">Integrated Marked.js for markdown rendering, DOMPurify for XSS protection, and Highlight.js for code syntax highlighting to safely display rich formatted content.</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI &amp; Design
            </div>
            <ul>
                <li>
                    <span class="icon">🎯</span>
                    <div>
                        <strong>Account deletion page redesign</strong>
                        <span class="detail">Fully redesigned the delete account page with enhanced styling, better visual hierarchy, and safer password confirmation flow with improved security headers and error messaging.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">📦</span>
                    <div>
                        <strong>CSS bundle optimization and architecture</strong>
                        <span class="detail">Extracted inline styles from delete_account.php into external CSS, automated CSS minification and bundling via build_css.php, and optimized loading with bundle.min.css for improved maintainability and performance.</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> Infrastructure &amp; Utilities
            </div>
            <ul>
                <li>
                    <span class="icon">⚙️</span>
                    <div>
                        <strong>Backend cleanup, indexing, and CSS build automation</strong>
                        <span class="detail">Added expired message cleanup and query index maintenance on the backend, while continuing CSS build automation, optimistic UI message states, and utility improvements for more reliable performance.</span>
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
        <span class="release-title">Authentication Hardening, Friend Request UX, and Chat Interface Polishing</span>
        <span class="release-date">2026-06-28</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features &amp; Enhancements
            </div>
            <ul>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>Authentication flow hardening</strong>
                        <span class="detail">Wrapped login and signup processing in safer try/catch handling, improved lockout detection, and stabilized error paths for more reliable account access.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">👥</span>
                    <div>
                        <strong>Friend request and social interactions</strong>
                        <span class="detail">Added friend search, send request, pending requests, and blocked user management to the social hub for smoother friend onboarding.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">✨</span>
                    <div>
                        <strong>Chat UI and favorites polish</strong>
                        <span class="detail">Refined chat search rendering, group member picker behavior, and favorites panel rendering with safer DOM updates and clearer empty states.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔧</span>
                    <div>
                        <strong>Password reset UX improvements</strong>
                        <span class="detail">Improved reset-password page messaging and the overall recovery experience for better user guidance.</span>
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
                <span class="dot dot-green"></span> New Features &amp; Enhancements
            </div>
            <ul>
                <li>
                    <span class="icon">👥</span>
                    <div>
                        <strong>Expanded friend system support</strong>
                        <span class="detail">Added backend API actions for sending, accepting, and rejecting friend requests, plus a pending requests flow and friend list refresh inside the DM hub.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧑‍💻</span>
                    <div>
                        <strong>User profile improvements</strong>
                        <span class="detail">Enhanced the user profile modal with Add Friend support, pending request access, and blocked user management for more direct social interactions.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">📸</span>
                    <div>
                        <strong>Media upload preview experience</strong>
                        <span class="detail">Added a dedicated upload modal with drag-and-drop support, file previews, and metadata display to simplify attaching media to conversations.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>Online user and group chat synchronization</strong>
                        <span class="detail">Enabled online user list auto-refresh and restored group chat detection during initial load so the correct sidebar state is preserved.</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</article>

<!-- ===== v2.2.18 ===== -->
<article class="release">

<!-- ===== v2.2.17 ===== -->
<article class="release">
    <div class="release-header">
        <span class="version-badge">v2.2.17</span>
        <span class="release-title">Smoother Animations, Optimized Transparency &amp; Enhanced Color Visibility</span>
        <span class="release-date">2026-06-07</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI &amp; Design
            </div>
            <ul>
                <li>
                    <span class="icon">✨</span>
                    <div>
                        <strong>Refined Animation System</strong>
                        <span class="detail">Audited and fine-tuned transitions across buttons, modals, and widgets. Eliminated excessive motion while improving the quality of interactive feedback for a more polished feel.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>Glassmorphism Optimization &amp; Color Contrast Overhaul</strong>
                        <span class="detail">Reduced overly aggressive blur and transparency effects that were hurting readability. Improved color contrast and brightness across the entire UI, resulting in a cleaner, more accessible, and more refined visual design.</span>
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
        <span class="release-title">Migration to SSE + Web Push Hybrid Notification Architecture</span>
        <span class="release-date">2026-06-07</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features &amp; Enhancements
            </div>
            <ul>
                <li>
                    <span class="icon">🔔</span>
                    <div>
                        <strong>SSE + Web Push Hybrid Notification System</strong>
                        <span class="detail">Completely overhauled the notification delivery infrastructure by adopting a hybrid architecture that combines Server-Sent Events (SSE) with the standard Web Push API. Notifications are now reliably delivered even when the app is in the background, balancing real-time responsiveness with battery efficiency.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>Improved Notification Reliability &amp; Delivery Rate</strong>
                        <span class="detail">Enhanced fallback handling for network interruptions and unstable connections, drastically reducing missed notifications. Optimized Service Worker integration for faster push notification response times.</span>
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
        <span class="release-title">Modern Dialog Enhancements & Premium Animations</span>
        <span class="release-date">2026-05-23</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features & Enhancements
            </div>
            <ul>
                <li>
                    <span class="icon">✨</span>
                    <div>
                        <strong>Declarative Light-dismiss Support</strong>
                        <span class="detail">Added <code>closedby="any"</code> to various dialogs (Settings, Gallery, Pinned list, etc.). Users can now dismiss modals smoothly by clicking outside (on the backdrop), providing a modern, native UX.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🛡️</span>
                    <div>
                        <strong>Global JS Fallback for Safari</strong>
                        <span class="detail">Implemented a lightweight event-delegation fallback logic during JS initialization for browsers that do not yet fully support the <code>closedby</code> attribute natively.</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI & Design
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>@starting-style Premium Transitions</strong>
                        <span class="detail">Utilized CSS <code>@starting-style</code>, <code>transition-behavior: allow-discrete</code>, and the <code>overlay</code> property to achieve ultra-smooth bidirectional entry and exit animations (fade & scale) purely via CSS.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">♿</span>
                    <div>
                        <strong>Reduced Motion Accessibility</strong>
                        <span class="detail">Ensured automatic fallback to simple, clean fade transitions in environments where animation reduction (<code>prefers-reduced-motion</code>) is enabled.</span>
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
        <span class="release-title">ule RefactoriPWA Strengthening & Notification Modng</span>
        <span class="release-date">2026-05-09</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> Major Updates
            </div>
            <ul>
                <li>
                    <span class="icon">🔔</span>
                    <div>
                        <strong>Notification Module Refactoring</strong>
                        <span class="detail">Decoupled notification logic into a standalone module (`notifications.js`), improving reliability and future scalability.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚡</span>
                    <div>
                        <strong>Enhanced PWA Caching</strong>
                        <span class="detail">Updated Service Worker to cache modular CSS and core scripts, significantly boosting offline performance.</span>
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
        <span class="release-title">New Documentation Pages</span>
        <span class="release-date">2026-05-07</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Content
            </div>
            <ul>
                <li>
                    <span class="icon">📝</span>
                    <div>
                        <strong>About, Privacy Policy, & Terms</strong>
                        <span class="detail">Added official pages for project information and legal compliance.</span>
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
        <span class="release-title">Night Theme Expansion</span>
        <span class="release-date">2026-05-06</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI & Design
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>Night Theme Color Palette</strong>
                        <span class="detail">Introduced new color schemes for the Night theme to improve accessibility and aesthetics.</span>
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
        <span class="release-title">Profile Settings Improvements</span>
        <span class="release-date">2026-05-06</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> UX Refinement
            </div>
            <ul>
                <li>
                    <span class="icon">⚙️</span>
                    <div>
                        <strong>Refined Modal Interaction</strong>
                        <span class="detail">Improved the behavior and transition of the user profile settings modal.</span>
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
        <span class="release-title">Landing Page Visual Update</span>
        <span class="release-date">2026-05-06</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI & Design
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>Styling Refinements</strong>
                        <span class="detail">Polished the landing page design for better visual appeal.</span>
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
        <span class="release-title">New Premium Landing Page Integration</span>
        <span class="release-date">2026-05-05</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Feature
            </div>
            <ul>
                <li>
                    <span class="icon">✨</span>
                    <div>
                        <strong>Next-Gen Landing Page (index.html)</strong>
                        <span class="detail">Launched a brand new, high-fidelity landing page designed to showcase the SYCS ecosystem. Featuring a sleek dual-panel layout with dynamic entrance animations for a premium first impression.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>Dedicated Design System (landing.css)</strong>
                        <span class="detail">Developed a standalone design system for the landing page, incorporating high-quality typography (Outfit & Inter), smooth transitions, and a modern color palette.</span>
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
                    <span class="icon">🚀</span>
                    <div>
                        <strong>Full Responsive Optimization</strong>
                        <span class="detail">Ensured seamless adaptability across all screen sizes, from desktop workstations to mobile devices, maintaining visual integrity throughout.</span>
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
        <span class="release-title">API Integration Improvements and Service Worker Reliability</span>
        <span class="release-date">2026-05-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> Stability & Fixes
            </div>
            <ul>
                <li>
                    <span class="icon">⚙️</span>
                    <div>
                        <strong>Improved API Response Integrity</strong>
                        <span class="detail">Ensured the process exits immediately after API handling, preventing unwanted HTML fragments from corrupting JSON responses.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔌</span>
                    <div>
                        <strong>Optimized Service Worker Caching</strong>
                        <span class="detail">Updated the cache logic to exclusively handle HTTP/HTTPS protocols, preventing crashes caused by browser extensions or internal protocols.</span>
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
        <span class="release-title">Status Selector UI Overhaul & Visual Polish</span>
        <span class="release-date">2026-05-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> UI & Design
            </div>
            <ul>
                <li>
                    <span class="icon">🎨</span>
                    <div>
                        <strong>Modernized Status Selector</strong>
                        <span class="detail">Completely redesigned the status dropdown with a custom chevron icon, glassmorphism effects (backdrop blur), and refined hover/focus states for a more consistent and premium experience.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">✨</span>
                    <div>
                        <strong>Enhanced Visual Feedback</strong>
                        <span class="detail">Optimized transitions and shadows across interactive elements to provide a smoother and more polished user interface.</span>
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
        <span class="release-title">Backend Hardening & Security Enhancements</span>
        <span class="release-date">2026-05-04</span>
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
                        <strong>Hardened API Handler</strong>
                        <span class="detail">Refactored API input processing to use secure wrappers, eliminating direct access to superglobals and reducing vulnerability risks.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔒</span>
                    <div>
                        <strong>Expanded Prepared Statements</strong>
                        <span class="detail">Ensured consistent use of prepared statements for critical operations like message pinning and deletion to bolster SQL injection protection.</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> Fixes & Improvements
            </div>
            <ul>
                <li>
                    <span class="icon">🐞</span>
                    <div>
                        <strong>Favorites Loading Fix</strong>
                        <span class="detail">Resolved a race condition in the favorites view by optimizing event listeners, ensuring reliable data display.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🏗️</span>
                    <div>
                        <strong>Improved Code Quality</strong>
                        <span class="detail">Implemented type hints and more robust error handling in the backend for enhanced system reliability.</span>
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
        <span class="release-title">Enhanced Favorites Management & UI Refinement</span>
        <span class="release-date">2026-05-04</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-green"></span> New Features
            </div>
            <ul>
                <li>
                    <span class="icon">⭐</span>
                    <div>
                        <strong>Dedicated Favorites Page</strong>
                        <span class="detail">Added a new page to view and manage all your favorite threads in one place.</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-yellow"></span> Improvements & UI
            </div>
            <ul>
                <li>
                    <span class="icon">📱</span>
                    <div>
                        <strong>Standardized Header Component</strong>
                        <span class="detail">Centralized header logic into a shared component, improving accessibility to thread search, attachment galleries, and pinned messages.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🌍</span>
                    <div>
                        <strong>Extended i18n Support</strong>
                        <span class="detail">Updated translation resources for favorite-related features in JA, EN, and ZH.</span>
                    </div>
                </li>
            </ul>
        </div>
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> Fixes & Internals
            </div>
            <ul>
                <li>
                    <span class="icon">🔧</span>
                    <div>
                        <strong>API Protocol Optimization</strong>
                        <span class="detail">Unified favorite operation APIs to use JSON payloads for better security and reliability.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🐞</span>
                    <div>
                        <strong>DM Header Display Fix</strong>
                        <span class="detail">Fixed a bug where the DM partner's name was not displayed correctly in the header.</span>
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
        <span class="release-title">Comprehensive Chat Feature Fixes & Enhancements</span>
        <span class="release-date">2026-05-03</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-red"></span> Bug Fixes
            </div>
            <ul>
                <li>
                    <span class="icon">📌</span>
                    <div>
                        <strong>Pin Message Feature Fully Fixed</strong>
                        <span class="detail">Resolved the bug where the pin/unpin button had no effect. The message list now auto-refreshes after pinning. The pinned messages modal now correctly passes the thread ID to the API, and displays sender name, timestamp, and content. Clicking a pinned item scrolls and highlights it in the chat.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">😊</span>
                    <div>
                        <strong>Reaction Picker Fixed</strong>
                        <span class="detail">Fixed the emoji reaction picker not appearing when the reaction button was clicked. A floating emoji picker (👍❤️😂 and 7 more) now correctly appears, and selecting an emoji toggles the reaction as expected.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">✏️</span>
                    <div>
                        <strong>Message Edit & Delete Fixed</strong>
                        <span class="detail">The edit button now correctly displays an inline editing area within the message. The delete button now works with a confirmation dialog before removing the message.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">↩️</span>
                    <div>
                        <strong>Reply Feature Fixed</strong>
                        <span class="detail">Fixed the <code>reply_to_id</code> not being sent to the API when replying. The backend <code>sendMessage</code> handler now correctly saves <code>reply_to_id</code> in the database.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔍</span>
                    <div>
                        <strong>Message Search & Attachment Gallery Fixed</strong>
                        <span class="detail">Fixed the API path duplication bug in the search function. Resolved the field name mismatch (<code>item.path</code> → <code>item.attachment_path</code>) in the attachment gallery that caused images not to display.</span>
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
                    <span class="icon">⌨️</span>
                    <div>
                        <strong>Keyboard Shortcuts Implemented</strong>
                        <span class="detail"><code>Alt+P</code> to show pinned messages, <code>/</code> to focus search, and <code>Alt+Shift+?</code> to display the shortcuts help modal are now all functional.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🗄️</span>
                    <div>
                        <strong>Enhanced Message Fetch API</strong>
                        <span class="detail">The <code>getMessages</code> API now fetches reactions, reply-source usernames, and online statuses in a single optimized query, eliminating redundant follow-up requests.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🔗</span>
                    <div>
                        <strong>Improved State Management</strong>
                        <span class="detail">Thread switching now syncs <code>window.SYCS_CONFIG.currentThreadId</code>, ensuring all modules always reference the correct active thread.</span>
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
        <span class="release-title">Massive Architecture Overhaul & Performance Tuning</span>
        <span class="release-date">2026-05-02</span>
    </div>
    <div class="release-body">
        <div class="section">
            <div class="section-title">
                <span class="dot dot-blue"></span> Stability & Security
            </div>
            <ul>
                <li>
                    <span class="icon">🏗️</span>
                    <div>
                        <strong>Backend API Decoupling</strong>
                        <span class="detail">Extracted API routing and database initialization logic into dedicated handler classes (<code>Handler.php</code>, <code>db_init.php</code>), significantly improving backend maintainability and security.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">⚙️</span>
                    <div>
                        <strong>Frontend Modularization</strong>
                        <span class="detail">Split the monolithic <code>index.js</code> into ES6 modules (<code>api.js</code>, <code>chat.js</code>, <code>ui.js</code>, etc.) to optimize code maintainability and load performance.</span>
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
                    <span class="icon">🎨</span>
                    <div>
                        <strong>CSS Componentization</strong>
                        <span class="detail">Decomposed the single <code>style.css</code> into logical modules (<code>layout.css</code>, <code>components.css</code>, <code>modals.css</code>, etc.) for better UI scalability.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧱</span>
                    <div>
                        <strong>UI Template Refactoring</strong>
                        <span class="detail">Split the dense HTML structure in <code>index.php</code> into reusable include files like <code>sidebar.php</code> and <code>modals.php</code>, enhancing UI consistency and development efficiency.</span>
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
        <span class="release-title">UI/UX Refinement & Extended i18n Support</span>
        <span class="release-date">2026-04-25</span>
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
                        <strong>Pinned Messages Icon Refresh</strong>
                        <span class="detail">Replaced the text emoji with a custom SVG icon (`pin.svg`) for better UI consistency and a professional look.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">👤</span>
                    <div>
                        <strong>Enhanced Profile Settings</strong>
                        <span class="detail">Localized labels for profile layout and banner images, eliminating hardcoded strings from the frontend templates.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🌍</span>
                    <div>
                        <strong>Expanded Localization (JA/EN/ZH)</strong>
                        <span class="detail">Added translation support for clock widget types (Digital/Analog) and ToDo list operations across all supported languages.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">🧹</span>
                    <div>
                        <strong>Frontend Code Cleanup</strong>
                        <span class="detail">Replaced inline Japanese text with dynamic i18n calls in template files to improve maintainability.</span>
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
