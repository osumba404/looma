const apiBaseUrl = '/api/';

function showAlert(elementId, message, type) {
    const alert = document.getElementById(elementId);
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.display = 'block';
    setTimeout(() => (alert.style.display = 'none'), 5000);
}

function showSpinner(button, show) {
    const spinner = button.querySelector('.spinner');
    if (show) {
        button.disabled = true;
        spinner.style.display = 'inline-block';
    } else {
        button.disabled = false;
        spinner.style.display = 'none';
    }
}

async function registerUser(event) {
    event.preventDefault();
    const form = event.target;
    const button = form.querySelector('button');
    showSpinner(button, true);

    const data = {
        full_name: form.full_name.value.trim(),
        username: form.username.value.trim(),
        phone: form.phone.value.trim(),
        email: form.email.value.trim(),
        password: form.password.value.trim()
    };

    try {
        const response = await fetch(apiBaseUrl + 'register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        let result;
        try {
            result = await response.json();
        } catch (e) {
            throw new Error('Invalid response: Not JSON');
        }

        if (response.ok) {
            showAlert('register-alert', result.message, 'success');
            localStorage.setItem('user_id', result.user_id);
            setTimeout(() => (window.location.href = 'verify.php'), 1000);
        } else {
            showAlert('register-alert', result.error || 'Registration failed', 'danger');
        }
    } catch (error) {
        showAlert('register-alert', 'Error: ' + error.message, 'danger');
    } finally {
        showSpinner(button, false);
    }
}

async function loginUser(event) {
    event.preventDefault();
    const form = event.target;
    const button = form.querySelector('button');
    showSpinner(button, true);

    const data = {
        identifier: form.identifier.value.trim(),
        password: form.password.value.trim()
    };

    try {
        const response = await fetch(apiBaseUrl + 'login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        let result;
        try {
            result = await response.json();
        } catch (e) {
            throw new Error('Invalid response: Not JSON');
        }

        if (response.ok) {
            showAlert('login-alert', result.message, 'success');
            localStorage.setItem('user_id', result.user_id);
            setTimeout(() => (window.location.href = 'wallet.php'), 1000);
        } else {
            showAlert('login-alert', result.error || 'Login failed', 'danger');
        }
    } catch (error) {
        showAlert('login-alert', 'Error: ' + error.message, 'danger');
    } finally {
        showSpinner(button, false);
    }
}

async function verifyPhone(event) {
    event.preventDefault();
    const form = event.target;
    const button = form.querySelector('button');
    showSpinner(button, true);

    const data = {
        user_id: localStorage.getItem('user_id'),
        code: form.code.value.trim()
    };

    try {
        const response = await fetch(apiBaseUrl + 'verify_phone.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        let result;
        try {
            result = await response.json();
        } catch (e) {
            throw new Error('Invalid response: Not JSON');
        }

        if (response.ok) {
            showAlert('verify-alert', result.message, 'success');
            setTimeout(() => (window.location.href = 'payment.php'), 1000);
        } else {
            showAlert('verify-alert', result.error || 'Verification failed', 'danger');
        }
    } catch (error) {
        showAlert('verify-alert', 'Error: ' + error.message, 'danger');
    } finally {
        showSpinner(button, false);
    }
}

async function initiatePayment() {
    const button = document.getElementById('pay-btn');
    showSpinner(button, true);

    const data = {
        user_id: localStorage.getItem('user_id')
    };

    try {
        const response = await fetch(apiBaseUrl + 'initiate_payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        let result;
        try {
            result = await response.json();
        } catch (e) {
            throw new Error('Invalid response: Not JSON');
        }

        if (response.ok) {
            showAlert('payment-alert', result.message, 'success');
            checkActivationStatus();
        } else {
            showAlert('payment-alert', result.error || 'Payment initiation failed', 'danger');
            showSpinner(button, false);
        }
    } catch (error) {
        showAlert('payment-alert', 'Error: ' + error.message, 'danger');
        showSpinner(button, false);
    }
}

async function checkActivationStatus() {
    const data = {
        user_id: localStorage.getItem('user_id')
    };

    try {
        const response = await fetch(apiBaseUrl + 'check_activation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        let result;
        try {
            result = await response.json();
        } catch (e) {
            throw new Error('Invalid response: Not JSON');
        }

        if (response.ok && result.is_activated) {
            showAlert('payment-alert', 'Account activated! Redirecting...', 'success');
            setTimeout(() => (window.location.href = 'wallet.php'), 1000);
        } else {
            setTimeout(checkActivationStatus, 5000);
        }
    } catch (error) {
        console.error('Activation check error:', error);
    }
}