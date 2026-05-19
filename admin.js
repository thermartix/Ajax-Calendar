const byId = (id) => document.getElementById(id);
let csrfToken = '';
async function api(path, options = {}) {
    const method = String(options.method || 'GET').toUpperCase();
    const headers = { 'Content-Type': 'application/json', ...(options.headers || {}) };
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) && csrfToken) {
        headers['X-CSRF-Token'] = csrfToken;
    }
    const r = await fetch(path, { ...options, method, headers });
    const d = await r.json();
    if (d && typeof d.csrf_token === 'string' && d.csrf_token) {
        csrfToken = d.csrf_token;
    }
    if (!r.ok || d.success === false) throw new Error(d.message || 'Request failed');
    return d;
}

let countries = [];
let currentUserId = 0;
let usersCache = [];
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

function isValidEmail(v) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v || '').trim());
}

async function addSingleUser() {
    const first_name = String(byId('newUserFirst').value || '').trim();
    const last_name = String(byId('newUserLast').value || '').trim();
    const email = String(byId('newUserEmail').value || '').trim().toLowerCase();
    const member_id = String(byId('newUserId').value || '').trim();
    const role = String(byId('newUserRole').value || 'visitor');
    if (!isValidEmail(email)) throw new Error('Please enter a valid email');
    if (!['visitor', 'editor', 'admin'].includes(role)) throw new Error('Invalid user level');
    await api('includes/api/admin_user_create.php', {
        method: 'POST',
        body: JSON.stringify({ first_name, last_name, email, member_id, role })
    });
}

function parseUsersCsv(text) {
    const lines = String(text || '').split(/\r?\n/).map((l) => l.trim()).filter(Boolean);
    if (!lines.length) return [];
    return lines.map((line) => {
        const cols = line.split(',').map((c) => c.trim());
        return {
            first_name: cols[0] || '',
            last_name: cols[1] || '',
            email: String(cols[2] || '').toLowerCase(),
            member_id: cols[3] || '',
            role: String(cols[4] || 'visitor').toLowerCase()
        };
    });
}

async function loadUsers() {
    const d = await api('includes/api/admin_users.php');
    usersCache = d.users.filter((u) => Number(u.user_id) !== Number(currentUserId));
    renderUsersTable();
}

function renderUsersTable() {
    const root = byId('usersRoot');
    const needle = String((byId('usersFilter')?.value || '')).trim().toLowerCase();
    const users = !needle ? usersCache : usersCache.filter((u) => {
        const hay = [
            u.username,
            u.email,
            u.member_id,
            u.first_name,
            u.last_name,
            u.role,
            String(u.user_id)
        ].join(' ').toLowerCase();
        return hay.includes(needle);
    });
    if (!users.length) {
        root.innerHTML = '<p class="event-meta">No matching users found.</p>';
        return;
    }
    root.innerHTML = `
        <div style="overflow:auto;">
            <table class="admin-users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>ID</th>
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
                            <td><input id="mid_${u.user_id}" value="${escHtml(u.member_id || '')}"></td>
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
                    member_id: document.getElementById(`mid_${userId}`).value,
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
    const settings = await api('includes/api/settings.php');
    byId('timezoneSelect').innerHTML = timezones.map((tz) => `<option value="${tz}" ${settings.calendarTimezone === tz ? 'selected' : ''}>${tz}</option>`).join('');
    byId('showEventAuthorToggle').checked = settings.showEventAuthor !== false;

    await loadCountries();
    if (session.user.role === 'admin') {
        byId('adminOnly').hidden = false;
        byId('usersFilter').addEventListener('input', () => renderUsersTable());
        await loadUsers();
    }
}

byId('saveTimezone').onclick = async () => {
    await api('includes/api/admin_timezone_update.php', { method: 'POST', body: JSON.stringify({ calendar_timezone: byId('timezoneSelect').value, show_event_author: byId('showEventAuthorToggle').checked ? 1 : 0 }) });
    byId('adminMsg').textContent = 'Settings saved';
};
byId('addUserBtn').onclick = async () => {
    try {
        await addSingleUser();
        byId('adminMsg').textContent = 'User created';
        byId('newUserFirst').value = '';
        byId('newUserLast').value = '';
        byId('newUserEmail').value = '';
        byId('newUserId').value = '';
        byId('newUserRole').value = 'visitor';
        await loadUsers();
    } catch (e) {
        byId('adminMsg').textContent = e.message;
    }
};
byId('importUsersBtn').onclick = async () => {
    try {
        const file = byId('usersCsvFile').files?.[0];
        if (!file) throw new Error('Please choose a CSV file first');
        const text = await file.text();
        const users = parseUsersCsv(text);
        if (!users.length) throw new Error('CSV has no rows');
        await api('includes/api/admin_user_import.php', {
            method: 'POST',
            body: JSON.stringify({ users })
        });
        byId('adminMsg').textContent = `Imported ${users.length} users`;
        byId('usersCsvFile').value = '';
        await loadUsers();
    } catch (e) {
        byId('adminMsg').textContent = e.message;
    }
};
byId('downloadUsersCsvTemplateBtn').onclick = () => {
    const header = 'name,surname,e-mail,ID,user level\n';
    const example = 'John,Doe,john.doe@example.com,1234567,visitor\n';
    const blob = new Blob([header, example], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'users_template.csv';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
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
