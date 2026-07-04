/**
 * SYCS Message Rendering Module
 */

import { formatMessage, applyHighlighting, getAvatarElement, t } from './utils.js';

/**
 * Render a message node recursively
 * @param {object} m Message object
 * @param {HTMLElement} parentContainer
 * @param {object} context Shared context (currentUserName, etc.)
 * @param {object} callbacks Action callbacks
 */
export function renderMessageNode(m, parentContainer, context, callbacks) {
  const { currentUserName, currentUserId } = context;
  const { onReply, onPin, onReact, onEdit, onDelete, onShowProfile, onToggleReaction } = callbacks;

  const wrapper = document.createElement("div");
  wrapper.className = "message-wrapper";
  wrapper.id = "message-" + m.id;

  const group = document.createElement("div");
  group.className = "message-group";

  group.appendChild(getAvatarElement(m.username, m.status || "online", m.avatar_url));

  const info = document.createElement("div");
  info.className = "message-info";

  const header = document.createElement("div");
  header.className = "message-header";

  const user = document.createElement("span");
  user.className = "message-user clickable-username";
  user.textContent = m.username;
  user.style.cursor = "pointer";
  user.onclick = (e) => {
    e.stopPropagation();
    onShowProfile(m.user_id, m.username);
  };

  const time = document.createElement("span");
  time.className = "message-time";
  time.textContent = m.created_at;

  const actions = document.createElement("div");
  actions.className = "message-actions";

  // Reply
  const replyBtn = createActionButton("assets/img/reply.svg", t("reply", "返信"), () => onReply(m.id, m.username, m.content));
  actions.appendChild(replyBtn);

  // Pin
  const isPinned = !!+m.is_pinned;
  const pinBtn = document.createElement("button");
  pinBtn.className = "msg-action-btn";
  if (isPinned) {
    pinBtn.textContent = "📍";
  } else {
    const pinImg = document.createElement("img");
    pinImg.src = "assets/img/pin.svg";
    pinImg.style.width = "16px";
    pinImg.style.height = "16px";
    pinImg.style.opacity = "0.6";
    pinBtn.appendChild(pinImg);
  }
  pinBtn.title = isPinned ? t("unpin", "ピン解除") : t("pin", "ピン留め");
  pinBtn.onclick = () => onPin(m.id);
  actions.appendChild(pinBtn);

  // React
  const reactBtn = createActionButton("assets/img/emoji.svg", t("reaction", "リアクション"), (e) => onReact(e, m.id));
  actions.appendChild(reactBtn);

  // Edit/Delete
  if (m.username === currentUserName) {
    actions.appendChild(createActionButton("assets/img/edit.svg", t("edit", "編集"), () => onEdit(m, false)));
    actions.appendChild(createActionButton("assets/img/trash.svg", t("delete", "削除"), () => onDelete(m.id)));
  }

  header.appendChild(user);
  header.appendChild(time);
  header.appendChild(actions);

  // Reply Quote
  if (m.reply_to_id && m.reply_username) {
    const quote = document.createElement("div");
    quote.className = "reply-quote";
    quote.style.cursor = "pointer";
    quote.innerHTML = `<span style="opacity:0.6; font-size:0.8rem;">↩️ ${t("replying_to", "返信先")}: </span><strong>${m.reply_username}</strong>`;
    quote.onclick = () => {
      const target = document.getElementById("message-" + m.reply_to_id);
      if (target) {
        target.scrollIntoView({ behavior: "smooth", block: "center" });
        target.style.backgroundColor = "rgba(99, 102, 241, 0.2)";
        setTimeout(() => (target.style.backgroundColor = ""), 2000);
      }
    };
    info.appendChild(quote);
  }

  // Content
  const contentDiv = document.createElement("div");
  contentDiv.className = "message-content";
  contentDiv.replaceChildren(formatMessage(m.content || "", currentUserName));
  applyHighlighting(contentDiv);

  if (m.is_edited == 1) {
    const editedLabel = document.createElement("span");
    editedLabel.style.fontSize = "0.7rem";
    editedLabel.style.opacity = "0.5";
    editedLabel.style.marginLeft = "5px";
    editedLabel.innerText = `(${t("edited", "編集済み")})`;
    contentDiv.appendChild(editedLabel);
  }

  // Attachments
  if (m.attachment_path) {
    renderAttachment(m.attachment_path, contentDiv);
  }

  info.appendChild(header);

  if (!!+m.is_pinned) {
    const pinBadge = document.createElement("div");
    pinBadge.className = "message-pinned-badge";
    pinBadge.textContent = `📌 ${t("pinned_message", "ピン留めされたメッセージ")}`;
    info.appendChild(pinBadge);
    group.classList.add("message-pinned");
  }

  info.appendChild(contentDiv);

  // Reactions
  if (m.reactions && m.reactions.length > 0) {
    const reactContainer = renderReactions(m.reactions, m.id, currentUserId, onToggleReaction);
    info.appendChild(reactContainer);
  }

  group.appendChild(info);
  wrapper.appendChild(group);

  // Children
  if (m.children.length > 0) {
    const childrenDiv = document.createElement("div");
    childrenDiv.className = "message-children";
    childrenDiv.style.marginLeft = "20px";
    childrenDiv.style.marginTop = "8px";
    childrenDiv.style.paddingLeft = "10px";
    childrenDiv.style.borderLeft = "2px solid var(--border-color)";
    m.children.forEach((child) => renderMessageNode(child, childrenDiv, context, callbacks));
    wrapper.appendChild(childrenDiv);
  }

  parentContainer.appendChild(wrapper);
}

