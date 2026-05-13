const state = {
    currentDate: new Date(),
    view: 'month',
    countries: [],
    selectedCountry: '',
    selectedLanguage: '',
    allEvents: [],
    events: [],
    user: null,
    datetimeFormat: 'eu',
    showEventAuthor: true
};
const EVENT_LANGUAGE_DEFS = [
    { code: 'en', name: 'English', flagIso: 'gb' },
    { code: 'fr', name: 'French', flagIso: 'fr' },
    { code: 'de', name: 'German', flagIso: 'de' },
    { code: 'hu', name: 'Hungarian', flagIso: 'hu' },
    { code: 'it', name: 'Italian', flagIso: 'it' },
    { code: 'ro', name: 'Romanian', flagIso: 'ro' },
    { code: 'es', name: 'Spanish', flagIso: 'es' },
    { code: 'pt', name: 'Portuguese', flagIso: 'pt' },
    { code: 'sk', name: 'Slovak', flagIso: 'sk' }
];
let pendingOpenEventId = null;
let langMenuOpen = false;
let activeViewedEvent = null;

const LANGUAGES = [
    { code: 'en', name: 'English', countryIso: 'gb' },
    { code: 'fr', name: 'French', countryIso: 'fr' },
    { code: 'de', name: 'German', countryIso: 'de' },
    { code: 'hu', name: 'Hungarian', countryIso: 'hu' },
    { code: 'it', name: 'Italian', countryIso: 'it' },
    { code: 'pt', name: 'Portuguese', countryIso: 'pt' },
    { code: 'ro', name: 'Romanian', countryIso: 'ro' },
    { code: 'sk', name: 'Slovak', countryIso: 'sk' },
    { code: 'es', name: 'Spanish', countryIso: 'es' }
].sort((a, b) => a.name.localeCompare(b.name));

