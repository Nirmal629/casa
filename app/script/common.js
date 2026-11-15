// Casa Shared Utility & Helper Layer (M7.1)
window.Casa = window.Casa || {};

/** Get current CSRF token */
window.Casa.csrfToken = function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;
    var input = document.querySelector('input[name="csrf_token"]');
    return input ? input.value : '';
};
window.csrfToken = window.Casa.csrfToken;

/** HTML Escaper to prevent XSS in template strings */
window.Casa.escapeHtml = function (str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};
window.escapeHtml = window.Casa.escapeHtml;

/** Currency Formatter */
window.Casa.formatCurrency = function (amount, symbol) {
    symbol = symbol || '$';
    var num = parseFloat(amount);
    if (isNaN(num)) return symbol + '0.00';
    return symbol + num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
};
window.formatCurrency = window.Casa.formatCurrency;

/** Date Formatter */
window.Casa.formatDate = function (dateStr, options) {
    if (!dateStr) return '';
    var date = new Date(dateStr);
    if (isNaN(date.getTime())) return dateStr;
    var opts = options || { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString(undefined, opts);
};
window.formatDate = window.Casa.formatDate;

/** Toast Notification Helper - Supports both (type, message, title) and (message, type) signatures */
window.Casa.showToast = function (arg1, arg2, title) {
    var knownTypes = ['success', 'error', 'warning', 'info'];
    var type = 'success';
    var message = '';

    if (knownTypes.indexOf(String(arg1).toLowerCase()) !== -1) {
        // Called as showToast(type, message, title)
        type = String(arg1).toLowerCase();
        message = String(arg2 || '');
    } else {
        // Called as showToast(message, type)
        message = String(arg1 || '');
        type = (arg2 && knownTypes.indexOf(String(arg2).toLowerCase()) !== -1) ? String(arg2).toLowerCase() : 'success';
    }

    if (typeof window.toastr !== 'undefined') {
        if (typeof window.toastr.clear === 'function') {
            window.toastr.clear();
        }
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: "2500",
            extendedTimeOut: "1000",
            showDuration: "200",
            hideDuration: "200",
            preventDuplicates: true,
            maxOpened: 1
        };
        if (typeof toastr[type] === 'function') {
            toastr[type](message, title);
        } else {
            toastr.info(message, title);
        }
    } else if (typeof window.showAlert === 'function') {
        window.showAlert(message, type);
    } else {
        // Fallback modern floating toaster without native browser alert
        var $toast = $('#casaFallbackToast');
        if (!$toast.length) {
            $toast = $('<div id="casaFallbackToast" style="position:fixed; top:20px; right:20px; z-index:999999; max-width:360px; padding:12px 18px; border-radius:10px; font-size:0.9rem; font-weight:500; color:#fff; box-shadow:0 10px 25px -5px rgba(0,0,0,0.3); display:flex; align-items:center; gap:10px; transition:all 0.3s ease;"></div>').appendTo('body');
        }
        var bg = type === 'error' ? '#ef4444' : (type === 'warning' ? '#f59e0b' : (type === 'info' ? '#3b82f6' : '#10b981'));
        $toast.css({ background: bg, opacity: 1 }).html(message).stop(true, true).fadeIn(200);
        setTimeout(function () {
            $toast.fadeOut(300);
        }, 3000);
    }
};
window.showToast = window.Casa.showToast;

