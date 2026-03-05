// frontend/js/errorHandler.js

class APIError extends Error {
    constructor(code, message, userMessage = null) {
        super(message);
        this.code = code;
        this.userMessage = userMessage || this.getDefaultUserMessage(code);
        this.timestamp = new Date().toISOString();
        this.name = 'APIError';
    }
    
    getDefaultUserMessage(code) {
        const messages = {
            'NETWORK_ERROR': 'ネットワークエラーが発生しました。接続を確認してください。',
            'TIMEOUT_ERROR': 'リクエストがタイムアウトしました。もう一度お試しください。',
            'VALIDATION_ERROR': '入力値が正しくありません。',
            'UNAUTHORIZED': 'ログインが必要です。',
            'FORBIDDEN': 'このアクションを実行する権限がありません。',
            'NOT_FOUND': 'リソースが見つかりません。',
            'SERVER_ERROR': 'サーバーエラーが発生しました。管理者に連絡してください。',
            'CSRF_ERROR': 'セッションの有効期限が切れたか、不正なリクエストです。ページを再読み込みしてください。',
        };
        return messages[code] || '予期しないエラーが発生しました。';
    }
}

class ClientLogger {
    error(message, context = {}) {
        console.error(`[ERROR] ${message}`, context);
        // 必要に応じてサーバーにログを送信する処理を追加可能
    }
    
    warn(message, context = {}) {
        console.warn(`[WARN] ${message}`, context);
    }
    
    info(message, context = {}) {
        console.info(`[INFO] ${message}`, context);
    }
}

class APIClient {
    constructor(timeout = 30000) {
        this.timeout = timeout;
        this.logger = new ClientLogger();
    }
    
    async request(endpoint, options = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeout);
        
        const defaultOptions = {
            method: 'POST',
            credentials: 'include',
            // API側でJSONを期待しているため、FormDataではなくJSONで送る場合はここを調整
            // ただしSYCSの既存コードは$_POSTを多用しているため、FormDataが基本となる
        };

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            signal: controller.signal
        };
        
        try {
            const response = await fetch(endpoint, mergedOptions);
            
            clearTimeout(timeoutId);
            
            let data;
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.includes("application/json")) {
                data = await response.json();
            } else {
                const text = await response.text();
                this.logger.error('Non-JSON response received', { status: response.status, body: text });
                throw new APIError('SERVER_ERROR', `HTTP ${response.status}: Invalid response format`);
            }
            
            if (!response.ok) {
                throw new APIError(
                    data.error?.code || 'HTTP_ERROR',
                    data.error?.message || `HTTP ${response.status}`,
                    data.error?.userMessage
                );
            }
            
            if (!data.success) {
                throw new APIError(
                    data.error?.code || 'API_ERROR',
                    data.error?.message || 'API processing failed',
                    data.error?.userMessage
                );
            }
            
            return data; // success=true の場合は data 全体を返す
            
        } catch (error) {
            clearTimeout(timeoutId);
            
            if (error instanceof APIError) {
                this.logger.error(error.message, { code: error.code, details: error.userMessage });
                throw error;
            }
            
            if (error.name === 'AbortError') {
                const timeoutError = new APIError(
                    'TIMEOUT_ERROR',
                    'Request timeout',
                    'リクエストがタイムアウトしました。もう一度お試しください。'
                );
                this.logger.error('Request timeout', { endpoint });
                throw timeoutError;
            }
            
            if (error instanceof TypeError && error.message.includes('Failed to fetch')) {
                const networkError = new APIError(
                    'NETWORK_ERROR',
                    'Network error',
                    'ネットワークエラーが発生しました。接続を確認してください。'
                );
                this.logger.error('Network error', { endpoint });
                throw networkError;
            }
            
            const unknownError = new APIError(
                'UNKNOWN_ERROR',
                error.message || 'Unknown error',
                '予期しないエラーが発生しました。'
            );
            this.logger.error('Unknown error', { error: error.message });
            throw unknownError;
        }
    }
}
