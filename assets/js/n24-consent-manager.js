/* ============================================
   N24 Consent Manager – DSGVO Consent Management
   WordPress plugin asset
   ============================================ */

(function () {
const defaultSettings = {
    storageKey: 'n24_consent_manager_consent',
    cookieName: 'n24_consent_manager_consent',
    privacyUrl: '/datenschutz/',
    imprintUrl: '/impressum/',
    legalPathSlugs: ['datenschutz', 'impressum'],
    providerName: '',
    providerAddress: '',
    bannerVersion: '',
    privacyPolicyVersion: '',
    necessaryOnlyMode: false,
    consentLogEnabled: true,
    consentLogEndpoint: '',
    consentLifetimeSeconds: 31536000,
    iconSvg: '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><circle cx="16" cy="16" r="12" stroke="currentColor" stroke-width="2.5"/><circle cx="11" cy="13" r="2.2" fill="currentColor"/><circle cx="18" cy="10" r="2" fill="currentColor"/><circle cx="21" cy="18" r="2.4" fill="currentColor"/><circle cx="13" cy="21" r="1.9" fill="currentColor"/></svg>',
    boxIconSvg: null,
    floatingIconSvg: null,
    texts: null,
    services: null,
    contentBlockers: {
        enabled: true,
        blockers: {}
    }
};

const defaultTexts = {
    dialog_title: 'Datenschutzeinstellungen',
    information_title: 'Cookie-Informationen',
    tab_overview: 'Übersicht',
    tab_details: 'Details & Cookies',
    tab_history: 'Historie',
    intro_text: 'Es werden Cookies genutzt. Notwendige Cookies sind für die Website-Funktion erforderlich. Andere Cookies sind optional und werden nur mit Ihrer Einwilligung gesetzt.',
    necessary_label: 'Notwendig',
    necessary_info: 'Essenziell für die Grundfunktionen der Website.',
    statistics_label: 'Statistik',
    statistics_inactive_label: 'Statistik (derzeit nicht aktiv)',
    statistics_info: 'Statistische Auswertung der Website-Nutzung.',
    statistics_inactive_info: 'Derzeit sind keine Statistik-Dienste aktiv.',
    marketing_label: 'Marketing',
    marketing_inactive_label: 'Marketing (derzeit nicht aktiv)',
    marketing_info: 'Personalisierte Inhalte und Werbung.',
    marketing_inactive_info: 'Derzeit sind keine Marketing-Dienste aktiv.',
    external_media_label: 'Externe Medien',
    external_media_inactive_label: 'Externe Medien (derzeit nicht aktiv)',
    external_media_info: 'Inhalte von Videoplattformen, Karten und Social-Media-Plattformen.',
    external_media_inactive_info: 'Derzeit sind keine externen Medien aktiv.',
    info_default: 'Essenziell für die Grundfunktionen der Website.',
    details_intro: 'Sie können auswählen, welche Kategorien Sie erlauben. Ihre Entscheidung können Sie jederzeit über Cookie-Einstellungen ändern.',
    necessary_only_intro: 'Derzeit werden ausschließlich technisch notwendige Speichertechnologien eingesetzt. Der Consent Manager speichert in diesem Modus keine Auswahl, Consent-ID oder Historie und setzt kein Consent-Cookie.',
    history_intro: 'Hier finden Sie den Verlauf Ihrer Einwilligungen.',
    consent_id_label: 'Ihre Consent-ID:',
    history_empty: 'Noch keine Einträge vorhanden.',
    reject_button: 'Alle ablehnen',
    necessary_only_button: 'Nur notwendige Cookies',
    accept_all_button: 'Alle akzeptieren',
    save_button: 'Auswahl speichern',
    customize_button: 'Auswahl anpassen',
    details_button: 'Details anzeigen',
    settings_link: 'Cookie-Einstellungen',
    information_settings_link: 'Cookie-Informationen',
    floating_aria_label: 'Datenschutz-Einstellungen öffnen',
    information_floating_aria_label: 'Cookie-Informationen öffnen',
    information_close_button: 'Schließen',
    service_always_on: 'Immer an',
    service_description_label: 'Beschreibung',
    service_provider_label: 'Provider',
    service_cookies_label: 'Cookie(s)',
    service_privacy_label: 'Datenschutzerklärung',
    service_cookie_policy_label: 'Cookierichtlinie',
    service_legal_basis_label: 'Rechtsgrundlage',
    service_third_country_label: 'Drittlandübermittlung',
    service_recipient_country_label: 'Empfängerland',
    service_safeguards_label: 'Garantien / Schutzmaßnahmen',
    service_count_single: 'Service',
    service_count_plural: 'Services',
    service_details_show_label: 'Details zu {category} anzeigen',
    service_details_hide_label: 'Details zu {category} ausblenden',
    cookie_name_label: 'Name',
    cookie_expiry_label: 'Laufzeit',
    cookie_purpose_label: 'Zweck',
    history_date_label: 'Datum',
    history_status_label: 'Status',
    content_blocker_title: '',
    content_blocker_text: 'Dieser externe Inhalt wird von %s geladen. Durch das Anzeigen akzeptieren Sie die Nutzungsbedingungen von %s.',
    content_blocker_button: 'Inhalt laden',
    content_blocker_always_button: 'Immer laden',
    content_blocker_missing_service_text: 'Der passende Dienst ist im Consent Manager noch nicht aktiv.'
};

const settings = {
    ...defaultSettings,
    ...(window.N24ConsentManagerSettings || window.ConsetManagerSettings || window.ConnyConsentSettings || {})
};

settings.texts = {
    ...defaultTexts,
    ...(settings.texts || {})
};
settings.iconSvg = settings.iconSvg || defaultSettings.iconSvg;
settings.boxIconSvg = settings.boxIconSvg || settings.iconSvg;
settings.floatingIconSvg = settings.floatingIconSvg || settings.iconSvg;

function escapeHTML(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}

function text(key) {
    return escapeHTML(settings.texts[key] ?? defaultTexts[key] ?? '');
}

function formatRawText(key, replacements = {}) {
    let value = settings.texts[key] ?? defaultTexts[key] ?? '';

    Object.entries(replacements).forEach(([placeholder, replacement]) => {
        value = String(value).split(`{${placeholder}}`).join(String(replacement));
    });

    return String(value);
}

function formatText(key, replacements = {}) {
    return escapeHTML(formatRawText(key, replacements));
}

const defaultServices = {
    necessary: [
        {
            id: 'consent_manager',
            name: 'Consent Manager',
            provider: settings.providerName,
            address: settings.providerAddress,
            privacyUrl: settings.privacyUrl,
            purpose: 'Speichert den Zustimmungsstatus des Benutzers für Cookie-Einstellungen.',
            legalBasis: 'Art. 6 Abs. 1 lit. f DSGVO und § 25 Abs. 2 TDDDG',
            thirdCountryTransfer: 'Nein',
            recipientCountry: 'Deutschland / EU',
            safeguards: 'Technisch notwendige Speicherung der Consent-Entscheidung im Browser des Nutzers.',
            cookies: [{ name: settings.storageKey, expiry: '1 Jahr', type: 'Local Storage', purpose: 'Technisch notwendig' }]
        }
    ],
    statistics: [],
    marketing: [],
    external_media: []
};

settings.services = settings.services || defaultServices;

const ConsentManager = {
    storageKey: settings.storageKey,
    consent: {
        necessary: true,
        statistics: false,
        marketing: false,
        external_media: false,
        services: {},
        history: [],
        uid: null,
        timestamp: null
    },

    services: settings.services,
    loadedServiceEmbeds: {},

    getOptionalCategories() {
        return ['statistics', 'marketing', 'external_media'];
    },

    getServiceCategories() {
        return ['necessary', ...this.getOptionalCategories()];
    },

    hasOptionalServices() {
        return this.getOptionalCategories().some(category => (
            Array.isArray(this.services[category]) && this.services[category].length > 0
        ));
    },

    isNecessaryOnlyMode() {
        return settings.necessaryOnlyMode === true || !this.hasOptionalServices();
    },

    init() {
        const necessaryOnlyMode = this.isNecessaryOnlyMode();

        if (necessaryOnlyMode) {
            this.clearStoredConsent();
            document.documentElement.classList.remove('consent-pending');
        } else {
            this.loadConsent();
        }

        const path = window.location.pathname;
        const isLegalPage = settings.legalPathSlugs.some(slug => path.includes(slug));

        if (!necessaryOnlyMode && !this.consent.timestamp && !isLegalPage) {
            this.showBanner();
        } else {
            document.documentElement.classList.remove('consent-pending');
        }
        this.createFloatingButton();
        this.bindEvents();
        if (!necessaryOnlyMode) {
            this.applyConsent();
        }
    },

    generateUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        }).toUpperCase();
    },

    createFloatingButton() {
        if (document.getElementById('consent-floating-btn')) return;
        const btn = document.createElement('button');
        btn.id = 'consent-floating-btn';
        btn.className = 'consent-floating-btn';
        btn.setAttribute(
            'aria-label',
            this.isNecessaryOnlyMode() ? settings.texts.information_floating_aria_label : settings.texts.floating_aria_label
        );
        btn.innerHTML = settings.floatingIconSvg;
        document.body.appendChild(btn);
        btn.addEventListener('click', () => {
            this.showBanner();
            btn.blur();
            setTimeout(() => this.updateCheckboxState(), 50);
        });
    },

    updateCheckboxState() {
        this.getOptionalCategories().forEach(cat => {
            const master = document.querySelector(`.consent-master-checkbox[data-category="${cat}"]`);
            if (master) master.checked = this.consent[cat];
        });
        this.updateDetailedView();
    },

    loadConsent() {
        const stored = localStorage.getItem(this.storageKey);
        if (stored) {
            try {
                this.consent = JSON.parse(stored);
                const timestamp = Date.parse(this.consent.timestamp || '');
                const lifetimeMilliseconds = this.getConsentLifetimeSeconds() * 1000;
                const versionChanged = (this.consent.bannerVersion || '') !== (settings.bannerVersion || '')
                    || (this.consent.privacyPolicyVersion || '') !== (settings.privacyPolicyVersion || '');

                if (!Number.isFinite(timestamp) || Date.now() - timestamp >= lifetimeMilliseconds || versionChanged) {
                    this.clearStoredConsent();
                    return;
                }

                if (!this.consent.services) this.consent.services = {};
                if (!this.consent.history) this.consent.history = [];
                this.getOptionalCategories().forEach(category => {
                    if (typeof this.consent[category] === 'undefined') {
                        this.consent[category] = false;
                    }
                });
            } catch (error) {
                localStorage.removeItem(this.storageKey);
            }
        }
    },

    clearStoredConsent() {
        try {
            localStorage.removeItem(this.storageKey);
        } catch (error) {
            // Storage can be unavailable in restricted browser contexts.
        }

        try {
            document.cookie = `${encodeURIComponent(settings.cookieName)}=; path=/; max-age=0; SameSite=Lax`;
        } catch (error) {
            // Cookie access can be unavailable in restricted browser contexts.
        }

        this.consent = {
            necessary: true,
            statistics: false,
            marketing: false,
            external_media: false,
            services: {},
            history: [],
            uid: null,
            timestamp: null
        };
    },

    saveToStorage() {
        localStorage.setItem(this.storageKey, JSON.stringify(this.consent));
    },

    getConsentLifetimeSeconds() {
        const lifetime = Number(settings.consentLifetimeSeconds);
        return Number.isFinite(lifetime) && lifetime > 0 ? Math.floor(lifetime) : 31536000;
    },

    getAllOptionalServices() {
        return this.getOptionalCategories().flatMap(category =>
            (this.services[category] || []).map(service => ({ category, service }))
        );
    },

    hasLoadedBlockedContent() {
        return Boolean(document.querySelector('[data-n24-loaded-content], iframe[src*="youtube"], iframe[src*="youtu.be"], iframe[src*="vimeo"], iframe[src*="google.com/maps"], iframe[src*="maps.google."], iframe[src*="instagram"], iframe[src*="facebook"], iframe[src*="fb.watch"], iframe[src*="openstreetmap"], iframe[src*="osm.org"], iframe[src*="soundcloud"], iframe[src*="spotify"], iframe[src*="x.com"], iframe[src*="twitter"], blockquote.instagram-media, blockquote.twitter-tweet, .fb-post, .fb-video, .fb-page, .fb-comment, .fb-comments'));
    },

    saveConsent(consentSettings) {
        if (!this.consent.uid) {
            this.consent.uid = this.generateUID();
        }

        const now = new Date().toISOString();
        const serviceConsent = {};
        const previousServiceConsent = { ...(this.consent.services || {}) };

        const logEntry = {
            timestamp: now,
            uid: this.consent.uid,
            bannerVersion: settings.bannerVersion || '',
            privacyPolicyVersion: settings.privacyPolicyVersion || '',
            settings: { ...consentSettings }
        };

        this.consent = {
            ...this.consent,
            ...consentSettings,
            bannerVersion: settings.bannerVersion || '',
            privacyPolicyVersion: settings.privacyPolicyVersion || '',
            timestamp: now
        };

        this.getAllOptionalServices().forEach(({ category, service }) => {
            serviceConsent[service.id] = Boolean(this.consent[category]);
        });

        this.consent.history.unshift(logEntry);
        if (this.consent.history.length > 10) this.consent.history.pop();

        if (document.getElementById('consent-banner')) {
            const serviceCBs = document.getElementById('consent-banner').querySelectorAll('.service-checkbox');
            serviceCBs.forEach(cb => {
                if (!cb.disabled) {
                    const id = cb.getAttribute('data-id');
                    const category = cb.getAttribute('data-cat');
                    serviceConsent[id] = Boolean(this.consent[category]) && cb.checked;
                }
            });
        }

        this.consent.services = serviceConsent;
        this.saveToStorage();
        logEntry.services = { ...serviceConsent };
        this.sendConsentLog(logEntry);
        this.executeRevokedServiceOptOut(previousServiceConsent, serviceConsent);
        document.cookie = `${encodeURIComponent(settings.cookieName)}=1; path=/; max-age=${this.getConsentLifetimeSeconds()}; SameSite=Lax`;
        const shouldReloadAfterRevocation = this.hasLoadedBlockedContent()
            && this.getAllOptionalServices().some(({ service }) => this.consent.services[service.id] === false);

        if (shouldReloadAfterRevocation) {
            document.documentElement.classList.remove('consent-pending');
            window.location.reload();
            return;
        }

        this.applyConsent();
        this.hideBanner();
    },

    applyConsent() {
        this.loadAllowedServiceEmbeds();
        this.restoreAllowedContentBlocks();
        window.dispatchEvent(new CustomEvent('n24ConsentChanged', {
            detail: { ...this.consent }
        }));
        window.dispatchEvent(new CustomEvent('connyConsentChanged', {
            detail: { ...this.consent }
        }));
    },

    isServiceAllowed(category, service) {
        if (category === 'necessary') return true;

        if (this.consent.services && Object.prototype.hasOwnProperty.call(this.consent.services, service.id)) {
            return Boolean(this.consent.services[service.id]);
        }

        return Boolean(this.consent[category]);
    },

    findService(serviceId, category = null) {
        const categories = category ? [category] : this.getServiceCategories();

        for (const cat of categories) {
            const match = (this.services[cat] || []).find(service => service.id === serviceId);
            if (match) {
                return { category: cat, service: match };
            }
        }

        return null;
    },

    decodeBlockedContent(encoded) {
        try {
            const binary = window.atob(encoded);
            const bytes = Uint8Array.from(binary, char => char.charCodeAt(0));
            return new TextDecoder().decode(bytes);
        } catch (error) {
            try {
                return window.atob(encoded);
            } catch (innerError) {
                return '';
            }
        }
    },

    applyEmbedPrivacyOptions(html) {
        const template = document.createElement('template');
        template.innerHTML = html;

        template.content.querySelectorAll('iframe[src]').forEach(iframe => {
            try {
                const url = new URL(iframe.getAttribute('src'), document.baseURI);
                const hostname = url.hostname.toLowerCase();

                if (hostname === 'vimeo.com' || hostname.endsWith('.vimeo.com')) {
                    url.searchParams.set('dnt', '1');
                    iframe.setAttribute('src', url.toString());
                }
            } catch (error) {
                // Keep malformed or non-URL sources unchanged.
            }
        });

        return template.innerHTML;
    },

    restoreAllowedContentBlocks() {
        if (!settings.contentBlockers || settings.contentBlockers.enabled === false) {
            return;
        }

        document.querySelectorAll('[data-n24-content-blocker]').forEach(block => {
            const serviceId = block.getAttribute('data-n24-service-id') || '';
            const category = block.getAttribute('data-n24-service-category') || '';
            const match = this.findService(serviceId, category);

            if (!match || !this.isServiceAllowed(match.category, match.service)) {
                return;
            }

            const html = this.decodeBlockedContent(block.getAttribute('data-n24-original-html') || '');

            if (!html) {
                return;
            }

            this.loadBlockedContent(block, html);
        });
    },

    loadBlockedContent(block, html = null) {
        const original = html || this.decodeBlockedContent(block.getAttribute('data-n24-original-html') || '');

        if (!original) {
            return;
        }

        const privacyAwareHtml = this.applyEmbedPrivacyOptions(original);
        const scrollState = this.captureContentBlockScrollState(block);
        this.blurActiveElementInside(block);
        const anchor = this.replaceBlockedElementWithHtml(block, privacyAwareHtml, scrollState);
        this.keepLoadedContentInPlace(anchor, scrollState);
        this.processLoadedExternalEmbed(privacyAwareHtml);
    },

    captureContentBlockScrollState(block) {
        const rect = block.getBoundingClientRect();

        return {
            rect,
            scrollX: window.scrollX,
            scrollY: window.scrollY,
            blockerKey: block.getAttribute('data-n24-content-blocker') || ''
        };
    },

    blurActiveElementInside(block) {
        if (!document.activeElement || !block.contains(document.activeElement)) {
            return;
        }

        try {
            document.activeElement.blur({ preventScroll: true });
        } catch (error) {
            document.activeElement.blur();
        }
    },

    replaceBlockedElementWithHtml(block, html, scrollState = null) {
        const template = document.createElement('template');
        template.innerHTML = html;
        const anchor = document.createElement('span');
        const rect = scrollState ? scrollState.rect : block.getBoundingClientRect();
        const blockerKey = scrollState ? scrollState.blockerKey : (block.getAttribute('data-n24-content-blocker') || '');

        anchor.className = 'n24-loaded-content';
        anchor.setAttribute('data-n24-loaded-content', blockerKey);
        anchor.style.minHeight = `${Math.max(1, Math.ceil(rect.height))}px`;
        anchor.style.width = `${Math.max(1, Math.ceil(rect.width))}px`;
        anchor.style.maxWidth = '100%';

        Array.from(template.content.childNodes).forEach(node => {
            if (node.nodeType === Node.ELEMENT_NODE && node.tagName.toLowerCase() === 'script') {
                const script = document.createElement('script');
                Array.from(node.attributes).forEach(attr => script.setAttribute(attr.name, attr.value));
                script.textContent = node.textContent || '';
                anchor.appendChild(script);
                return;
            }

            anchor.appendChild(node.cloneNode(true));
        });

        block.replaceWith(anchor);

        return anchor;
    },

    keepLoadedContentInPlace(anchor, scrollState) {
        if (!anchor || !scrollState) {
            return;
        }

        const restoreScroll = () => {
            if (!anchor.isConnected) {
                return;
            }

            window.scrollTo(scrollState.scrollX, scrollState.scrollY);
        };

        const holdAnchor = () => {
            if (!anchor.isConnected) {
                return;
            }

            const currentViewportTop = anchor.getBoundingClientRect().top;
            const delta = currentViewportTop - scrollState.rect.top;

            if (Math.abs(delta) > 1) {
                window.scrollBy(0, delta);
            }
        };

        const correct = () => {
            restoreScroll();
            holdAnchor();
        };

        correct();
        window.requestAnimationFrame(correct);
        window.setTimeout(correct, 50);
        window.setTimeout(correct, 150);
        window.setTimeout(correct, 350);
        window.setTimeout(correct, 900);
    },

    processLoadedExternalEmbed(html) {
        if (!/instagram\.com/i.test(html)) {
            return;
        }

        window.setTimeout(() => {
            if (window.instgrm && window.instgrm.Embeds && typeof window.instgrm.Embeds.process === 'function') {
                window.instgrm.Embeds.process();
            }
        }, 150);
    },

    acceptBlockedContent(block, persist = true) {
        if (block.getAttribute('data-n24-service-available') === '0') {
            return;
        }

        if (!persist) {
            this.loadBlockedContent(block);
            return;
        }

        const serviceId = block.getAttribute('data-n24-service-id') || '';
        const category = block.getAttribute('data-n24-service-category') || '';
        const match = this.findService(serviceId, category);

        if (!match) {
            this.showBanner();
            setTimeout(() => this.updateCheckboxState(), 50);
            return;
        }

        if (!this.consent.uid) {
            this.consent.uid = this.generateUID();
        }

        const now = new Date().toISOString();
        this.consent = {
            ...this.consent,
            necessary: true,
            [match.category]: true,
            timestamp: now
        };

        this.consent.services = {
            ...(this.consent.services || {}),
            [serviceId]: true
        };

        this.consent.history = this.consent.history || [];
        this.consent.history.unshift({
            timestamp: now,
            uid: this.consent.uid,
            bannerVersion: settings.bannerVersion || '',
            privacyPolicyVersion: settings.privacyPolicyVersion || '',
            settings: {
                necessary: true,
                statistics: this.consent.statistics,
                marketing: this.consent.marketing,
                external_media: this.consent.external_media,
                services: { ...this.consent.services }
            }
        });
        this.sendConsentLog({
            timestamp: now,
            uid: this.consent.uid,
            bannerVersion: settings.bannerVersion || '',
            privacyPolicyVersion: settings.privacyPolicyVersion || '',
            settings: {
                necessary: true,
                statistics: this.consent.statistics,
                marketing: this.consent.marketing,
                external_media: this.consent.external_media
            },
            services: { ...this.consent.services }
        });
        if (this.consent.history.length > 10) this.consent.history.pop();

        this.saveToStorage();
        document.cookie = `${encodeURIComponent(settings.cookieName)}=1; path=/; max-age=${this.getConsentLifetimeSeconds()}; SameSite=Lax`;
        this.applyConsent();
        this.updateCheckboxState();
    },

    getServiceEmbedCode(service) {
        const explicitCode = String(service.embedCode || '').trim();

        if (explicitCode) {
            return explicitCode;
        }

        const serviceId = String(service.serviceId || '').trim();

        if (!serviceId) {
            return '';
        }

        if (service.id === 'google_analytics_4' && /^G-[A-Z0-9_-]+$/i.test(serviceId)) {
            const safeId = serviceId.replace(/[^A-Z0-9_-]/gi, '');
            return `<script async src="https://www.googletagmanager.com/gtag/js?id=${safeId}"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','${safeId}',{'anonymize_ip':true});</script>`;
        }

        if (service.id === 'google_tag_manager' && /^GTM-[A-Z0-9_-]+$/i.test(serviceId)) {
            const safeId = serviceId.replace(/[^A-Z0-9_-]/gi, '');
            return `<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','${safeId}');</script>`;
        }

        if (service.id === 'google_ads' && /^AW-[A-Z0-9_-]+$/i.test(serviceId)) {
            const safeId = serviceId.replace(/[^A-Z0-9_-]/gi, '');
            return `<script async src="https://www.googletagmanager.com/gtag/js?id=${safeId}"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','${safeId}');</script>`;
        }

        if (service.id === 'meta_pixel' && /^[0-9]{5,30}$/.test(serviceId)) {
            const safeId = serviceId.replace(/[^0-9]/g, '');
            return `<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','${safeId}');fbq('track','PageView');</script>`;
        }

        if (service.id === 'linkedin_insight_tag' && /^[0-9]{3,30}$/.test(serviceId)) {
            const safeId = serviceId.replace(/[^0-9]/g, '');
            return `<script>_linkedin_partner_id='${safeId}';window._linkedin_data_partner_ids=window._linkedin_data_partner_ids||[];window._linkedin_data_partner_ids.push(_linkedin_partner_id);</script><script>(function(l){if(!l){window.lintrk=function(a,b){window.lintrk.q.push([a,b])};window.lintrk.q=[]}var s=document.getElementsByTagName('script')[0];var b=document.createElement('script');b.type='text/javascript';b.async=true;b.src='https://snap.licdn.com/li.lms-analytics/insight.min.js';s.parentNode.insertBefore(b,s)})(window.lintrk);</script>`;
        }

        return '';
    },

    getServiceOptOutCode(service) {
        return String(service.optOutCode || '').trim();
    },

    executeRevokedServiceOptOut(previousServices, nextServices) {
        this.getAllOptionalServices().forEach(({ service }) => {
            if (previousServices[service.id] !== true || nextServices[service.id] === true) {
                return;
            }

            delete this.loadedServiceEmbeds[service.id];
            const code = this.getServiceOptOutCode(service);

            if (code) {
                this.executeEmbedCode(code, `${service.id}-opt-out-${Date.now()}`);
            }
        });
    },

    sendConsentLog(entry) {
        if (!settings.consentLogEnabled || !settings.consentLogEndpoint || !entry || !entry.uid) {
            return;
        }

        const body = JSON.stringify(entry);

        try {
            if (navigator.sendBeacon) {
                const blob = new Blob([body], { type: 'application/json' });
                if (navigator.sendBeacon(settings.consentLogEndpoint, blob)) {
                    return;
                }
            }
        } catch (error) {}

        try {
            fetch(settings.consentLogEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                keepalive: true,
                headers: {
                    'Content-Type': 'application/json'
                },
                body
            }).catch(() => {});
        } catch (error) {}
    },

    loadAllowedServiceEmbeds() {
        this.getOptionalCategories().forEach(category => {
            (this.services[category] || []).forEach(service => {
                if (!this.isServiceAllowed(category, service)) {
                    return;
                }

                if (this.loadedServiceEmbeds[service.id]) {
                    return;
                }

                const code = this.getServiceEmbedCode(service);

                if (!code) {
                    return;
                }

                this.loadedServiceEmbeds[service.id] = true;
                this.executeEmbedCode(code, service.id);
            });
        });
    },

    executeEmbedCode(code, serviceId) {
        const template = document.createElement('template');
        template.innerHTML = code;
        const wrapper = document.createElement('div');
        wrapper.hidden = true;
        wrapper.setAttribute('data-n24-consent-service', serviceId);
        document.body.appendChild(wrapper);

        Array.from(template.content.childNodes).forEach(node => {
            if (node.nodeType === Node.ELEMENT_NODE && node.tagName.toLowerCase() === 'script') {
                const script = document.createElement('script');
                Array.from(node.attributes).forEach(attr => script.setAttribute(attr.name, attr.value));

                script.textContent = node.textContent || '';
                document.head.appendChild(script);
                return;
            }

            wrapper.appendChild(node.cloneNode(true));
        });
    },

    showBanner() {
        if (document.getElementById('consent-banner')) return;
        this.previousActiveElement = document.activeElement;
        const hasOptionalServices = this.hasOptionalServices();
        const necessaryOnlyMode = this.isNecessaryOnlyMode();

        const html = `
      <div id="consent-banner" class="consent-banner consent-dialog-outline" role="dialog" aria-modal="true" aria-labelledby="consent-title" tabindex="-1">
        <div class="consent-content">
          <div class="consent-header">
            <div class="flex-align-center">
              <span class="consent-header-icon">${settings.boxIconSvg}</span>
              <h2 id="consent-title" class="mt-0 fs-1-5">${necessaryOnlyMode ? text('information_title') : text('dialog_title')}</h2>
            </div>
            <div class="consent-tabs">
               <button class="tab-btn active" data-tab="simple">${text('tab_overview')}</button>
               <button class="tab-btn" data-tab="details">${text('tab_details')}</button>
               ${necessaryOnlyMode ? '' : `<button class="tab-btn" data-tab="history">${text('tab_history')}</button>`}
            </div>
          </div>

          <div id="view-simple" class="consent-view active">
            <p>${hasOptionalServices ? text('intro_text') : text('necessary_only_intro')}</p>
            <div class="consent-categories">
              <label class="consent-option disabled" data-info="${text('necessary_info')}">
                <input type="checkbox" checked disabled>
                <span class="checkbox-visual"></span>
                <span>${text('necessary_label')}</span>
              </label>
              ${this.services.statistics && this.services.statistics.length > 0 ? `
              <label class="consent-option" data-info="${text('statistics_info')}">
                <input type="checkbox" id="consent-stats" class="consent-master-checkbox" data-category="statistics">
                <span class="checkbox-visual"></span>
                <span>${text('statistics_label')}</span>
              </label>` : ''}
              ${this.services.marketing && this.services.marketing.length > 0 ? `
              <label class="consent-option" data-info="${text('marketing_info')}">
                <input type="checkbox" id="consent-marketing" class="consent-master-checkbox" data-category="marketing">
                <span class="checkbox-visual"></span>
                <span>${text('marketing_label')}</span>
              </label>` : ''}
              ${this.services.external_media && this.services.external_media.length > 0 ? `
              <label class="consent-option" data-info="${text('external_media_info')}">
                <input type="checkbox" id="consent-external-media" class="consent-master-checkbox" data-category="external_media">
                <span class="checkbox-visual"></span>
                <span>${text('external_media_label')}</span>
              </label>` : ''}
            </div>
            <div id="consent-info-display" class="consent-info-display">
               <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
               <span>${text('info_default')}</span>
            </div>
          </div>

          <div id="view-details" class="consent-view">
            <p class="detail-intro">${hasOptionalServices ? text('details_intro') : text('necessary_only_intro')}</p>
            <div class="service-accordion" id="service-list"></div>
          </div>

          ${necessaryOnlyMode ? '' : `<div id="view-history" class="consent-view">
            <div class="history-container">
                <p class="history-intro">
                  ${text('history_intro')}
                  <br><br>
                  <strong>${text('consent_id_label')}</strong> <span id="consent-uid" class="history-uid"></span>
                </p>
                <div id="history-list" class="history-list">
                   <!-- History Items rendered here -->
                </div>
            </div>
          </div>`}

          <div class="consent-actions" style="display:flex; flex-direction:row; flex-wrap:wrap; gap:10px; margin-bottom:10px;">
            ${necessaryOnlyMode
                ? `<button id="consent-close-information" class="btn btn-primary" style="flex:1; min-width:140px; justify-content:center;">${text('information_close_button')}</button>`
                : `<button id="consent-reject" class="btn btn-primary" style="flex:1; min-width:140px; justify-content:center;">${text('reject_button')}</button>
                   <button id="consent-accept-all" class="btn btn-primary" style="flex:1; min-width:140px; justify-content:center;">${text('accept_all_button')}</button>`}
          </div>
          ${necessaryOnlyMode ? '' : `<div class="consent-secondary-actions" style="display:flex; flex-direction:row; flex-wrap:wrap; gap:10px; align-items:center;">
            <button id="consent-save" class="btn btn-ghost btn-sm" style="flex:1; min-width:140px; justify-content:center;">${text('save_button')}</button>
            <button id="consent-customize" class="btn btn-ghost btn-sm" type="button" style="flex:1; min-width:140px; justify-content:center; text-align:center;">${text('customize_button')}</button>
          </div>`}
        </div>
      </div>
    `;

        document.body.insertAdjacentHTML('beforeend', html);
        requestAnimationFrame(() => {
            document.getElementById('consent-banner').classList.add('visible');
            this.renderServiceList();
            this.renderHistory();
            this.updateCheckboxState();
        });

        this.bindBannerEvents();
        this.bindInfoHoverEvents();
        this.bindTabs();
    },

    renderHistory() {
        const container = document.getElementById('history-list');
        const uidSpan = document.getElementById('consent-uid');
        if (uidSpan && this.consent.uid) uidSpan.textContent = this.consent.uid;

        if (!container) return;

        if (!this.consent.history || this.consent.history.length === 0) {
            container.innerHTML = `<p class="text-italic">${text('history_empty')}</p>`;
            return;
        }

        let html = `<table class="cookie-table"><thead><tr><th>${text('history_date_label')}</th><th>${text('history_status_label')}</th></tr></thead><tbody>`;
        this.consent.history.forEach(entry => {
            const date = new Date(entry.timestamp).toLocaleString('de-DE');
            const s = entry.settings;
            const statusParts = [];
            if (s.necessary) statusParts.push(`${settings.texts.necessary_label}: ✅`);
            this.getOptionalCategories().forEach(category => {
                if (!Array.isArray(this.services[category]) || this.services[category].length === 0) return;
                statusParts.push(`${settings.texts[`${category}_label`]}: ${s[category] ? '✅' : '❌'}`);
            });
            if (entry.bannerVersion) statusParts.push(`Banner: ${entry.bannerVersion}`);
            if (entry.privacyPolicyVersion) statusParts.push(`Datenschutz: ${entry.privacyPolicyVersion}`);

            html += `
            <tr>
              <td class="table-date">${escapeHTML(date)}</td>
              <td class="table-date">${escapeHTML(statusParts.join(' '))}</td>
            </tr>
          `;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    },

    renderServiceList() {
        const container = document.getElementById('service-list');
        if (!container) return;

        let html = '';
        const categories = this.getServiceCategories();
        const titles = {
            necessary: settings.texts.necessary_label ?? defaultTexts.necessary_label,
            statistics: settings.texts.statistics_label ?? defaultTexts.statistics_label,
            marketing: settings.texts.marketing_label ?? defaultTexts.marketing_label,
            external_media: settings.texts.external_media_label ?? defaultTexts.external_media_label
        };

        categories.forEach(cat => {
            const services = this.services[cat];
            if (!Array.isArray(services) || services.length === 0) return;
            const isNecessary = cat === 'necessary';
            const detailsId = `consent-service-details-${cat}`;

            html += `
        <div class="service-group" data-category="${cat}">
          <div class="service-header">
            <label class="service-toggle">
              <input type="checkbox" class="category-checkbox" data-cat="${cat}" ${isNecessary ? 'checked disabled' : ''}>
              <span class="checkbox-visual small"></span>
              <strong>${escapeHTML(titles[cat])}</strong>
            </label>
            <span class="service-badge">${services.length} ${services.length > 1 ? text('service_count_plural') : text('service_count_single')}</span>
            <button type="button" class="service-expand-btn" aria-label="${formatText('service_details_show_label', { category: titles[cat] })}" aria-expanded="false" aria-controls="${detailsId}"><span aria-hidden="true">▼</span></button>
          </div>
          <div id="${detailsId}" class="service-details hidden">
            ${services.map(s => `
              <div class="service-item">
                <div class="service-item-header">
                   <div class="service-name-text">${escapeHTML(s.name)}</div>
                   <label class="service-selection">
                     <span class="text-muted-small">${isNecessary ? text('service_always_on') : ''}</span>
                     <input type="checkbox" class="service-checkbox" data-cat="${cat}" data-id="${escapeHTML(s.id)}" ${isNecessary ? 'checked disabled' : ''}>
                     <span class="switch-visual"></span>
                   </label>
                </div>
                <div class="service-meta-grid">
                   <div class="meta-label">${text('service_description_label')}</div>
                   <div class="meta-value">${escapeHTML(s.purpose)}</div>
                   <div class="meta-label">${text('service_provider_label')}</div>
                   <div class="meta-value">
                     ${escapeHTML(s.provider)}<br>
                     <small class="text-muted-small">${escapeHTML(s.address || '')}</small><br>
                     <a href="${escapeHTML(s.privacyUrl || '#')}" class="link-muted">${text('service_privacy_label')}</a>
                     ${s.cookiePolicyUrl && s.cookiePolicyUrl !== s.privacyUrl ? `<br><a href="${escapeHTML(s.cookiePolicyUrl)}" class="link-muted">${text('service_cookie_policy_label')}</a>` : ''}
                   </div>
                   <div class="meta-label">${text('service_legal_basis_label')}</div>
                   <div class="meta-value">${escapeHTML(s.legalBasis || '')}</div>
                   <div class="meta-label">${text('service_third_country_label')}</div>
                   <div class="meta-value">${escapeHTML(s.thirdCountryTransfer || '')}</div>
                   <div class="meta-label">${text('service_recipient_country_label')}</div>
                   <div class="meta-value">${escapeHTML(s.recipientCountry || '')}</div>
                   <div class="meta-label">${text('service_safeguards_label')}</div>
                   <div class="meta-value">${escapeHTML(s.safeguards || '')}</div>
                   ${Array.isArray(s.cookies) && s.cookies.length > 0 ? `
                     <div class="meta-label meta-label-full">${text('service_cookies_label')}</div>
                     <div class="meta-value meta-value-full cookie-table-wrap">
                       <table class="cookie-table">
                         <thead><tr><th>${text('cookie_name_label')}</th><th>${text('cookie_expiry_label')}</th><th>${text('cookie_purpose_label')}</th></tr></thead>
                         <tbody>
                           ${s.cookies.map(c => `
                             <tr>
                               <td>${escapeHTML(c.name)}</td>
                               <td>${escapeHTML(c.expiry)}</td>
                               <td>${escapeHTML(c.purpose || c.type)}</td>
                             </tr>
                           `).join('')}
                         </tbody>
                       </table>
                     </div>
                   ` : ''}
                </div>
              </div>
            `).join('')}
          </div>
        </div>
      `;
        });
        container.innerHTML = html;

        container.querySelectorAll('.service-header').forEach(header => {
            header.addEventListener('click', (e) => {
                if (e.target.tagName === 'INPUT' || e.target.classList.contains('checkbox-visual')) return;
                const group = header.closest('.service-group');
                const details = group.querySelector('.service-details');
                const btn = group.querySelector('.service-expand-btn');
                details.classList.toggle('hidden');
                const isExpanded = !details.classList.contains('hidden');
                const category = group.getAttribute('data-category');
                btn.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
                btn.setAttribute(
                    'aria-label',
                    formatRawText(isExpanded ? 'service_details_hide_label' : 'service_details_show_label', { category: titles[category] })
                );
                const icon = btn.querySelector('[aria-hidden="true"]');
                if (icon) icon.textContent = isExpanded ? '▲' : '▼';
            });
        });

        container.querySelectorAll('.category-checkbox').forEach(cb => {
            cb.addEventListener('change', (e) => {
                const cat = e.target.getAttribute('data-cat');
                const checked = e.target.checked;
                const group = cb.closest('.service-group');
                group.querySelectorAll('.service-checkbox').forEach(scb => {
                    if (!scb.disabled) scb.checked = checked;
                });
                const master = document.querySelector(`.consent-master-checkbox[data-category="${cat}"]`);
                if (master) master.checked = checked;
            });
        });

        container.querySelectorAll('.service-checkbox').forEach(cb => {
            cb.addEventListener('change', (e) => {
                const cat = e.target.getAttribute('data-cat');
                const group = cb.closest('.service-group');
                const checked = group.querySelectorAll('.service-checkbox:checked:not(:disabled)');
                const catCb = group.querySelector('.category-checkbox');
                if (catCb && !catCb.disabled) catCb.checked = checked.length > 0;
                const master = document.querySelector(`.consent-master-checkbox[data-category="${cat}"]`);
                if (master) master.checked = checked.length > 0;
            });
        });
    },

    updateDetailedView() {
        const hasServiceData = Object.keys(this.consent.services).length > 0;
        this.getOptionalCategories().forEach(cat => {
            const catServices = this.services[cat];
            const catConsent = this.consent[cat];

            if (catServices) {
                catServices.forEach(s => {
                    const el = document.querySelector(`.service-checkbox[data-id="${s.id}"]`);
                    if (el) {
                        if (hasServiceData && this.consent.services[s.id] !== undefined) {
                            el.checked = this.consent.services[s.id];
                        } else {
                            el.checked = catConsent;
                        }
                    }
                });
            }

            const catCb = document.querySelector(`.category-checkbox[data-cat="${cat}"]`);
            if (catCb) {
                const group = catCb.closest('.service-group');
                const checkedCount = group.querySelectorAll('.service-checkbox:checked').length;
                catCb.checked = checkedCount > 0;
            }
        });
    },

    activateTab(tabName) {
        const tab = document.querySelector(`.tab-btn[data-tab="${tabName}"]`);
        const target = document.getElementById(`view-${tabName}`);

        if (!tab || !target) return;

        document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        document.querySelectorAll('.consent-view').forEach(v => v.classList.remove('active'));
        target.classList.add('active');

        const customizeButton = document.getElementById('consent-customize');
        const saveButton = document.getElementById('consent-save');
        const secondaryActions = document.querySelector('.consent-secondary-actions');

        if (customizeButton) {
            customizeButton.hidden = tabName === 'details';
        }

        if (secondaryActions) {
            secondaryActions.hidden = tabName === 'details' && !saveButton;
        }

        if (tabName === 'history') {
            this.renderHistory();
        }
    },

    bindTabs() {
        const tabs = document.querySelectorAll('.tab-btn');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                this.activateTab(tab.dataset.tab);
            });
        });
    },

    bindInfoHoverEvents() {
        const display = document.querySelector('#consent-info-display span');
        const options = document.querySelectorAll('.consent-option');
        options.forEach(option => {
            option.addEventListener('mouseenter', () => {
                const info = option.getAttribute('data-info');
                if (display && info) display.textContent = info;
                document.getElementById('consent-info-display')?.classList.add('active');
            });
            option.addEventListener('mouseleave', () => {
                if (display) display.textContent = settings.texts.info_default;
                document.getElementById('consent-info-display')?.classList.remove('active');
            });
            const input = option.querySelector('input');
            if (input && !input.disabled) {
                input.addEventListener('change', () => {
                    const cat = input.getAttribute('data-category');
                    const detailCatCb = document.querySelector(`.category-checkbox[data-cat="${cat}"]`);
                    if (detailCatCb) {
                        detailCatCb.checked = input.checked;
                        detailCatCb.dispatchEvent(new Event('change'));
                    }
                });
            }
        });
    },

    hideBanner() {
        document.documentElement.classList.remove('consent-pending');
        const banner = document.getElementById('consent-banner');
        if (banner) {
            banner.classList.remove('visible');
            setTimeout(() => {
                banner.remove();
                if (this.previousActiveElement) this.previousActiveElement.focus();
            }, 400);
        }
    },

    bindBannerEvents() {
        const banner = document.getElementById('consent-banner');

        document.getElementById('consent-close-information')?.addEventListener('click', () => {
            this.hideBanner();
        });

        document.getElementById('consent-accept-all')?.addEventListener('click', () => {
            banner.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                if (!cb.disabled) cb.checked = true;
            });
            this.saveConsent({
                necessary: true,
                statistics: Array.isArray(this.services.statistics) && this.services.statistics.length > 0,
                marketing: Array.isArray(this.services.marketing) && this.services.marketing.length > 0,
                external_media: Array.isArray(this.services.external_media) && this.services.external_media.length > 0
            });
        });

        document.getElementById('consent-reject')?.addEventListener('click', () => {
            this.saveConsent({ necessary: true, statistics: false, marketing: false, external_media: false });
        });

        document.getElementById('consent-save')?.addEventListener('click', () => {
            this.saveConsent({
                necessary: true,
                statistics: document.getElementById('consent-stats')?.checked || false,
                marketing: document.getElementById('consent-marketing')?.checked || false,
                external_media: document.getElementById('consent-external-media')?.checked || false
            });
        });

        document.getElementById('consent-customize')?.addEventListener('click', () => {
            this.activateTab('details');
        });

        const focusableSelector = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
        banner.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                const focusable = Array.from(banner.querySelectorAll(focusableSelector))
                    .filter(el => el.offsetParent !== null && !el.disabled);

                if (focusable.length === 0) return;

                const first = focusable[0];
                const last = focusable[focusable.length - 1];

                if (e.shiftKey) {
                    if (document.activeElement === first) {
                        e.preventDefault();
                        last.focus();
                    }
                } else {
                    if (document.activeElement === last) {
                        e.preventDefault();
                        first.focus();
                    }
                }
            }
            if (e.key === 'Escape') {
                this.hideBanner();
            }
        });

        setTimeout(() => {
            if (banner) banner.focus({ preventScroll: true });
        }, 600);
    },

    bindEvents() {
        const knownSettingsLabels = new Set([
            settings.texts.settings_link,
            defaultTexts.settings_link,
            settings.texts.information_settings_link,
            defaultTexts.information_settings_link
        ].filter(Boolean).map(label => String(label).trim()));
        const openLinks = Array.from(document.querySelectorAll('.cookie-settings-link, a[href="#"]'))
            .filter(link => link.classList.contains('cookie-settings-link') || knownSettingsLabels.has(link.textContent.trim()));

        openLinks.forEach(link => {
            if (this.isNecessaryOnlyMode()) {
                link.textContent = settings.texts.information_settings_link;
                link.setAttribute('aria-label', settings.texts.information_floating_aria_label);
            }

            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.showBanner();
                setTimeout(() => {
                    this.updateCheckboxState();
                    this.activateTab(this.isNecessaryOnlyMode() ? 'simple' : 'details');
                }, 50);
            });
        });

        document.addEventListener('click', (event) => {
            const button = event.target.closest('.n24-content-blocker-load-once, .n24-content-blocker-accept');

            if (!button) {
                return;
            }

            event.preventDefault();
            if (button.disabled || button.getAttribute('aria-disabled') === 'true') {
                return;
            }

            const block = button.closest('[data-n24-content-blocker]');

            if (block) {
                this.acceptBlockedContent(block, button.classList.contains('n24-content-blocker-accept'));
            }
        });
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        ConsentManager.init();
    });
} else {
    ConsentManager.init();
}

window.N24ConsentManager = ConsentManager;
window.ConsetManager = ConsentManager;
window.ConnyConsentManager = ConsentManager;
})();
