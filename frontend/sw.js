// SYCS Service Worker - PWA + Push Notifications
const CACHE_VERSION = "sycs-v1";
const STATIC_CACHE = CACHE_VERSION + "-static";
const DYNAMIC_CACHE = CACHE_VERSION + "-dynamic";

// 静的アセット（事前キャッシュ対象）
const STATIC_ASSETS = [
  "./css/bundle.min.css",
  "./css/style-index.css",
  "./assets/img/SYCS_favicon.svg",
  "./assets/img/SYCS_Logo.svg",
  "./assets/img/camera_off.svg",
  "./assets/img/camera_on.svg",
  "./assets/img/edit.svg",
  "./assets/img/emoji.svg",
  "./assets/img/files.svg",
  "./assets/img/hangup.svg",
  "./assets/img/mic.svg",
  "./assets/img/mic_muted.svg",
  "./assets/img/pin.svg",
  "./assets/img/reply.svg",
  "./assets/img/screen_share.svg",
  "./assets/img/trash.svg",
  "./js/index.js",
  "./js/webrtc.js",
  "./js/locate.js",
];

// インストール: 静的アセットを事前キャッシュ
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches
      .open(STATIC_CACHE)
      .then((cache) => {
        console.log("[SW] 静的アセットをキャッシュ中...");
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => self.skipWaiting())
      .catch((err) => {
        console.warn("[SW] 一部のアセットキャッシュに失敗:", err);
        return self.skipWaiting();
      }),
  );
});

// アクティベート: 古いキャッシュを削除
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) => {
        return Promise.all(
          keys
            .filter((key) => key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
            .map((key) => {
              console.log("[SW] 古いキャッシュを削除:", key);
              return caches.delete(key);
            }),
        );
      })
      .then(() => self.clients.claim()),
  );
});

// フェッチ: ネットワークファースト（API）+ キャッシュファースト（静的）
self.addEventListener("fetch", (event) => {
  const url = new URL(event.request.url);

  // API リクエストはキャッシュしない
  if (url.searchParams.has("api") || event.request.method !== "GET") {
    return;
  }

  // アップロードファイルはキャッシュしない
  if (url.pathname.includes("/uploads/")) {
    return;
  }

  // 外部CDNリソース（fonts, scripts等）
  if (url.origin !== location.origin) {
    event.respondWith(
      caches.match(event.request).then((cached) => {
        if (cached) return cached;
        return fetch(event.request)
          .then((response) => {
            if (response.ok && (url.protocol === "http:" || url.protocol === "https:")) {
              const clone = response.clone();
              caches.open(DYNAMIC_CACHE).then((cache) => {
                cache.put(event.request, clone);
              });
            }
            return response;
          })
          .catch(() => cached);
      }),
    );
    return;
  }

  // 静的アセット: キャッシュファースト
  if (
    STATIC_ASSETS.some((asset) =>
      url.pathname.endsWith(asset.replace("./", "")),
    )
  ) {
    event.respondWith(
      caches.match(event.request).then((cached) => {
        const fetchPromise = fetch(event.request)
          .then((response) => {
            if (response.ok && (url.protocol === "http:" || url.protocol === "https:")) {
              const clone = response.clone();
              caches.open(STATIC_CACHE).then((cache) => {
                cache.put(event.request, clone);
              });
            }
            return response;
          })
          .catch(() => cached);

        return cached || fetchPromise;
      }),
    );
    return;
  }

  // HTMLページ: ネットワークファースト（オフライン時はキャッシュ）
  if (event.request.headers.get("accept")?.includes("text/html")) {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          if (response.ok) {
            const clone = response.clone();
            caches.open(DYNAMIC_CACHE).then((cache) => {
              cache.put(event.request, clone);
            });
          }
          return response;
        })
        .catch(() => {
          return caches.match(event.request).then((cached) => {
            if (cached) return cached;
            // オフラインフォールバック
            return new Response(
              `<!DOCTYPE html>
                            <html lang="ja">
                            <head>
                                <meta charset="UTF-8">
                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                <title>SYCS - オフライン</title>
                                <style>
                                    * { margin: 0; padding: 0; box-sizing: border-box; }
                                    body {
                                        font-family: 'Inter', sans-serif;
                                        background: #1a1a2e;
                                        color: #e0e0e0;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        min-height: 100vh;
                                        text-align: center;
                                        padding: 20px;
                                    }
                                    .offline-container {
                                        max-width: 400px;
                                    }
                                    .offline-icon {
                                        font-size: 4rem;
                                        margin-bottom: 1.5rem;
                                        opacity: 0.7;
                                    }
                                    h1 {
                                        color: #6366f1;
                                        margin-bottom: 1rem;
                                        font-size: 1.5rem;
                                    }
                                    p {
                                        color: #94a3b8;
                                        margin-bottom: 2rem;
                                        line-height: 1.6;
                                    }
                                    .retry-btn {
                                        background: #6366f1;
                                        color: white;
                                        border: none;
                                        padding: 12px 32px;
                                        border-radius: 8px;
                                        font-size: 1rem;
                                        cursor: pointer;
                                        transition: background 0.2s;
                                    }
                                    .retry-btn:hover {
                                        background: #4f46e5;
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="offline-container">
                                    <div class="offline-icon">📡</div>
                                    <h1>SYCS - オフライン</h1>
                                    <p>インターネット接続がありません。<br>接続を確認して、もう一度お試しください。</p>
                                    <button class="retry-btn" onclick="location.reload()">再接続</button>
                                </div>
                            </body>
                            </html>`,
              {
                headers: { "Content-Type": "text/html; charset=utf-8" },
              },
            );
          });
        }),
    );
    return;
  }

  // その他のリソース: Stale While Revalidate
  event.respondWith(
    caches.match(event.request).then((cached) => {
      const fetchPromise = fetch(event.request)
        .then((response) => {
          if (response.ok && (url.protocol === "http:" || url.protocol === "https:")) {
            const clone = response.clone();
            caches.open(DYNAMIC_CACHE).then((cache) => {
              cache.put(event.request, clone);
            });
          }
          return response;
        })
        .catch(() => cached);

      return cached || fetchPromise;
    }),
  );
});

// プッシュ通知
self.addEventListener("push", function (event) {
  const data = event.data
    ? event.data.json()
    : { title: "SYCS Notification", body: "New Message" };

  const options = {
    body: data.body,
    icon: data.icon || "assets/img/SYCS_favicon.svg",
    badge: "assets/img/SYCS_favicon.svg",
    data: data.data,
    vibrate: [100, 50, 100],
    actions: [
      { action: "open", title: "開く" },
      { action: "close", title: "閉じる" },
    ],
  };

  event.waitUntil(self.registration.showNotification(data.title || "SYCS", options));
});

// 通知クリック
self.addEventListener("notificationclick", function (event) {
  event.notification.close();

  if (event.action === "close") return;

  const url = event.notification.data?.url || "index.php";

  event.waitUntil(
    clients.matchAll({ type: "window" }).then((windowClients) => {
      for (var i = 0; i < windowClients.length; i++) {
        var client = windowClients[i];
        if (client.url.includes("index.php") && "focus" in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    }),
  );
});

// バックグラウンド同期（将来の拡張用）
self.addEventListener("sync", (event) => {
  if (event.tag === "sync-messages") {
    console.log("[SW] メッセージ同期中...");
  }
});
