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

// Click-to-play videos (x-site.videos): load the youtube-nocookie player only
// after the visitor taps play, so YouTube sees nothing before that.
document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[data-youtube]') : null;
    if (!link || !/^[A-Za-z0-9_-]{11}$/.test(link.dataset.youtube ?? '')) {
        return;
    }

    event.preventDefault();
    const player = document.createElement('iframe');
    player.src = `https://www.youtube-nocookie.com/embed/${link.dataset.youtube}?autoplay=1&rel=0`;
    player.title = link.dataset.title ?? 'Video';
    player.allow = 'autoplay; encrypted-media; picture-in-picture';
    player.allowFullscreen = true;
    player.className = 'absolute inset-0 size-full';
    link.replaceWith(player);
});