function createActionButton(src, title, onclick) {
  const btn = document.createElement("button");
  btn.className = "msg-action-btn";
  const img = document.createElement("img");
  img.src = src;
  img.style.width = "16px";
  img.style.height = "16px";
  btn.appendChild(img);
  btn.title = title;
  btn.onclick = onclick;
  return btn;
}

function renderAttachment(path, container) {
  const ext = path.split(".").pop().toLowerCase();
  const isImage = ["jpg", "jpeg", "png", "gif", "webp", "svg"].includes(ext);
  const isAudio = ["mp3", "wav", "ogg"].includes(ext);
  const isVideo = ["mp4", "webm", "ogv", "mov", "avi"].includes(ext);

  if (isImage) {
    const img = document.createElement("img");
    img.src = path;
    img.loading = "lazy";
    img.className = "preview-img";
    img.style.display = "block";
    img.style.marginTop = "10px";
    img.onclick = () => window.open(path, "_blank");
    container.appendChild(img);
  } else if (isAudio) {
    const audio = document.createElement("audio");
    audio.src = path;
    audio.controls = true;
    audio.style.display = "block";
    audio.style.marginTop = "10px";
    audio.style.maxWidth = "100%";
    container.appendChild(audio);
  } else if (isVideo) {
    const video = document.createElement("video");
    video.src = path;
    video.controls = true;
    video.style.display = "block";
    video.style.marginTop = "10px";
    video.style.maxWidth = "100%";
    container.appendChild(video);
  }

  const dlLink = document.createElement("a");
  const fileName = path.split("/").pop();
  dlLink.href = "download.php?file=" + fileName;
  dlLink.target = "_blank";
  dlLink.innerText = `⬇️ ${t("download", "ダウンロード")}`;
  dlLink.style.display = "inline-block";
  dlLink.style.fontSize = "0.75rem";
  dlLink.style.marginTop = "5px";
  dlLink.style.color = "var(--accent-color)";
  container.appendChild(dlLink);
}

function renderReactions(reactions, messageId, currentUserId, onToggleReaction) {
  const reactContainer = document.createElement("div");
  reactContainer.className = "reactions-container";

  const grouped = {};
  reactions.forEach((r) => {
    if (!grouped[r.emoji]) grouped[r.emoji] = [];
    grouped[r.emoji].push(r.user_id);
  });

  Object.keys(grouped).forEach((emoji) => {
    const badge = document.createElement("div");
    const isMyReaction = grouped[emoji].includes(currentUserId);
    badge.className = `reaction-badge ${isMyReaction ? "active" : ""}`;
    badge.innerHTML = `<span>${emoji}</span><span class="reaction-count">${grouped[emoji].length}</span>`;
    badge.onclick = () => onToggleReaction(messageId, emoji);
    reactContainer.appendChild(badge);
  });
  return reactContainer;
}
