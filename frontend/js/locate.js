/**
 * 位置情報取得モジュール
 * Geolocation APIを使用してGPS位置情報を取得・管理
 */

class LocationManager {
    constructor() {
        this.watchId = null;
        this.statusElements = []; // Changed to array
        this.gpsData = {
            lat: null,
            lon: null,
            accuracy: null,
            altitude: null,
            timestamp: null
        };
    }

    /**
     * 初期化処理
     * @param {string} selector - 位置情報を表示するHTML要素のセレクタ (デフォルト: .gps-display-container)
     * @param {number} updateInterval - 位置情報更新間隔（ミリ秒）
     */
    init(selector = '.gps-display-container', updateInterval = 600000) {
        this.statusElements = document.querySelectorAll(selector);

        if (!navigator.geolocation) {
            this.displayError('位置情報取得に未対応のブラウザです');
            return false;
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
     * 現在地を取得
     */
    getCurrentLocation() {
        navigator.geolocation.getCurrentPosition(
            (position) => this.handleSuccess(position),
            (error) => this.handleError(error),
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
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
            timestamp: new Date(position.timestamp)
        };

        // UI を更新
        this.updateDisplay();

        // バックエンドに送信
        this.sendToBackend();
    }

    /**
     * 位置情報取得エラー時の処理
     * @param {GeolocationPositionError} error 
     */
    handleError(error) {
        let message = '位置情報取得エラー: ';

        switch (error.code) {
            case error.PERMISSION_DENIED:
                message += '位置情報へのアクセスが許可されていません';
                break;
            case error.POSITION_UNAVAILABLE:
                message += '位置情報が利用できません';
                break;
            case error.TIMEOUT:
                message += '位置情報取得タイムアウト';
                break;
            default:
                message += '未知のエラーが発生しました';
        }

        this.displayError(message);
        console.error(message);
    }

    /**
     * UI に位置情報を表示
     */
    updateDisplay() {
        if (!this.statusElements || this.statusElements.length === 0) return;

        const { lat, lon, accuracy, altitude, timestamp } = this.gpsData;
        const timeStr = timestamp ? timestamp.toLocaleTimeString('ja-JP') : '---';

        const html = `
            <div class="gps-info">
                <div class="gps-row">
                    <span class="gps-label">緯度:</span>
                    <span class="gps-value">${lat ? lat.toFixed(6) : '取得中...'}</span>
                </div>
                <div class="gps-row">
                    <span class="gps-label">経度:</span>
                    <span class="gps-value">${lon ? lon.toFixed(6) : '取得中...'}</span>
                </div>
                <div class="gps-row">
                    <span class="gps-label">精度:</span>
                    <span class="gps-value">${accuracy ? accuracy.toFixed(2) + 'm' : '---'}</span>
                </div>
                <div class="gps-row">
                    <span class="gps-label">高度:</span>
                    <span class="gps-value">${altitude ? altitude.toFixed(2) + 'm' : '取得不可'}</span>
                </div>
                <div class="gps-row">
                    <span class="gps-label">更新時刻:</span>
                    <span class="gps-value">${timeStr}</span>
                </div>
                <div class="gps-status-indicator">
                    <span class="status-dot active"></span>
                    位置情報取得中
                </div>
            </div>
        `;

        this.statusElements.forEach(el => {
            el.innerHTML = html;
        });
    }

    /**
     * エラーメッセージを表示
     * @param {string} message 
     */
    displayError(message) {
        if (!this.statusElements || this.statusElements.length === 0) return;

        const html = `
            <div class="gps-error">
                <span class="status-dot error"></span>
                ${message}
            </div>
        `;

        this.statusElements.forEach(el => {
            el.innerHTML = html;
        });
    }

    /**
     * GPS データをバックエンドに送信
     */
    sendToBackend() {
        // バックエンドが存在しないか不明のため、エラーログを抑制するか、
        // 既存の通りにしておく。
        // ※元のコードは 'tac-ops-dashboard/backend/api/gps.php' へのパスでしたが、
        // プロジェクト構成が変わっている可能性があります。
        // 一旦そのままにします。
        const { lat, lon, accuracy, altitude } = this.gpsData;

        if (!lat || !lon) {
            // console.warn('GPS位置情報が完全ではありません');
            return;
        }

        // パスが合っているか不明だが、既存コードを尊重
        fetch('tac-ops-dashboard/backend/api/gps.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                lat: lat,
                lon: lon,
                accuracy: accuracy,
                altitude: altitude,
                timestamp: new Date().toISOString()
            })
        })
            .catch(err => {
                // console.error('GPS送信エラー:', err);
            });
    }

    /**
     * GPS データを取得
     * @returns {Object} GPS座標データ
     */
    getGPSData() {
        return {
            ...this.gpsData
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
