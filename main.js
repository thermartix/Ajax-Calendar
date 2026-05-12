const state = {
    currentDate: new Date(),
    view: 'month',
    countries: [],
    selectedCountry: '',
    events: [],
    user: null,
    datetimeFormat: 'eu'
};
let pendingOpenEventId = null;
let langMenuOpen = false;
let activeViewedEvent = null;

const LANGUAGES = [
    { code: 'cs', name: 'Czech', countryIso: 'cz' },
    { code: 'en', name: 'English', countryIso: 'gb' },
    { code: 'fr', name: 'French', countryIso: 'fr' },
    { code: 'de', name: 'German', countryIso: 'de' },
    { code: 'hu', name: 'Hungarian', countryIso: 'hu' },
    { code: 'it', name: 'Italian', countryIso: 'it' },
    { code: 'ro', name: 'Romanian', countryIso: 'ro' },
    { code: 'sk', name: 'Slovak', countryIso: 'sk' },
    { code: 'es', name: 'Spanish', countryIso: 'es' }
].sort((a, b) => a.name.localeCompare(b.name));

const I18N = {
    en: { eventCalendar: 'Event Calendar', prev: 'Previous', today: 'Today', next: 'Next', newEvent: 'New Event', share: 'Share', copied: 'Copied', copyFailed: 'Copy failed', eventLanguage: 'Event language', interpretation: 'Interpretation' },
    de: { eventCalendar: 'Ereigniskalender', prev: 'Zurück', today: 'Heute', next: 'Weiter', newEvent: 'Neues Ereignis', share: 'Teilen', copied: 'Kopiert', copyFailed: 'Fehlgeschlagen', eventLanguage: 'Veranstaltungssprache', interpretation: 'Dolmetschen' },
    it: { eventCalendar: 'Calendario Eventi', prev: 'Precedente', today: 'Oggi', next: 'Successivo', newEvent: 'Nuovo Evento', share: 'Condividi', copied: 'Copiato', copyFailed: 'Errore copia', eventLanguage: "Lingua dell'evento", interpretation: 'Interpretazione' },
    es: { eventCalendar: 'Calendario de Eventos', prev: 'Anterior', today: 'Hoy', next: 'Siguiente', newEvent: 'Nuevo Evento', share: 'Compartir', copied: 'Copiado', copyFailed: 'Error al copiar', eventLanguage: 'Idioma del evento', interpretation: 'Interpretación' },
    fr: { eventCalendar: 'Calendrier des Événements', prev: 'Précédent', today: "Aujourd'hui", next: 'Suivant', newEvent: 'Nouvel Événement', share: 'Partager', copied: 'Copié', copyFailed: 'Échec copie', eventLanguage: "Langue de l'événement", interpretation: 'Interprétation' },
    hu: { eventCalendar: 'Eseménynaptár', prev: 'Előző', today: 'Ma', next: 'Következő', newEvent: 'Új Esemény', share: 'Megosztás', copied: 'Másolva', copyFailed: 'Sikertelen', eventLanguage: 'Esemény nyelve', interpretation: 'Tolmácsolás' },
    ro: { eventCalendar: 'Calendar Evenimente', prev: 'Anterior', today: 'Astăzi', next: 'Următor', newEvent: 'Eveniment Nou', share: 'Distribuie', copied: 'Copiat', copyFailed: 'Eroare copiere', eventLanguage: 'Limba evenimentului', interpretation: 'Interpretare' },
    sk: { eventCalendar: 'Kalendár Udalostí', prev: 'Predchádzajúci', today: 'Dnes', next: 'Ďalší', newEvent: 'Nová Udalosť', share: 'Zdieľať', copied: 'Skopírované', copyFailed: 'Chyba kopírovania', eventLanguage: 'Jazyk udalosti', interpretation: 'Tlmočenie' },
    cs: { eventCalendar: 'Kalendář Událostí', prev: 'Předchozí', today: 'Dnes', next: 'Další', newEvent: 'Nová Událost', share: 'Sdílet', copied: 'Zkopírováno', copyFailed: 'Chyba kopírování', eventLanguage: 'Jazyk události', interpretation: 'Tlumočení' }
};

