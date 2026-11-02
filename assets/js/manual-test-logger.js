/**
 * CASA Manual Testing Journey Logger
 * Captures user journey, clicks, navigation, modals, form submissions, API requests, response times, and JS errors.
 * Development-only QA tool.
 */
(function (window, document) {
    'use strict';

    if (window.__casaManualTestLoggerInitialized) {
        return;
    }
    window.__casaManualTestLoggerInitialized = true;

    const STORAGE_KEY_SESSION_ID = 'casa_qa_session_id';
    const STORAGE_KEY_ACTIVE = 'casa_qa_active';
    const STORAGE_KEY_SEQ = 'casa_qa_seq';

    function isLoggerActive() {
        return localStorage.getItem(STORAGE_KEY_ACTIVE) === 'true';
    }

    function getSessionId() {
        return localStorage.getItem(STORAGE_KEY_SESSION_ID) || 'CASA-TEST-001';
    }

    function nextSequence() {
        let seq = parseInt(sessionStorage.getItem(STORAGE_KEY_SEQ) || '0', 10);
        seq += 1;
        sessionStorage.setItem(STORAGE_KEY_SEQ, String(seq));
        return seq;
    }

    function generateCorrelationId() {
        return 'CORR-' + Math.random().toString(36).substring(2, 8).toUpperCase() + '-' + Date.now().toString(36).toUpperCase();
    }

    function getEndpointUrl() {
        return '/api/manual_test_logger.php';
    }

    function cleanString(str, maxLen = 120) {
        if (!str) return '';
        const cleaned = String(str).replace(/[\r\n\t]+/g, ' ').replace(/\s+/g, ' ').trim();
        return cleaned.length > maxLen ? cleaned.substring(0, maxLen) + '...' : cleaned;
    }

    function sanitizeUrl(rawUrl) {
        try {
            const urlObj = new URL(rawUrl, window.location.origin);
            const params = new URLSearchParams(urlObj.search);
            const sensitiveKeys = ['token', 'password', 'csrf', 'key', 'secret', 'auth', 'card', 'cvv'];
            for (const key of Array.from(params.keys())) {
                const lower = key.toLowerCase();
                if (sensitiveKeys.some(s => lower.includes(s))) {
                    params.set(key, '[REDACTED]');
                }
            }
            urlObj.search = params.toString();
            return urlObj.pathname + (urlObj.search ? urlObj.search : '');
        } catch (e) {
            return rawUrl;
        }
    }

    function getElementIdentifier(el) {
        if (!el || !(el instanceof Element)) return null;

        const tag = el.tagName.toLowerCase();
        const id = el.id ? '#' + el.id : '';
        let classes = '';
        if (el.className && typeof el.className === 'string') {
            const filtered = el.className.split(/\s+/).filter(c => c && !c.startsWith('js-') && !c.includes('active')).slice(0, 3);
            if (filtered.length) classes = '.' + filtered.join('.');
        }

        let label = '';
        if (el.dataset && el.dataset.id) {
            label += ` [id=${el.dataset.id}]`;
        }
        if (el.getAttribute('title')) {
            label += ` "${cleanString(el.getAttribute('title'), 40)}"`;
        } else if (el.innerText && ['button', 'a', 'span', 'li'].includes(tag)) {
            const text = cleanString(el.innerText, 40);
            if (text) label += ` "${text}"`;
        }

        return `${tag}${id}${classes}${label}`;
    }

    function emitLog(actionType, extraData = {}) {
        if (!isLoggerActive()) {
            return;
        }

        const sessionId = getSessionId();
        const seq = nextSequence();
        const now = new Date();
        const timestamp = now.toISOString();

        const logEntry = {
            session_id: sessionId,
            seq: seq,
            timestamp: timestamp,
            url: sanitizeUrl(window.location.href),
            referrer: document.referrer ? sanitizeUrl(document.referrer) : null,
            action_type: actionType,
            element: extraData.element || null,
            api: extraData.api || null,
            status_code: extraData.status_code !== undefined ? extraData.status_code : null,
            duration_ms: extraData.duration_ms !== undefined ? extraData.duration_ms : null,
            error: extraData.error || null,
            correlation_id: extraData.correlation_id || generateCorrelationId(),
            meta: extraData.meta || null
        };

        const payload = JSON.stringify({
            action: 'log',
            session_id: sessionId,
            data: logEntry
        });

        // Broadcast to in-page panel if open
        if (window.dispatchEvent) {
            window.dispatchEvent(new CustomEvent('casa:test-log-emitted', { detail: logEntry }));
        }

        // Asynchronous non-blocking dispatch
        const endpoint = getEndpointUrl();
        if (navigator.sendBeacon) {
            try {
                const blob = new Blob([payload], { type: 'application/json' });
                navigator.sendBeacon(endpoint, blob);
                return;
            } catch (e) {}
        }

        if (window.fetch) {
            window.fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: payload,
                keepalive: true
            }).catch(() => {});
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. PAGE NAVIGATION & LIFECYCLE
    // ─────────────────────────────────────────────────────────────────────────
    window.addEventListener('DOMContentLoaded', function () {
        if (isLoggerActive()) {
            emitLog('NAVIGATE', {
                meta: {
                    title: document.title,
                    pathname: window.location.pathname
                }
            });
        }
    });

    // ─────────────────────────────────────────────────────────────────────────
    // 2. USER CLICKS & INTERACTION
    // ─────────────────────────────────────────────────────────────────────────
    let lastClickTarget = null;
    let lastClickTime = 0;

    document.addEventListener('click', function (e) {
        if (!isLoggerActive()) return;

        const target = e.target;
        if (!target || !(target instanceof Element)) return;

        // Find relevant clickable parent if clicking an icon or span
        const clickable = target.closest('button, a, [role="button"], [data-bs-toggle], input[type="submit"], input[type="button"], .session-card, .dock-btn, .nav-link, .edit_btn, .view_btn, .delete_btn, .session-players-btn');
        const elementToLog = clickable || target;

        // Ignore clicks on body/html/testing panel
        if (elementToLog.closest('#casa-qa-panel') || ['body', 'html'].includes(elementToLog.tagName.toLowerCase())) {
            return;
        }

        const now = Date.now();
        if (elementToLog === lastClickTarget && (now - lastClickTime) < 300) {
            return; // debounce double click
        }
        lastClickTarget = elementToLog;
        lastClickTime = now;

        const elementDesc = getElementIdentifier(elementToLog);
        emitLog('CLICK', {
            element: elementDesc
        });
    }, true);

    // ─────────────────────────────────────────────────────────────────────────
    // 3. FORM SUBMISSION
    // ─────────────────────────────────────────────────────────────────────────
    document.addEventListener('submit', function (e) {
        if (!isLoggerActive()) return;

        const form = e.target;
        if (!form || !(form instanceof HTMLFormElement)) return;
        if (form.closest('#casa-qa-panel')) return;

        const formId = form.id ? '#' + form.id : (form.name ? `form[name="${form.name}"]` : 'form');
        const action = form.getAttribute('action') || window.location.pathname;
        const method = (form.getAttribute('method') || 'GET').toUpperCase();

        // Safely extract input field names (NO sensitive values)
        const fieldNames = [];
        Array.from(form.elements).forEach(input => {
            if (input.name && !fieldNames.includes(input.name)) {
                fieldNames.push(input.name);
            }
        });

        emitLog('FORM_SUBMIT', {
            element: formId,
            meta: {
                action: sanitizeUrl(action),
                method: method,
                fields: fieldNames
            }
        });
    }, true);

    // ─────────────────────────────────────────────────────────────────────────
    // 4. MODALS (Bootstrap & Casa customModals)
    // ─────────────────────────────────────────────────────────────────────────
    document.addEventListener('show.bs.modal', function (e) {
        if (!isLoggerActive()) return;
        const modal = e.target;
        emitLog('MODAL_OPEN', { element: getElementIdentifier(modal) });
    });

    document.addEventListener('hide.bs.modal', function (e) {
        if (!isLoggerActive()) return;
        const modal = e.target;
        emitLog('MODAL_CLOSE', { element: getElementIdentifier(modal) });
    });

    // Observer for CASA customModal_wrap classes
    const modalObserver = new MutationObserver(function (mutations) {
        if (!isLoggerActive()) return;
        mutations.forEach(m => {
            if (m.type === 'attributes' && m.attributeName === 'class') {
                const el = m.target;
                if (el instanceof Element && el.classList.contains('customModal_wrap')) {
                    if (el.classList.contains('open')) {
                        emitLog('MODAL_OPEN', { element: getElementIdentifier(el) });
                    }
                }
            }
        });
    });

    window.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.customModal_wrap').forEach(m => {
            modalObserver.observe(m, { attributes: true });
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // 5. API INTERCEPTION (fetch & XMLHttpRequest)
    // ─────────────────────────────────────────────────────────────────────────
    const origFetch = window.fetch;
    if (origFetch) {
        window.fetch = function (input, init = {}) {
            if (!isLoggerActive()) {
                return origFetch.apply(this, arguments);
            }

            const url = typeof input === 'string' ? input : (input && input.url ? input.url : '');
            // Do not intercept the logger's own endpoint
            if (url.includes('/api/manual_test_logger.php')) {
                return origFetch.apply(this, arguments);
            }

            const method = (init.method || (input && input.method) || 'GET').toUpperCase();
            const correlationId = generateCorrelationId();
            const startTime = Date.now();

            // Inject tracking headers
            init.headers = init.headers || {};
            if (init.headers instanceof Headers) {
                init.headers.set('X-Test-Session-Id', getSessionId());
                init.headers.set('X-Correlation-Id', correlationId);
            } else if (Array.isArray(init.headers)) {
                init.headers.push(['X-Test-Session-Id', getSessionId()]);
                init.headers.push(['X-Correlation-Id', correlationId]);
            } else {
                init.headers['X-Test-Session-Id'] = getSessionId();
                init.headers['X-Correlation-Id'] = correlationId;
            }

            emitLog('API_CALL', {
                api: { endpoint: sanitizeUrl(url), method: method },
                correlation_id: correlationId
            });

            return origFetch.call(this, input, init).then(response => {
                const duration = Date.now() - startTime;
                emitLog('API_RESPONSE', {
                    api: { endpoint: sanitizeUrl(url), method: method },
                    status_code: response.status,
                    duration_ms: duration,
                    correlation_id: correlationId,
                    error: response.status >= 400 ? { status: response.status, statusText: response.statusText } : null
                });
                return response;
            }).catch(err => {
                const duration = Date.now() - startTime;
                emitLog('API_ERROR', {
                    api: { endpoint: sanitizeUrl(url), method: method },
                    status_code: 0,
                    duration_ms: duration,
                    correlation_id: correlationId,
                    error: { message: err.message || 'Network request failed' }
                });
                throw err;
            });
        };
    }

    // Intercept XMLHttpRequest
    const origXHR = window.XMLHttpRequest;
    if (origXHR) {
        const origOpen = origXHR.prototype.open;
        const origSend = origXHR.prototype.send;

        origXHR.prototype.open = function (method, url) {
            this._qa_method = method;
            this._qa_url = url;
            this._qa_correlation_id = generateCorrelationId();
            return origOpen.apply(this, arguments);
        };

        origXHR.prototype.send = function () {
            if (isLoggerActive() && this._qa_url && !this._qa_url.includes('/api/manual_test_logger.php')) {
                const correlationId = this._qa_correlation_id || generateCorrelationId();
                const startTime = Date.now();
                const method = (this._qa_method || 'GET').toUpperCase();
                const cleanUrl = sanitizeUrl(this._qa_url);

                try {
                    this.setRequestHeader('X-Test-Session-Id', getSessionId());
                    this.setRequestHeader('X-Correlation-Id', correlationId);
                } catch (e) {}

                emitLog('API_CALL', {
                    api: { endpoint: cleanUrl, method: method },
                    correlation_id: correlationId
                });

                this.addEventListener('loadend', function () {
                    const duration = Date.now() - startTime;
                    const status = this.status;
                    emitLog('API_RESPONSE', {
                        api: { endpoint: cleanUrl, method: method },
                        status_code: status,
                        duration_ms: duration,
                        correlation_id: correlationId,
                        error: status >= 400 ? { status: status, statusText: this.statusText } : null
                    });
                });
            }

            return origSend.apply(this, arguments);
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. JAVASCRIPT ERRORS
    // ─────────────────────────────────────────────────────────────────────────
    window.addEventListener('error', function (e) {
        if (!isLoggerActive()) return;

        emitLog('JS_ERROR', {
            error: {
                category: 'JavaScriptError',
                message: cleanString(e.message, 250),
                source: e.filename ? e.filename.split('/').pop() : 'inline',
                line: e.lineno,
                col: e.colno
            }
        });
    });

    window.addEventListener('unhandledrejection', function (e) {
        if (!isLoggerActive()) return;

        const reason = e.reason;
        const msg = (reason && reason.message) ? reason.message : String(reason);
        emitLog('JS_PROMISE_REJECTION', {
            error: {
                category: 'UnhandledRejection',
                message: cleanString(msg, 250)
            }
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // 7. PUBLIC CONTROLLER API (window.CasaTestLogger)
    // ─────────────────────────────────────────────────────────────────────────
    window.CasaTestLogger = {
        start: function (sessionId) {
            const sid = (sessionId || '').replace(/[^A-Za-z0-9_\-]/g, '') || ('CASA-TEST-' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '-' + Math.floor(100 + Math.random() * 900));
            localStorage.setItem(STORAGE_KEY_SESSION_ID, sid);
            localStorage.setItem(STORAGE_KEY_ACTIVE, 'true');
            sessionStorage.setItem(STORAGE_KEY_SEQ, '0');

            // Send start marker to backend
            const endpoint = getEndpointUrl();
            origFetch ? origFetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'start', session_id: sid, url: window.location.pathname })
            }).catch(() => {}) : null;

            if (window.dispatchEvent) {
                window.dispatchEvent(new CustomEvent('casa:test-session-started', { detail: { session_id: sid } }));
            }
            return sid;
        },

        stop: function () {
            const sid = getSessionId();
            localStorage.setItem(STORAGE_KEY_ACTIVE, 'false');

            const endpoint = getEndpointUrl();
            origFetch ? origFetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'stop', session_id: sid, url: window.location.pathname, seq: nextSequence() })
            }).catch(() => {}) : null;

            if (window.dispatchEvent) {
                window.dispatchEvent(new CustomEvent('casa:test-session-stopped', { detail: { session_id: sid } }));
            }
        },

        isActive: function () {
            return isLoggerActive();
        },

        getSessionId: function () {
            return getSessionId();
        },

        logCustomEvent: function (actionType, details) {
            emitLog(actionType, details);
        },

        getLogsUrl: function (sessionId) {
            const sid = sessionId || getSessionId();
            return `${getEndpointUrl()}?action=view&session_id=${encodeURIComponent(sid)}`;
        },

        getExportUrl: function (sessionId) {
            const sid = sessionId || getSessionId();
            return `${getEndpointUrl()}?action=export&session_id=${encodeURIComponent(sid)}`;
        }
    };
})(window, document);