/** Modern Custom Confirmation Modal */
window.Casa.confirmModal = function (opts) {
    if (typeof opts === 'string') {
        opts = { text: opts };
    }
    opts = opts || {};
    var title = opts.title || 'Are you sure?';
    var text = opts.text || opts.message || 'Are you sure you want to proceed?';
    var confirmText = opts.confirmButtonText || opts.confirmText || 'Confirm';
    var cancelText = opts.cancelButtonText || opts.cancelText || 'Cancel';
    var icon = opts.icon || 'question';
    var confirmBtnClass = opts.confirmBtnClass || opts.confirmButtonClass || '';
    var onConfirm = opts.onConfirm || function () {};
    var onCancel = opts.onCancel || function () {};

    return new Promise(function (resolve) {
        $('#casaConfirmModalShell').remove();

        var iconHtml = '';
        var defaultBtnStyle = 'background:linear-gradient(135deg, #2563eb, #1d4ed8); color:#ffffff; box-shadow:0 3px 8px rgba(37,99,235,0.28);';

        if (icon === 'money' || icon === 'success' || confirmBtnClass.indexOf('success') !== -1) {
            iconHtml = '<div class="casa-confirm-icon-badge casa-confirm-icon-success" style="width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); color:#059669; border:1px solid rgba(16,185,129,0.25); box-shadow:0 3px 8px -1px rgba(16,185,129,0.18); display:flex; align-items:center; justify-content:center; font-size:16px; margin:0 auto 8px;"><i class="fa-solid fa-dollar-sign"></i></div>';
            defaultBtnStyle = 'background:linear-gradient(135deg, #059669, #047857); color:#ffffff; box-shadow:0 3px 8px rgba(5,150,105,0.28);';
        } else if (icon === 'danger' || icon === 'error' || confirmBtnClass.indexOf('danger') !== -1) {
            iconHtml = '<div class="casa-confirm-icon-badge casa-confirm-icon-danger" style="width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); color:#e11d48; border:1px solid rgba(244,63,94,0.25); box-shadow:0 3px 8px -1px rgba(225,29,72,0.18); display:flex; align-items:center; justify-content:center; font-size:16px; margin:0 auto 8px;"><i class="fa-solid fa-rotate-left"></i></div>';
            defaultBtnStyle = 'background:linear-gradient(135deg, #e11d48, #be123c); color:#ffffff; box-shadow:0 3px 8px rgba(225,29,72,0.28);';
        } else if (icon === 'warning' || confirmBtnClass.indexOf('warning') !== -1) {
            iconHtml = '<div class="casa-confirm-icon-badge casa-confirm-icon-warning" style="width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); color:#d97706; border:1px solid rgba(245,158,11,0.25); box-shadow:0 3px 8px -1px rgba(245,158,11,0.18); display:flex; align-items:center; justify-content:center; font-size:16px; margin:0 auto 8px;"><i class="fa-solid fa-triangle-exclamation"></i></div>';
            defaultBtnStyle = 'background:linear-gradient(135deg, #d97706, #b45309); color:#ffffff; box-shadow:0 3px 8px rgba(217,119,6,0.28);';
        } else {
            iconHtml = '<div class="casa-confirm-icon-badge casa-confirm-icon-info" style="width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color:#2563eb; border:1px solid rgba(37,99,235,0.25); box-shadow:0 3px 8px -1px rgba(37,99,235,0.18); display:flex; align-items:center; justify-content:center; font-size:16px; margin:0 auto 8px;"><i class="fa-solid fa-circle-question"></i></div>';
            defaultBtnStyle = 'background:linear-gradient(135deg, #2563eb, #1d4ed8); color:#ffffff; box-shadow:0 3px 8px rgba(37,99,235,0.28);';
        }

        var modalHtml = `
            <div id="casaConfirmModalShell" class="casa-confirm-shell" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15,23,42,0.55); backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px); z-index:999999; display:flex; align-items:center; justify-content:center; padding:12px;">
              <div class="casa-confirm-card" style="width:100%; max-width:310px; background:#ffffff; border-radius:14px; box-shadow:0 16px 36px -10px rgba(15,23,42,0.22), 0 0 0 1px rgba(226,232,240,0.9); overflow:hidden; position:relative; padding:16px 16px 12px; animation:casaPopIn 0.18s cubic-bezier(0.16, 1, 0.3, 1); text-align:center;">
                <button type="button" id="casaConfirmCloseCross" class="casa-confirm-close-btn" style="position:absolute; top:6px; right:6px; width:22px; height:22px; border:none; background:transparent; color:#94a3b8; font-size:16px; line-height:1; border-radius:6px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center;" title="Close">&times;</button>
                ${iconHtml}
                <h4 class="casa-confirm-title" style="font-size:0.95rem; font-weight:700; color:#0f172a; margin:0 0 4px 0; letter-spacing:-0.01em; line-height:1.25;">${title}</h4>
                <div class="casa-confirm-text" style="font-size:0.78rem; color:#64748b; line-height:1.4; margin:0 0 12px 0; padding:0 4px;">${text}</div>
                <div class="casa-confirm-actions" style="display:flex; gap:8px; align-items:center; justify-content:stretch;">
                  <button type="button" id="casaConfirmCancelBtn" class="casa-confirm-btn casa-confirm-cancel-btn" style="flex:1; padding:6px 12px; border-radius:7px; font-weight:600; font-size:0.78rem; color:#475569; background:#f1f5f9; border:1px solid #e2e8f0; cursor:pointer; outline:none;">${cancelText}</button>
                  <button type="button" id="casaConfirmOkBtn" class="casa-confirm-btn casa-confirm-ok-btn ${confirmBtnClass}" style="flex:1; padding:6px 12px; border-radius:7px; font-weight:600; font-size:0.78rem; border:none; cursor:pointer; outline:none; ${defaultBtnStyle}">${confirmText}</button>
                </div>
              </div>
            </div>
        `;

        $('body').append(modalHtml);

        var cleanup = function () {
            $('#casaConfirmModalShell').fadeOut(100, function () {
                $(this).remove();
            });
            $(document).off('keydown.casaconfirm');
        };

        $('#casaConfirmOkBtn').focus();

        $('#casaConfirmOkBtn').on('click', function () {
            cleanup();
            if (typeof onConfirm === 'function') onConfirm();
            resolve(true);
        });

        $('#casaConfirmCancelBtn, #casaConfirmCloseCross, #casaConfirmModalShell').on('click', function (e) {
            if (e.target === this) {
                cleanup();
                if (typeof onCancel === 'function') onCancel();
                resolve(false);
            }
        });

        $(document).on('keydown.casaconfirm', function (e) {
            if (e.key === 'Escape') {
                cleanup();
                if (typeof onCancel === 'function') onCancel();
                resolve(false);
            } else if (e.key === 'Enter') {
                cleanup();
                if (typeof onConfirm === 'function') onConfirm();
                resolve(true);
            }
        });
    });
};
window.CasaConfirm = window.Casa.confirmModal;

