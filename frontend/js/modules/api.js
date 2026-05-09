/**
 * SYCS API Module
 */

import { t } from './utils.js';

/**
 * トースト通知を表示する (Stubs for now, will be implemented in ui.js)
 */
let showToastFn = (title, message, type = "success", duration = 5000) => {
    console.log(`Toast: [${type}] ${title} - ${message}`);
};

export function registerShowToast(fn) {
    showToastFn = fn;
}

/**
 * API Wrapper
 * @param {string} path 
 * @param {string} method 
 * @param {FormData|object|null} body 
 * @returns {Promise<any>}
 */
export async function api(path, method = "GET", body = null) {
  const csrfToken = window.SYCS_CONFIG.csrfToken;
  const opts = {
    method,
  };
  
  if (method === "POST") {
    if (!body) body = new FormData();

    if (typeof body === "string") {
      // Assume JSON
      opts.headers = { "Content-Type": "application/json" };
      // CSRF token needs to be added to JSON if it's an object
      try {
          const parsed = JSON.parse(body);
          if (typeof parsed === "object") {
              parsed.csrf_token = csrfToken;
              body = JSON.stringify(parsed);
          }
      } catch (e) {}
      opts.body = body;
    } else {
      if (!(body instanceof FormData)) {
        const formData = new FormData();
        for (const key in body) {
          formData.append(key, body[key]);
        }
        body = formData;
      }

      if (!body.has("csrf_token")) {
        body.append("csrf_token", csrfToken);
      }
      opts.body = body;
    }
  } else if (body) {
    opts.body = body;
  }

  try {
    const res = await fetch(`index.php?api=${path}`, opts);
    const text = await res.text();

    try {
      const json = JSON.parse(text);
      if (json && json.success === false) {
        showToastFn(
          t("error", "エラー"),
          json.error || t("unknown_error", "不明なエラーが発生しました"),
          "error",
        );
      }
      return json;
    } catch (parseError) {
      console.error("JSON parse error:", parseError, text);
      const errorMsg = t("server_error_json", "サーバーエラー: JSONパースに失敗しました");
      showToastFn(t("system_error", "システムエラー"), errorMsg, "error");
      return {
        error: errorMsg,
        details: text.substring(0, 500),
      };
    }
  } catch (fetchError) {
    console.error("Fetch error:", fetchError);
    const errorMsg = t("network_error", "ネットワークエラー") + ": " + fetchError.message;
    showToastFn(t("network_error", "通信エラー"), t("connection_failed", "サーバーに接続できませんでした"), "error");
    return {
      error: errorMsg,
    };
  }
}
