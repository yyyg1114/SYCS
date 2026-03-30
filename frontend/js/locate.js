/**
 * 位置情報取得モジュール
 * Geolocation APIを使用してGPS位置情報を取得・管理
 */

class LocationManager {
  constructor(statusElementId = "gps-status-display") {
    this.statusElement = document.getElementById(statusElementId);
    this.gpsData = {};
    this.watchId = null;

    // レート制限設定
    this.lastUpdateTime = 0;
    this.minUpdateInterval = 5000; // 5秒ごと
    this.maxUpdateInterval = 30000; // 最大30秒
  }

  /**
   * 位置情報監視を開始
   * @param {number} updateInterval - 更新間隔（ミリ秒）
   * @returns {boolean}
   */
  start(updateInterval = 5000) {
    if (!navigator.geolocation) {
      this.displayError("位置情報取得に未対応のブラウザです");
      return false;
    }

    // 更新間隔の検証
    if (updateInterval < this.minUpdateInterval) {
      console.warn(
        `更新間隔が短すぎます。${this.minUpdateInterval}ms に設定します。`,
      );
      updateInterval = this.minUpdateInterval;
    }
    if (updateInterval > this.maxUpdateInterval) {
      console.warn(
        `更新間隔が長すぎます。${this.maxUpdateInterval}ms に設定します。`,
      );
      updateInterval = this.maxUpdateInterval;
    }

    // 初回位置取得
    this.getCurrentLocation();

    // 定期的な位置取得
    setInterval(() => {
      this.getCurrentLocation();
    }, updateInterval);

    return true;
  }

  /**
   * 初期化メソッド (index.js からの呼び出し用)
   * @param {string} statusElementId
   * @param {number} updateInterval
   */
  init(statusElementId, updateInterval) {
    if (statusElementId) {
      this.statusElement = document.getElementById(statusElementId);
    }
    this.start(updateInterval);
  }