function getLang() {
    const saved = localStorage.getItem('app_lang') || 'en';
    return I18N[saved] ? saved : 'en';
}

function t(key) {
    const lang = getLang();
    return I18N[lang]?.[key] || I18N.en[key] || key;
}

function showErrorWindow(message) {
    const text = byId('errorDialogText');
    text.value = String(message || 'Unknown error');
    byId('errorDialog').showModal();
}

const byId = (id) => document.getElementById(id);
const fmtDate = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

function parseSqlLocal(iso) {
    if (!iso) return null;
    const m = String(iso).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/);
    if (!m) return new Date(iso);
    return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]), Number(m[4]), Number(m[5]), Number(m[6] || 0), 0);
}

function formatDateTimeByPreference(d) {
    if (!(d instanceof Date) || Number.isNaN(d.getTime())) return '';
    if (state.datetimeFormat === 'eu') {
        return `${String(d.getDate()).padStart(2, '0')}.${String(d.getMonth() + 1).padStart(2, '0')}.${d.getFullYear()} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
    }
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    const yyyy = d.getFullYear();
    const hhRaw = d.getHours();
    const mmn = String(d.getMinutes()).padStart(2, '0');
    const ampm = hhRaw >= 12 ? 'PM' : 'AM';
    const hh = String(((hhRaw + 11) % 12) + 1).padStart(2, '0');
    return `${mm}/${dd}/${yyyy} ${hh}:${mmn} ${ampm}`;
}

function formatEventTimeRange(startIso, endIso) {
    const s = parseSqlLocal(startIso);
    const e = parseSqlLocal(endIso);
    if (!s || !e) return '';
    const sameDay = s.getFullYear() === e.getFullYear() && s.getMonth() === e.getMonth() && s.getDate() === e.getDate();
    if (state.datetimeFormat === 'eu') {
        if (sameDay) {
            return `${String(s.getDate()).padStart(2, '0')}.${String(s.getMonth() + 1).padStart(2, '0')}.${s.getFullYear()} ${String(s.getHours()).padStart(2, '0')}:${String(s.getMinutes()).padStart(2, '0')}-${String(e.getHours()).padStart(2, '0')}:${String(e.getMinutes()).padStart(2, '0')}`;
        }
        return `Start: ${String(s.getDate()).padStart(2, '0')}.${String(s.getMonth() + 1).padStart(2, '0')}.${s.getFullYear()}, ${String(s.getHours()).padStart(2, '0')}:${String(s.getMinutes()).padStart(2, '0')}\nEnd: ${String(e.getDate()).padStart(2, '0')}.${String(e.getMonth() + 1).padStart(2, '0')}.${e.getFullYear()}, ${String(e.getHours()).padStart(2, '0')}:${String(e.getMinutes()).padStart(2, '0')}`;
    }
    if (sameDay) {
        return `${formatDateTimeByPreference(s)}-${String(((e.getHours() + 11) % 12) + 1).padStart(2, '0')}:${String(e.getMinutes()).padStart(2, '0')} ${e.getHours() >= 12 ? 'PM' : 'AM'}`;
    }
    return `Start: ${formatDateTimeByPreference(s)}\nEnd: ${formatDateTimeByPreference(e)}`;
}

function parseDateInput(value) {
    const v = String(value || '').trim();
    if (!v) return null;
    if (state.datetimeFormat === 'eu') {
        const m = v.match(/^(\d{1,2})[./](\d{1,2})[./](\d{4})\s+(\d{1,2}):(\d{2})$/);
        if (!m) return null;
        return new Date(Number(m[3]), Number(m[2]) - 1, Number(m[1]), Number(m[4]), Number(m[5]), 0, 0);
    }
    const m = v.match(/^(\d{1,2})[./](\d{1,2})[./](\d{4})\s+(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!m) return null;
    let h = Number(m[4]) % 12;
    if (m[6].toUpperCase() === 'PM') h += 12;
    return new Date(Number(m[3]), Number(m[1]) - 1, Number(m[2]), h, Number(m[5]), 0, 0);
}

function toSqlDateTime(d) {
    return `${fmtDate(d)} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}:00`;
}

function toLocalInputValue(d) {
    return `${fmtDate(d)}T${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}

function countryFlagHtml(code, cls = '') {
    const norm = String(code || '').trim().toLowerCase();
    if (norm === 'dach') {
        return [
            `<span class="flag-chip ${cls}"><img src="https://flagcdn.com/w40/de.png" alt="DE"></span>`,
            `<span class="flag-chip ${cls}"><img src="https://flagcdn.com/w40/at.png" alt="AT"></span>`,
            `<span class="flag-chip ${cls}"><img src="https://flagcdn.com/w40/ch.png" alt="CH"></span>`
        ].join('');
    }
    if (/^[a-z]{2}$/.test(norm)) {
        return `<span class="flag-chip ${cls}"><img src="https://flagcdn.com/w40/${norm}.png" alt="${norm.toUpperCase()}"></span>`;
    }
    return `<span class="flag-chip ${cls}">${String(code || '?')}</span>`;
}

function langFlagImg(iso, alt) {
    const safeIso = String(iso || '').toLowerCase();
    const safeAlt = String(alt || '').replace(/"/g, '&quot;');
    return `<img src="https://flagcdn.com/w40/${safeIso}.png" alt="${safeAlt}">`;
}

function renderLanguagePicker() {
    const current = LANGUAGES.find((l) => l.code === getLang()) || LANGUAGES.find((l) => l.code === 'en');
    const menu = langMenuOpen ? `<div class="lang-menu">${LANGUAGES.map((l) => `<button class="lang-item" data-lang="${l.code}" title="${l.name}">${langFlagImg(l.countryIso, l.name)}</button>`).join('')}</div>` : '';
    byId('langBlock').innerHTML = `<button id="langToggleBtn" class="lang-toggle" title="Language">${langFlagImg(current.countryIso, current.name)}</button>${menu}`;
    byId('langToggleBtn').onclick = () => {
        langMenuOpen = !langMenuOpen;
        renderLanguagePicker();
    };
    byId('langBlock').querySelectorAll('.lang-item').forEach((el) => {
        el.addEventListener('click', async () => {
            localStorage.setItem('app_lang', el.dataset.lang);
            langMenuOpen = false;
            applyI18nTexts();
            renderLanguagePicker();
            if (activeViewedEvent && byId('eventViewDialog').open) {
                openEventView(activeViewedEvent);
            }
            await refreshCalendar();
        });
    });
}

function applyI18nTexts() {
    document.title = 'Immunotec Zoom and Event calendar';
    byId('appTitle').textContent = t('eventCalendar');
    byId('prevBtn').textContent = t('prev');
    byId('todayBtn').textContent = t('today');
    byId('nextBtn').textContent = t('next');
    byId('newEventBtn').textContent = t('newEvent');
    const shareBtn = byId('shareEventBtn');
    if (shareBtn) {
        const txt = shareBtn.textContent.trim();
        if (txt === '' || Object.values(I18N).some((d) => [d.share, d.copied, d.copyFailed].includes(txt))) {
            shareBtn.textContent = t('share');
        }
    }
    const isEu = state.datetimeFormat === 'eu';
    byId('eventStart').placeholder = isEu ? 'DD/MM/YYYY HH:MM' : 'MM/DD/YYYY HH:MM AM';
    byId('eventEnd').placeholder = isEu ? 'DD/MM/YYYY HH:MM' : 'MM/DD/YYYY HH:MM AM';
    if (byId('eventRecurrenceUntil')) {
        byId('eventRecurrenceUntil').placeholder = isEu ? 'DD/MM/YYYY HH:MM' : 'MM/DD/YYYY HH:MM AM';
    }
}

function countriesFlagsRow(codes) {
    const arr = Array.isArray(codes) ? codes : [];
    if (!arr.length) return '';
    return `<div class="flag-row">${arr.map((c) => countryFlagHtml(c)).join('')}</div>`;
}

async function api(path, options = {}) {
    const response = await fetch(path, { headers: { 'Content-Type': 'application/json' }, ...options });
    const raw = await response.text();
    let data = null;
    try {
        data = raw ? JSON.parse(raw) : {};
    } catch (err) {
        throw new Error(`Invalid JSON response (${response.status}) from ${path}: ${raw.slice(0, 200)}`);
    }
    if (!response.ok || data.success === false) throw new Error(data.message || 'Request failed');
    return data;
}

function getRange() {
    const d = new Date(state.currentDate);
    if (state.view === 'day') return { start: new Date(d.getFullYear(), d.getMonth(), d.getDate()), end: new Date(d.getFullYear(), d.getMonth(), d.getDate(), 23, 59, 59) };
    if (state.view === 'week') {
        const day = d.getDay();
        const start = new Date(d);
        start.setDate(d.getDate() - day);
        const end = new Date(start);
        end.setDate(start.getDate() + 6);
        end.setHours(23, 59, 59, 0);
        return { start, end };
    }
    if (state.view === 'month') return { start: new Date(d.getFullYear(), d.getMonth(), 1), end: new Date(d.getFullYear(), d.getMonth() + 1, 0, 23, 59, 59) };
    if (state.view === 'year') return { start: new Date(d.getFullYear(), 0, 1), end: new Date(d.getFullYear(), 11, 31, 23, 59, 59) };
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
    byId('rangeLabel').textContent = `${formatDateTimeByPreference(start).split(' ')[0]} - ${formatDateTimeByPreference(end).split(' ')[0]}`;
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

function updateEventUrl(id) {
    const url = new URL(window.location.href);
    if (id) url.searchParams.set('event', String(id));
    else url.searchParams.delete('event');
    window.history.replaceState({}, '', url.toString());
}

function openEventView(eventItem) {
    if (!eventItem) return;
    activeViewedEvent = eventItem;
    const dlg = byId('eventViewDialog');
    const hero = byId('eventViewHero');
    if (eventItem.image_path) hero.innerHTML = `<img src="${eventItem.image_path}" alt="${eventItem.title || 'Event image'}">`;
    else hero.innerHTML = `<div class="event-view-fallback">${eventItem.title || 'Event'}</div>`;

    byId('eventViewTitle').textContent = eventItem.title || 'Event';
    byId('eventViewCountriesRow').innerHTML = countriesFlagsRow(eventItem.country_codes || []);
    byId('eventViewCountriesRow').className = 'event-countries-inline';
    byId('eventViewMeta').textContent = formatEventTimeRange(eventItem.start_at, eventItem.end_at);
    byId('eventViewMeta').style.whiteSpace = 'pre-line';
    byId('eventViewDescription').textContent = eventItem.description || '';
    byId('eventViewLinkWrap').innerHTML = eventItem.event_link ? `<a href="${eventItem.event_link}" target="_blank" rel="noopener">${eventItem.event_link}</a>` : '';

    const languageFlag = eventItem.event_language_country_code ? `<div><strong>${t('eventLanguage')}:</strong> <span class="flag-row">${countryFlagHtml(eventItem.event_language_country_code, 'main-language')}</span></div>` : '';
    const interpFlags = Array.isArray(eventItem.interpretation_country_codes) && eventItem.interpretation_country_codes.length
        ? `<div><strong>${t('interpretation')}:</strong> ${countriesFlagsRow(eventItem.interpretation_country_codes)}</div>` : '';
    byId('eventViewQrWrap').innerHTML = `${languageFlag}${interpFlags}`;
    const qrImg = byId('eventViewQrImg');
    if (eventItem.event_link) {
        qrImg.src = `https://quickchart.io/qr?size=110&text=${encodeURIComponent(eventItem.event_link)}`;
        qrImg.style.display = 'block';
    } else {
        qrImg.style.display = 'none';
        qrImg.removeAttribute('src');
    }
    byId('eventViewAuthor').textContent = `by ${eventItem.creator_name || eventItem.username || 'Unknown'}`;

    byId('shareEventBtn').onclick = async () => {
        const btn = byId('shareEventBtn');
        const url = new URL(window.location.href);
        url.searchParams.set('event', String(eventItem.id));
        try {
            await navigator.clipboard.writeText(url.toString());
            btn.textContent = t('copied');
            setTimeout(() => { btn.textContent = t('share'); }, 2200);
        } catch (err) {
            btn.textContent = t('copyFailed');
            setTimeout(() => { btn.textContent = t('share'); }, 2200);
        }
    };
    byId('shareEventBtn').textContent = t('share');
    updateEventUrl(eventItem.id);
    dlg.showModal();
}

function fillDateField(fieldId, pickerId, dateObj) {
    if (!dateObj) {
        byId(fieldId).value = '';
        byId(pickerId).value = '';
        return;
    }
    byId(fieldId).value = formatDateTimeByPreference(dateObj);
    byId(pickerId).value = toLocalInputValue(dateObj);
}

function updateRecurrenceVisibility() {
    const show = byId('eventRecurrenceType').value === 'monthly_nth_weekday';
    byId('recurrenceMonthlyWrap').style.display = show ? 'grid' : 'none';
}

function openEventDialog(eventItem = null, prefillDate = null) {
    if (!eventItem && !state.user) return;
    if (eventItem && (!state.user || !eventItem.can_edit)) return openEventView(eventItem);

    byId('eventDialogTitle').textContent = eventItem ? 'Edit Event' : 'New Event';
    byId('eventId').value = eventItem ? eventItem.id : '';
    byId('eventTitle').value = eventItem?.title || '';
    byId('eventDescription').value = eventItem?.description || '';
    byId('eventLink').value = eventItem?.event_link || '';

    const selectedCountries = new Set((eventItem?.country_ids || []).map((v) => String(v)));
    if (!selectedCountries.size && (state.user?.country_id || state.selectedCountry)) {
        selectedCountries.add(String(state.user?.country_id || state.selectedCountry));
    }
    Array.from(byId('eventCountry').options).forEach((opt) => { opt.selected = selectedCountries.has(opt.value); });

    const selectedInterp = new Set((eventItem?.interpretation_country_codes || []).map((code) => String(code)));
    Array.from(byId('eventInterpretationCountries').options).forEach((opt) => { opt.selected = selectedInterp.has(opt.dataset.code || ''); });

    byId('eventLanguageCountry').value = eventItem?.event_language_country_code || '';
    byId('eventRecurrenceType').value = eventItem?.recurrence_type || 'none';
    byId('eventRecurWeek').value = String(eventItem?.recur_week || 1);
    byId('eventRecurWeekday').value = String(eventItem?.recur_weekday ?? 1);

    if (eventItem) {
        fillDateField('eventStart', 'eventStartPicker', parseSqlLocal(eventItem.start_at));
        fillDateField('eventEnd', 'eventEndPicker', parseSqlLocal(eventItem.end_at));
        fillDateField('eventRecurrenceUntil', 'eventRecurrenceUntilPicker', parseSqlLocal(eventItem.recurrence_until || ''));
    } else if (prefillDate instanceof Date && !Number.isNaN(prefillDate.getTime())) {
        const startSeed = new Date(prefillDate.getFullYear(), prefillDate.getMonth(), prefillDate.getDate(), 9, 0, 0, 0);
        const endSeed = new Date(prefillDate.getFullYear(), prefillDate.getMonth(), prefillDate.getDate(), 10, 0, 0, 0);
        fillDateField('eventStart', 'eventStartPicker', startSeed);
        fillDateField('eventEnd', 'eventEndPicker', endSeed);
        byId('eventRecurrenceUntil').value = '';
        byId('eventRecurrenceUntilPicker').value = '';
    } else {
        byId('eventStart').value = '';
        byId('eventEnd').value = '';
        byId('eventStartPicker').value = '';
        byId('eventEndPicker').value = '';
        byId('eventRecurrenceUntil').value = '';
        byId('eventRecurrenceUntilPicker').value = '';
    }

    byId('deleteEventBtn').hidden = !(eventItem && eventItem.can_edit);
    updateRecurrenceVisibility();
    byId('eventDialog').showModal();
}

function renderList(events) {
    const root = byId('calendarRoot');
    root.innerHTML = '';
    const wrap = document.createElement('div');
    wrap.className = 'list-view';
    events.forEach((e) => {
        const card = document.createElement('article');
        card.className = 'event-card is-clickable';
        card.innerHTML = `<h4>${e.title}</h4>
            <p class="event-meta">${formatEventTimeRange(e.start_at, e.end_at).replace('\n', ' | ')}</p>
            <p>${e.description || ''}</p>`;
        card.addEventListener('click', () => openEventDialog(e));
        wrap.appendChild(card);
    });
    if (!events.length) wrap.innerHTML = '<p>No events in this range and category.</p>';
    root.appendChild(wrap);
}

function renderMonthLike(anchor) {
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
    monthGrid(anchor).forEach((d) => {
        const cell = document.createElement('div');
        cell.className = 'day-cell';
        if (d.getMonth() !== anchor.getMonth()) cell.classList.add('other');
        const num = document.createElement('div');
        num.className = 'day-num';
        num.textContent = String(d.getDate());
        cell.appendChild(num);
        eventsForDate(d).slice(0, 4).forEach((e) => {
            const pill = document.createElement('div');
            pill.className = 'event-pill';
            const st = parseSqlLocal(e.start_at);
            pill.textContent = `${String(st.getHours()).padStart(2, '0')}:${String(st.getMinutes()).padStart(2, '0')} ${e.title}`;
            pill.addEventListener('click', () => openEventDialog(e));
            cell.appendChild(pill);
        });
        cell.addEventListener('dblclick', () => openEventDialog(null, d));
        grid.appendChild(cell);
    });
    root.appendChild(grid);
}

function renderYear() { renderList(state.events); }
function renderWeek() { renderList(state.events); }
function renderDay() { renderList(eventsForDate(state.currentDate)); }

function renderView() {
    updateRangeLabel();
    if (state.view === 'month') renderMonthLike(state.currentDate);
    else if (state.view === 'year') renderYear();
    else if (state.view === 'week') renderWeek();
    else if (state.view === 'day') renderDay();
    else renderList(state.events);
}

async function loadSession() {
    const data = await api('includes/api/auth_session.php');
    state.user = data.user;
    state.datetimeFormat = data.user?.datetime_format === 'us' ? 'us' : 'eu';
    const auth = byId('authBlock');
    if (!state.user) {
        auth.innerHTML = '';
        byId('newEventBtn').hidden = true;
    } else {
        auth.innerHTML = `<button id="openProfileBtn"><strong>${state.user.username}</strong> (${state.user.role})</button> <button id="logoutBtn">Logout</button>`;
        byId('openProfileBtn').onclick = () => {
            byId('profileFirstName').value = state.user.first_name || '';
            byId('profileLastName').value = state.user.last_name || '';
            byId('profileDatetimeFormat').value = state.datetimeFormat;
            byId('profileDialog').showModal();
        };
        byId('logoutBtn').onclick = async () => {
            await api('includes/api/auth_logout.php', { method: 'POST', body: '{}' });
            await bootstrap();
        };
        byId('newEventBtn').hidden = false;
    }
    renderLanguagePicker();
    applyI18nTexts();
}

async function loadCountries() {
    const data = await api('includes/api/countries.php');
    state.countries = data.countries;
    byId('countryFilter').innerHTML = ['<option value="">All Countries</option>', ...state.countries.map((c) => `<option value="${c.id}">${c.name}</option>`)].join('');
    byId('eventCountry').innerHTML = state.countries.map((c) => `<option value="${c.id}" data-code="${c.code}">${c.name}</option>`).join('');
    byId('eventLanguageCountry').innerHTML = ['<option value="">Select language</option>', ...state.countries.map((c) => `<option value="${c.code}">${c.name}</option>`)].join('');
    byId('eventInterpretationCountries').innerHTML = state.countries.map((c) => `<option value="${c.id}" data-code="${c.code}">${c.name}</option>`).join('');
    byId('signupCountry').innerHTML = state.countries.map((c) => `<option value="${c.id}">${c.name}</option>`).join('');
}

async function loadEvents() {
    let { start, end } = getRange();
    if (state.view === 'month') {
        const days = monthGrid(state.currentDate);
        start = days[0];
        end = days[days.length - 1];
    }
    const params = new URLSearchParams({ start: fmtDate(start), end: fmtDate(end) });
    if (state.selectedCountry) params.set('country_id', state.selectedCountry);
    state.events = (await api(`includes/api/events_list.php?${params.toString()}`)).events;
    if (pendingOpenEventId !== null) {
        const match = state.events.find((e) => Number(e.id) === Number(pendingOpenEventId));
        if (match) openEventView(match);
        else {
            try {
                const one = await api(`includes/api/event_get.php?id=${encodeURIComponent(String(pendingOpenEventId))}`);
                if (one.event) openEventView(one.event);
            } catch (err) { /* ignore */ }
        }
        pendingOpenEventId = null;
    }
}

async function refreshCalendar() { await loadEvents(); renderView(); }
async function bootstrap() { await loadSession(); await loadCountries(); await refreshCalendar(); }

function wireDateInput(textId, pickerId, btnId) {
    const txt = byId(textId);
    const picker = byId(pickerId);
    byId(btnId).addEventListener('click', () => { if (picker.showPicker) picker.showPicker(); else picker.focus(); });
    txt.addEventListener('focus', () => { if (picker.showPicker) picker.showPicker(); });
    picker.addEventListener('change', () => {
        const d = parseSqlLocal(picker.value.replace('T', ' ') + ':00');
        if (d) txt.value = formatDateTimeByPreference(d);
    });
}

byId('viewSelect').addEventListener('change', async (e) => { state.view = e.target.value; await refreshCalendar(); });
byId('countryFilter').addEventListener('change', async (e) => { state.selectedCountry = e.target.value; await refreshCalendar(); });
byId('prevBtn').addEventListener('click', async () => { stepDate(-1); await refreshCalendar(); });
byId('nextBtn').addEventListener('click', async () => { stepDate(1); await refreshCalendar(); });
byId('todayBtn').addEventListener('click', async () => { state.currentDate = new Date(); await refreshCalendar(); });
byId('newEventBtn').addEventListener('click', () => openEventDialog());
byId('cancelEventBtn').addEventListener('click', () => byId('eventDialog').close());
byId('closeEventViewIconBtn').addEventListener('click', () => byId('eventViewDialog').close());
byId('eventViewDialog').addEventListener('close', () => updateEventUrl(null));
byId('eventViewDialog').addEventListener('close', () => { activeViewedEvent = null; });
byId('cancelProfileBtn').addEventListener('click', () => byId('profileDialog').close());
byId('closeErrorBtn').addEventListener('click', () => byId('errorDialog').close());
byId('copyErrorBtn').addEventListener('click', async () => {
    const txt = byId('errorDialogText').value || '';
    try {
        await navigator.clipboard.writeText(txt);
    } catch (err) {
        // keep silent; user can still select and copy manually
    }
});

wireDateInput('eventStart', 'eventStartPicker', 'eventStartPickBtn');
wireDateInput('eventEnd', 'eventEndPicker', 'eventEndPickBtn');
wireDateInput('eventRecurrenceUntil', 'eventRecurrenceUntilPicker', 'eventRecurrenceUntilPickBtn');
byId('eventRecurrenceType').addEventListener('change', updateRecurrenceVisibility);

byId('profileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    await api('includes/api/profile_update.php', {
        method: 'POST',
        body: JSON.stringify({
            first_name: byId('profileFirstName').value.trim(),
            last_name: byId('profileLastName').value.trim(),
            datetime_format: byId('profileDatetimeFormat').value
        })
    });
    byId('profileDialog').close();
    await bootstrap();
});

