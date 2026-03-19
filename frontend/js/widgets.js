document.addEventListener('DOMContentLoaded', () => {
    initWidgets();
    initClock();
    initNotepad();
    initFiler();
});

function initWidgets() {
    const tabs = document.querySelectorAll('.widget-tab');
    const panes = document.querySelectorAll('.widget-pane');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.widget;
            
            tabs.forEach(t => t.classList.remove('active'));
            panes.forEach(p => p.classList.remove('active'));
            
            tab.classList.add('active');
            document.getElementById(`widget-${target}`).classList.add('active');

            if (target === 'filer') {
                loadFiles();
            }
        });
    });
}

function initClock() {
    const toggle = document.getElementById('clock-type-toggle');
    const analog = document.getElementById('analog-clock');
    const digital = document.getElementById('digital-clock');
    const face = analog ? analog.querySelector('.clock-face') : null;

    // Create indices
    if (face) {
        // Hour markers (12)
        for (let i = 1; i <= 12; i++) {
            const index = document.createElement('div');
            index.className = 'clock-index hour';
            index.style.setProperty('--i', i);
            face.appendChild(index);
        }
        // Minute markers (60-12=48)
        for (let i = 1; i <= 60; i++) {
            if (i % 5 === 0) continue;
            const index = document.createElement('div');
            index.className = 'clock-index minute';
            index.style.setProperty('--i', i);
            face.appendChild(index);
        }
    }

    // Restore preference
    const isDigital = localStorage.getItem('sycs_clock_type') === 'digital';
    if (toggle) {
        toggle.checked = !isDigital;
        if (isDigital) {
            analog.style.display = 'none';
            digital.style.display = 'block';
        }
    }

    if (toggle) {
        toggle.addEventListener('change', () => {
            if (toggle.checked) {
                analog.style.display = 'flex';
                digital.style.display = 'none';
                localStorage.setItem('sycs_clock_type', 'analog');
            } else {
                analog.style.display = 'none';
                digital.style.display = 'block';
                localStorage.setItem('sycs_clock_type', 'digital');
            }
        });
    }

    function updateClock() {
        const now = new Date();
        const seconds = now.getSeconds();
        const minutes = now.getMinutes();
        const hours = now.getHours();

        // Digital
        if (digital) {
            digital.textContent = now.toLocaleTimeString('ja-JP', { hour12: false });
        }

        // Analog Hands
        const secDeg = (seconds / 60) * 360;
        const minDeg = (minutes / 60) * 360 + (seconds / 60) * 6;
        const hourDeg = (hours / 12) * 360 + (minutes / 60) * 30;

        const secHand = document.querySelector('.second-hand');
        const minHand = document.querySelector('.minute-hand');
        const hourHand = document.querySelector('.hour-hand');

        if (secHand) secHand.style.transform = `rotate(${secDeg}deg)`;
        if (minHand) minHand.style.transform = `rotate(${minDeg}deg)`;
        if (hourHand) hourHand.style.transform = `rotate(${hourDeg}deg)`;

        // Date
        const dateSpan = document.querySelector('.date-window span');
        if (dateSpan) {
            dateSpan.textContent = now.getDate();
        }
    }

    setInterval(updateClock, 1000);
    updateClock();
}

function initNotepad() {
    const area = document.getElementById('notepad-area');
    if (!area) return;

    // Load
    area.value = localStorage.getItem('sycs_notepad_content') || '';

    // Save with debounce
    let timeout;
    area.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            localStorage.setItem('sycs_notepad_content', area.value);
        }, 500);
    });
}

function initFiler() {
    // Filer initialization - load once if active
    if (document.getElementById('widget-filer').classList.contains('active')) {
        loadFiles();
    }
}

async function loadFiles() {
    const list = document.getElementById('file-list');
    if (!list) return;

    list.innerHTML = '<div class="loading">読み込み中...</div>';

    try {
        const res = await fetch('index.php?api=get_my_files');
        const files = await res.json();

        if (!files || files.length === 0) {
            list.innerHTML = '<div class="empty-files">ファイルはありません</div>';
            return;
        }

        list.innerHTML = '';
        files.forEach(file => {
            const item = document.createElement('a');
            item.className = 'file-item';
            item.href = `download.php?file=${encodeURIComponent(file.attachment_path)}`;
            item.target = '_blank';
            
            const fileName = file.attachment_path.split('/').pop();
            const ext = fileName.split('.').pop().toLowerCase();
            
            let icon = '📄';
            if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) icon = '🖼️';
            if (['mp4', 'webm', 'mov'].includes(ext)) icon = '🎬';
            if (['mp3', 'wav', 'ogg'].includes(ext)) icon = '🎵';
            if (['pdf'].includes(ext)) icon = '📕';
            if (['zip', 'rar', '7z'].includes(ext)) icon = '📦';

            item.innerHTML = `
                <span class="file-icon">${icon}</span>
                <div class="file-info">
                    <span class="file-name" title="${fileName}">${fileName}</span>
                    <span class="file-date">${new Date(file.created_at).toLocaleDateString()}</span>
                </div>
            `;
            list.appendChild(item);
        });
    } catch (err) {
        console.error('Failed to load files:', err);
        list.innerHTML = '<div class="empty-files">読み込み失敗</div>';
    }
}