/** Confirm Action Dialog - Unified to Modern Squeezed Modal */
window.Casa.confirmAction = function (options) {
    options = options || {};
    var onConfirm = options.onConfirm || options.callback || function () {};
    var onCancel = options.onCancel || function () {};
    return window.Casa.confirmModal({
        title: options.title || 'Are you sure?',
        text: options.text || options.message || 'This action cannot be undone.',
        confirmText: options.confirmButtonText || options.confirmText || 'Yes, proceed',
        cancelText: options.cancelButtonText || options.cancelText || 'Cancel',
        icon: options.icon || 'warning',
        confirmBtnClass: options.confirmButtonClass || options.confirmBtnClass || '',
        onConfirm: onConfirm,
        onCancel: onCancel
    });
};
window.confirmAction = window.Casa.confirmAction;

/** Unified AJAX Wrapper */
window.Casa.ajax = function (url, options) {
    options = options || {};
    var method = (options.method || 'GET').toUpperCase();
    var headers = options.headers || {};

    headers['X-Requested-With'] = 'XMLHttpRequest';
    var token = window.Casa.csrfToken();
    if (token) {
        headers['X-CSRF-TOKEN'] = token;
    }

    var fetchOpts = {
        method: method,
        headers: headers
    };

    if (options.body) {
        if (options.body instanceof FormData) {
            fetchOpts.body = options.body;
        } else if (typeof options.body === 'object') {
            headers['Content-Type'] = 'application/json';
            fetchOpts.body = JSON.stringify(options.body);
        } else {
            fetchOpts.body = options.body;
        }
    }

    return fetch(url, fetchOpts)
        .then(function (res) {
            if (!res.ok) {
                throw new Error('HTTP error ' + res.status);
            }
            return res.json();
        })
        .catch(function (err) {
            console.error('Casa.ajax Error:', err);
            if (!options.silent) {
                window.Casa.showToast(options.errorMessage || 'An error occurred. Please try again.', 'error');
            }
            throw err;
        });
};
window.casaAjax = window.Casa.ajax;

