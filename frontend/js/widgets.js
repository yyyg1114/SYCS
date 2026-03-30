document.addEventListener('DOMContentLoaded', () => {
    initWidgets();
    initClock();
    initNotepad();
    initFiler();
    initTodo();
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
            if (target === 'todo') {
                renderTodos();
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
        // Minute markers (60)
        for (let i = 1; i <= 60; i++) {
            if (i % 5 === 0) continue;
            const index = document.createElement('div');
            index.className = 'clock-index minute';
            index.style.setProperty('--i', i);
            face.appendChild(index);
        }
        // 1/5 second markers (300)
        for (let i = 1; i <= 300; i++) {
            if (i % 5 === 0) continue;
            const index = document.createElement('div');
            index.className = 'clock-index fifth';
            index.style.setProperty('--i', i);
            face.appendChild(index);
        }

        // Sub-dial indices
        const sub9 = face.querySelector('.sub-9');
        if (sub9) {
            for (let i = 0; i < 12; i++) {
                const index = document.createElement('div');
                index.className = 'sub-index' + (i % 3 === 0 ? ' major' : '');
                index.style.setProperty('--si', i);
                index.style.setProperty('--sa', '30deg');
                sub9.appendChild(index);
            }
        }
        const sub3 = face.querySelector('.sub-3');
        if (sub3) {
            for (let i = 0; i < 7; i++) {
                const index = document.createElement('div');
                index.className = 'sub-index major';
                index.style.setProperty('--si', i);
                index.style.setProperty('--sa', (360/7) + 'deg');
                sub3.appendChild(index);
            }
        }
        const sub6 = face.querySelector('.sub-6');
        if (sub6) {
            for (let i = 0; i < 12; i++) {
                const index = document.createElement('div');
                index.className = 'sub-index' + (i % 3 === 0 ? ' major' : '');
                index.style.setProperty('--si', i);
                index.style.setProperty('--sa', '30deg');
                sub6.appendChild(index);
            }
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
        const ms = now.getMilliseconds();
        const seconds = now.getSeconds() + ms / 1000;
        const minutes = now.getMinutes() + seconds / 60;
        const hours = now.getHours() + minutes / 60;

        // Digital (Update once per second)
        if (digital && ms < 50) {
            digital.textContent = now.toLocaleTimeString('ja-JP', { hour12: false });
        }

        // Analog Hands
        const steppedSeconds = Math.floor(seconds * 5) / 5;
        const secDeg = steppedSeconds * 6;
        const minDeg = minutes * 6;
        const hourDeg = (hours % 12) * 30;

        const secHand = face.querySelector('.second-hand');
        const minHand = face.querySelector('.minute-hand');
        const hourHand = face.querySelector('.hour-hand');

        if (secHand) secHand.style.transform = `rotate(${secDeg}deg)`;
        if (minHand) minHand.style.transform = `rotate(${minDeg}deg)`;
        if (hourHand) hourHand.style.transform = `rotate(${hourDeg}deg)`;

        // Sub-dials
        const sub9Hand = face.querySelector('.sub-9 .sub-hand');
        const sub3Hand = face.querySelector('.sub-3 .sub-hand');
        const sub6Hand = face.querySelector('.sub-6 .sub-hand');

        if (sub9Hand) {
            // 24h dial - step by hour
            const sub9Deg = (Math.floor(hours) / 24) * 360;
            sub9Hand.style.setProperty('--sub-angle', `${sub9Deg}deg`);
        }
        if (sub3Hand) {
            // Day of week dial - step by day
            const sub3Deg = (now.getDay() / 7) * 360;
            sub3Hand.style.setProperty('--sub-angle', `${sub3Deg}deg`);
        }
        if (sub6Hand) {
            // Constant seconds dial - step by second
            const sub6Deg = Math.floor(seconds) * 6;
            sub6Hand.style.setProperty('--sub-angle', `${sub6Deg}deg`);
        }

        // Date (Update once per day/hour)
        if (ms < 50) {
            const dateSpan = document.querySelector('.date-window span');
            if (dateSpan) {
                dateSpan.textContent = now.getDate();
            }
        }

        requestAnimationFrame(updateClock);
    }

    requestAnimationFrame(updateClock);
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

// --- Todo Widget ---
let todos = [];

function initTodo() {
    const input = document.getElementById('todo-input');
    if (!input) return;

    // Load from localStorage
    const saved = localStorage.getItem('sycs_todos');
    if (saved) {
        try {
            todos = JSON.parse(saved);
        } catch (e) {
            todos = [];
        }
    }

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            addTodo();
        }
    });

    renderTodos();
}

function addTodo() {
    const input = document.getElementById('todo-input');
    const text = input.value.trim();
    if (!text) return;

    const newTodo = {
        id: Date.now(),
        text: text,
        completed: false
    };

    todos.push(newTodo);
    saveTodos();
    input.value = '';
    renderTodos();
}

function toggleTodo(id) {
    todos = todos.map(t => t.id === id ? { ...t, completed: !t.completed } : t);
    saveTodos();
    renderTodos();
}

function deleteTodo(id) {
    todos = todos.filter(t => t.id !== id);
    saveTodos();
    renderTodos();
}

function saveTodos() {
    localStorage.setItem('sycs_todos', JSON.stringify(todos));
}

function renderTodos() {
    const list = document.getElementById('todo-list');
    if (!list) return;

    if (todos.length === 0) {
        list.innerHTML = `<div class="empty-files" style="padding-top:20px;">${window.SYCS_CONFIG.translations.no_tasks || 'タスクはありません'}</div>`;
        return;
    }

    list.innerHTML = '';
    // Sort: Uncompleted first, then by id descending
    const sorted = [...todos].sort((a, b) => {
        if (a.completed !== b.completed) return a.completed ? 1 : -1;
        return b.id - a.id;
    });

    sorted.forEach(todo => {
        const item = document.createElement('div');
        item.className = `todo-item ${todo.completed ? 'completed' : ''}`;
        
        item.innerHTML = `
            <input type="checkbox" class="todo-checkbox" ${todo.completed ? 'checked' : ''} onchange="toggleTodo(${todo.id})">
            <span class="todo-text">${escapeHTML(todo.text)}</span>
            <button class="todo-delete" onclick="deleteTodo(${todo.id})" title="削除">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </button>
        `;
        list.appendChild(item);
    });
}

function escapeHTML(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
