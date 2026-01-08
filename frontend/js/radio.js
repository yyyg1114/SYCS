/**
 * Radio Logs管理スクリプト
 * ダッシュボードでラジオログを表示・更新します
 */

async function loadRadio() {
    try {
        const response = await fetch('../backend/radio_logs.php?action=get&limit=20');
        const data = await response.json();
        
        if (data.success) {
            displayRadioLogs(data.logs);
        } else {
            console.error('ラジオログ取得エラー:', data.error);
        }
    } catch (error) {
        console.error('ラジオログ取得失敗:', error);
    }
}

function displayRadioLogs(logs) {
    const radioList = document.getElementById('radioLogs');
    
    if (!radioList) return;
    
    if (logs.length === 0) {
        radioList.innerHTML = '<li style="color: #999;">ラジオログがありません</li>';
        return;
    }
    
    radioList.innerHTML = '';
    
    logs.forEach(log => {
        const logItem = document.createElement('li');
        logItem.className = `radio-log ${log.type.toLowerCase()}`;
        logItem.dataset.id = log.id;
        
        const datetime = new Date(log.log_datetime);
        const formattedTime = datetime.toLocaleString('ja-JP', {
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        
        const typeLabel = getTypeLabel(log.type);
        const typeColor = getTypeColor(log.type);
        
        logItem.innerHTML = `
            <div class="radio-log-header">
                <span class="radio-time">${formattedTime}</span>
                <span class="radio-source">${escapeHtml(log.source)}</span>
                <span class="radio-type" style="background-color: ${typeColor};">${typeLabel}</span>
                <button class="radio-delete-btn" data-log-id="${log.id}" title="このログを削除">✕</button>
            </div>
            <div class="radio-message">
                <span class="radio-seq">[${log.type_seq}]</span>
                <span class="radio-text">${escapeHtml(log.message)}</span>
            </div>
        `;
        
        radioList.appendChild(logItem);
        
        // 削除ボタンイベントリスナー
        const deleteBtn = logItem.querySelector('.radio-delete-btn');
        deleteBtn.addEventListener('click', (e) => {
            e.preventDefault();
            deleteRadioLog(log.id, logItem);
        });
    });
}

async function deleteRadioLog(logId, logElement) {
    if (!confirm('このラジオログを削除しますか？')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', logId);
        
        const response = await fetch('../backend/radio_logs.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // UIからも削除
            logElement.style.opacity = '0';
            logElement.style.transition = 'opacity 0.3s ease-out';
            setTimeout(() => {
                logElement.remove();
            }, 300);
        } else {
            alert('削除失敗: ' + (data.error || '不明なエラー'));
        }
    } catch (error) {
        console.error('削除エラー:', error);
        alert('削除処理中にエラーが発生しました');
    }
}

function getTypeLabel(type) {
    const typeMap = {
        'INFO': '情報',
        'MOVE': '移動',
        'CHECK': '確認',
        'WARN': '警告',
        'CMD': '指令',
        'COMMIT': '完了'
    };
    return typeMap[type] || type;
}

function getTypeColor(type) {
    const colorMap = {
        'INFO': '#007bff',    // 青
        'MOVE': '#17a2b8',    // シアン
        'CHECK': '#ffc107',   // 黄色
        'WARN': '#dc3545',    // 赤
        'CMD': '#6f42c1',     // 紫
        'COMMIT': '#28a745'   // 緑
    };
    return colorMap[type] || '#6c757d';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 初期化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadRadio);
} else {
    loadRadio();
}