const I18N = {
    en: { eventCalendar: 'Event Calendar', prev: 'Previous', today: 'Today', next: 'Next', newEvent: 'New Event', share: 'Share', copied: 'Copied', copyFailed: 'Copy failed', eventLanguage: 'Event language', interpretation: 'Interpretation', consultantMeeting: 'consultant meeting', consultantTraining: 'consultant training', consultantMeetingTrainingShort: 'consultant meeting / training', customersGuestsWelcome: 'customers and guests welcome', onlineEvent: 'Online Event', offlineEvent: 'Offline Event', venueAddress: 'Venue Address', ticketUrl: 'Ticket URL', getTicketNow: 'Get your ticket now!', soldOutLabel: 'Sold out', soldOutNo: 'Available', soldOutYes: 'Sold out', soldOutBadge: 'SOLD OUT', pastEvent: 'past event', audience: 'Audience', customersGuests: 'Guests & customers', consultantsMeeting: 'Consultant meeting', consultantsTraining: 'Consultant training', eventMode: 'Event Mode', selectMode: 'Select mode', meetingLink: 'Meeting Link', zoomLink: 'Zoom link', onlineZoomEvent: 'online Zoom event', inPersonMeeting: 'in person meeting', onlineZoomMeetingForConsultants: 'online Zoom meeting for consultants', onlineZoomTrainingForConsultants: 'online Zoom training for consultants', onlineZoomEventForGuestsCustomers: 'online Zoom event for guests and customers', inPersonMeetingForConsultants: 'in person meeting for consultants', inPersonTrainingForConsultants: 'in person training for consultants', inPersonEventForGuestsCustomers: 'in person event for guests and customers', guestsCustomersOverlay: 'Guests and customers welcome.' },
    de: { eventCalendar: 'Ereigniskalender', prev: 'Zurück', today: 'Heute', next: 'Weiter', newEvent: 'Neues Ereignis', share: 'Teilen', copied: 'Kopiert', copyFailed: 'Fehlgeschlagen', eventLanguage: 'Veranstaltungssprache', interpretation: 'Es wird in die folgenden Sprachen übersetzt:', consultantMeeting: 'Beratermeeting', consultantTraining: 'Beratertraining', consultantMeetingTrainingShort: 'Beratermeeting/Training', customersGuestsWelcome: 'Kunden und Gäste willkommen', onlineEvent: 'Online-Event', offlineEvent: 'Offline-Event', venueAddress: 'Veranstaltungsort', ticketUrl: 'Ticket-URL', getTicketNow: 'Jetzt Ticket sichern!', soldOutLabel: 'Ausverkauft', soldOutNo: 'Verfügbar', soldOutYes: 'Ausverkauft', soldOutBadge: 'AUSVERKAUFT', pastEvent: 'Vergangenes Event', audience: 'Zielgruppe', customersGuests: 'Gäste und Kunden willkommen', consultantsMeeting: 'Beratermeeting', consultantsTraining: 'Beratertraining', eventMode: 'Eventmodus', selectMode: 'Modus wählen', meetingLink: 'Meeting-Link', zoomLink: 'Zoom-Link', onlineZoomEvent: 'Online-Zoom-Event', inPersonMeeting: 'Präsenztreffen', onlineZoomMeetingForConsultants: 'Online-Zoom-Meeting für Berater', onlineZoomTrainingForConsultants: 'Online-Zoom-Training für Berater', onlineZoomEventForGuestsCustomers: 'Online-Zoom-Event für Gäste und Kunden', inPersonMeetingForConsultants: 'Präsenzmeeting für Berater', inPersonTrainingForConsultants: 'Präsenztraining für Berater', inPersonEventForGuestsCustomers: 'Präsenzveranstaltung für Gäste und Kunden', guestsCustomersOverlay: 'Gäste und Kunden willkommen.' },
    it: { eventCalendar: 'Calendario Eventi', prev: 'Precedente', today: 'Oggi', next: 'Successivo', newEvent: 'Nuovo Evento', share: 'Condividi', copied: 'Copiato', copyFailed: 'Errore copia', eventLanguage: "Lingua dell'evento", interpretation: 'Interpretazione', consultantMeeting: 'riunione consulenti', consultantTraining: 'formazione consulenti', consultantMeetingTrainingShort: 'riunione/formazione consulenti', customersGuestsWelcome: 'clienti e ospiti benvenuti', onlineEvent: 'Evento online', offlineEvent: 'Evento offline', venueAddress: 'Indirizzo sede', ticketUrl: 'URL biglietti', getTicketNow: 'Acquista ora il tuo biglietto!', soldOutLabel: 'Tutto esaurito', soldOutNo: 'Disponibile', soldOutYes: 'Esaurito', soldOutBadge: 'TUTTO ESAURITO', pastEvent: 'evento passato', audience: 'Pubblico', customersGuests: 'Ospiti e clienti benvenuti', consultantsMeeting: 'Riunione consulenti', consultantsTraining: 'Formazione consulenti', eventMode: 'Modalità evento', selectMode: 'Seleziona modalità', meetingLink: 'Link riunione', zoomLink: 'Link Zoom', onlineZoomEvent: 'evento Zoom online', inPersonMeeting: 'incontro in presenza', onlineZoomMeetingForConsultants: 'riunione Zoom online per consulenti', onlineZoomTrainingForConsultants: 'formazione Zoom online per consulenti', onlineZoomEventForGuestsCustomers: 'evento Zoom online per ospiti e clienti', inPersonMeetingForConsultants: 'riunione in presenza per consulenti', inPersonTrainingForConsultants: 'formazione in presenza per consulenti', inPersonEventForGuestsCustomers: 'evento in presenza per ospiti e clienti', guestsCustomersOverlay: 'Ospiti e clienti benvenuti.' },
    es: { eventCalendar: 'Calendario de Eventos', prev: 'Anterior', today: 'Hoy', next: 'Siguiente', newEvent: 'Nuevo Evento', share: 'Compartir', copied: 'Copiado', copyFailed: 'Error al copiar', eventLanguage: 'Idioma del evento', interpretation: 'Interpretación', consultantMeeting: 'reunión de consultores', consultantTraining: 'formación de consultores', consultantMeetingTrainingShort: 'reunión/formación de consultores', customersGuestsWelcome: 'clientes e invitados bienvenidos', onlineEvent: 'Evento online', offlineEvent: 'Evento presencial', venueAddress: 'Dirección del lugar', ticketUrl: 'URL de entradas', getTicketNow: '¡Consigue tu entrada ahora!', soldOutLabel: 'Entradas agotadas', soldOutNo: 'Disponible', soldOutYes: 'Agotado', soldOutBadge: 'AGOTADO', pastEvent: 'evento pasado', audience: 'Público', customersGuests: 'Invitados y clientes bienvenidos', consultantsMeeting: 'Reunión de consultores', consultantsTraining: 'Formación de consultores', eventMode: 'Modo del evento', selectMode: 'Seleccionar modo', meetingLink: 'Enlace de reunión', zoomLink: 'Enlace Zoom', onlineZoomEvent: 'evento Zoom online', inPersonMeeting: 'reunión presencial', onlineZoomMeetingForConsultants: 'reunión Zoom online para consultores', onlineZoomTrainingForConsultants: 'formación Zoom online para consultores', onlineZoomEventForGuestsCustomers: 'evento Zoom online para invitados y clientes', inPersonMeetingForConsultants: 'reunión presencial para consultores', inPersonTrainingForConsultants: 'formación presencial para consultores', inPersonEventForGuestsCustomers: 'evento presencial para invitados y clientes', guestsCustomersOverlay: 'Invitados y clientes bienvenidos.' },
    fr: { eventCalendar: 'Calendrier des Événements', prev: 'Précédent', today: "Aujourd'hui", next: 'Suivant', newEvent: 'Nouvel Événement', share: 'Partager', copied: 'Copié', copyFailed: 'Échec copie', eventLanguage: "Langue de l'événement", interpretation: 'Interprétation', consultantMeeting: 'réunion de consultants', consultantTraining: 'formation consultants', consultantMeetingTrainingShort: 'réunion/formation consultants', customersGuestsWelcome: 'clients et invités bienvenus', onlineEvent: 'Événement en ligne', offlineEvent: 'Événement sur place', venueAddress: 'Adresse du lieu', ticketUrl: 'URL billetterie', getTicketNow: 'Réservez votre billet maintenant !', soldOutLabel: 'Complet', soldOutNo: 'Disponible', soldOutYes: 'Complet', soldOutBadge: 'COMPLET', pastEvent: 'événement passé', audience: 'Public', customersGuests: 'Invités et clients bienvenus', consultantsMeeting: 'Réunion de consultants', consultantsTraining: 'Formation consultants', eventMode: "Mode d'événement", selectMode: 'Choisir le mode', meetingLink: 'Lien de réunion', zoomLink: 'Lien Zoom', onlineZoomEvent: 'événement Zoom en ligne', inPersonMeeting: 'réunion en présentiel', onlineZoomMeetingForConsultants: 'réunion Zoom en ligne pour consultants', onlineZoomTrainingForConsultants: 'formation Zoom en ligne pour consultants', onlineZoomEventForGuestsCustomers: 'événement Zoom en ligne pour invités et clients', inPersonMeetingForConsultants: 'réunion en présentiel pour consultants', inPersonTrainingForConsultants: 'formation en présentiel pour consultants', inPersonEventForGuestsCustomers: 'événement en présentiel pour invités et clients', guestsCustomersOverlay: 'Invités et clients bienvenus.' },
    hu: { eventCalendar: 'Eseménynaptár', prev: 'Előző', today: 'Ma', next: 'Következő', newEvent: 'Új Esemény', share: 'Megosztás', copied: 'Másolva', copyFailed: 'Sikertelen', eventLanguage: 'Esemény nyelve', interpretation: 'Tolmácsolás', consultantMeeting: 'tanácsadói megbeszélés', consultantTraining: 'tanácsadói képzés', consultantMeetingTrainingShort: 'tanácsadói megbeszélés/képzés', customersGuestsWelcome: 'ügyfeleket és vendégeket várunk', onlineEvent: 'Online esemény', offlineEvent: 'Személyes esemény', venueAddress: 'Helyszín címe', ticketUrl: 'Jegyvásárlási URL', getTicketNow: 'Szerezd be a jegyed most!', soldOutLabel: 'Teltház', soldOutNo: 'Elérhető', soldOutYes: 'Teltházas', soldOutBadge: 'TELTHÁZ', pastEvent: 'korábbi esemény', audience: 'Közönség', customersGuests: 'Vendégek és ügyfelek várva', consultantsMeeting: 'Tanácsadói megbeszélés', consultantsTraining: 'Tanácsadói képzés', eventMode: 'Esemény típusa', selectMode: 'Válassz módot', meetingLink: 'Találkozó link', zoomLink: 'Zoom link', onlineZoomEvent: 'online Zoom esemény', inPersonMeeting: 'személyes találkozó', onlineZoomMeetingForConsultants: 'online Zoom megbeszélés tanácsadóknak', onlineZoomTrainingForConsultants: 'online Zoom képzés tanácsadóknak', onlineZoomEventForGuestsCustomers: 'online Zoom esemény vendégeknek és ügyfeleknek', inPersonMeetingForConsultants: 'személyes megbeszélés tanácsadóknak', inPersonTrainingForConsultants: 'személyes képzés tanácsadóknak', inPersonEventForGuestsCustomers: 'személyes esemény vendégeknek és ügyfeleknek', guestsCustomersOverlay: 'Vendégek és ügyfelek várva.' },
    pt: { eventCalendar: 'Calendário de Eventos', prev: 'Anterior', today: 'Hoje', next: 'Próximo', newEvent: 'Novo Evento', share: 'Partilhar', copied: 'Copiado', copyFailed: 'Falha ao copiar', eventLanguage: 'Idioma do evento', interpretation: 'Interpretação', consultantMeeting: 'reunião de consultores', consultantTraining: 'treino de consultores', consultantMeetingTrainingShort: 'reunião/treino de consultores', customersGuestsWelcome: 'clientes e convidados bem-vindos', onlineEvent: 'Evento online', offlineEvent: 'Evento presencial', venueAddress: 'Morada do local', ticketUrl: 'URL de bilhetes', getTicketNow: 'Garanta já o seu bilhete!', soldOutLabel: 'Esgotado', soldOutNo: 'Disponível', soldOutYes: 'Esgotado', soldOutBadge: 'ESGOTADO', pastEvent: 'evento passado', audience: 'Público', customersGuests: 'Convidados e clientes bem-vindos', consultantsMeeting: 'Reunião de consultores', consultantsTraining: 'Treino de consultores', eventMode: 'Modo do evento', selectMode: 'Selecionar modo', meetingLink: 'Link da reunião', zoomLink: 'Link Zoom', onlineZoomEvent: 'evento Zoom online', inPersonMeeting: 'reunião presencial', onlineZoomMeetingForConsultants: 'reunião Zoom online para consultores', onlineZoomTrainingForConsultants: 'treino Zoom online para consultores', onlineZoomEventForGuestsCustomers: 'evento Zoom online para convidados e clientes', inPersonMeetingForConsultants: 'reunião presencial para consultores', inPersonTrainingForConsultants: 'treino presencial para consultores', inPersonEventForGuestsCustomers: 'evento presencial para convidados e clientes', guestsCustomersOverlay: 'Convidados e clientes bem-vindos.' },
    ro: { eventCalendar: 'Calendar Evenimente', prev: 'Anterior', today: 'Astăzi', next: 'Următor', newEvent: 'Eveniment Nou', share: 'Distribuie', copied: 'Copiat', copyFailed: 'Eroare copiere', eventLanguage: 'Limba evenimentului', interpretation: 'Interpretare', consultantMeeting: 'întâlnire consultanți', consultantTraining: 'training consultanți', consultantMeetingTrainingShort: 'întâlnire/training consultanți', customersGuestsWelcome: 'clienți și invitați bineveniți', onlineEvent: 'Eveniment online', offlineEvent: 'Eveniment offline', venueAddress: 'Adresa locației', ticketUrl: 'URL bilete', getTicketNow: 'Ia-ți biletul acum!', soldOutLabel: 'Epuizat', soldOutNo: 'Disponibil', soldOutYes: 'Epuizat', soldOutBadge: 'EPUIZAT', pastEvent: 'eveniment trecut', audience: 'Public', customersGuests: 'Invitați și clienți bineveniți', consultantsMeeting: 'Întâlnire consultanți', consultantsTraining: 'Training consultanți', eventMode: 'Tip eveniment', selectMode: 'Selectează tipul', meetingLink: 'Link întâlnire', zoomLink: 'Link Zoom', onlineZoomEvent: 'eveniment Zoom online', inPersonMeeting: 'întâlnire fizică', onlineZoomMeetingForConsultants: 'întâlnire Zoom online pentru consultanți', onlineZoomTrainingForConsultants: 'training Zoom online pentru consultanți', onlineZoomEventForGuestsCustomers: 'eveniment Zoom online pentru invitați și clienți', inPersonMeetingForConsultants: 'întâlnire fizică pentru consultanți', inPersonTrainingForConsultants: 'training fizic pentru consultanți', inPersonEventForGuestsCustomers: 'eveniment fizic pentru invitați și clienți', guestsCustomersOverlay: 'Invitați și clienți bineveniți.' },
    sk: { eventCalendar: 'Kalendár Udalostí', prev: 'Predchádzajúci', today: 'Dnes', next: 'Ďalší', newEvent: 'Nová Udalosť', share: 'Zdieľať', copied: 'Skopírované', copyFailed: 'Chyba kopírovania', eventLanguage: 'Jazyk udalosti', interpretation: 'Tlmočenie', consultantMeeting: 'stretnutie konzultantov', consultantTraining: 'školenie konzultantov', consultantMeetingTrainingShort: 'stretnutie/školenie konzultantov', customersGuestsWelcome: 'zákazníci a hostia vítaní', onlineEvent: 'Online udalosť', offlineEvent: 'Prezenčná udalosť', venueAddress: 'Adresa miesta', ticketUrl: 'URL vstupeniek', getTicketNow: 'Získajte si vstupenku teraz!', soldOutLabel: 'Vypredané', soldOutNo: 'Dostupné', soldOutYes: 'Vypredané', soldOutBadge: 'VYPREDANÉ', pastEvent: 'minulé podujatie', audience: 'Publikum', customersGuests: 'Hostia a zákazníci vítaní', consultantsMeeting: 'Stretnutie konzultantov', consultantsTraining: 'Školenie konzultantov', eventMode: 'Typ udalosti', selectMode: 'Vyber typ', meetingLink: 'Odkaz na stretnutie', zoomLink: 'Zoom odkaz', onlineZoomEvent: 'online Zoom udalosť', inPersonMeeting: 'osobné stretnutie', onlineZoomMeetingForConsultants: 'online Zoom stretnutie pre konzultantov', onlineZoomTrainingForConsultants: 'online Zoom školenie pre konzultantov', onlineZoomEventForGuestsCustomers: 'online Zoom udalosť pre hostí a zákazníkov', inPersonMeetingForConsultants: 'osobné stretnutie pre konzultantov', inPersonTrainingForConsultants: 'osobné školenie pre konzultantov', inPersonEventForGuestsCustomers: 'osobná udalosť pre hostí a zákazníkov', guestsCustomersOverlay: 'Hostia a zákazníci vítaní.' }
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

function formatTimeRangeOnly(startIso, endIso) {
    const s = parseSqlLocal(startIso);
    const e = parseSqlLocal(endIso);
    if (!s || !e) return '';
    if (state.datetimeFormat === 'eu') {
        return `${String(s.getHours()).padStart(2, '0')}:${String(s.getMinutes()).padStart(2, '0')}-${String(e.getHours()).padStart(2, '0')}:${String(e.getMinutes()).padStart(2, '0')}`;
    }
    const to12 = (d) => {
        const h = d.getHours();
        const m = String(d.getMinutes()).padStart(2, '0');
        const ap = h >= 12 ? 'PM' : 'AM';
        const hh = ((h + 11) % 12) + 1;
        return `${hh}:${m} ${ap}`;
    };
    return `${to12(s)}-${to12(e)}`;
}

function recurrenceSummary(eventItem) {
    if (!eventItem || eventItem.recurrence_type !== 'monthly_nth_weekday') return '';
    const weekdayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const nthMap = { 1: '1st', 2: '2nd', 3: '3rd', 4: '4th', 5: '5th' };
    const weekday = weekdayNames[Number(eventItem.recur_weekday)];
    if (!weekday) return '';
    let weeks = [];
    if (Array.isArray(eventItem.recur_weeks)) {
        weeks = eventItem.recur_weeks.map((n) => nthMap[Number(n)]).filter(Boolean);
    } else if (typeof eventItem.recur_week === 'string' && eventItem.recur_week.includes(',')) {
        weeks = eventItem.recur_week.split(',').map((v) => nthMap[Number(v.trim())]).filter(Boolean);
    } else {
        const one = nthMap[Number(eventItem.recur_week)];
        if (one) weeks = [one];
    }
    if (!weeks.length) return '';
    const weeksTxt = weeks.length === 1 ? weeks[0] : `${weeks.slice(0, -1).join(', ')} and ${weeks[weeks.length - 1]}`;
    return `Every ${weeksTxt} ${weekday} of the month from ${formatTimeRangeOnly(eventItem.start_at, eventItem.end_at)}`;
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
    const setText = (id, value) => {
        const el = byId(id);
        if (el) el.textContent = value;
    };
    document.title = 'Immunotec Zoom and Event calendar';
    setText('appTitle', t('eventCalendar'));
    setText('prevBtn', t('prev'));
    setText('todayBtn', t('today'));
    setText('nextBtn', t('next'));
    setText('newEventBtn', t('newEvent'));
    setText('eventModeLabel', 'Event type');
    setText('eventLinkLabel', t('meetingLink'));
    setText('eventVenueAddressLabel', t('venueAddress'));
    setText('eventTicketUrlLabel', t('ticketUrl'));
    setText('eventAudienceTypeLabel', t('audience'));
    setText('eventAudienceGuestsLabel', t('customersGuests'));
    setText('eventAudienceConsultantMeetingLabel', t('consultantsMeeting'));
    setText('eventAudienceConsultantTrainingLabel', t('consultantsTraining'));
    setText('eventSoldOutSwitchLabel', t('soldOutLabel'));
    setText('eventModeOnlineLabel', t('onlineEvent'));
    setText('eventModeOfflineLabel', t('offlineEvent'));
    const legend = byId('audienceLegend');
    if (legend) {
        legend.innerHTML = `<span class="item"><span class="swatch guests"></span>${t('customersGuestsWelcome')}</span><span class="item"><span class="swatch consultants"></span>${t('consultantMeetingTrainingShort')}</span>`;
    }
    const shareBtn = byId('shareEventBtn');
    if (shareBtn) {
        const txt = shareBtn.textContent.trim();
        if (txt === '' || Object.values(I18N).some((d) => [d.share, d.copied, d.copyFailed].includes(txt))) {
            shareBtn.textContent = t('share');
        }
    }
    const isEu = state.datetimeFormat === 'eu';
    const eventStart = byId('eventStart');
    if (eventStart) eventStart.placeholder = isEu ? 'DD/MM/YYYY HH:MM' : 'MM/DD/YYYY HH:MM AM';
    const eventEnd = byId('eventEnd');
    if (eventEnd) eventEnd.placeholder = isEu ? 'DD/MM/YYYY HH:MM' : 'MM/DD/YYYY HH:MM AM';
    const eventRecurrenceUntil = byId('eventRecurrenceUntil');
    if (eventRecurrenceUntil) {
        eventRecurrenceUntil.placeholder = isEu ? 'DD/MM/YYYY HH:MM' : 'MM/DD/YYYY HH:MM AM';
    }
}

function getRadioValue(name, fallback = '') {
    const selected = document.querySelector(`input[name="${name}"]:checked`);
    return selected ? String(selected.value) : fallback;
}

function setRadioValue(name, value) {
    const val = String(value);
    const candidate = document.querySelector(`input[name="${name}"][value="${val}"]`);
    if (candidate) {
        candidate.checked = true;
        return;
    }
    const first = document.querySelector(`input[name="${name}"]`);
    if (first) first.checked = true;
}

function audienceSuffix(eventItem) {
    if (eventItem?.audience_type === 'consultant_training') return t('consultantTraining');
    if (eventItem?.audience_type === 'consultant_meeting' || eventItem?.audience_type === 'consultants') return t('consultantMeeting');
    return t('customersGuestsWelcome');
}

function eventDisplayTitle(eventItem) {
    return eventItem?.title || 'Event';
}

function isConsultantsOnly(eventItem) {
    return eventItem?.audience_type === 'consultant_meeting' || eventItem?.audience_type === 'consultant_training' || eventItem?.audience_type === 'consultants';
}

function modeAudienceSentence(eventItem) {
    const audience = eventItem?.audience_type;
    const isOnline = (eventItem?.event_mode || 'online') === 'online';
    if (isOnline && audience === 'consultant_meeting') return t('onlineZoomMeetingForConsultants');
    if (isOnline && audience === 'consultant_training') return t('onlineZoomTrainingForConsultants');
    if (isOnline) return t('onlineZoomEventForGuestsCustomers');
    if (audience === 'consultant_meeting') return t('inPersonMeetingForConsultants');
    if (audience === 'consultant_training') return t('inPersonTrainingForConsultants');
    return t('inPersonEventForGuestsCustomers');
}

function heroOverlayText(eventItem) {
    if (eventItem?.audience_type === 'consultant_training') return t('consultantsTraining');
    if (eventItem?.audience_type === 'consultant_meeting' || eventItem?.audience_type === 'consultants') return t('consultantsMeeting');
    return t('guestsCustomersOverlay');
}

function isSoldOut(eventItem) {
    return String(eventItem?.sold_out ?? '0') === '1';
}

function isPastEvent(eventItem) {
    const end = parseSqlLocal(eventItem?.end_at || '');
    if (!end) return false;
    return end.getTime() < Date.now();
}

function eventLanguageOptions() {
    const byCode = new Map(state.countries.map((c) => [String(c.code || '').toLowerCase(), c]));
    return EVENT_LANGUAGE_DEFS.map((d) => {
        let c = byCode.get(d.code);
        // Accept GB-backed English rows and still present them as English with UK flag.
        if (!c && d.code === 'en') c = byCode.get('gb');
        if (!c) return null;
        return { id: c.id, code: String(c.code || d.code).toLowerCase(), name: d.name };
    }).filter(Boolean);
}

function languageNameByCode(code) {
    const norm = String(code || '').toLowerCase();
    const row = EVENT_LANGUAGE_DEFS.find((d) => d.code === norm);
    return row ? row.name : String(code || '').toUpperCase();
}

function deriveLanguageCodeFromCountryCode(countryCode) {
    const code = String(countryCode || '').trim().toLowerCase();
    const explicitMap = {
        dach: 'de',
        de: 'de',
        at: 'de',
        ch: 'de',
        fr: 'fr',
        it: 'it',
        es: 'es',
        pt: 'pt',
        ro: 'ro',
        hu: 'hu',
        sk: 'sk',
        gb: 'en',
        uk: 'en',
        us: 'en'
    };
    return explicitMap[code] || '';
}

function eventLanguageCodes(eventItem) {
    const out = new Set();
    const main = String(eventItem?.event_language_country_code || '').trim().toLowerCase();
    if (main) out.add(main);
    const interp = Array.isArray(eventItem?.interpretation_country_codes) ? eventItem.interpretation_country_codes : [];
    interp.forEach((c) => {
        const v = String(c || '').trim().toLowerCase();
        if (v) out.add(v);
    });
    if (!out.size) {
        const sourceCountries = Array.isArray(eventItem?.country_codes) ? eventItem.country_codes : [];
        sourceCountries.forEach((cc) => {
            const derived = deriveLanguageCodeFromCountryCode(cc);
            if (derived) out.add(derived);
        });
    }
    return out;
}

function rebuildLanguageFilterOptions() {
    const filter = byId('languageFilter');
    if (!filter) return;
    const selected = state.selectedLanguage || '';
    const codeSet = new Set();
    state.allEvents.forEach((e) => {
        eventLanguageCodes(e).forEach((c) => codeSet.add(c));
    });
    const sortedCodes = Array.from(codeSet).sort((a, b) => languageNameByCode(a).localeCompare(languageNameByCode(b)));
    filter.innerHTML = ['<option value="">All languages</option>', ...sortedCodes.map((c) => `<option value="${c}">${languageNameByCode(c)}</option>`)].join('');
    filter.value = sortedCodes.includes(selected) ? selected : '';
    state.selectedLanguage = filter.value || '';
}

async function loadLanguageFilterOptions() {
    const filter = byId('languageFilter');
    if (!filter) return;
    try {
        const data = await api('includes/api/event_languages.php');
        const raw = Array.isArray(data?.codes) ? data.codes : [];
        const codes = raw.map((c) => String(c || '').toLowerCase()).filter(Boolean);
        const selected = state.selectedLanguage || '';
        filter.innerHTML = ['<option value="">All languages</option>', ...codes.sort((a, b) => languageNameByCode(a).localeCompare(languageNameByCode(b))).map((c) => `<option value="${c}">${languageNameByCode(c)}</option>`)].join('');
        filter.value = codes.includes(selected) ? selected : '';
        state.selectedLanguage = filter.value || '';
    } catch (err) {
        rebuildLanguageFilterOptions();
    }
}

function applyClientFilters() {
    if (!state.selectedLanguage) {
        state.events = state.allEvents.slice();
        return;
    }
    state.events = state.allEvents.filter((ev) => eventLanguageCodes(ev).has(state.selectedLanguage));
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
        const day = (d.getDay() + 6) % 7; // Monday=0 ... Sunday=6
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
    if (state.view === 'month') {
        const monthLocaleMap = { en: 'en-GB', de: 'de-DE', it: 'it-IT', es: 'es-ES', fr: 'fr-FR', hu: 'hu-HU', pt: 'pt-PT', ro: 'ro-RO', sk: 'sk-SK' };
        const locale = monthLocaleMap[getLang()] || 'en-GB';
        const rawMonthName = start.toLocaleString(locale, { month: 'long' });
        const monthName = rawMonthName
            ? rawMonthName.charAt(0).toLocaleUpperCase(locale) + rawMonthName.slice(1)
            : rawMonthName;
        const year = start.getFullYear();
        const dd = String(end.getDate()).padStart(2, '0');
        const mm = String(end.getMonth() + 1).padStart(2, '0');
        const yyyy = end.getFullYear();
        byId('rangeLabel').textContent = `${monthName} ${year}  |  01.-${dd}.${mm}.${yyyy}`;
        return;
    }
    byId('rangeLabel').textContent = `${formatDateTimeByPreference(start).split(' ')[0]} - ${formatDateTimeByPreference(end).split(' ')[0]}`;
}

function monthGrid(anchorDate) {
    const year = anchorDate.getFullYear();
    const month = anchorDate.getMonth();
    const first = new Date(year, month, 1);
    const start = new Date(first);
    const mondayIndex = (first.getDay() + 6) % 7; // Monday=0 ... Sunday=6
    start.setDate(first.getDate() - mondayIndex);
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
    const overlayText = heroOverlayText(eventItem);
    const soldOutBadge = isSoldOut(eventItem) ? `<div class="hero-overlay-soldout">${t('soldOutBadge')}</div>` : '';
    if (eventItem.image_path) hero.innerHTML = `<img src="${eventItem.image_path}" alt="${eventItem.title || 'Event image'}"><div class="hero-overlay-note">${overlayText}</div>${soldOutBadge}`;
    else hero.innerHTML = `<div class="event-view-fallback">${eventItem.title || 'Event'}</div>`;

    byId('eventViewTitle').textContent = eventDisplayTitle(eventItem);
    const modeBadge = modeAudienceSentence(eventItem);
    byId('eventViewCountriesRow').innerHTML = `${countriesFlagsRow(eventItem.country_codes || [])}<span class="event-mode-badge">${modeBadge}</span>`;
    byId('eventViewCountriesRow').className = 'event-countries-inline';
    const metaEl = byId('eventViewMeta');
    metaEl.textContent = formatEventTimeRange(eventItem.start_at, eventItem.end_at);
    metaEl.style.whiteSpace = 'pre-line';
    if (isPastEvent(eventItem)) {
        metaEl.append(' ');
        const pastSpan = document.createElement('span');
        pastSpan.className = 'event-past-badge event-past-inline';
        pastSpan.textContent = t('pastEvent');
        metaEl.appendChild(pastSpan);
    }
    byId('eventViewRecurrence').textContent = recurrenceSummary(eventItem);
    byId('eventViewTicketWrap').innerHTML = '';
    byId('eventViewDescription').textContent = eventItem.description || '';
    byId('eventViewDescription').style.whiteSpace = 'pre-line';
    const flagIsoByCode = new Map(EVENT_LANGUAGE_DEFS.map((d) => [d.code, d.flagIso]));
    flagIsoByCode.set('gb', 'gb');
    const languageCode = String(eventItem.event_language_country_code || '').toLowerCase();
    const languageFlagIso = languageCode ? flagIsoByCode.get(languageCode) : null;
    const interpFlagIsos = (Array.isArray(eventItem.interpretation_country_codes) ? eventItem.interpretation_country_codes : []).map((c) => flagIsoByCode.get(String(c || '').toLowerCase())).filter(Boolean);
    const languageText = languageFlagIso ? `<div><strong>${t('eventLanguage')}:</strong> <span class="flag-row">${countryFlagHtml(languageFlagIso, 'main-language')}</span></div>` : '';
    const interpText = interpFlagIsos.length ? `<div><strong>${t('interpretation')}:</strong> <div class="flag-row">${interpFlagIsos.map((iso) => countryFlagHtml(iso)).join('')}</div></div>` : '';
    byId('eventViewQrWrap').innerHTML = `${languageText}${interpText}`;

    if ((eventItem.event_mode || 'online') === 'offline') {
        const venueParts = String(eventItem.venue_address || '').split(/\r?\n/).map((s) => s.trim()).filter(Boolean);
        const venueName = venueParts.length ? venueParts[0] : '';
        const venueAddrLines = venueParts.length > 1 ? venueParts.slice(1) : [];
        const venueImg = eventItem.venue_image_path
            ? `<div class="venue-image-col"><img src="${eventItem.venue_image_path}" alt="Venue photo"></div>`
            : '';
        const venueAddr = eventItem.venue_address
            ? `<div class="venue-address-col"><strong>${t('venueAddress')}:</strong>${venueName ? `<div class="venue-name">${venueName}</div>` : ''}${venueAddrLines.length ? `<div>${venueAddrLines.join('<br>')}</div>` : (venueName ? '' : `<div>${String(eventItem.venue_address).replace(/\n/g, '<br>')}</div>`)}</div>`
            : '';
        if (eventItem.ticket_url) {
            const ticketLabel = isSoldOut(eventItem) ? `${t('soldOutYes')}!` : t('getTicketNow');
            byId('eventViewTicketWrap').innerHTML = `<a href="${eventItem.ticket_url}" target="_blank" rel="noopener" class="ticket-cta"><span class="ticket-icon" aria-hidden="true">&#127915;</span>${ticketLabel}</a>`;
        }
        byId('eventViewLinkWrap').innerHTML = (venueImg || venueAddr) ? `<div class="venue-layout">${venueImg}${venueAddr}</div>` : '';
    } else {
        byId('eventViewLinkWrap').innerHTML = eventItem.event_link ? `<strong>${t('zoomLink')}:</strong> <a href="${eventItem.event_link}" target="_blank" rel="noopener">${eventItem.event_link}</a>` : '';
    }
    const qrImg = byId('eventViewQrImg');
    if ((eventItem.event_mode || 'online') === 'online' && eventItem.event_link) {
        qrImg.src = `https://quickchart.io/qr?size=110&text=${encodeURIComponent(eventItem.event_link)}`;
        qrImg.style.display = 'block';
    } else {
        qrImg.style.display = 'none';
        qrImg.removeAttribute('src');
    }
    const authorEl = byId('eventViewAuthor');
    if (state.showEventAuthor) {
        authorEl.textContent = `by ${eventItem.creator_name || eventItem.username || 'Unknown'}`;
        authorEl.style.display = '';
    } else {
        authorEl.textContent = '';
        authorEl.style.display = 'none';
    }

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

function setEventFormImagePreview(src) {
    const wrap = byId('eventFormImagePreview');
    if (!src) {
        wrap.style.display = 'none';
        wrap.innerHTML = '';
        return;
    }
    wrap.style.display = 'block';
    wrap.innerHTML = `<img src="${src}" alt="Event header image preview">`;
}

function setVenueFormImagePreview(src) {
    const wrap = byId('eventVenueImagePreview');
    if (!src) {
        wrap.style.display = 'none';
        wrap.innerHTML = '';
        return;
    }
    wrap.style.display = 'block';
    wrap.innerHTML = `<img src="${src}" alt="Venue image preview">`;
}

function updateEventModeVisibility() {
    const mode = getRadioValue('eventMode', 'online');
    byId('eventOnlineWrap').style.display = mode === 'online' ? 'grid' : 'none';
    byId('eventOfflineWrap').style.display = mode === 'offline' ? 'grid' : 'none';
}

function openEventDialog(eventItem = null, prefillDate = null) {
    if (!eventItem && !state.user) return;
    if (eventItem && (!state.user || !eventItem.can_edit)) return openEventView(eventItem);

    byId('eventDialogTitle').textContent = eventItem ? 'Edit Event' : 'New Event';
    byId('eventForm').dataset.occurrenceStartAt = eventItem?.start_at || '';
    byId('eventForm').dataset.recurrenceType = eventItem?.recurrence_type || 'none';
    byId('copyFromId').value = '';
    byId('eventId').value = eventItem ? eventItem.id : '';
    byId('eventTitle').value = eventItem?.title || '';
    byId('eventDescription').value = eventItem?.description || '';
    byId('eventLinkOnline').value = eventItem?.event_link || '';
    setRadioValue('eventMode', eventItem ? (eventItem.event_mode || 'online') : 'online');
    byId('eventVenueAddress').value = eventItem?.venue_address || '';
    byId('eventTicketUrl').value = eventItem?.ticket_url || '';
    byId('eventVenueImage').value = '';
    setVenueFormImagePreview(eventItem?.venue_image_path || null);
    const rawAudience = eventItem?.audience_type || 'customers_guests';
    setRadioValue('eventAudienceType', rawAudience === 'consultants' ? 'consultant_meeting' : rawAudience);
    byId('eventSoldOut').checked = isSoldOut(eventItem);
    byId('eventImage').value = '';
    setEventFormImagePreview(eventItem?.image_path || null);

    const selectedCountries = new Set((eventItem?.country_ids || []).map((v) => String(v)));
    if (!selectedCountries.size && (state.user?.country_id || state.selectedCountry)) {
        selectedCountries.add(String(state.user?.country_id || state.selectedCountry));
    }
    Array.from(byId('eventCountry').options).forEach((opt) => { opt.selected = selectedCountries.has(opt.value); });

    const selectedInterp = new Set((eventItem?.interpretation_country_codes || []).map((code) => String(code || '').toLowerCase()));
    Array.from(byId('eventInterpretationCountries').options).forEach((opt) => { opt.selected = selectedInterp.has(opt.dataset.code || ''); });

    byId('eventLanguageCountry').value = String(eventItem?.event_language_country_code || '').toLowerCase();
    byId('eventRecurrenceType').value = eventItem?.recurrence_type || 'none';
    const recurWeeks = Array.isArray(eventItem?.recur_weeks) && eventItem.recur_weeks.length
        ? eventItem.recur_weeks.map((n) => String(n))
        : [String(eventItem?.recur_week || 1)];
    Array.from(byId('eventRecurWeek').options).forEach((opt) => {
        opt.selected = recurWeeks.includes(opt.value);
    });
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
    byId('copyEventBtn').hidden = !(eventItem && eventItem.can_edit);
    updateEventModeVisibility();
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
        card.className = `event-card is-clickable ${isConsultantsOnly(e) ? 'audience-consultants' : 'audience-guests'}`;
        card.title = audienceSuffix(e);
        card.innerHTML = `<h4>${eventDisplayTitle(e)}</h4>
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
    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach((d) => {
        const h = document.createElement('div');
        h.className = 'day-head';
        h.textContent = d;
        grid.appendChild(h);
    });
    monthGrid(anchor).forEach((d) => {
        const cell = document.createElement('div');
        cell.className = 'day-cell';
        if (d.getMonth() !== anchor.getMonth()) cell.classList.add('other');
        const now = new Date();
        if (d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth() && d.getDate() === now.getDate()) {
            cell.classList.add('today');
        }
        const num = document.createElement('div');
        num.className = 'day-num';
        num.textContent = String(d.getDate());
        cell.appendChild(num);
        eventsForDate(d).slice(0, 4).forEach((e) => {
            const pill = document.createElement('div');
            pill.className = `event-pill ${isConsultantsOnly(e) ? 'audience-consultants' : ''}`;
            pill.title = audienceSuffix(e);
            const st = parseSqlLocal(e.start_at);
            pill.textContent = `${String(st.getHours()).padStart(2, '0')}:${String(st.getMinutes()).padStart(2, '0')} ${eventDisplayTitle(e)}`;
            pill.addEventListener('click', () => openEventDialog(e));
            cell.appendChild(pill);
        });
        cell.addEventListener('click', (ev) => {
            if (!state.user) return;
            if (ev.target && ev.target.closest && ev.target.closest('.event-pill')) return;
            openEventDialog(null, d);
        });
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
            if (state.user.role === 'admin') {
                window.location.href = 'admin.php';
                return;
            }
            byId('profileDialogTitle').textContent = `Profile of ${state.user.email || state.user.username || ''}`;
            byId('profileFirstName').value = state.user.first_name || '';
            byId('profileLastName').value = state.user.last_name || '';
            byId('profileCountry').value = state.user.country_id ? String(state.user.country_id) : '';
            byId('profileNewPassword').value = '';
            byId('profileNewPassword2').value = '';
            const showCountry = state.user.role !== 'visitor';
            const countryRow = byId('profileCountryRow');
            if (countryRow) countryRow.style.display = showCountry ? '' : 'none';
            byId('profileDatetimeFormat').value = state.datetimeFormat;
            byId('profileDialog').showModal();
        };
        byId('logoutBtn').onclick = async () => {
            await api('includes/api/auth_logout.php', { method: 'POST', body: '{}' });
            await bootstrap();
        };
        const role = String(state.user.role || '');
        const canOpenEditor = role === 'admin' || role === 'editor' || (role === 'category_editor' && Number(state.user.country_id || 0) > 0);
        byId('newEventBtn').hidden = !canOpenEditor;
    }
    renderLanguagePicker();
    applyI18nTexts();
}

async function loadSettings() {
    try {
        const s = await api('includes/api/settings.php');
        state.showEventAuthor = s.showEventAuthor !== false;
    } catch (err) {
        state.showEventAuthor = true;
    }
}

async function loadCountries() {
    const data = await api('includes/api/countries.php');
    state.countries = data.countries;
    byId('countryFilter').innerHTML = ['<option value="">All Countries</option>', ...state.countries.map((c) => `<option value="${c.id}">${c.name}</option>`)].join('');
    byId('eventCountry').innerHTML = state.countries.map((c) => `<option value="${c.id}" data-code="${c.code}">${c.name}</option>`).join('');
    const languageOpts = eventLanguageOptions();
    byId('eventLanguageCountry').innerHTML = ['<option value="">Select language</option>', ...languageOpts.map((c) => `<option value="${c.code}" data-country-id="${c.id}">${c.name}</option>`)].join('');
    byId('eventInterpretationCountries').innerHTML = languageOpts.map((c) => `<option value="${c.id}" data-code="${c.code}">${c.name}</option>`).join('');
    byId('signupCountry').innerHTML = state.countries.map((c) => `<option value="${c.id}">${c.name}</option>`).join('');
    byId('profileCountry').innerHTML = ['<option value="">No default country</option>', ...state.countries.map((c) => `<option value="${c.id}">${c.name}</option>`)].join('');
    await loadLanguageFilterOptions();
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
    state.allEvents = (await api(`includes/api/events_list.php?${params.toString()}`)).events;
    applyClientFilters();
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
async function bootstrap() { await loadSession(); await loadSettings(); await loadCountries(); await refreshCalendar(); }

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
byId('languageFilter').addEventListener('change', async (e) => { state.selectedLanguage = String(e.target.value || '').toLowerCase(); applyClientFilters(); renderView(); });
byId('prevBtn').addEventListener('click', async () => { stepDate(-1); await refreshCalendar(); });
byId('nextBtn').addEventListener('click', async () => { stepDate(1); await refreshCalendar(); });
byId('todayBtn').addEventListener('click', async () => { state.currentDate = new Date(); await refreshCalendar(); });
byId('newEventBtn').addEventListener('click', () => openEventDialog());
byId('cancelEventBtn').addEventListener('click', () => byId('eventDialog').close());
byId('copyEventBtn').addEventListener('click', () => {
    const sourceId = Number(byId('eventId').value || 0);
    if (!sourceId) return;
    const startDate = parseDateInput(byId('eventStart').value);
    const endDate = parseDateInput(byId('eventEnd').value);
    if (!startDate || !endDate) {
        showErrorWindow('Current event start/end must be valid before copying.');
        return;
    }
    const durationMs = Math.max(0, endDate.getTime() - startDate.getTime());
    const example = state.datetimeFormat === 'eu' ? 'DD/MM/YYYY HH:MM' : 'MM/DD/YYYY HH:MM AM';
    const input = window.prompt(`New event start date/time (${example}):`, byId('eventStart').value);
    if (input === null) return;
    const newStart = parseDateInput(input);
    if (!newStart) {
        showErrorWindow('Invalid date/time format for copy.');
        return;
    }
    const newEnd = new Date(newStart.getTime() + durationMs);
    fillDateField('eventStart', 'eventStartPicker', newStart);
    fillDateField('eventEnd', 'eventEndPicker', newEnd);
    byId('copyFromId').value = String(sourceId);
    byId('eventId').value = '';
    byId('eventDialogTitle').textContent = 'Copy Event';
    byId('deleteEventBtn').hidden = true;
});
byId('eventImage').addEventListener('change', () => {
    const file = byId('eventImage').files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => setEventFormImagePreview(String(reader.result || ''));
    reader.readAsDataURL(file);
});
byId('eventVenueImage').addEventListener('change', () => {
    const file = byId('eventVenueImage').files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => setVenueFormImagePreview(String(reader.result || ''));
    reader.readAsDataURL(file);
});
document.querySelectorAll('input[name="eventMode"]').forEach((el) => {
    el.addEventListener('change', updateEventModeVisibility);
});
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
    const newPassword = byId('profileNewPassword').value;
    const newPassword2 = byId('profileNewPassword2').value;
    if (newPassword !== newPassword2) {
        showErrorWindow('New passwords do not match.');
        return;
    }
    await api('includes/api/profile_update.php', {
        method: 'POST',
        body: JSON.stringify({
            first_name: byId('profileFirstName').value.trim(),
            last_name: byId('profileLastName').value.trim(),
            country_id: byId('profileCountry').value,
            datetime_format: byId('profileDatetimeFormat').value,
            new_password: newPassword
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
        const recurWeeks = Array.from(byId('eventRecurWeek').selectedOptions).map((o) => Number(o.value)).filter((n) => n >= 1 && n <= 5);
        const form = new FormData();
        if (byId('eventId').value) form.append('id', byId('eventId').value);
        if (byId('copyFromId').value) form.append('copy_from_id', byId('copyFromId').value);
        form.append('title', byId('eventTitle').value.trim());
        form.append('description', byId('eventDescription').value.trim());
        const mode = getRadioValue('eventMode', 'online').trim();
        if (mode !== 'online' && mode !== 'offline') throw new Error('Select event mode');
        form.append('event_mode', mode);
        form.append('event_link', mode === 'online' ? byId('eventLinkOnline').value.trim() : '');
        form.append('venue_address', mode === 'offline' ? byId('eventVenueAddress').value.trim() : '');
        form.append('ticket_url', mode === 'offline' ? byId('eventTicketUrl').value.trim() : '');
        form.append('audience_type', getRadioValue('eventAudienceType', 'customers_guests'));
        form.append('sold_out', byId('eventSoldOut').checked ? '1' : '0');
        form.append('country_ids', JSON.stringify(countryIds));
        const selectedLanguageCode = String(byId('eventLanguageCountry').value || '').toLowerCase();
        form.append('event_language_country_id', String((state.countries.find((c) => String(c.code || '').toLowerCase() === selectedLanguageCode) || {}).id || ''));
        form.append('interpretation_country_ids', JSON.stringify(interpIds));
        form.append('start_at', toSqlDateTime(startDate));
        form.append('end_at', toSqlDateTime(endDate));
        form.append('recurrence_type', recurrenceType);
        if (recurrenceType === 'monthly_nth_weekday') {
            if (!recurWeeks.length) throw new Error('Select at least one week in month for recurrence');
            form.append('recur_week', String(recurWeeks[0]));
            form.append('recur_weeks', JSON.stringify(recurWeeks));
            form.append('recur_weekday', byId('eventRecurWeekday').value);
            form.append('recurrence_until', recurrenceUntilDate ? toSqlDateTime(recurrenceUntilDate) : '');
        }
        const img = byId('eventImage').files[0];
        if (img) form.append('event_image', img);
        const venueImg = byId('eventVenueImage').files[0];
        if (venueImg) form.append('venue_image', venueImg);
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
    const currentOccurrenceStartAt = byId('eventForm').dataset.occurrenceStartAt || '';
    const currentRecurrenceType = byId('eventForm').dataset.recurrenceType || 'none';
    if (!window.confirm('Are you sure you want to delete this event?')) return;

    let payload = { id, scope: 'series' };
    const isRecurring = currentRecurrenceType === 'monthly_nth_weekday';
    if (isRecurring) {
        const choice = window.prompt('Recurring event delete:\nType "1" to delete only this occurrence.\nType "2" to delete the full series.', '1');
        if (choice === null) return;
        const c = String(choice).trim();
        if (c === '1') {
            if (!currentOccurrenceStartAt) {
                showErrorWindow('Could not determine the selected occurrence time.');
                return;
            }
            payload = { id, scope: 'occurrence', occurrence_start_at: currentOccurrenceStartAt };
        } else if (c === '2') {
            payload = { id, scope: 'series' };
        } else {
            showErrorWindow('Delete cancelled. Please enter 1 (occurrence) or 2 (series).');
            return;
        }
    }

    await api('includes/api/event_delete.php', { method: 'POST', body: JSON.stringify(payload) });
    byId('eventDialog').close();
    await refreshCalendar();
});

byId('loginBtn').addEventListener('click', async () => {
    window.location.href = 'login/';
});

byId('signupBtn').addEventListener('click', async () => {
    window.location.href = 'login/';
});

bootstrap().catch((err) => { byId('calendarRoot').innerHTML = `<p>Initialization failed: ${err.message}</p>`; });
(() => {
    const eventId = new URL(window.location.href).searchParams.get('event');
    if (eventId) pendingOpenEventId = Number(eventId);
})();