  /**
   * 現在地を取得
   */
  getCurrentLocation() {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(
      (position) => this.handleSuccess(position),
      (error) => this.handleError(error),
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
      },
    );
  }

  /**
   * 位置情報取得成功時の処理
   * @param {GeolocationPosition} position
   */
  handleSuccess(position) {
    const coords = position.coords;

    // GPS データを更新
    this.gpsData = {
      lat: coords.latitude,
      lon: coords.longitude,
      accuracy: coords.accuracy,
      altitude: coords.altitude,
      timestamp: new Date(position.timestamp),
    };

    // UI を更新
    this.updateDisplay();

    // レート制限チェック後にバックエンドに送信
    this.sendToBackendWithRateLimit();
  }

  /**
   * 位置情報取得エラー時の処理
   * @param {GeolocationPositionError} error
   */
  handleError(error) {
    let message = "位置情報取得エラー: ";

    switch (error.code) {
      case error.PERMISSION_DENIED:
        message += "位置情報へのアクセスが許可されていません";
        break;
      case error.POSITION_UNAVAILABLE:
        message += "位置情報が利用できません";
        break;
      case error.TIMEOUT:
        message += "位置情報取得タイムアウト";
        break;
      default:
        message += "未知のエラーが発生しました";
    }

    this.displayError(message);
    console.error(message);
  }

  /**
   * UI に位置情報を表示
   */
  updateDisplay() {
    const { lat, lon, accuracy, altitude, timestamp } = this.gpsData;
    const timeStr = timestamp ? timestamp.toLocaleTimeString("ja-JP") : "---";

    // 更新対象の要素をすべて取得
    const targets = [];
    if (this.statusElement) targets.push(this.statusElement);
    // すべての gps-status クラスまたは ID を持つ要素を対象にする
    const profileGps = document.getElementById("gps-status");
    if (profileGps && !targets.includes(profileGps)) targets.push(profileGps);
    
    document.querySelectorAll(".gps-status-target").forEach(el => {
      if (!targets.includes(el)) targets.push(el);
    });

    if (targets.length === 0) return;

    const createDisplay = (isCompact = false) => {
      const wrapper = document.createElement("div");
      wrapper.className = isCompact ? "gps-info compact" : "gps-info";

      if (!lat || !lon) {
        wrapper.innerHTML = `<div class="gps-waiting"><span class="status-dot error"></span> 位置情報を取得中...</div>`;
        return wrapper;
      }

      const createRow = (label, value) => {
        const row = document.createElement("div");
        row.className = "gps-row";
        const labelSpan = document.createElement("span");
        labelSpan.className = "gps-label";
        labelSpan.textContent = label;
        const valueSpan = document.createElement("span");
        valueSpan.className = "gps-value";
        valueSpan.textContent = value;
        row.appendChild(labelSpan);
        row.appendChild(valueSpan);
        return row;
      };

      wrapper.appendChild(createRow("緯度:", lat.toFixed(6)));
      wrapper.appendChild(createRow("経度:", lon.toFixed(6)));
      wrapper.appendChild(
        createRow("精度:", accuracy ? accuracy.toFixed(2) + "m" : "---"),
      );
      wrapper.appendChild(
        createRow("高度:", altitude ? altitude.toFixed(2) + "m" : "取得不可"),
      );
      wrapper.appendChild(createRow("更新:", timeStr));

      return wrapper;
    };

    targets.forEach(target => {
      const isCompact = target.classList.contains("compact-gps") || target.id === "gps-status";
      target.textContent = "";
      target.appendChild(createDisplay(isCompact));
    });

    // ヘッダーのステータスドットを更新
    const headerDots = document.querySelectorAll(".gps-status-indicator");
    headerDots.forEach(dotContainer => {
      dotContainer.textContent = "";
      const dot = document.createElement("span");
      dot.className = "status-dot active";
      dotContainer.appendChild(dot);
    });
  }

  /**
   * エラーメッセージを表示
   * @param {string} message
   */
  displayError(message) {
    const targets = [];
    if (this.statusElement) targets.push(this.statusElement);
    const profileGps = document.getElementById("gps-status");
    if (profileGps) targets.push(profileGps);

    targets.forEach(target => {
      target.textContent = "";
      const errorDiv = document.createElement("div");
      errorDiv.className = "gps-error";
      const dot = document.createElement("span");
      dot.className = "status-dot error";
      errorDiv.appendChild(dot);
      errorDiv.appendChild(document.createTextNode(" " + message));
      target.appendChild(errorDiv);
    });

    // ヘッダーのステータスドットもエラー表示に更新
    const headerDots = document.querySelectorAll(".gps-status-indicator");
    headerDots.forEach(dotContainer => {
      dotContainer.textContent = "";
      const dot = document.createElement("span");
      dot.className = "status-dot error";
      dotContainer.appendChild(dot);
    });
  }

  /**
   * レート制限を考慮してバックエンドに送信
   */
  sendToBackendWithRateLimit() {
    const now = Date.now();
    const timeSinceLastUpdate = now - this.lastUpdateTime;

    // 5秒以内の更新は送信しない
    if (timeSinceLastUpdate < this.minUpdateInterval) {
      console.log(
        `[locate.js] レート制限中。次の送信まで ${this.minUpdateInterval - timeSinceLastUpdate}ms 待機`,
      );
      return;
    }

    this.lastUpdateTime = now;
    this.sendToBackend();
  }

  /**
   * GPS データをバックエンドに送信
   */
  sendToBackend() {
    const { lat, lon, accuracy, altitude } = this.gpsData;

    if (!lat || !lon) {
      console.warn("GPS位置情報が完全ではありません");
      return;
    }

    const body = new FormData();
    body.append("lat", lat);
    body.append("lon", lon);
    body.append("accuracy", accuracy);
    body.append("altitude", altitude);

    // CSRF Token
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
    if (csrfToken) {
      body.append("csrf_token", csrfToken);
    }

    console.log("[locate.js] 送信データ:", { lat, lon, accuracy, altitude });

    fetch("index.php?api=update_location", {
      method: "POST",
      body: body,
    })
      .then((res) => {
        if (!res.ok && res.status !== 429) {
          throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.json();
      })
      .then((data) => {
        if (data.success) {
          console.log("[locate.js] 位置情報の更新に成功しました");
        } else if (data.error === "rate_limit_exceeded") {
          console.warn(
            "[locate.js] レート制限に達しました。しばらく待機してください。",
          );
        } else {
          console.error("[locate.js] 位置情報の更新に失敗:", data.error);
        }
      })
      .catch((err) => {
        console.error("GPS送信エラー:", err);
      });
  }

  /**
   * GPS データを取得
   * @returns {Object} GPS座標データ
   */
  getGPSData() {
    return {
      ...this.gpsData,
    };
  }

  /**
   * 位置情報監視を停止
   */
  stop() {
    if (this.watchId !== null) {
      navigator.geolocation.clearWatch(this.watchId);
      this.watchId = null;
    }
  }
}

// グローバルインスタンスを作成
const locationManager = new LocationManager();
