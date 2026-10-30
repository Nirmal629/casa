/**
 * Casa Host - Copy Session Details Module
 * Handles copying formatted event schedule details and duplicating events.
 */
(function (window, $) {
    'use strict';

    const CopyDetailsModule = {
        init: function () {
            this.bindEvents();
        },

        copyTextToClipboard: function (text, onSuccess, onError) {
            if (!text) {
                if (onError) onError('No details available to copy.');
                return;
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text)
                    .then(function () {
                        if (onSuccess) onSuccess();
                    })
                    .catch(function (err) {
                        CopyDetailsModule.fallbackCopy(text, onSuccess, onError);
                    });
            } else {
                CopyDetailsModule.fallbackCopy(text, onSuccess, onError);
            }
        },

        fallbackCopy: function (text, onSuccess, onError) {
            try {
                const temp = document.createElement('textarea');
                temp.value = text;
                temp.style.position = 'fixed';
                temp.style.left = '-9999px';
                temp.style.top = '-9999px';
                document.body.appendChild(temp);
                temp.focus();
                temp.select();
                const successful = document.execCommand('copy');
                document.body.removeChild(temp);
                if (successful && onSuccess) onSuccess();
                else if (!successful && onError) onError('Failed to copy to clipboard.');
            } catch (err) {
                if (onError) onError(err);
            }
        },

        showFeedback: function (buttonEl, message) {
            if (typeof window.showToast === 'function') {
                window.showToast('success', message || 'Event details copied to clipboard!');
            } else if (typeof toastr !== 'undefined' && toastr.success) {
                toastr.success(message || 'Event details copied to clipboard!');
            }

            if (buttonEl) {
                const $btn = $(buttonEl);
                const originalHtml = $btn.html();
                $btn.html('<i class="fa-solid fa-check text-success"></i>');
                setTimeout(function () {
                    $btn.html(originalHtml);
                }, 1500);
            }
        },

        onCopyCardClick: function (e) {
            e.preventDefault();
            if (e.stopImmediatePropagation) e.stopImmediatePropagation();
            const btn = e.currentTarget || this;
            const eventId = btn.getAttribute('data-id') || $(btn).data('id');
            const dataInput = document.getElementById('event_data_' + eventId);

            if (!dataInput || !dataInput.value) {
                if (typeof window.showToast === 'function') {
                    window.showToast('warning', 'No event details found to copy.');
                }
                return;
            }

            CopyDetailsModule.copyTextToClipboard(
                dataInput.value,
                function () {
                    CopyDetailsModule.showFeedback(btn, 'Event details copied to clipboard!');
                },
                function (err) {
                    console.error('Clipboard copy error:', err);
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', 'Unable to copy event details.');
                    }
                }
            );
        },

        bindEvents: function () {
            // Delegated click handler for card "Copy Details" button
            $(document).off('click.copySessionDetails', '.copy_event')
                       .on('click.copySessionDetails', '.copy_event', this.onCopyCardClick);
        }
    };

    window.CasaHostCopyDetails = CopyDetailsModule;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            CopyDetailsModule.init();
        });
    } else {
        CopyDetailsModule.init();
    }
})(window, window.jQuery || window.$);
