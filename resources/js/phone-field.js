import { problemWith, showProblem } from './form-checks';
import { exampleMobile, loadPhoneLibrary, withoutCountryCode, parseMobile, regionOf } from './phone';

// The country code dropdown next to each mobile number box
// (resources/views/components/phone-field.blade.php). The list of countries,
// names and flags comes from country-list-with-dial-code-and-flag and loads
// the first time the list is opened.

let countries = null;

async function loadCountries() {
    if (!countries) {
        const module = await import('country-list-with-dial-code-and-flag');
        // CommonJS package: the list can sit one or two "default"s deep.
        const api = [module, module.default, module.default?.default].find((candidate) => typeof candidate?.getAll === 'function');
        const list = api.getAll();
        const seen = new Set();
        countries = list
            .map((country) => country.data ?? country)
            .filter((country) => !seen.has(country.code) && seen.add(country.code))
            .sort((a, b) => a.name.localeCompare(b.name));
    }

    return countries;
}

function parts(root) {
    return {
        country: root.querySelector('[data-phone-country]'),
        toggle: root.querySelector('[data-phone-toggle]'),
        flag: root.querySelector('[data-phone-flag]'),
        dial: root.querySelector('[data-phone-dial]'),
        panel: root.querySelector('[data-phone-panel]'),
        search: root.querySelector('[data-phone-search]'),
        list: root.querySelector('[data-phone-list]'),
        number: root.querySelector('[data-phone]'),
    };
}

function allowed(root) {
    return new Set((root.dataset.countries ?? '').split(' ').filter(Boolean));
}

function preferred(root) {
    return (root.dataset.preferred ?? '').split(' ').filter(Boolean);
}

function choose(root, country, { focus = true } = {}) {
    const p = parts(root);
    p.country.value = country.code;
    p.country.dispatchEvent(new Event('input', { bubbles: true }));
    p.country.dispatchEvent(new Event('change', { bubbles: true }));
    p.flag.textContent = country.flag;
    p.dial.textContent = country.dial_code;
    p.toggle.setAttribute('aria-label', `${root.dataset.countryLabel}: ${country.name} ${country.dial_code}`);
    close(root);

    loadPhoneLibrary()
        .then(() => {
            const example = exampleMobile(country.code);
            if (example) {
                p.number.placeholder = example;
            }
            if (p.number.getAttribute('aria-invalid') === 'true') {
                showProblem(p.number, problemWith(p.number));
            }
        })
        .catch(() => null);

    if (focus) {
        p.number.focus();
    }
}

function option(root, country) {
    const item = document.createElement('li');
    item.setAttribute('role', 'option');
    item.dataset.code = country.code;
    item.tabIndex = -1;
    item.setAttribute('aria-selected', String(parts(root).country.value === country.code));
    item.className = 'flex cursor-pointer items-center gap-2 px-3 py-2 text-sm text-zinc-800 hover:bg-zinc-100 focus:bg-zinc-100 focus:outline-none aria-selected:font-semibold';
    item.innerHTML = '<span aria-hidden="true"></span><span class="flex-1"></span><span class="text-zinc-500"></span>';
    item.children[0].textContent = country.flag;
    item.children[1].textContent = country.name;
    item.children[2].textContent = country.dial_code;
    item.addEventListener('click', () => choose(root, country));
    item.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            choose(root, country);
        }
    });

    return item;
}

function matches(country, query) {
    if (query === '') {
        return true;
    }
    const q = query.toLowerCase().replace(/^\+/, '');

    return (
        country.name.toLowerCase().includes(q) ||
        (country.local_name ?? '').toLowerCase().includes(q) ||
        country.code.toLowerCase() === q ||
        country.dial_code.replace(/\D/g, '').startsWith(q.replace(/\D/g, '') || '-')
    );
}