// Safe modal HTML rendering helper attached to window.Casa and window
if (!window.Casa) window.Casa = {};
window.Casa.setSafeModalContent = function (container, html) {
    if (!container) return;
    if (typeof container === 'string') {
        container = document.querySelector(container);
    }
    if (!container) return;

    if (!html || html.trim() === '') {
        container.innerHTML = '<p class="text-muted text-center my-3">There is no data available.</p>';
        return;
    }
    var cleaned = html
        .replace(/<!DOCTYPE[^>]*>/gi, '')
        .replace(/<\/?(?:html|head|meta|title|body)\b[^>]*>/gi, '');

    cleaned = cleaned.replace(/<style\b[^>]*>([\s\S]*?)<\/style>/gi, function (match, css) {
        var safeCss = css
            .replace(/\b(body|html)\s*\{[^}]*\}/gi, '')
            .replace(/^\s*\*\s*\{[^}]*\}/gmi, '');
        return '<style>' + safeCss + '</style>';
    });

    container.innerHTML = cleaned;
    if (window.CasaDropdown && typeof window.CasaDropdown.initAll === 'function') {
        window.CasaDropdown.initAll(container);
    }
};
window.setSafeModalContent = window.Casa.setSafeModalContent;

// Automatically configure jQuery $.ajax to send CSRF token for mutating requests
if (typeof jQuery !== 'undefined' && jQuery.ajaxSetup) {
    jQuery.ajaxSetup({
        beforeSend: function (xhr, settings) {
            if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type || 'GET') && !this.crossDomain) {
                var token = window.Casa.csrfToken();
                if (token) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', token);
                }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    // Prevent Bootstrap modal scrollbar layout shift & padding-right jump
    document.addEventListener('show.bs.modal', function () {
        document.body.style.overflow = 'hidden';
    });
    document.addEventListener('hidden.bs.modal', function () {
        if (!document.querySelectorAll('.modal.show').length) {
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }
    });
});

// Navbar, Tab Initialization, Sidebar & Submenu
jQuery(document).ready(function () {

    // Parse URL parameter to activate specific tab on load
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    if (activeTab) {
        let tabButton = jQuery('#' + activeTab);
        if (tabButton.length === 0) {
            tabButton = jQuery('button[data-bs-target="#' + activeTab + '"]');
        }
        if (tabButton.length > 0) {
            setTimeout(function () {
                tabButton.trigger('click');
            }, 100);
        }
    }

    // Main Menu Sidebar Toggle
    jQuery("#open_Sidebar").click(function () {
        jQuery(".menubar_box").toggleClass("open");
        jQuery(".responsivemenubar_btn").toggleClass("on");
    });

    // Sub Menu Accordion Toggle
    jQuery(".navber_wrap li .icon").click(function () {
        jQuery(this)
            .toggleClass("active")
            .next(".navber_wrap li ul")
            .slideToggle()
            .parent()
            .siblings()
            .find(".navber_wrap li ul")
            .slideUp()
            .prev()
            .removeClass("active");
    });

    // Close Sidebar When Any Menu Link Is Clicked
    jQuery(".menubar_box .navber_wrap li a").click(function () {
        jQuery(".menubar_box").removeClass("open");
        jQuery(".responsivemenubar_btn").removeClass("on");
    });
});

