// Main script launcher for application modules.
(() => {
    'use strict';

    const getScriptPath = () => {
        const scripts = document.getElementsByTagName('script');
        for (let i = 0; i < scripts.length; i++) {
            const src = scripts[i]?.src || '';
            if (src.includes('app/script/index.js')) {
                return src.substring(0, src.indexOf('app/script/index.js'));
            }
            if (src.includes('app/script/script.js')) {
                return src.substring(0, src.indexOf('app/script/script.js'));
            }
            if (src.includes('app/script/')) {
                return src.substring(0, src.indexOf('app/script/'));
            }
        }
        return '';
    };

    const getAppVersion = () => {
        const meta = document.querySelector('meta[name="app-version"]');
        if (meta && meta.content) return meta.content;
        if (window.Casa && window.Casa.version) return window.Casa.version;
        return '1.0.9';
    };

    const scriptPath = getScriptPath();
    if (typeof window.BASE_URL === 'undefined' || !window.BASE_URL) {
        window.BASE_URL = scriptPath;
    }
    const path = (window.location.pathname || '').toLowerCase();

    const modules = [
        'app/script/common.js',
        'app/script/activity-tracker.js'
    ];

    const isHost = path.includes('/host') || !!document.querySelector('[data-page^="host"], .host-container, #host_content');
    const isPlayer = path.includes('/player') || path.includes('/hub') || !!document.querySelector('[data-page^="player"], .player-container, #player_content');
    const isTournament = path.includes('/tournament') || !!document.querySelector('.tournament-container, [data-page^="tournament"]');
    const isAuth = path.includes('/login') || path.includes('/register') || !!document.querySelector('#loginForm, #registerForm, form[action*="login"]');

    if (isHost) {
        modules.push('app/script/host.js');
    }
    if (isPlayer) {
        modules.push('app/script/player.js');
    }
    if (isTournament) {
        modules.push('app/script/tournament.js');
    }
    if (isAuth) {
        modules.push('app/script/auth.js');
    }

    const hasLedger = isHost || isPlayer || !!document.querySelector('.monthly-ledger-card, .patmentTb, .playPaymentModal, .PayAmountModal, .playarPayment_game');
    if (hasLedger) {
        modules.push('app/script/ledger.js');
    }

    // Fallback: If on an unclassified route, load appropriate interactive modules
    if (!isHost && !isPlayer && !isTournament && !isAuth) {
        modules.push('app/script/auth.js', 'app/script/player.js');
    }

    const version = `v=${encodeURIComponent(getAppVersion())}`;

    modules.forEach((moduleUrl) => {
        const script = document.createElement('script');
        script.src = `${scriptPath}${moduleUrl}?${version}`;
        script.async = false;
        document.head.appendChild(script);
    });
})();