byId('eventForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
        const startDate = parseDateInput(byId('eventStart').value);
        const endDate = parseDateInput(byId('eventEnd').value);
        if (!startDate || !endDate) throw new Error('Invalid date/time format');
        const countryIds = Array.from(byId('eventCountry').selectedOptions).map((o) => Number(o.value)).filter((n) => n > 0);
        if (!countryIds.length) throw new Error('Select at least one country');
        const interpIds = Array.from(byId('eventInterpretationCountries').selectedOptions).map((o) => Number(o.value)).filter((n) => n > 0);
        const recurrenceType = byId('eventRecurrenceType').value;
        const recurrenceUntilInput = byId('eventRecurrenceUntil').value.trim();
        const recurrenceUntilDate = recurrenceUntilInput ? parseDateInput(recurrenceUntilInput) : null;
        if (recurrenceUntilInput && !recurrenceUntilDate) throw new Error('Invalid recurrence end date/time format');
        const form = new FormData();
        if (byId('eventId').value) form.append('id', byId('eventId').value);
        form.append('title', byId('eventTitle').value.trim());
        form.append('description', byId('eventDescription').value.trim());
        form.append('event_link', byId('eventLink').value.trim());
        form.append('country_ids', JSON.stringify(countryIds));
        form.append('event_language_country_id', String((state.countries.find((c) => c.code === byId('eventLanguageCountry').value) || {}).id || ''));
        form.append('interpretation_country_ids', JSON.stringify(interpIds));
        form.append('start_at', toSqlDateTime(startDate));
        form.append('end_at', toSqlDateTime(endDate));
        form.append('recurrence_type', recurrenceType);
        if (recurrenceType === 'monthly_nth_weekday') {
            form.append('recur_week', byId('eventRecurWeek').value);
            form.append('recur_weekday', byId('eventRecurWeekday').value);
            form.append('recurrence_until', recurrenceUntilDate ? toSqlDateTime(recurrenceUntilDate) : '');
        }
        const img = byId('eventImage').files[0];
        if (img) form.append('event_image', img);
        const response = await fetch('includes/api/event_save.php', { method: 'POST', body: form });
        const raw = await response.text();
        let data = {};
        try {
            data = raw ? JSON.parse(raw) : {};
        } catch (err) {
            throw new Error(`Invalid server response (${response.status}): ${raw.slice(0, 220)}`);
        }
        if (!response.ok || data.success === false) throw new Error(data.message || 'Request failed');
        byId('eventDialog').close();
        await refreshCalendar();
    } catch (err) {
        showErrorWindow(err.message || 'Could not save event');
    }
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
        await api('includes/api/auth_login.php', { method: 'POST', body: JSON.stringify({ username: byId('loginUsername').value, password: byId('loginPassword').value }) });
        byId('authDialog').close();
        await bootstrap();
    } catch (err) { byId('authMessage').textContent = err.message; }
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
    } catch (err) { byId('authMessage').textContent = err.message; }
});

bootstrap().catch((err) => { byId('calendarRoot').innerHTML = `<p>Initialization failed: ${err.message}</p>`; });
(() => {
    const eventId = new URL(window.location.href).searchParams.get('event');
    if (eventId) pendingOpenEventId = Number(eventId);
})();
