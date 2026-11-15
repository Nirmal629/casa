/** Legacy script.js launcher forwarding to app/script/index.js */
(() => {
    'use strict';

    const getScriptPath = () => {
        const scripts = document.getElementsByTagName('script');
        for (let i = 0; i < scripts.length; i++) {
            const src = scripts[i]?.src || '';
            if (src.includes('app/script/script.js')) {
                return src.substring(0, src.indexOf('app/script/script.js'));
            }
        }
        return '';
    };

    const scriptPath = getScriptPath();
    const script = document.createElement('script');
    script.src = `${scriptPath}app/script/index.js?v=1`;
    script.async = false;
    document.head.appendChild(script);
})();
