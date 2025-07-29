document.addEventListener('DOMContentLoaded', function () {
    const rememberCheckbox = document.getElementById('remember_me');
    const loginBtn = document.getElementById('robloxLoginBtn');

    if (localStorage.getItem('remember_me_preference') === '1') {
        rememberCheckbox.checked = true;
    }

    rememberCheckbox.addEventListener('change', function () {
        if (this.checked) {
            localStorage.setItem('remember_me_preference', '1');
        } else {
            localStorage.removeItem('remember_me_preference');
        }
    });

    loginBtn.addEventListener('click', function (e) {
        e.preventDefault();

        const authUrl = this.getAttribute('data-auth-url');
        const rememberMe = rememberCheckbox.checked;

        if (!authUrl) {
            alert('Login is not available. Please check configuration.');
            return;
        }

        if (rememberMe) {
            fetch('/auth/set-remember-session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ remember_me: true })
            }).then(() => {
                const separator = authUrl.includes('?') ? '&' : '?';
                window.location.href = authUrl + separator + 'remember_me=1';
            }).catch(() => {
                const separator = authUrl.includes('?') ? '&' : '?';
                window.location.href = authUrl + separator + 'remember_me=1';
            });
        } else {
            window.location.href = authUrl;
        }
    });
});