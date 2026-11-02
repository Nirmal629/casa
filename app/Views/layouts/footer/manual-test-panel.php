<?php
/**
 * Development-only Manual Testing Control Panel Widget.
 * Loaded only in development environment.
 */
?>
<!-- Development Manual Testing Widget -->
<div id="casa-qa-panel" class="casa-qa-wrapper" style="position:fixed; bottom:20px; right:20px; z-index:999999; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size:13px;">
    <!-- Floating Badge Button -->
    <button type="button" id="casa-qa-toggle-btn" style="display:flex; align-items:center; gap:8px; background:#1e1e2e; color:#cdd6f4; border:1px solid #45475a; border-radius:30px; padding:8px 16px; box-shadow:0 6px 20px rgba(0,0,0,0.35); cursor:pointer; font-weight:600; font-size:12px; transition:all 0.2s ease;">
        <span style="font-size:14px;">🧪</span>
        <span id="casa-qa-status-pill" style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#a6adc8;"></span>
        <span id="casa-qa-status-text">QA Logger: Ready</span>
    </button>

    <!-- Expanded Drawer / Panel -->
    <div id="casa-qa-card" style="display:none; position:absolute; bottom:50px; right:0; width:440px; max-width:92vw; background:#181825; color:#cdd6f4; border:1px solid #313244; border-radius:12px; box-shadow:0 12px 35px rgba(0,0,0,0.5); overflow:hidden;">
        <!-- Header -->
        <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; background:#11111b; border-bottom:1px solid #313244;">
            <div style="display:flex; align-items:center; gap:8px;">
                <span style="font-size:16px;">🧪</span>
                <strong style="color:#cdd6f4; font-size:13px;">Manual Testing Journey Logger</strong>
            </div>
            <button type="button" id="casa-qa-close-btn" style="background:none; border:none; color:#a6adc8; cursor:pointer; font-size:16px; padding:2px 6px;">✕</button>
        </div>

        <!-- Controls Body -->
        <div style="padding:16px;">
            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#a6adc8; margin-bottom:4px; font-weight:700;">Test Session ID</label>
                <div style="display:flex; gap:6px;">
                    <input type="text" id="casa-qa-session-input" placeholder="e.g. CASA-TEST-001" style="flex:1; background:#1e1e2e; border:1px solid #45475a; color:#cdd6f4; border-radius:6px; padding:7px 10px; font-size:12px; font-family:monospace;" value="CASA-TEST-001">
                    <button type="button" id="casa-qa-gen-btn" title="Generate New Session ID" style="background:#313244; border:none; color:#cdd6f4; border-radius:6px; padding:0 10px; cursor:pointer; font-size:12px;">New</button>
                </div>
            </div>

            <div style="display:flex; gap:8px; margin-bottom:14px;">
                <button type="button" id="casa-qa-start-btn" style="flex:1; background:#a6e3a1; color:#11111b; border:none; border-radius:6px; padding:8px; font-weight:700; cursor:pointer; transition:opacity 0.2s;">
                    ▶ Start Testing
                </button>
                <button type="button" id="casa-qa-stop-btn" style="flex:1; background:#f38ba8; color:#11111b; border:none; border-radius:6px; padding:8px; font-weight:700; cursor:pointer; transition:opacity 0.2s; display:none;">
                    ⏹ Stop Testing
                </button>
                <button type="button" id="casa-qa-view-btn" style="background:#89b4fa; color:#11111b; border:none; border-radius:6px; padding:8px 12px; font-weight:700; cursor:pointer;">
                    📋 Logs
                </button>
                <button type="button" id="casa-qa-export-btn" style="background:#cba6f7; color:#11111b; border:none; border-radius:6px; padding:8px 12px; font-weight:700; cursor:pointer;" title="Download JSONL Log">
                    💾 Export
                </button>
            </div>

            <!-- Live Session Timeline View -->
            <div id="casa-qa-log-container" style="display:none; max-height:240px; overflow-y:auto; background:#11111b; border:1px solid #313244; border-radius:6px; padding:10px; font-family:Consolas, monospace; font-size:11px;">
                <div id="casa-qa-log-list" style="display:flex; flex-direction:column; gap:6px;">
                    <span style="color:#6c7086;">No events logged yet in this session.</span>
                </div>
            </div>
        </div>

        <!-- Footer Info -->
        <div style="padding:8px 16px; background:#11111b; border-top:1px solid #313244; display:flex; justify-content:space-between; align-items:center; font-size:11px; color:#6c7086;">
            <span>DEV MODE ONLY</span>
            <span id="casa-qa-log-counter">0 events captured</span>
        </div>
    </div>
</div>

