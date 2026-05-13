const byId = (id) => document.getElementById(id);
async function api(path, options = {}) {
    const r = await fetch(path, { headers: { 'Content-Type': 'application/json' }, ...options });
    const d = await r.json();
    if (!r.ok || d.success === false) throw new Error(d.message || 'Request failed');
    return d;
}

async function init() {
    const countries = await api('../includes/api/countries.php');
    byId('signupCountry').innerHTML = countries.countries.map((c) => `<option value="${c.id}">${c.name}</option>`).join('');
}

byId('loginBtn').onclick = async () => {
    try {
        await api('../includes/api/auth_login.php', {
            method: 'POST',
            body: JSON.stringify({ username: byId('loginUsername').value, password: byId('loginPassword').value })
        });
        location.href = '../';
    } catch (e) {
        byId('authMessage').textContent = e.message;
    }
};

byId('otpRequestBtn').onclick = async () => {
    try {
        await api('../includes/api/auth_otp_request.php', {
            method: 'POST',
            body: JSON.stringify({ username: byId('otpUsername').value })
        });
        byId('authMessage').textContent = 'OTP sent. Check your email.';
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
        location.href = '../';
    } catch (e) {
        byId('authMessage').textContent = e.message;
    }
};

byId('signupBtn').onclick = async () => {
    try {
        await api('../includes/api/auth_signup.php', {
            method: 'POST',
            body: JSON.stringify({
                username: byId('signupUsername').value,
                email: byId('signupEmail').value,
                password: byId('signupPassword').value,
                passwordRepeat: byId('signupPassword2').value,
                first_name: byId('signupFirst').value,
                last_name: byId('signupLast').value,
                country_id: byId('signupCountry').value
            })
        });
        byId('authMessage').textContent = 'Registered. Waiting for admin approval and role assignment.';
    } catch (e) {
        byId('authMessage').textContent = e.message;
    }
};

init().catch((e) => { byId('authMessage').textContent = e.message; });
