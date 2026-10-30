import { initConsent } from './consent';

// MKT-3: remember the campaign a visitor arrived from (utm_source, utm_medium,
// utm_campaign) for this browser tab, and add it to every Google Form link so
// each enquiry records its source. Runs only on the public pages; nothing is
// sent anywhere until the visitor opens a form and submits it.

const STORAGE_KEY = 'site.utm';
const FIELDS = ['source', 'medium', 'campaign'];

function readStored() {
    try {
        return JSON.parse(sessionStorage.getItem(STORAGE_KEY) ?? 'null');
    } catch {
        return null;
    }
}

function store(value) {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(value));
    } catch {
        // Storage can be blocked (private mode); the links still work without it.
    }
}

function referrerSource() {
    if (!document.referrer) {
        return 'direct';
    }

    try {
        const host = new URL(document.referrer).hostname;

        return host === window.location.hostname ? 'direct' : host;
    } catch {
        return 'direct';
    }
}

export function captureUtm(search = window.location.search) {
    const params = new URLSearchParams(search);
    const fromUrl = {};

    for (const field of FIELDS) {
        const value = params.get(`utm_${field}`);

        if (value) {
            fromUrl[field] = value.slice(0, 100);
        }
    }

    // A tagged link always wins; otherwise keep what this tab arrived with.
    if (Object.keys(fromUrl).length > 0) {
        store(fromUrl);

        return fromUrl;
    }

    const stored = readStored();

    if (stored) {
        return stored;
    }

    const first = { source: referrerSource() };
    store(first);

    return first;
}

export function decorateFormLinks(utm) {
    for (const link of document.querySelectorAll('a[data-utm-entries]')) {
        let entries;

        try {
            entries = JSON.parse(link.dataset.utmEntries);
        } catch {
            continue;
        }

        const url = new URL(link.href);

        for (const [field, entry] of Object.entries(entries)) {
            if (utm[field]) {
                url.searchParams.set(entry, utm[field]);
            }
        }

        link.href = url.toString();
    }
}

decorateFormLinks(captureUtm());
initConsent();
