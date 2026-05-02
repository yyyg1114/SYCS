/**
 * SYCS Utilities Module
 */

export const translations = window.SYCS_CONFIG.translations || {};

/**
 * Translate a key
 * @param {string} key
 * @param {string} defaultText
 * @returns {string}
 */
export function t(key, defaultText = null) {
  return translations[key] || defaultText || key;
}

/**
 * Helper to escape HTML to prevent XSS
 * @param {string} str
 * @returns {string}
 */
export function escapeHTML(str) {
  if (str === null || str === undefined) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

/**
 * Helper to apply a regex rule to text nodes within a fragment and replace match with elements
 * @param {DocumentFragment} fragment
 * @param {RegExp} regex
 * @param {Function} elementFactory
 */
export function applyRule(fragment, regex, elementFactory) {
  const walker = document.createTreeWalker(
    fragment,
    NodeFilter.SHOW_TEXT,
    null,
    false,
  );
  const textNodes = [];
  while (walker.nextNode()) textNodes.push(walker.currentNode);

  for (const node of textNodes) {
    let parent = node.parentElement;
    let inProtectedTag = false;
    while (parent && parent !== fragment) {
      if (parent.tagName === "CODE" || parent.tagName === "PRE") {
        inProtectedTag = true;
        break;
      }
      parent = parent.parentElement;
    }
    if (inProtectedTag) continue;

    const text = node.nodeValue;
    let lastIndex = 0;
    let match;
    const newNodes = [];
    let hasMatch = false;

    while ((match = regex.exec(text)) !== null) {
      hasMatch = true;
      if (match.index > lastIndex) {
        newNodes.push(
          document.createTextNode(text.substring(lastIndex, match.index)),
        );
      }
      const element = elementFactory(...match);
      if (element) {
        newNodes.push(element);
      }
      lastIndex = regex.lastIndex;
      if (!regex.global) break;
    }

    if (hasMatch) {
      if (lastIndex < text.length) {
        newNodes.push(document.createTextNode(text.substring(lastIndex)));
      }
      const p = node.parentNode;
      for (const newNode of newNodes) {
        p.insertBefore(newNode, node);
      }
      p.removeChild(node);
    }
    regex.lastIndex = 0;
  }
}

/**
 * Format a message string into a DocumentFragment with rich text elements
 * @param {string} text
 * @param {string} currentUserName
 * @returns {DocumentFragment}
 */
export function formatMessage(text, currentUserName) {
  const fragment = document.createDocumentFragment();
  if (!text) return fragment;

  fragment.appendChild(document.createTextNode(text));

  // 1. Code Blocks: ```lang\ncode\n```
  applyRule(fragment, /```(\w*)\n([\s\S]*?)\n```/g, (match, lang, code) => {
    const pre = document.createElement("pre");
    const codeEl = document.createElement("code");
    if (lang) codeEl.className = `language-${lang}`;
    codeEl.textContent = code.trim();
    pre.appendChild(codeEl);
    return pre;
  });

  // 2. Inline Code: `code`
  applyRule(fragment, /`([^`]+)`/g, (match, code) => {
    const codeEl = document.createElement("code");
    codeEl.textContent = code;
    return codeEl;
  });

  // 3. Bold: **text**
  applyRule(fragment, /\*\*([^*]+)\*\*/g, (match, content) => {
    const b = document.createElement("b");
    b.textContent = content;
    return b;
  });

  // 4. Italic: *text* or _text_
  applyRule(fragment, /\*([^*]+)\*/g, (match, content) => {
    const i = document.createElement("i");
    i.textContent = content;
    return i;
  });
  applyRule(fragment, /_([^_]+)_/g, (match, content) => {
    const i = document.createElement("i");
    i.textContent = content;
    return i;
  });

  // 5. Underline: __text__
  applyRule(fragment, /__([^_]+)__/g, (match, content) => {
    const u = document.createElement("u");
    u.textContent = content;
    return u;
  });

  // 6. Strikethrough: ~~text~~
  applyRule(fragment, /~~([^~]+)~~/g, (match, content) => {
    const del = document.createElement("del");
    del.textContent = content;
    return del;
  });

  // 7. Blockquotes: > text
  applyRule(fragment, /^> (.*$)/gm, (match, content) => {
    const bq = document.createElement("blockquote");
    bq.textContent = content;
    return bq;
  });

  // 8. Mentions: @username
  applyRule(fragment, /@([a-zA-Z0-9_]+)/g, (match, username) => {
    const span = document.createElement("span");
    const isMe = username === currentUserName;
    span.className = `mention${isMe ? " mention-me" : ""}`;
    span.textContent = match;
    return span;
  });

  // 9. Auto-link URLs
  const urlRegex = /(https?:\/\/[^\s<]+)/g;
  applyRule(fragment, urlRegex, (match, url) => {
    const a = document.createElement("a");
    a.href = url;
    a.target = "_blank";
    a.rel = "noopener noreferrer";
    a.textContent = url;
    return a;
  });

  // 10. Line breaks
  applyRule(fragment, /\n/g, () => {
    return document.createElement("br");
  });

  return fragment;
}

/**
 * Apply syntax highlighting to code blocks
 * @param {HTMLElement} container
 */
export function applyHighlighting(container) {
  if (typeof hljs !== 'undefined') {
    container.querySelectorAll('pre code').forEach((block) => {
      hljs.highlightElement(block);
    });
  }
}

/**
 * Generate an avatar element
 * @param {string} name
 * @param {string} status
 * @param {string|null} avatarUrl
 * @returns {HTMLElement}
 */
export function getAvatarElement(name, status = "none", avatarUrl = null) {
  const initial = name ? name.charAt(0).toUpperCase() : "?";
  const colors = ["#6366f1", "#ec4899", "#8b5cf6", "#10b981", "#f59e0b", "#3b82f6"];
  const colorIdx = (name ? name.length : 0) % colors.length;

  const container = document.createElement("div");
  container.className = "avatar-container";

  const div = document.createElement("div");
  div.className = "avatar";

  if (avatarUrl) {
    const img = document.createElement("img");
    img.src = avatarUrl;
    img.style.width = "100%";
    img.style.height = "100%";
    img.style.borderRadius = "50%";
    img.style.objectFit = "cover";
    div.appendChild(img);
  } else {
    div.style.background = colors[colorIdx];
    div.textContent = initial;
  }

  container.appendChild(div);

  if (status !== "none") {
    const indicator = document.createElement("div");
    indicator.className = `status-indicator status-${status}`;
    container.appendChild(indicator);
  }

  return container;
}

/**
 * Get a skeleton loader element
 * @returns {HTMLElement}
 */
export function getSkeletonLoader() {
  const container = document.createElement("div");
  container.className = "skeleton-container";
  for (let i = 0; i < 4; i++) {
    const item = document.createElement("div");
    item.className = "skeleton-item";

    const avatar = document.createElement("div");
    avatar.className = "skeleton-avatar skeleton-shimmer";

    const info = document.createElement("div");
    info.className = "skeleton-info";

    const name = document.createElement("div");
    name.className = "skeleton-name skeleton-shimmer";

    const text1 = document.createElement("div");
    text1.className = "skeleton-text skeleton-shimmer";

    const text2 = document.createElement("div");
    text2.className = "skeleton-text short skeleton-shimmer";

    info.appendChild(name);
    info.appendChild(text1);
    info.appendChild(text2);

    item.appendChild(avatar);
    item.appendChild(info);
    container.appendChild(item);
  }
  return container;
}
