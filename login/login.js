const byId = (id) => document.getElementById(id);
let csrfToken = '';
const getLang = () => (window.AppI18n ? window.AppI18n.normalizeLang(localStorage.getItem('app_lang') || 'en') : 'en');
const t = (k) => (window.i18next && window.i18next.isInitialized ? window.i18next.t(k) : k);

async function seedCsrfToken() {
    const r = await fetch('../includes/api/auth_session.php', {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' }
    });
    const raw = await r.text();
    let d = {};
    try {
        d = raw ? JSON.parse(raw) : {};
    } catch (err) {
        d = {};
    }
    if (d && typeof d.csrf_token === 'string' && d.csrf_token) {
        csrfToken = d.csrf_token;
    }
}

async function api(path, options = {}) {
    return apiInternal(path, options, true);
}

async function apiInternal(path, options = {}, allowCsrfRetry = true) {
    const method = String(options.method || 'GET').toUpperCase();
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) && !csrfToken) {
        await seedCsrfToken();
    }
    const headers = { 'Content-Type': 'application/json', ...(options.headers || {}) };
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) && csrfToken) {
        headers['X-CSRF-Token'] = csrfToken;
    }
    const r = await fetch(path, { ...options, method, headers });
    const raw = await r.text();
    let d = null;
    try {
        d = raw ? JSON.parse(raw) : {};
    } catch (err) {
        const snippet = String(raw || '').replace(/\s+/g, ' ').slice(0, 180);
        throw new Error(`${t('serverInvalidResponse')} ${snippet}`);
    }
    if (d && typeof d.csrf_token === 'string' && d.csrf_token) {
        csrfToken = d.csrf_token;
    }
    if (
        allowCsrfRetry &&
        ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) &&
        r.status === 403 &&
        d &&
        String(d.message || '') === 'Invalid CSRF token' &&
        csrfToken
    ) {
        return apiInternal(path, options, false);
    }
    if (!r.ok || d.success === false) throw new Error(d.message || t('requestFailed'));
    return d;
}

let otpCountdown = 0;
let otpTimer = null;

function showPane(name, keepMessage = false) {
    byId('passwordLoginPane').classList.toggle('hidden', name !== 'login');
    byId('otpPane').classList.toggle('hidden', name !== 'otp');
    byId('signupPane').classList.toggle('hidden', name !== 'signup');
    byId('setupPane').classList.toggle('hidden', name !== 'setup');
    byId('formTitle').textContent = name === 'signup' ? t('signUp') : (name === 'otp' ? t('forgotPasswordOtp') : (name === 'setup' ? t('setPassword') : t('login')));
    if (!keepMessage) byId('authMessage').textContent = '';
}

function setOtpCountdown(seconds) {
    otpCountdown = seconds;
    byId('resendOtpLink').classList.remove('hidden');
    byId('resendOtpLink').style.pointerEvents = 'none';
    byId('resendOtpLink').style.opacity = '0.5';
    byId('otpHint').textContent = t('otpSentResendIn').replace('{seconds}', String(otpCountdown));
    if (otpTimer) clearInterval(otpTimer);
    otpTimer = setInterval(() => {
        otpCountdown -= 1;
        if (otpCountdown <= 0) {
            clearInterval(otpTimer);
            otpTimer = null;
            byId('otpHint').textContent = t('otpCanResendNow');
            byId('resendOtpLink').style.pointerEvents = 'auto';
            byId('resendOtpLink').style.opacity = '1';
            return;
        }
        byId('otpHint').textContent = t('otpSentResendIn').replace('{seconds}', String(otpCountdown));
    }, 1000);
}

function validateEmailField() {
    const v = String(byId('signupEmail').value || '').trim();
    const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
    byId('signupEmail').classList.toggle('input-error', !ok);
    byId('signupEmailError').classList.toggle('hidden', ok);
    return ok;
}

function isValidIdentifier(v) {
    const s = String(v || '').trim();
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s) || /^\d{7}$/.test(s);
}

function validateIdentifierField(inputId, errorId) {
    const el = byId(inputId);
    const err = byId(errorId);
    const ok = isValidIdentifier(el.value);
    el.classList.toggle('input-error', !ok);
    err.classList.toggle('hidden', ok);
    return ok;
}

async function init() {
    if (window.AppI18n) await window.AppI18n.initI18n(getLang());
    const back = byId('loginBackLink');
    if (back) back.textContent = t('backToCalendar');
    const params = new URLSearchParams(window.location.search);
    const setupMode = params.get('setup') === '1';
    const session = await api('../includes/api/auth_session.php');
    if (session.loggedIn) {
        window.location.href = '../';
        return;
    }
    const countries = await api('../includes/api/countries.php');
    byId('signupCountry').innerHTML = countries.countries.map((c) => `<option value="${c.id}">${c.name}</option>`).join('');
    showPane(setupMode ? 'setup' : 'login');
}

byId('showOtpLink').onclick = () => showPane('otp');
byId('showSignupLink').onclick = () => showPane('signup');
byId('backToLoginFromOtp').onclick = () => showPane('login');
byId('backToLoginFromSignup').onclick = () => showPane('login');

byId('loginBtn').onclick = async () => {
    try {
        if (!validateIdentifierField('loginUsername', 'loginUsernameError')) return;
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
    if (!validateIdentifierField('otpUsername', 'otpUsernameError')) return;
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
byId('loginUsername').addEventListener('input', () => {
    if (String(byId('loginUsername').value || '').trim() === '') {
        byId('loginUsername').classList.remove('input-error');
        byId('loginUsernameError').classList.add('hidden');
        return;
    }
    validateIdentifierField('loginUsername', 'loginUsernameError');
});
byId('otpUsername').addEventListener('input', () => {
    if (String(byId('otpUsername').value || '').trim() === '') {
        byId('otpUsername').classList.remove('input-error');
        byId('otpUsernameError').classList.add('hidden');
        return;
    }
    validateIdentifierField('otpUsername', 'otpUsernameError');
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
                member_id: byId('signupMemberId').value,
                country_id: byId('signupCountry').value
            })
        });
        byId('authMessage').textContent = t('signupCreatedConfirmEmail');
        showPane('login', true);
    } catch (e) {
        byId('authMessage').textContent = e.message;
    }
};

byId('setupBtn').onclick = async () => {
    try {
        const p1 = String(byId('setupPassword').value || '');
        const p2 = String(byId('setupPassword2').value || '');
        if (p1 !== p2) throw new Error(t('passwordsDoNotMatch'));
        if (p1.length < 8) throw new Error(t('passwordMin8'));
        const params = new URLSearchParams(window.location.search);
        await api('../includes/api/auth_setup_password.php', {
            method: 'POST',
            body: JSON.stringify({
                uid: Number(params.get('uid') || 0),
                token: String(params.get('token') || ''),
                password: p1
            })
        });
        byId('authMessage').textContent = t('passwordSetCanLogin');
        showPane('login', true);
    } catch (e) {
        byId('authMessage').textContent = e.message;
    }
};

init().catch((e) => { byId('authMessage').textContent = e.message; });
