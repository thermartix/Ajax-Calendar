const byId = (id) => document.getElementById(id);
const getLang = () => (window.AppI18n ? window.AppI18n.normalizeLang(localStorage.getItem('app_lang') || 'en') : 'en');
const t = (k) => (window.i18next && window.i18next.isInitialized ? window.i18next.t(k) : k);
const escHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\"/g, '&quot;')
    .replace(/'/g, '&#39;');

const state = { speakers: [], csrfToken: '', activeSpeaker: null, canManage: false };

async function api(path, options = {}) {
    const opts = { credentials: 'same-origin', ...options };
    const headers = new Headers(opts.headers || {});
    if (state.csrfToken && !headers.has('X-CSRF-Token')) headers.set('X-CSRF-Token', state.csrfToken);
    opts.headers = headers;
    const res = await fetch(path, opts);
    const txt = await res.text();
    let data = {};
    try { data = txt ? JSON.parse(txt) : {}; } catch (e) { throw new Error(t('invalidServerResponse')); }
    if (typeof data.csrf_token === 'string' && data.csrf_token) state.csrfToken = data.csrf_token;
    if (!res.ok || data.success === false) throw new Error(data.message || `HTTP ${res.status}`);
    return data;
}

async function loadSession() {
    const s = await api('includes/api/auth_session.php');
    const role = String(s?.user?.role || '');
    state.canManage = role === 'admin' || role === 'editor';
    if (!state.canManage) throw new Error(t('speakerManageAccessRequired'));
}

async function loadSpeakers() {
    const data = await api('includes/api/speakers.php');
    state.speakers = Array.isArray(data.speakers) ? data.speakers : [];
}

function shortBio(text) {
    const raw = String(text || '').trim();
    if (raw.length <= 140) return raw;
    return `${raw.slice(0, 137)}...`;
}

function renderCards() {
    const root = byId('speakerCards');
    if (!state.speakers.length) {
        root.innerHTML = `<p>${escHtml(t('speakerManageNoSpeakers'))}</p>`;
        return;
    }
    root.innerHTML = state.speakers.map((s) => `
        <article class="speaker-card" data-id="${s.id}">
            <div class="speaker-card-photo">${s.profile_image_path ? `<img src="${escHtml(s.profile_image_path)}" alt="${escHtml(s.name)}">` : `<span>${escHtml(t('speakerManageNoPhoto'))}</span>`}</div>
            <h4>${escHtml(s.name)}</h4>
            <p>${escHtml(shortBio(s.bio || ''))}</p>
        </article>
    `).join('');

    root.querySelectorAll('.speaker-card').forEach((el) => {
        el.addEventListener('click', () => {
            const id = Number(el.getAttribute('data-id') || 0);
            const speaker = state.speakers.find((x) => Number(x.id) === id);
            if (speaker) openEditor(speaker);
        });
    });
}

function renderVisitorPanel() {
    const s = state.activeSpeaker || {};
    byId('speakerVisitorPanel').innerHTML = `
        <article class="event-dialog-visitor-card">
            <div class="event-view">
                <div class="event-view-body">
                    <h3>${escHtml(s.name || '')}</h3>
                    ${s.profile_image_path ? `<p><img src="${escHtml(s.profile_image_path)}" alt="${escHtml(s.name || 'Speaker')}" style="max-width:260px; border-radius:12px;"></p>` : ''}
                    <p style="white-space:pre-line;">${escHtml(s.bio || '')}</p>
                </div>
            </div>
        </article>
    `;
}

function setTab(tab) {
    const edit = tab === 'edit';
    byId('speakerTabEdit').classList.toggle('is-active', edit);
    byId('speakerTabVisitor').classList.toggle('is-active', !edit);
    byId('speakerEditPanel').hidden = !edit;
    byId('speakerVisitorPanel').hidden = edit;
    if (!edit) renderVisitorPanel();
}

function syncDraft() {
    state.activeSpeaker = {
        ...(state.activeSpeaker || {}),
        id: Number(byId('speakerId').value || 0),
        name: byId('speakerName').value.trim(),
        profile_image_path: byId('speakerImage').value.trim(),
        bio: byId('speakerBio').value
    };
}

function fillForm(speaker) {
    state.activeSpeaker = { ...speaker };
    byId('speakerId').value = speaker?.id ? String(speaker.id) : '';
    byId('speakerName').value = speaker?.name || '';
    byId('speakerImage').value = speaker?.profile_image_path || '';
    byId('speakerBio').value = speaker?.bio || '';
}

function openEditor(speaker = null) {
    fillForm(speaker || { id: 0, name: '', bio: '', profile_image_path: '' });
    byId('speakerDialogTitle').textContent = speaker?.id ? t('speakerManageEditSpeaker') : t('speakerManageNewSpeaker');
    setTab('edit');
    byId('speakerEditorDialog').showModal();
}

async function saveSpeaker() {
    syncDraft();
    const s = state.activeSpeaker || {};
    await api('includes/api/speaker_save.php', {
        method: 'POST',
        body: JSON.stringify({
            id: s.id > 0 ? s.id : undefined,
            name: s.name || '',
            profile_image_path: s.profile_image_path || '',
            bio: s.bio || ''
        })
    });
    await loadSpeakers();
    renderCards();
    byId('speakerEditorDialog').close();
}

async function init() {
    if (window.AppI18n) await window.AppI18n.initI18n(getLang());
    const title = byId('speakerManageTitle');
    const subtitle = byId('speakerManageSubtitle');
    const backBtn = byId('speakerManageBackBtn');
    if (title) title.textContent = t('speakerManageTitle');
    if (subtitle) subtitle.textContent = t('speakerManageSubtitle');
    if (backBtn) backBtn.textContent = t('backToCalendar');
    byId('newSpeakerBtn').textContent = t('speakerManageNewSpeaker');
    await loadSession();
    await loadSpeakers();
    renderCards();

    byId('newSpeakerBtn').addEventListener('click', () => openEditor(null));
    byId('speakerTabEdit').addEventListener('click', () => setTab('edit'));
    byId('speakerTabVisitor').addEventListener('click', () => { syncDraft(); setTab('visitor'); });
    byId('speakerCancelBtn').addEventListener('click', () => byId('speakerEditorDialog').close());
    byId('speakerEditorForm').addEventListener('input', () => {
        if (!byId('speakerVisitorPanel').hidden) {
            syncDraft();
            renderVisitorPanel();
        }
    });
    byId('speakerEditorForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveSpeaker();
    });
}

init().catch((err) => {
    byId('speakerCards').innerHTML = `<p>${escHtml(err.message || t('failedToLoad'))}</p>`;
});