// Sticky Header & Scroll To Top Button
document.addEventListener("DOMContentLoaded", function () {
    var header = document.getElementById("main_Header");
    var sticky = header ? header.offsetTop : 0;

    var scrollTrigger = 150;
    var backToTop = document.querySelector('.return-to-top');

    function onScroll() {
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        // Sticky Header
        if (header) {
            if (scrollTop > sticky) {
                header.classList.add("sticky");
            } else {
                header.classList.remove("sticky");
            }
        }

        // Back To Top Button
        if (backToTop) {
            if (scrollTop > scrollTrigger) {
                backToTop.classList.add("show");
            } else {
                backToTop.classList.remove("show");
            }
        }
    }

    // Scroll event listener
    window.addEventListener("scroll", onScroll);
    onScroll();

    // Back to top click handler
    if (backToTop) {
        backToTop.addEventListener("click", function (e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
    }
});

// Hero Banner Slider
jQuery('.herobanner_slider').slick({
    slidesToShow: 1,
    slidesToScroll: 1,
    fade: true,
    infinite: true,
    autoplay: true,
    autoplaySpeed: 3000,
    arrows: false,
    dots: true,
    pauseOnHover: true
});

jQuery('.herobanner_slider').on('mouseenter', function () {
    jQuery(this).slick('slickPause');
});

jQuery('.herobanner_slider').on('mouseleave', function () {
    jQuery(this).slick('slickPlay');
});

// Testimonials / Clients Slider
jQuery('.testimonials_slider').slick({
    infinite: true,
    speed: 300,
    slidesToShow: 3,
    slidesToScroll: 1,
    autoplay: true,
    autoplaySpeed: 2500,
    dots: false,
    arrows: true,
    responsive: [
        {
            breakpoint: 1400,
            settings: {
                slidesToShow: 3,
                slidesToScroll: 1,
                infinite: true
            }
        },
        {
            breakpoint: 1024,
            settings: {
                slidesToShow: 2,
                slidesToScroll: 1,
                infinite: true
            }
        },
        {
            breakpoint: 768,
            settings: {
                slidesToShow: 2,
                slidesToScroll: 1,
                autoplay: true
            }
        },
        {
            breakpoint: 600,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: true
            }
        },
        {
            breakpoint: 480,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: true
            }
        }
    ],
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'
});

// Casa Photos Slider
$('.casaphotos_slider').slick({
    speed: 300,
    infinite: true,
    slidesToShow: 6,
    slidesToScroll: 1,
    dots: false,
    autoplay: true,
    autoplaySpeed: 3000,
    arrows: true,
    responsive: [
        {
            breakpoint: 1400,
            settings: {
                slidesToShow: 5,
                slidesToScroll: 1
            }
        },
        {
            breakpoint: 1024,
            settings: {
                slidesToShow: 4,
                slidesToScroll: 1
            }
        },
        {
            breakpoint: 768,
            settings: {
                slidesToShow: 3,
                slidesToScroll: 1,
                autoplay: true
            }
        },
        {
            breakpoint: 576,
            settings: {
                slidesToShow: 2,
                slidesToScroll: 1,
                autoplay: true
            }
        }
    ],
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'
});

// Casa Video Slider
$('.casavideo_slider').slick({
    speed: 300,
    infinite: true,
    slidesToShow: 6,
    slidesToScroll: 1,
    dots: false,
    autoplay: true,
    autoplaySpeed: 3000,
    arrows: true,
    responsive: [
        {
            breakpoint: 1400,
            settings: {
                slidesToShow: 5,
                slidesToScroll: 1
            }
        },
        {
            breakpoint: 1024,
            settings: {
                slidesToShow: 4,
                slidesToScroll: 1
            }
        },
        {
            breakpoint: 768,
            settings: {
                slidesToShow: 3,
                slidesToScroll: 1,
                autoplay: true
            }
        },
        {
            breakpoint: 576,
            settings: {
                slidesToShow: 2,
                slidesToScroll: 1,
                autoplay: true
            }
        }
    ],
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'
});

