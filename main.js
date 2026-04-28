const state = {
    currentDate: new Date(),
    view: 'month',
    countries: [],
    selectedCountry: '',
    events: [],
    user: null
};

const byId = (id) => document.getElementById(id);
const fmtDate = (d) => d.toISOString().slice(0, 10);
const fmtDateTime = (iso) => new Date(iso).toLocaleString();

async function api(path, options = {}) {
    const response = await fetch(path, {
        headers: { 'Content-Type': 'application/json' },
        ...options
    });
    const data = await response.json();
    if (!response.ok || data.success === false) {
        throw new Error(data.message || 'Request failed');
    }
    return data;
}

function getRange() {
    const d = new Date(state.currentDate);
    if (state.view === 'day') {
        return { start: new Date(d.getFullYear(), d.getMonth(), d.getDate()), end: new Date(d.getFullYear(), d.getMonth(), d.getDate(), 23, 59, 59) };
    }
    if (state.view === 'week') {
        const day = d.getDay();
        const start = new Date(d);
        start.setDate(d.getDate() - day);
        const end = new Date(start);
        end.setDate(start.getDate() + 6);
        end.setHours(23, 59, 59, 0);
        return { start, end };
    }
    if (state.view === 'month') {
        return { start: new Date(d.getFullYear(), d.getMonth(), 1), end: new Date(d.getFullYear(), d.getMonth() + 1, 0, 23, 59, 59) };
    }
    if (state.view === 'year') {
        return { start: new Date(d.getFullYear(), 0, 1), end: new Date(d.getFullYear(), 11, 31, 23, 59, 59) };
    }
    const start = new Date(d);
    start.setMonth(d.getMonth() - 3);
    const end = new Date(d);
    end.setMonth(d.getMonth() + 3);
    return { start, end };
}

function stepDate(dir) {
    const d = state.currentDate;
    if (state.view === 'day') d.setDate(d.getDate() + dir);
    else if (state.view === 'week') d.setDate(d.getDate() + (7 * dir));
    else if (state.view === 'month') d.setMonth(d.getMonth() + dir);
    else if (state.view === 'year') d.setFullYear(d.getFullYear() + dir);
    else d.setMonth(d.getMonth() + dir);
}

function updateRangeLabel() {
    const { start, end } = getRange();
    byId('rangeLabel').textContent = `${start.toLocaleDateString()} - ${end.toLocaleDateString()}`;
}

function monthGrid(anchorDate) {
    const year = anchorDate.getFullYear();
    const month = anchorDate.getMonth();
    const first = new Date(year, month, 1);
    const start = new Date(first);
    start.setDate(first.getDate() - first.getDay());
    const days = [];
    for (let i = 0; i < 42; i += 1) {
        const d = new Date(start);
        d.setDate(start.getDate() + i);
        days.push(d);
    }
    return days;
}

function eventsForDate(dateObj) {
    const key = fmtDate(dateObj);
    return state.events.filter((e) => e.start_at.slice(0, 10) <= key && e.end_at.slice(0, 10) >= key);
}

function openEventDialog(eventItem = null) {
    if (!state.user) return;
    const dlg = byId('eventDialog');
    byId('eventDialogTitle').textContent = eventItem ? 'Edit Event' : 'New Event';
    byId('eventId').value = eventItem ? eventItem.id : '';
    byId('eventTitle').value = eventItem?.title || '';
    byId('eventDescription').value = eventItem?.description || '';
    byId('eventLink').value = eventItem?.event_link || '';
    byId('eventCountry').value = eventItem?.country_id || state.user.country_id || state.selectedCountry || '';
    byId('eventStart').value = eventItem ? eventItem.start_at.replace(' ', 'T').slice(0, 16) : '';
    byId('eventEnd').value = eventItem ? eventItem.end_at.replace(' ', 'T').slice(0, 16) : '';
    const canDelete = eventItem && eventItem.can_edit;
    byId('deleteEventBtn').hidden = !canDelete;
    if (state.user.role === 'category_editor') {
        byId('eventCountry').value = String(state.user.country_id || '');
        byId('eventCountry').disabled = true;
    } else {
        byId('eventCountry').disabled = false;
    }
    dlg.showModal();
}

