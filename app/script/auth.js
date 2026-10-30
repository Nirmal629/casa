// Custom Register Modal
$(document).ready(function () {
    $('#resisterEvent').click(function () {
        $('#resisterEvent_add').addClass('open');
    });

    $('#resisterEvent_close').click(function () {
        $('#resisterEvent_add').removeClass('open');
    });
});

window.toggleCasaPassword = window.toggleCasaPassword || function(btn) {
    if (!btn) return;
    var container = btn.closest('.position-relative') || btn.parentElement;
    if (!container) return;
    var input = container.querySelector('input[name="password"], input[type="password"], input[type="text"]');
    var icon = btn.querySelector('.password-eye-icon') || btn.querySelector('i');
    if (!input) return;

    var isPassword = input.getAttribute('type') === 'password';
    input.setAttribute('type', isPassword ? 'text' : 'password');

    if (icon) {
        if (isPassword) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    btn.setAttribute('aria-pressed', String(isPassword));
    btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');

    btn.classList.remove('is-toggling');
    void btn.offsetWidth;
    btn.classList.add('is-toggling');
};

// Event delegation for password toggle buttons without inline onclick handlers
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.toggle-password-btn');
    if (btn) {
        e.preventDefault();
        window.toggleCasaPassword(btn);
    }
});

// Double-submit protection for login form
document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form && form.matches('form[action="/login"]')) {
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn && !submitBtn.disabled) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Logging in...';
        }
    }
});