// Tournament Card Slider
$('.tournamentcard_slider').slick({
    speed: 300,
    infinite: true,
    slidesToShow: 4,
    slidesToScroll: 1,
    dots: false,
    autoplay: true,
    autoplaySpeed: 3000,
    arrows: true,
    responsive: [
        {
            breakpoint: 1024,
            settings: {
                slidesToShow: 3,
                slidesToScroll: 1
            }
        },
        {
            breakpoint: 841,
            settings: {
                slidesToShow: 2,
                slidesToScroll: 1,
                autoplay: false
            }
        },
        {
            breakpoint: 576,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: false
            }
        },
        {
            breakpoint: 300,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: false
            }
        }
    ],
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'
});

// Additional Slick Sliders
$('.tourSlick').slick({
    infinite: false,
    slidesToShow: 1,
    slidesToScroll: 1,
    dots: false,
    arrows: true,
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'
});

$('.badgeSlick').slick({
    infinite: false,
    slidesToShow: 1,
    slidesToScroll: 1,
    dots: false,
    arrows: true,
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'
});

if ($('.reviewSlick').length) {
    try {
        $('.reviewSlick').slick({
            infinite: false,
            slidesToShow: 1,
            slidesToScroll: 1,
            dots: false,
            arrows: true,
            accessibility: false,
            prevArrow: '<button class="slide-arrow prev-arrow"></button>',
            nextArrow: '<button class="slide-arrow next-arrow"></button>'
        });
    } catch (e) {
        console.warn('reviewSlick init notice:', e);
    }
}

// Book Venues Slider
$('.bookVenues_slider').slick({
    speed: 300,
    infinite: true,
    slidesToShow: 4,
    slidesToScroll: 1,
    dots: false,
    autoplay: true,
    autoplaySpeed: 2000,
    arrows: true,
    responsive: [
        {
            breakpoint: 1024,
            settings: {
                slidesToShow: 3,
                slidesToScroll: 1
            }
        },
        {
            breakpoint: 600,
            settings: {
                slidesToShow: 2,
                slidesToScroll: 1
            }
        },
        {
            breakpoint: 480,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1
            }
        }
    ],
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'
});

// Discover Games Slider
$('.discoverGames_slider').slick({
    speed: 300,
    infinite: true,
    slidesToShow: 4,
    slidesToScroll: 1,
    dots: false,
    autoplay: true,
    autoplaySpeed: 2000,
    arrows: true,
    responsive: [
        {
            breakpoint: 1024,
            settings: {
                slidesToShow: 3,
                slidesToScroll: 1
            }
        },
        {
            breakpoint: 600,
            settings: {
                slidesToShow: 2,
                slidesToScroll: 1
            }
        },
        {
            breakpoint: 480,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1
            }
        }
    ],
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'
});

// Advertisement Slider
$('.advertisement_slider').slick({
    speed: 300,
    infinite: true,
    slidesToShow: 4,
    slidesToScroll: 1,
    dots: false,
    autoplay: true,
    autoplaySpeed: 2000,
    arrows: true,
    responsive: [
        {
            breakpoint: 1400,
            settings: {
                slidesToShow: 3,
                slidesToScroll: 1
            }
        },
        {
            breakpoint: 992,
            settings: {
                slidesToShow: 2,
                slidesToScroll: 1
            }
        },
        {
            breakpoint: 576,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1
            }
        },
        {
            breakpoint: 480,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1
            }
        }
    ],
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'
});

// Inner Banner Slider
$('.innerbanner_slider').slick({
    speed: 300,
    infinite: true,
    slidesToShow: 1,
    slidesToScroll: 1,
    dots: false,
    autoplay: true,
    autoplaySpeed: 2500,
    arrows: false,
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'
});