function renderList(events) {
    const root = byId('calendarRoot');
    root.innerHTML = '';
    const wrap = document.createElement('div');
    wrap.className = 'list-view';
    events.forEach((e) => {
        const card = document.createElement('article');
        card.className = 'event-card';
        card.innerHTML = `<h4>${e.title}</h4>
            <p class="event-meta">${fmtDateTime(e.start_at)} to ${fmtDateTime(e.end_at)} | ${e.country_name} | by ${e.username}</p>
            <p>${e.description || ''}</p>
            ${e.event_link ? `<p><a href="${e.event_link}" target="_blank" rel="noopener">Open Event Link</a></p>` : ''}`;
        if (state.user && e.can_edit) {
            const b = document.createElement('button');
            b.textContent = 'Edit';
            b.addEventListener('click', () => openEventDialog(e));
            card.appendChild(b);
        }
        wrap.appendChild(card);
    });
    if (!events.length) {
        wrap.innerHTML = '<p>No events in this range and category.</p>';
    }
    root.appendChild(wrap);
}

function renderMonthLike(anchor, compact = false) {
    const root = byId('calendarRoot');
    root.innerHTML = '';
    const grid = document.createElement('div');
    grid.className = 'calendar-grid';
    ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach((d) => {
        const h = document.createElement('div');
        h.className = 'day-head';
        h.textContent = d;
        grid.appendChild(h);
    });

    const days = monthGrid(anchor);
    days.forEach((d) => {
        const cell = document.createElement('div');
        cell.className = 'day-cell';
        if (d.getMonth() !== anchor.getMonth()) cell.classList.add('other');
        const num = document.createElement('div');
        num.className = 'day-num';
        num.textContent = String(d.getDate());
        cell.appendChild(num);
        const events = eventsForDate(d).slice(0, compact ? 2 : 4);
        events.forEach((e) => {
            const pill = document.createElement('div');
            pill.className = 'event-pill';
            pill.textContent = `${new Date(e.start_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} ${e.title}`;
            pill.addEventListener('click', () => openEventDialog(e));
            cell.appendChild(pill);
        });
        cell.addEventListener('dblclick', () => openEventDialog());
        grid.appendChild(cell);
    });
    root.appendChild(grid);
}

function renderYear() {
    const root = byId('calendarRoot');
    root.innerHTML = '';
    const wrap = document.createElement('div');
    wrap.style.display = 'grid';
    wrap.style.gridTemplateColumns = 'repeat(auto-fit,minmax(280px,1fr))';
    wrap.style.gap = '10px';
    for (let m = 0; m < 12; m += 1) {
        const section = document.createElement('section');
        section.className = 'event-card';
        const title = document.createElement('h4');
        title.textContent = new Date(state.currentDate.getFullYear(), m, 1).toLocaleDateString(undefined, { month: 'long' });
        section.appendChild(title);
        const list = document.createElement('ul');
        const monthEvents = state.events.filter((e) => new Date(e.start_at).getMonth() === m);
        if (!monthEvents.length) {
            list.innerHTML = '<li>No events</li>';
        } else {
            monthEvents.slice(0, 5).forEach((e) => {
                const li = document.createElement('li');
                li.textContent = `${new Date(e.start_at).toLocaleDateString()}: ${e.title} (${e.country_name})`;
                list.appendChild(li);
            });
        }
        section.appendChild(list);
        wrap.appendChild(section);
    }
    root.appendChild(wrap);
}

function renderWeek() {
    const root = byId('calendarRoot');
    root.innerHTML = '';
    const list = document.createElement('div');
    list.className = 'list-view';
    const { start } = getRange();
    for (let i = 0; i < 7; i += 1) {
        const d = new Date(start);
        d.setDate(start.getDate() + i);
        const card = document.createElement('article');
        card.className = 'event-card';
        card.innerHTML = `<h4>${d.toLocaleDateString(undefined, { weekday: 'long', month: 'short', day: 'numeric' })}</h4>`;
        const events = eventsForDate(d);
        if (!events.length) {
            card.innerHTML += '<p class="event-meta">No events</p>';
        } else {
            events.forEach((e) => {
                const p = document.createElement('div');
                p.className = 'event-pill';
                p.textContent = `${new Date(e.start_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} ${e.title} (${e.country_name})`;
                p.addEventListener('click', () => openEventDialog(e));
                card.appendChild(p);
            });
        }
        list.appendChild(card);
    }
    root.appendChild(list);
}

function renderDay() {
    renderList(eventsForDate(state.currentDate));
}

function renderView() {
    updateRangeLabel();
    if (state.view === 'month') renderMonthLike(state.currentDate, false);
    else if (state.view === 'year') renderYear();
    else if (state.view === 'week') renderWeek();
    else if (state.view === 'day') renderDay();
    else renderList(state.events);
}

