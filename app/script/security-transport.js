/* Shared same-origin CSRF transport for legacy forms, XHR and fetch. */
(function () {
    'use strict';
    function token() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }
    function local(url) {
        try { return new URL(url || location.href, location.href).origin === location.origin; }
        catch (_) { return false; }
    }
    function mutates(method) { return !/^(GET|HEAD|OPTIONS|TRACE)$/i.test(method || 'GET'); }
    function protectForm(form) {
        if (!form || form.tagName !== 'FORM' || !mutates(form.method) || !local(form.action)) return;
        var input = form.querySelector('input[name="csrf_token"]');
        if (!input) {
            input = document.createElement('input'); input.type = 'hidden'; input.name = 'csrf_token'; form.appendChild(input);
        }
        input.value = token();
    }
    document.addEventListener('submit', function (event) { protectForm(event.target); }, true);
    document.addEventListener('DOMContentLoaded', function () { document.querySelectorAll('form').forEach(protectForm); });
    if (window.fetch) {
        var originalFetch = window.fetch;
        window.fetch = function (input, init) {
            var options = Object.assign({}, init || {});
            var request = typeof Request !== 'undefined' && input instanceof Request ? input : null;
            if (mutates(options.method || (request && request.method)) && local(request ? request.url : input) && token()) {
                var headers = new Headers(options.headers || (request && request.headers) || {});
                headers.set('X-CSRF-Token', token()); options.headers = headers;
            }
            return originalFetch.call(this, input, options);
        };
    }
    var open = XMLHttpRequest.prototype.open, send = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.open = function (method, url) {
        this._casaCsrf = mutates(method) && local(url); return open.apply(this, arguments);
    };
    XMLHttpRequest.prototype.send = function () {
        if (this._casaCsrf && token()) this.setRequestHeader('X-CSRF-Token', token());
        return send.apply(this, arguments);
    };
}());
