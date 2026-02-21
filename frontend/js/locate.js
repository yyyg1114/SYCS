/**
 * 位置情報取得モジュール
 * Geolocation APIを使用してGPS位置情報を取得・管理
 */

class LocationManager {
    constructor() {
        this.watchId = null;
        this.statusElement = null;
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
     * @param {string} elementId - 位置情報を表示するHTML要素のID
     * @param {number} updateInterval - 位置情報更新間隔（ミリ秒）
     */
    init(elementId, updateInterval = 1000) {
        this.statusElement = document.getElementById(elementId);

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
        if (!this.statusElement) return;

        const { lat, lon, accuracy, altitude, timestamp } = this.gpsData;
        const timeStr = timestamp ? timestamp.toLocaleTimeString('ja-JP') : '---';

        this.statusElement.textContent = '';
        
        const wrapper = document.createElement('div');
        wrapper.className = 'gps-info';

        const createRow = (label, value) => {
            const row = document.createElement('div');
            row.className = 'gps-row';
            const labelSpan = document.createElement('span');
            labelSpan.className = 'gps-label';
            labelSpan.textContent = label;
            const valueSpan = document.createElement('span');
            valueSpan.className = 'gps-value';
            valueSpan.textContent = value;
            row.appendChild(labelSpan);
            row.appendChild(valueSpan);
            return row;
        };

        wrapper.appendChild(createRow('緯度:', lat ? lat.toFixed(6) : '取得中...'));
        wrapper.appendChild(createRow('経度:', lon ? lon.toFixed(6) : '取得中...'));
        wrapper.appendChild(createRow('精度:', accuracy ? accuracy.toFixed(2) + 'm' : '---'));
        wrapper.appendChild(createRow('高度:', altitude ? altitude.toFixed(2) + 'm' : '取得不可'));
        wrapper.appendChild(createRow('更新時刻:', timeStr));

        const indicator = document.createElement('div');
        indicator.className = 'gps-status-indicator';
        const dot = document.createElement('span');
        dot.className = 'status-dot active';
        indicator.appendChild(dot);
        indicator.appendChild(document.createTextNode(' 位置情報取得中'));
        wrapper.appendChild(indicator);

        this.statusElement.appendChild(wrapper);
    }

    /**
     * エラーメッセージを表示
     * @param {string} message 
     */
    displayError(message) {
        if (!this.statusElement) return;

        this.statusElement.textContent = '';
        const errorDiv = document.createElement('div');
        errorDiv.className = 'gps-error';
        const dot = document.createElement('span');
        dot.className = 'status-dot error';
        errorDiv.appendChild(dot);
        errorDiv.appendChild(document.createTextNode(' ' + message));
        this.statusElement.appendChild(errorDiv);
    }

    /**
     * GPS データをバックエンドに送信
     */
    sendToBackend() {
        const { lat, lon, accuracy, altitude } = this.gpsData;

        if (!lat || !lon) {
            console.warn('GPS位置情報が完全ではありません');
            return;
        }

        const body = new FormData();
        body.append('lat', lat);
        body.append('lon', lon);
        body.append('accuracy', accuracy);
        body.append('altitude', altitude);

        fetch('index.php?api=update_location', {
            method: 'POST',
            body: body
        })
            .catch(err => {
                console.error('GPS送信エラー:', err);
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
