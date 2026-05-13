const byId = (id) => document.getElementById(id);

async function api(path, options = {}) {
    const r = await fetch(path, { headers: { 'Content-Type': 'application/json' }, ...options });
    const d = await r.json();
    if (!r.ok || d.success === false) throw new Error(d.message || 'Request failed');
    return d;
}

let otpCountdown = 0;
let otpTimer = null;

function showPane(name) {
    byId('passwordLoginPane').classList.toggle('hidden', name !== 'login');
    byId('otpPane').classList.toggle('hidden', name !== 'otp');
    byId('signupPane').classList.toggle('hidden', name !== 'signup');
    byId('formTitle').textContent = name === 'signup' ? 'Sign up' : (name === 'otp' ? 'Login with OTP' : 'Login');
    byId('authMessage').textContent = '';
}

function setOtpCountdown(seconds) {
    otpCountdown = seconds;
    byId('resendOtpLink').classList.remove('hidden');
    byId('resendOtpLink').style.pointerEvents = 'none';
    byId('resendOtpLink').style.opacity = '0.5';
    byId('otpHint').textContent = `OTP sent. You can resend in ${otpCountdown}s.`;
    if (otpTimer) clearInterval(otpTimer);
    otpTimer = setInterval(() => {
        otpCountdown -= 1;
        if (otpCountdown <= 0) {
            clearInterval(otpTimer);
            otpTimer = null;
            byId('otpHint').textContent = 'You can resend OTP now.';
            byId('resendOtpLink').style.pointerEvents = 'auto';
            byId('resendOtpLink').style.opacity = '1';
            return;
        }
        byId('otpHint').textContent = `OTP sent. You can resend in ${otpCountdown}s.`;
    }, 1000);
}

function validateEmailField() {
    const v = String(byId('signupEmail').value || '').trim();
    const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
    byId('signupEmail').classList.toggle('input-error', !ok);
    byId('signupEmailError').classList.toggle('hidden', ok);
    return ok;
}

async function init() {
    const session = await api('../includes/api/auth_session.php');
    if (session.loggedIn) {
        window.location.href = '../';
        return;
    }
    const countries = await api('../includes/api/countries.php');
    byId('signupCountry').innerHTML = countries.countries.map((c) => `<option value="${c.id}">${c.name}</option>`).join('');
    showPane('login');
}

byId('showOtpLink').onclick = () => showPane('otp');
byId('showSignupLink').onclick = () => showPane('signup');
byId('backToLoginFromOtp').onclick = () => showPane('login');
byId('backToLoginFromSignup').onclick = () => showPane('login');

byId('loginBtn').onclick = async () => {
    try {
        await api('../includes/api/auth_login.php', {
            method: 'POST',
            body: JSON.stringify({
                username: String(byId('loginUsername').value || '').trim(),
                password: byId('loginPassword').value
            })
        });
        window.location.href = '../';
    } catch (e) {
        byId('authMessage').textContent = e.message;
    }
};

async function requestOtp() {
    await api('../includes/api/auth_otp_request.php', {
        method: 'POST',
        body: JSON.stringify({ username: String(byId('otpUsername').value || '').trim() })
    });
    byId('otpAfterSend').classList.remove('hidden');
    setOtpCountdown(60);
}

byId('otpRequestBtn').onclick = async () => {
    try {
        await requestOtp();
    } catch (e) {
        byId('authMessage').textContent = e.message;
    }
};

byId('resendOtpLink').onclick = async () => {
    if (otpCountdown > 0) return;
    try {
        await requestOtp();
    } catch (e) {
        byId('authMessage').textContent = e.message;
    }
};

byId('otpVerifyBtn').onclick = async () => {
    try {
        await api('../includes/api/auth_otp_verify.php', {
            method: 'POST',
            body: JSON.stringify({ code: byId('otpCode').value })
        });
        window.location.href = '../';
    } catch (e) {
        byId('authMessage').textContent = e.message;
    }
};

byId('signupEmail').addEventListener('input', () => {
    if (String(byId('signupEmail').value || '').trim() === '') {
        byId('signupEmail').classList.remove('input-error');
        byId('signupEmailError').classList.add('hidden');
        return;
    }
    validateEmailField();
});

byId('signupBtn').onclick = async () => {
    try {
        if (!validateEmailField()) return;
        const email = String(byId('signupEmail').value || '').trim().toLowerCase();
        await api('../includes/api/auth_signup.php', {
            method: 'POST',
            body: JSON.stringify({
                username: email,
                email,
                password: byId('signupPassword').value,
                first_name: byId('signupFirst').value,
                last_name: byId('signupLast').value,
                country_id: byId('signupCountry').value
            })
        });
        byId('authMessage').textContent = 'Signup created. Please confirm your email from the link we sent.';
        showPane('login');
    } catch (e) {
        byId('authMessage').textContent = e.message;
    }
};

init().catch((e) => { byId('authMessage').textContent = e.message; });