<script>
(function () {
    const toggleBtn = document.getElementById('casa-qa-toggle-btn');
    const card = document.getElementById('casa-qa-card');
    const closeBtn = document.getElementById('casa-qa-close-btn');
    const sessionInput = document.getElementById('casa-qa-session-input');
    const genBtn = document.getElementById('casa-qa-gen-btn');
    const startBtn = document.getElementById('casa-qa-start-btn');
    const stopBtn = document.getElementById('casa-qa-stop-btn');
    const viewBtn = document.getElementById('casa-qa-view-btn');
    const exportBtn = document.getElementById('casa-qa-export-btn');
    const logContainer = document.getElementById('casa-qa-log-container');
    const logList = document.getElementById('casa-qa-log-list');
    const statusPill = document.getElementById('casa-qa-status-pill');
    const statusText = document.getElementById('casa-qa-status-text');
    const counterText = document.getElementById('casa-qa-log-counter');

    function syncUI() {
        if (!window.CasaTestLogger) return;

        const active = window.CasaTestLogger.isActive();
        const currentSid = window.CasaTestLogger.getSessionId();

        if (sessionInput && currentSid) {
            sessionInput.value = currentSid;
        }

        if (active) {
            statusPill.style.background = '#a6e3a1'; // Green
            statusPill.style.boxShadow = '0 0 8px #a6e3a1';
            statusText.textContent = `QA: ${currentSid} (ACTIVE)`;
            startBtn.style.display = 'none';
            stopBtn.style.display = 'block';
            sessionInput.disabled = true;
            genBtn.disabled = true;
        } else {
            statusPill.style.background = '#a6adc8'; // Gray
            statusPill.style.boxShadow = 'none';
            statusText.textContent = `QA: ${currentSid} (STOPPED)`;
            startBtn.style.display = 'block';
            stopBtn.style.display = 'none';
            sessionInput.disabled = false;
            genBtn.disabled = false;
        }
    }

    toggleBtn.addEventListener('click', function () {
        card.style.display = card.style.display === 'none' ? 'block' : 'none';
    });

    closeBtn.addEventListener('click', function () {
        card.style.display = 'none';
    });

    genBtn.addEventListener('click', function () {
        const d = new Date();
        const num = Math.floor(100 + Math.random() * 900);
        sessionInput.value = `CASA-TEST-${num}`;
    });

    startBtn.addEventListener('click', function () {
        const sid = sessionInput.value.trim() || 'CASA-TEST-001';
        window.CasaTestLogger.start(sid);
        syncUI();
        refreshLogs();
    });

    stopBtn.addEventListener('click', function () {
        window.CasaTestLogger.stop();
        syncUI();
        refreshLogs();
    });

    viewBtn.addEventListener('click', function () {
        logContainer.style.display = logContainer.style.display === 'none' ? 'block' : 'none';
        if (logContainer.style.display === 'block') {
            refreshLogs();
        }
    });

    exportBtn.addEventListener('click', function () {
        const sid = sessionInput.value.trim() || window.CasaTestLogger.getSessionId();
        window.location.href = window.CasaTestLogger.getExportUrl(sid);
    });

    function formatTime(isoStr) {
        if (!isoStr) return '';
        try {
            const d = new Date(isoStr);
            return d.toTimeString().split(' ')[0];
        } catch (e) {
            return '';
        }
    }

    function renderLogItems(logs) {
        logList.innerHTML = '';
        counterText.textContent = `${logs.length} events captured`;

        if (!logs.length) {
            logList.innerHTML = '<span style="color:#6c7086;">No events logged yet in this session.</span>';
            return;
        }

        logs.forEach(item => {
            const row = document.createElement('div');
            row.style.borderBottom = '1px solid #1e1e2e';
            row.style.paddingBottom = '4px';

            const seqBadge = `<span style="color:#89b4fa;">[${String(item.seq).padStart(3, '0')}]</span>`;
            const timeBadge = `<span style="color:#6c7086;">${formatTime(item.timestamp)}</span>`;

            let typeColor = '#f9e2af';
            let detail = '';

            switch (item.action_type) {
                case 'NAVIGATE':
                    typeColor = '#89dceb';
                    detail = `<strong style="color:#89dceb;">NAVIGATE:</strong> ${item.url || ''}`;
                    break;
                case 'CLICK':
                    typeColor = '#fab387';
                    detail = `<strong style="color:#fab387;">CLICK:</strong> ${item.element || ''}`;
                    break;
                case 'MODAL_OPEN':
                    typeColor = '#cba6f7';
                    detail = `<strong style="color:#cba6f7;">MODAL:</strong> ${item.element || ''}`;
                    break;
                case 'FORM_SUBMIT':
                    typeColor = '#f38ba8';
                    detail = `<strong style="color:#f38ba8;">SUBMIT:</strong> ${item.element || ''}`;
                    break;
                case 'API_CALL':
                    typeColor = '#a6e3a1';
                    detail = `<strong style="color:#a6e3a1;">API →</strong> ${(item.api && item.api.method) || ''} ${(item.api && item.api.endpoint) || ''}`;
                    break;
                case 'API_RESPONSE':
                    typeColor = item.status_code >= 400 ? '#f38ba8' : '#a6e3a1';
                    detail = `<strong style="color:${typeColor};">API ←</strong> ${item.status_code} (${item.duration_ms}ms)`;
                    break;
                case 'JS_ERROR':
                case 'PHP_EXCEPTION':
                    typeColor = '#f38ba8';
                    detail = `<strong style="color:#f38ba8;">ERROR:</strong> ${(item.error && item.error.message) || ''}`;
                    break;
                default:
                    detail = `<strong>${item.action_type}:</strong> ${item.message || ''}`;
            }

            row.innerHTML = `${seqBadge} ${timeBadge} ${detail}`;
            logList.appendChild(row);
        });

        logContainer.scrollTop = logContainer.scrollHeight;
    }

    function refreshLogs() {
        const sid = sessionInput.value.trim() || window.CasaTestLogger.getSessionId();
        fetch(window.CasaTestLogger.getLogsUrl(sid))
            .then(res => res.json())
            .then(data => {
                if (data && data.success && Array.isArray(data.logs)) {
                    renderLogItems(data.logs);
                }
            })
            .catch(() => {});
    }

    window.addEventListener('casa:test-log-emitted', function (e) {
        if (logContainer.style.display === 'block') {
            refreshLogs();
        } else {
            const currentCount = parseInt(counterText.textContent, 10) || 0;
            counterText.textContent = `${currentCount + 1} events captured`;
        }
    });

    document.addEventListener('DOMContentLoaded', syncUI);
    syncUI();
})();
</script>