async function loadSession() {
    const data = await api('includes/api/auth_session.php');
    state.user = data.user;
    const auth = byId('authBlock');
    if (!state.user) {
        auth.innerHTML = '<button id="openAuth" class="accent">Login / Sign up</button>';
        byId('newEventBtn').hidden = true;
        byId('openAuth').onclick = () => byId('authDialog').showModal();
    } else {
        auth.innerHTML = `<strong>${state.user.username}</strong> (${state.user.role}) <button id="logoutBtn">Logout</button>`;
        byId('logoutBtn').onclick = async () => {
            await api('includes/api/auth_logout.php', { method: 'POST', body: '{}' });
            await bootstrap();
        };
        byId('newEventBtn').hidden = false;
    }
}

async function loadCountries() {
    const data = await api('includes/api/countries.php');
    state.countries = data.countries;
    const options = ['<option value="">All Countries</option>', ...state.countries.map((c) => `<option value="${c.id}">${c.name}</option>`)];
    byId('countryFilter').innerHTML = options.join('');
    byId('eventCountry').innerHTML = state.countries.map((c) => `<option value="${c.id}">${c.name}</option>`).join('');
    byId('signupCountry').innerHTML = state.countries.map((c) => `<option value="${c.id}">${c.name}</option>`).join('');
}

async function loadEvents() {
    const { start, end } = getRange();
    const params = new URLSearchParams({ start: fmtDate(start), end: fmtDate(end) });
    if (state.selectedCountry) params.set('country_id', state.selectedCountry);
    const data = await api(`includes/api/events_list.php?${params.toString()}`);
    state.events = data.events;
}

async function refreshCalendar() {
    await loadEvents();
    renderView();
}

async function bootstrap() {
    await loadSession();
    await loadCountries();
    await refreshCalendar();
}

byId('viewSelect').addEventListener('change', async (e) => {
    state.view = e.target.value;
    await refreshCalendar();
});
byId('countryFilter').addEventListener('change', async (e) => {
    state.selectedCountry = e.target.value;
    await refreshCalendar();
});
byId('prevBtn').addEventListener('click', async () => {
    stepDate(-1);
    await refreshCalendar();
});
byId('nextBtn').addEventListener('click', async () => {
    stepDate(1);
    await refreshCalendar();
});
byId('todayBtn').addEventListener('click', async () => {
    state.currentDate = new Date();
    await refreshCalendar();
});
byId('newEventBtn').addEventListener('click', () => openEventDialog());

byId('cancelEventBtn').addEventListener('click', () => byId('eventDialog').close());

byId('eventForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {
        id: byId('eventId').value || null,
        title: byId('eventTitle').value.trim(),
        description: byId('eventDescription').value.trim(),
        event_link: byId('eventLink').value.trim(),
        country_id: Number(byId('eventCountry').value),
        start_at: byId('eventStart').value.replace('T', ' ') + ':00',
        end_at: byId('eventEnd').value.replace('T', ' ') + ':00'
    };
    await api('includes/api/event_save.php', { method: 'POST', body: JSON.stringify(payload) });
    byId('eventDialog').close();
    await refreshCalendar();
});

byId('deleteEventBtn').addEventListener('click', async () => {
    const id = Number(byId('eventId').value);
    if (!id) return;
    await api('includes/api/event_delete.php', { method: 'POST', body: JSON.stringify({ id }) });
    byId('eventDialog').close();
    await refreshCalendar();
});

byId('loginBtn').addEventListener('click', async () => {
    try {
        await api('includes/api/auth_login.php', {
            method: 'POST',
            body: JSON.stringify({ username: byId('loginUsername').value, password: byId('loginPassword').value })
        });
        byId('authDialog').close();
        await bootstrap();
    } catch (err) {
        byId('authMessage').textContent = err.message;
    }
});

byId('signupBtn').addEventListener('click', async () => {
    try {
        await api('includes/api/auth_signup.php', {
            method: 'POST',
            body: JSON.stringify({
                username: byId('signupUsername').value,
                password: byId('signupPassword').value,
                passwordRepeat: byId('signupPassword2').value,
                role: byId('signupRole').value,
                country_id: byId('signupCountry').value
            })
        });
        byId('authDialog').close();
        await bootstrap();
    } catch (err) {
        byId('authMessage').textContent = err.message;
    }
});

bootstrap().catch((err) => {
    byId('calendarRoot').innerHTML = `<p>Initialization failed: ${err.message}</p>`;
});