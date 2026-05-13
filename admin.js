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
    const roles = ['visitor', 'editor', 'admin'];
    return roles.map((r) => `<option value="${r}" ${String(selected) === r ? 'selected' : ''}>${r}</option>`).join('');
}

function escHtml(v) {
    return String(v ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function loadUsers() {
    const d = await api('includes/api/admin_users.php');
    const root = byId('usersRoot');
    const users = d.users.filter((u) => Number(u.user_id) !== Number(currentUserId));
    if (!users.length) {
        root.innerHTML = '<p class="event-meta">No other users found.</p>';
        return;
    }
    root.innerHTML = `
        <div style="overflow:auto;">
            <table class="admin-users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>E-mail</th>
                        <th>E-mail Confirmed</th>
                        <th>Approved</th>
                        <th>Role</th>
                        <th>Primary Country</th>
                        <th>Allowed Countries (IDs)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${users.map((u) => `
                        <tr data-user-id="${u.user_id}">
                            <td>${escHtml(u.username)}<br><span class="event-meta">${escHtml((u.first_name || '') + ' ' + (u.last_name || ''))}</span></td>
                            <td><input id="em_${u.user_id}" type="email" value="${escHtml(u.email || u.username || '')}"></td>
                            <td style="text-align:center;">${u.email_verified ? 'Yes' : 'No'}</td>
                            <td style="text-align:center;"><input id="ap_${u.user_id}" type="checkbox" ${u.is_approved ? 'checked' : ''}></td>
                            <td><select id="ro_${u.user_id}">${roleOptions(u.role === 'category_editor' ? 'editor' : u.role)}</select></td>
                            <td><select id="pc_${u.user_id}">${countryOptions(u.country_id)}</select></td>
                            <td><input id="ac_${u.user_id}" value="${escHtml((u.allowed_country_ids || []).join(','))}"></td>
                            <td style="white-space:nowrap;">
                                <button class="accent" data-action="save" data-user-id="${u.user_id}">Save</button>
                                <button class="danger" data-action="delete" data-user-id="${u.user_id}">Delete</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;

    root.querySelectorAll('button[data-action="save"]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const userId = Number(btn.dataset.userId || 0);
            const allowed = (document.getElementById(`ac_${userId}`).value || '').split(',').map((s) => Number(s.trim())).filter((n) => n > 0);
            await api('includes/api/admin_user_update.php', {
                method: 'POST',
                body: JSON.stringify({
                    user_id: userId,
                    email: document.getElementById(`em_${userId}`).value,
                    is_approved: document.getElementById(`ap_${userId}`).checked ? 1 : 0,
                    role: document.getElementById(`ro_${userId}`).value,
                    country_id: Number(document.getElementById(`pc_${userId}`).value),
                    allowed_country_ids: allowed
                })
            });
            byId('adminMsg').textContent = 'User updated';
            await loadUsers();
        });
    });

    root.querySelectorAll('button[data-action="delete"]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const userId = Number(btn.dataset.userId || 0);
            const email = document.getElementById(`em_${userId}`).value || `user #${userId}`;
            if (!window.confirm(`Delete user ${email}? This cannot be undone.`)) return;
            await api('includes/api/admin_user_delete.php', {
                method: 'POST',
                body: JSON.stringify({ user_id: userId })
            });
            byId('adminMsg').textContent = 'User deleted';
            await loadUsers();
        });
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
