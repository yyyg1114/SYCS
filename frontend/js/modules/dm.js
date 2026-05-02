/**
 * SYCS DM Module
 */

import { api } from './api.js';

export async function getDmPartners() {
  return await api("get_dm_partners");
}

export async function getDirectMessages(partnerId) {
  return await api(`get_direct_messages&partner_id=${partnerId}`);
}

export async function sendDirectMessage(receiverId, content, file = null) {
  const body = new FormData();
  body.append("receiver_id", receiverId);
  body.append("content", content);
  if (file) body.append("attachment", file);
  return await api("send_direct_message", "POST", body);
}