function render(root) {
    const p = parts(root);
    const ok = allowed(root);
    const query = p.search.value.trim();
    const all = countries.filter((country) => ok.has(country.code) && matches(country, query));
    const top = query === '' ? preferred(root).map((code) => all.find((country) => country.code === code)).filter(Boolean) : [];

    p.list.replaceChildren();
    top.forEach((country) => p.list.appendChild(option(root, country)));
    if (top.length) {
        const line = document.createElement('li');
        line.setAttribute('role', 'separator');
        line.className = 'my-1 border-t border-zinc-200';
        p.list.appendChild(line);
    }
    all.filter((country) => !top.includes(country)).forEach((country) => p.list.appendChild(option(root, country)));
    if (!p.list.querySelector('[role="option"]')) {
        const none = document.createElement('li');
        none.className = 'px-3 py-2 text-sm text-zinc-500';
        none.textContent = root.dataset.noMatch;
        p.list.appendChild(none);
    }
}

async function open(root) {
    const p = parts(root);
    await loadCountries();
    p.search.value = '';
    render(root);
    p.panel.hidden = false;
    p.toggle.setAttribute('aria-expanded', 'true');
    p.search.focus();
}

function close(root) {
    const p = parts(root);
    p.panel.hidden = true;
    p.toggle.setAttribute('aria-expanded', 'false');
}

/**
 * Someone typed the full number with + in front: move the dropdown to that country.
 */
async function followTypedCountry(root) {
    const p = parts(root);
    if (!/^\s*(\+|00)/.test(p.number.value)) {
        return;
    }
    await loadPhoneLibrary();
    const parsed = parseMobile(p.number.value.replace(/^\s*00/, '+'), p.country.value);
    const region = parsed && regionOf(parsed);
    if (region && allowed(root).has(region)) {
        await loadCountries();
        const country = countries.find((c) => c.code === region);
        if (country) {
            p.number.value = withoutCountryCode(parsed);
            p.number.dispatchEvent(new Event('input', { bubbles: true }));
            choose(root, country, { focus: false });
        }
    }
}

function onClick(event) {
    const toggle = event.target.closest('[data-phone-toggle]');
    if (toggle) {
        const root = toggle.closest('[data-phone-field]');
        if (toggle.getAttribute('aria-expanded') === 'true') {
            close(root);
        } else {
            open(root);
        }

        return;
    }
    document.querySelectorAll('[data-phone-field] [data-phone-panel]:not([hidden])').forEach((panel) => {
        if (!panel.parentElement.contains(event.target)) {
            close(panel.closest('[data-phone-field]'));
        }
    });
}

function onKeydown(event) {
    const root = event.target.closest?.('[data-phone-field]');
    if (!root || !event.target.closest('[data-phone-panel]')) {
        return;
    }
    const p = parts(root);
    const items = [...p.list.querySelectorAll('[role="option"]')];
    const index = items.indexOf(document.activeElement);

    if (event.key === 'Escape') {
        event.preventDefault();
        close(root);
        p.toggle.focus();
    } else if (event.key === 'ArrowDown') {
        event.preventDefault();
        items[Math.min(index + 1, items.length - 1)]?.focus();
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        (index <= 0 ? p.search : items[index - 1]).focus();
    } else if (event.key === 'Enter' && event.target === p.search) {
        event.preventDefault();
        items[0]?.click();
    }
}

function onInput(event) {
    if (event.target.matches?.('[data-phone-search]')) {
        render(event.target.closest('[data-phone-field]'));
    }
}

function onFocusOut(event) {
    if (event.target.matches?.('[data-phone-field] [data-phone]')) {
        followTypedCountry(event.target.closest('[data-phone-field]')).catch(() => null);
    }
}

export function initPhoneFields() {
    if (window.phoneFieldsReady) {
        return;
    }
    window.phoneFieldsReady = true;
    document.addEventListener('click', onClick);
    document.addEventListener('keydown', onKeydown);
    document.addEventListener('input', onInput);
    document.addEventListener('focusout', onFocusOut);
}
