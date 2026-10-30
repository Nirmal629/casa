/**
 * Registration Request Modal Script
 * Location: /app/script/auth/register.js
 */
(function () {
    'use strict';

    function initRegisterForm() {
        var form = document.getElementById('industrialRegisterForm');
        if (!form) return;

        // Auto-capitalize name input on typing
        var nameInput = form.querySelector('input[name="NAME"]');
        if (nameInput) {
            nameInput.addEventListener('input', function () {
                this.value = this.value.replace(/\b\w/g, function (l) {
                    return l.toUpperCase();
                });
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var submitBtn = document.getElementById('submitBtn');
            var btnText = document.getElementById('btnText');
            var btnSpinner = document.getElementById('btnSpinner');
            var responseMsg = document.getElementById('responseMsg');

            if (!submitBtn || !btnText || !btnSpinner || !responseMsg) return;

            // UI State: Processing
            submitBtn.disabled = true;
            btnText.textContent = 'Processing...';
            btnSpinner.classList.remove('d-none');
            btnSpinner.style.display = 'inline-block';
            responseMsg.style.display = 'none';
            responseMsg.className = 'mt-3 text-center py-2 px-3 rounded response-msg';

            var formData = new FormData(form);
            var apiUrl = typeof BASE_URL !== 'undefined' ? BASE_URL + 'api/add_user.php' : 'api/add_user.php';

            // Send registration AJAX request
            fetch(apiUrl, {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
                .then(function (res) {
                    return res.json();
                })
                .then(function (res) {
                    if (res && res.status === 'success') {
                        // Success state: safe text rendering
                        responseMsg.className = 'mt-3 text-center py-2 px-3 rounded response-msg response-msg-success';
                        responseMsg.style.display = 'block';
                        responseMsg.innerHTML = '<i class="fa-solid fa-circle-check"></i> ';
                        var textSpan = document.createElement('span');
                        textSpan.textContent = res.message || 'Registration request submitted successfully.';
                        responseMsg.appendChild(textSpan);

                        // Clear form
                        form.reset();

                        // Replace submit button with Home button
                        submitBtn.style.display = 'none';
                        var existingHomeBtn = form.querySelector('.btn-success-home');
                        if (!existingHomeBtn) {
                            var homeBtn = document.createElement('a');
                            homeBtn.href = '/index.php';
                            homeBtn.className = 'btn-submit-premium btn-success-home w-100 d-block text-center text-decoration-none mt-2';
                            homeBtn.innerHTML = 'Go to Home <i class="fa-solid fa-house ms-2"></i>';
                            submitBtn.parentNode.appendChild(homeBtn);
                        }
                    } else {
                        // Error state
                        responseMsg.className = 'mt-3 text-center py-2 px-3 rounded response-msg response-msg-error';
                        responseMsg.style.display = 'block';
                        responseMsg.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ';
                        var errorText = document.createElement('span');
                        errorText.textContent = (res && res.message) ? res.message : 'Registration failed.';
                        responseMsg.appendChild(errorText);

                        submitBtn.disabled = false;
                        btnText.innerHTML = 'Complete Registration <i class="fa-solid fa-arrow-right ms-2"></i>';
                        btnSpinner.style.display = 'none';
                        btnSpinner.classList.add('d-none');
                    }
                })
                .catch(function () {
                    responseMsg.className = 'mt-3 text-center py-2 px-3 rounded response-msg response-msg-error';
                    responseMsg.style.display = 'block';
                    responseMsg.textContent = 'System Error: Connection failed.';

                    submitBtn.disabled = false;
                    btnText.innerHTML = 'Complete Registration <i class="fa-solid fa-arrow-right ms-2"></i>';
                    btnSpinner.style.display = 'none';
                    btnSpinner.classList.add('d-none');
                });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRegisterForm);
    } else {
        initRegisterForm();
    }
})();
