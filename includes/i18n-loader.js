(function () {
    const SUPPORTED = ['en', 'de', 'it', 'es', 'fr', 'hu', 'pt', 'ro', 'sk'];
    const loaded = new Set();

    function normalizeLang(lang) {
        const v = String(lang || 'en').toLowerCase();
        return SUPPORTED.includes(v) ? v : 'en';
    }

    function loadScript(src) {
        return new Promise((resolve, reject) => {
            const existing = document.querySelector(`script[data-i18n-src="${src}"]`);
            if (existing) {
                if (existing.dataset.loaded === '1') resolve();
                else existing.addEventListener('load', () => resolve(), { once: true });
                return;
            }
            const s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.dataset.i18nSrc = src;
            s.addEventListener('load', () => {
                s.dataset.loaded = '1';
                resolve();
            }, { once: true });
            s.addEventListener('error', () => reject(new Error(`Failed to load ${src}`)), { once: true });
            document.head.appendChild(s);
        });
    }

    async function ensureLanguage(lang) {
        const code = normalizeLang(lang);
        if (loaded.has(code)) return code;
        const base = String(window.APP_I18N_BASE || 'includes/i18n/').replace(/\\/g, '/');
        await loadScript(`${base}${code}.js`);
        const bundle = window.APP_I18N_LANG && window.APP_I18N_LANG[code];
        if (bundle && window.i18next) {
            const hasBundle = typeof window.i18next.getResourceBundle === 'function'
                ? !!window.i18next.getResourceBundle(code, 'translation')
                : false;
            if (!hasBundle && typeof window.i18next.addResourceBundle === 'function') {
                window.i18next.addResourceBundle(code, 'translation', bundle, true, true);
            }
        }
        loaded.add(code);
        return code;
    }

    async function initI18n(lang) {
        if (!window.i18next) throw new Error('i18next is not available');
        const code = await ensureLanguage(lang || 'en');
        const resources = {};
        const currentBundle = window.APP_I18N_LANG && window.APP_I18N_LANG[code];
        if (currentBundle) resources[code] = { translation: currentBundle };
        if (code !== 'en') {
            await ensureLanguage('en');
            const enBundle = window.APP_I18N_LANG && window.APP_I18N_LANG.en;
            if (enBundle) resources.en = { translation: enBundle };
        }
        if (!window.i18next.isInitialized) {
            await window.i18next.init({
                lng: code,
                fallbackLng: 'en',
                defaultNS: 'translation',
                resources,
                interpolation: { escapeValue: false }
            });
        } else {
            if (typeof window.i18next.addResourceBundle === 'function') {
                Object.keys(resources).forEach((lng) => {
                    window.i18next.addResourceBundle(lng, 'translation', resources[lng].translation || {}, true, true);
                });
            }
            if (window.i18next.language !== code) {
                await window.i18next.changeLanguage(code);
            }
        }
        return code;
    }

    window.AppI18n = {
        supported: SUPPORTED.slice(),
        normalizeLang,
        ensureLanguage,
        initI18n
    };
})();
