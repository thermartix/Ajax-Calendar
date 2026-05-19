(function () {
    const byId = (id) => document.getElementById(id);
    const getLang = () => (window.AppI18n ? window.AppI18n.normalizeLang(localStorage.getItem('app_lang') || 'en') : 'en');
    const t = (k) => (window.i18next && window.i18next.isInitialized ? window.i18next.t(k) : k);
    const esc = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#39;');

    async function api(path) {
        const res = await fetch(path, { credentials: 'same-origin' });
        const txt = await res.text();
        const data = txt ? JSON.parse(txt) : {};
        if (!res.ok || data.success === false) throw new Error(data.message || t('requestFailed'));
        return data;
    }

    function fmt(dt) {
        if (!dt) return '';
        const d = new Date(String(dt).replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return String(dt);
        return d.toLocaleString();
    }

    async function init() {
        if (window.AppI18n) await window.AppI18n.initI18n(getLang());
        const backBtn = byId('speakerBackBtn');
        const recentTitle = byId('speakerRecentEventsTitle');
        if (backBtn) backBtn.textContent = t('backToCalendar');
        if (recentTitle) recentTitle.textContent = t('speakerRecentEvents');
        const url = new URL(window.location.href);
        const id = url.searchParams.get('id') || '';
        const slug = url.searchParams.get('slug') || '';
        const qs = new URLSearchParams();
        if (slug) qs.set('slug', slug);
        else qs.set('id', id);
        const data = await api(`includes/api/speaker_get.php?${qs.toString()}`);
        const s = data.speaker;
        byId('speakerName').textContent = s.name || t('speaker');
        byId('speakerBio').textContent = s.bio || '';
        byId('speakerImageWrap').innerHTML = s.profile_image_path ? `<img src="${esc(s.profile_image_path)}" alt="${esc(s.name || t('speaker'))}" style="max-width:280px; border-radius:10px;">` : '';
        byId('speakerEvents').innerHTML = (s.events || []).length
            ? s.events.map((e) => `<p><a href="./?event=${encodeURIComponent(String(e.id))}">${esc(e.title || t('event'))}</a><br><small>${esc(fmt(e.start_at))}</small></p>`).join('')
            : `<p>${esc(t('speakerNoEventsYet'))}</p>`;
    }

    init().catch((err) => {
        byId('speakerBio').textContent = err.message || t('speakerCouldNotLoad');
    });
})();