// Store Product Slider
$('.sttoreproduct_slider').slick({
    speed: 300,
    infinite: true,
    slidesToShow: 4,
    slidesToScroll: 1,
    dots: false,
    autoplay: true,
    autoplaySpeed: 2000,
    arrows: true,
    responsive: [
        {
            breakpoint: 1400,
            settings: {
                slidesToShow: 3,
                slidesToScroll: 1
            }
        },
        {
            breakpoint: 992,
            settings: {
                slidesToShow: 2,
                slidesToScroll: 1
            }
        },
        {
            breakpoint: 576,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1
            }
        },
        {
            breakpoint: 480,
            settings: {
                slidesToShow: 1,
                slidesToScroll: 1
            }
        }
    ],
    prevArrow: '<button class="slide-arrow prev-arrow"></button>',
    nextArrow: '<button class="slide-arrow next-arrow"></button>'
});

// Popular Sports Tab Filter
$(document).ready(function () {
    $('.clickme a').click(function () {
        $('.clickme a').removeClass('activelink');
        $(this).addClass('activelink');
        var tagid = $(this).data('tag');
        $('.list').removeClass('active').addClass('hide');
        $('#' + tagid).addClass('active').removeClass('hide');
    });
});

// Playground Section Counter Animation
document.addEventListener("DOMContentLoaded", function () {
    const counters = document.querySelectorAll(".count");
    if (!counters.length) return;
    let started = false;

    function startCounting() {
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute("data-target") || "0", 10);
            if (!target) {
                counter.textContent = "0";
                return;
            }
            const speed = 30;
            let count = 0;
            const step = Math.max(1, Math.ceil(target / 40));

            const updateCount = () => {
                count += step;
                if (count < target) {
                    counter.textContent = count.toLocaleString();
                    setTimeout(updateCount, speed);
                } else {
                    counter.textContent = target.toLocaleString();
                }
            };
            updateCount();
        });
    }

    function checkVisibility() {
        const section = document.querySelector(".playground_sec");
        if (!section) return;
        const rect = section.getBoundingClientRect();
        if (!started && rect.top < window.innerHeight && rect.bottom >= 0) {
            started = true;
            startCounting();
            window.removeEventListener("scroll", checkVisibility);
        }
    }

    window.addEventListener("scroll", checkVisibility);
    checkVisibility();
});

// Event Card Layout Max-Width Adjuster
$(document).ready(function () {
    if ($('.event-card').length == 1) {
        $('.event-card').css('max-width', '100%');
    }
    if ($('.event-card').length == 2) {
        $('.event-card').css('max-width', '49%');
    }
});

// Bootstrap Tooltips Initializer
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Bootstrap Popovers Initializer
function initPopovers() {
    const clickHandlers = initPopovers.clickHandlers || (initPopovers.clickHandlers = new WeakMap());
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');

    popoverTriggerList.forEach(triggerEl => {
        if (bootstrap.Popover.getInstance(triggerEl)) {
            bootstrap.Popover.getInstance(triggerEl).dispose();
        }
        new bootstrap.Popover(triggerEl);

        const previousHandler = clickHandlers.get(triggerEl);
        if (previousHandler) triggerEl.removeEventListener('click', previousHandler);
        const onClick = function () {
            popoverTriggerList.forEach(otherEl => {
                if (otherEl !== triggerEl) bootstrap.Popover.getInstance(otherEl)?.hide();
            });
        };
        clickHandlers.set(triggerEl, onClick);
        triggerEl.addEventListener('click', onClick);
    });
}

// Utility function to set input value or inner text
function setText(id, value) {
    const el = document.getElementById(id);
    if (!el) return;

    if (el.tagName === 'INPUT' || el.tagName === 'SELECT') {
        el.value = value;
    } else {
        el.innerText = value;
    }
}

// Close Custom Modals on Outside Click
$(document).ready(function () {
    $(document).on('click', '.customModal_wrap', function (e) {
        if ($(e.target).hasClass('customModal_wrap')) {
            $(this).removeClass('open');
        }
    });
});

