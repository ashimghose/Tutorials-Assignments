// Google Analytics 4, only on public pages and only after the visitor taps
// "Allow" in the cookie banner. "No, thanks" means the script never loads.
// The choice is kept in this browser; "Cookie settings" in the footer reopens it.

const STORAGE_KEY = 'site.analytics-consent';

function readChoice() {
    try {
        return localStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
}

function saveChoice(choice) {
    try {
        localStorage.setItem(STORAGE_KEY, choice);
    } catch {
        // Storage can be blocked (private mode); the banner then shows on each page.
    }
}

function loadGoogleAnalytics(id) {
    if (window.gtag) {
        return;
    }

    window.dataLayer = window.dataLayer || [];
    window.gtag = function gtag() {
        window.dataLayer.push(arguments);
    };
    window.gtag('js', new Date());
    window.gtag('config', id, {
        allow_google_signals: false,
        allow_ad_personalization_signals: false,
    });

    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(id)}`;
    document.head.appendChild(script);
}

function removeGoogleCookies() {
    for (const cookie of document.cookie.split(';')) {
        const name = cookie.split('=')[0].trim();

        if (name.startsWith('_ga')) {
            const domain = window.location.hostname.replace(/^www\./, '');
            document.cookie = `${name}=; Max-Age=0; path=/`;
            document.cookie = `${name}=; Max-Age=0; path=/; domain=.${domain}`;
        }
    }
}

export function initConsent() {
    const banner = document.getElementById('cookie-consent');

    if (!banner) {
        return;
    }

    const id = banner.dataset.gaId;
    const choice = readChoice();

    if (choice === 'granted') {
        loadGoogleAnalytics(id);
    } else if (choice !== 'denied') {
        banner.hidden = false;
    }

    banner.addEventListener('click', (event) => {
        const button = event.target.closest('[data-consent]');

        if (!button) {
            return;
        }

        const next = button.dataset.consent;
        const wasGranted = readChoice() === 'granted';
        saveChoice(next);
        banner.hidden = true;

        if (next === 'granted') {
            loadGoogleAnalytics(id);
        } else if (wasGranted) {
            // Withdrawn: clear the cookies and reload so the script stops.
            removeGoogleCookies();
            window.location.reload();
        }
    });

    for (const link of document.querySelectorAll('[data-cookie-settings]')) {
        link.addEventListener('click', () => {
            banner.hidden = false;
        });
    }
}
