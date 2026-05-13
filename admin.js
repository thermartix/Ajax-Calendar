const byId = (id) => document.getElementById(id);
async function api(path, options = {}) {
    const r = await fetch(path, { headers: { 'Content-Type': 'application/json' }, ...options });
    const d = await r.json();
    if (!r.ok || d.success === false) throw new Error(d.message || 'Request failed');
    return d;
}

let countries = [];
let currentUserId = 0;
const timezones = ['Europe/Prague', 'Europe/Madrid', 'Europe/Paris', 'Europe/Rome', 'Europe/Berlin', 'Europe/Vienna', 'Europe/Budapest', 'Europe/Bucharest', 'UTC'];

async function loadCountries() {
    const d = await api('includes/api/countries.php');
    countries = d.countries;
    byId('countriesText').value = countries.map((c) => `${c.code}|${c.name}`).join('\n');
}

function countryOptions(selected) {
    return countries.map((c) => `<option value="${c.id}" ${Number(selected) === Number(c.id) ? 'selected' : ''}>${c.name}</option>`).join('');
}

function roleOptions(selected) {
    const roles = ['editor', 'admin'];
    return roles.map((r) => `<option value="${r}" ${String(selected) === r ? 'selected' : ''}>${r}</option>`).join('');
}

async function loadUsers() {
    const d = await api('includes/api/admin_users.php');
    const root = byId('usersRoot');
    root.innerHTML = '';
    d.users.forEach((u) => {
        if (Number(u.user_id) === Number(currentUserId)) return;
        const wrap = document.createElement('div');
        wrap.className = 'event-card';
        wrap.style.marginBottom = '8px';
        wrap.innerHTML = `<strong>${u.username}</strong> (${(u.first_name || '') + ' ' + (u.last_name || '')})<br><label>E-mail</label><input id="em_${u.user_id}" type="email" value="${String(u.email || u.username || '').replace(/"/g, '&quot;')}"><br><label>Approved <input type="checkbox" id="ap_${u.user_id}" ${u.is_approved ? 'checked' : ''}></label><br><label>Role</label><select id="ro_${u.user_id}">${roleOptions(u.role === 'category_editor' ? 'editor' : u.role)}</select><br><label>Primary country</label><select id="pc_${u.user_id}">${countryOptions(u.country_id)}</select><br><label>Allowed countries (comma IDs)</label><input id="ac_${u.user_id}" value="${(u.allowed_country_ids || []).join(',')}"><br><button id="su_${u.user_id}" class="accent">Save user</button>`;
        root.appendChild(wrap);
        document.getElementById(`su_${u.user_id}`).onclick = async () => {
            const allowed = (document.getElementById(`ac_${u.user_id}`).value || '').split(',').map((s) => Number(s.trim())).filter((n) => n > 0);
            await api('includes/api/admin_user_update.php', {
                method: 'POST',
                body: JSON.stringify({
                    user_id: u.user_id,
                    email: document.getElementById(`em_${u.user_id}`).value,
                    is_approved: document.getElementById(`ap_${u.user_id}`).checked ? 1 : 0,
                    role: document.getElementById(`ro_${u.user_id}`).value,
                    country_id: Number(document.getElementById(`pc_${u.user_id}`).value),
                    allowed_country_ids: allowed
                })
            });
            byId('adminMsg').textContent = 'User updated';
            await loadUsers();
        };
    });
}

async function init() {
    const session = await api('includes/api/auth_session.php');
    if (!session.loggedIn) {
        location.href = 'login/';
        return;
    }
    currentUserId = Number(session.user.user_id || 0);
    byId('profileFirst').value = session.user.first_name || '';
    byId('profileLast').value = session.user.last_name || '';
    byId('profileEmail').value = session.user.email || session.user.username || '';
    byId('profileNewPassword').value = '';
    byId('profileNewPassword2').value = '';

    const settings = await api('includes/api/settings.php');
    byId('timezoneSelect').innerHTML = timezones.map((tz) => `<option value="${tz}" ${settings.calendarTimezone === tz ? 'selected' : ''}>${tz}</option>`).join('');
    byId('showEventAuthorToggle').checked = settings.showEventAuthor !== false;

    await loadCountries();
    if (session.user.role === 'admin') {
        byId('adminOnly').hidden = false;
        await loadUsers();
    }
}

byId('saveProfile').onclick = async () => {
    const pw1 = byId('profileNewPassword').value;
    const pw2 = byId('profileNewPassword2').value;
    if (pw1 !== pw2) {
        byId('adminMsg').textContent = 'New passwords do not match';
        return;
    }
    await api('includes/api/profile_update.php', {
        method: 'POST',
        body: JSON.stringify({
            first_name: byId('profileFirst').value,
            last_name: byId('profileLast').value,
            email: byId('profileEmail').value,
            new_password: pw1
        })
    });
    byId('profileNewPassword').value = '';
    byId('profileNewPassword2').value = '';
    byId('adminMsg').textContent = 'Profile saved';
};
byId('saveTimezone').onclick = async () => {
    await api('includes/api/admin_timezone_update.php', { method: 'POST', body: JSON.stringify({ calendar_timezone: byId('timezoneSelect').value, show_event_author: byId('showEventAuthorToggle').checked ? 1 : 0 }) });
    byId('adminMsg').textContent = 'Settings saved';
};
byId('saveCountries').onclick = async () => {
    const items = byId('countriesText').value.split('\n').map((l) => l.trim()).filter(Boolean).map((line) => {
        const [code, name] = line.split('|');
        return { code: (code || '').trim(), name: (name || '').trim() };
    });
    const res = await api('includes/api/admin_countries_save.php', { method: 'POST', body: JSON.stringify({ countries: items }) });
    let msg = 'Countries saved';
    if ((res.skipped_in_use_codes || []).length) {
        msg += ` (kept in use: ${(res.skipped_in_use_codes || []).join(', ')})`;
    }
    byId('adminMsg').textContent = msg;
    await loadCountries();
};

init().catch((e) => { byId('adminMsg').textContent = e.message; });