// Auto-refresh layout & slick slider position after page/module load
(function() {
    function refreshLayoutAndSlick() {
        if (window.jQuery && jQuery.fn.slick) {
            jQuery('.slick-slider, .herobanner_slider, .tournamentcard_slider, .sttoreproduct_slider, .casaphotos_slider, .casavideo_slider, .discoverGames_slider').each(function() {
                if (jQuery(this).hasClass('slick-initialized')) {
                    jQuery(this).slick('setPosition');
                }
            });
        }
        if (typeof AOS !== 'undefined') {
            AOS.refresh();
        }
    }

    jQuery(window).on('load resize orientationchange', refreshLayoutAndSlick);

    setTimeout(refreshLayoutAndSlick, 200);
    setTimeout(refreshLayoutAndSlick, 600);
    setTimeout(refreshLayoutAndSlick, 1200);
})();

// Centralized DOM and state updater for scheduler game cards across host and player views
function updateSchedulerCardStats($card, res, eventId) {
    if (!$card || !$card.length || !res) return;

    const totalJoined = (res.total_join !== undefined)
        ? parseInt(res.total_join, 10)
        : (parseInt($card.find('.session-stat-joined .stat-value').text(), 10) || 0);
    const totalConfirmed = (res.total_confirmed !== undefined)
        ? parseInt(res.total_confirmed, 10)
        : (parseInt($card.find('.session-stat-confirm .stat-value').text(), 10) || 0);

    $card.find('.session-stat-joined .stat-value').text(totalJoined);
    $card.find('.session-player-spot-text strong').text(totalJoined);
    $card.find('.session-stat-joined').attr('data-bs-content', 'Total Joined: ' + totalJoined);

    $card.find('.session-stat-confirm .stat-value').text(totalConfirmed);
    $card.find('.session-stat-confirm').attr('data-bs-content', 'Total Confirmed: ' + totalConfirmed);

    if (res.court_status_text) {
        $card.find('.session-court-status-text').text(res.court_status_text);
    }

    if (res.court_status_badge) {
        $card.find('.session-court-badge').text(res.court_status_badge);
    }

    if (res.formatted_cost) {
        const costText = String(res.formatted_cost).replace(/^CAD\s*/i, '$ ');
        $card.find('.session-badge-price span').text(costText);
    }
    if (res.event_court !== undefined) {
        $card.find('.session-badge-court span').text('Court: ' + res.event_court);
    }

    if (res.user_joined === true || res.user_join_status === 'Y') {
        if (res.user_confirmed === 'Y') {
            $card.css('background-color', '#1987543b');
        } else {
            $card.css('background-color', '#ffc10738');
        }
    } else if (res.user_joined === false || res.user_join_status === 'N' || res.user_cancelled === true) {
        $card.css('background-color', '');
    } else if (res.user_confirmed === 'Y') {
        $card.css('background-color', '#1987543b');
    } else if (res.user_confirmed === 'N') {
        $card.css('background-color', '#ffc10738');
    }

    // Sync hidden #data_ payload so modals and subsequent actions use fresh pricing & counts
    const dataId = eventId || res.event_id || $card.attr('data-event-id');
    if (dataId) {
        const $dataInput = $('#data_' + dataId);
        if ($dataInput.length) {
            try {
                const currentData = JSON.parse($dataInput.val() || '{}');
                if (res.event_cost !== undefined) currentData.EVENT_COST = res.event_cost;
                if (res.total_join !== undefined) currentData.total_join = res.total_join;
                if (res.total_confirmed !== undefined) currentData.total_confirmed = res.total_confirmed;
                if (res.court_status_text) currentData.JOIN_MESSAGE = res.court_status_text;
                if (res.courts_confirmed !== undefined) currentData.COURTS_CONFIRMED = res.courts_confirmed;
                $dataInput.val(JSON.stringify(currentData));
            } catch (err) {
                console.error('[updateSchedulerCardStats] Failed to sync data payload:', err);
            }
        }
    }
}
window.updateSchedulerCardStats = updateSchedulerCardStats;

