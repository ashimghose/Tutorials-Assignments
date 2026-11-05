import { isValidMobile, loadPhoneLibrary, phoneLibraryReady } from './phone';

// Checks a form in the browser with the same rules and messages as the
// server, before it is sent. The attributes come from the server's own rules
// (app/Support/ClientRules.php): required, maxlength, minlength, min, max,
// data-pattern, data-confirmed, data-password and data-phone, each with a
// data-msg-* message. The server checks everything again.
//
// Works on plain forms and Livewire forms: add data-checks and novalidate.

// ui-checkbox is Flux's checkbox (<flux:checkbox>).
const FIELD_SELECTOR = ['input', 'textarea', 'select', 'ui-checkbox'].flatMap((tag) => [`${tag}[name]`, `${tag}[wire\\:model]`]).join(', ');
const SKIP_TYPES = ['hidden', 'submit', 'button', 'reset', 'file', 'image'];

function fieldName(el) {
    return el.getAttribute('name') ?? el.getAttribute('wire:model') ?? '';
}

function chars(value) {
    return [...value.trim()].length;
}

function formOf(el) {
    return el.form ?? el.closest('form');
}

function isFluxCheckbox(el) {
    return el.localName === 'ui-checkbox';
}

function isEmpty(el) {
    if (isFluxCheckbox(el)) {
        return !el.hasAttribute('data-checked');
    }
    if (el.type === 'checkbox') {
        return !el.checked;
    }
    if (el.type === 'radio') {
        const group = formOf(el)?.querySelectorAll(`input[type="radio"][name="${CSS.escape(el.name)}"]`) ?? [el];

        return ![...group].some((radio) => radio.checked);
    }

    return el.value.trim() === '';
}

function countryFor(el) {
    const name = el.dataset.phone;
    const scope = el.closest('[data-phone-field]') ?? formOf(el) ?? document;

    return scope.querySelector(`[data-phone-country], [name="${CSS.escape(name)}"]`)?.value || 'IN';
}

function passwordProblem(el) {
    const checks = (el.dataset.password ?? '').split(' ').filter(Boolean);
    const value = el.value;
    const tests = {
        mixed: () => /\p{Ll}/u.test(value) && /\p{Lu}/u.test(value),
        letters: () => /\p{L}/u.test(value),
        numbers: () => /\p{N}/u.test(value),
        symbols: () => /[\p{Z}\p{S}\p{P}]/u.test(value),
    };

    const failed = checks.find((check) => tests[check] && !tests[check]());

    return failed ? el.dataset[`msgPassword${failed[0].toUpperCase()}${failed.slice(1)}`] : null;
}

/**
 * The first broken rule's message, or null when the field is fine.
 */
export function problemWith(el) {
    const msg = (key, fallback) => el.dataset[key] || fallback || el.validationMessage || 'Please check this field.';

    if (el.hasAttribute('required') && isEmpty(el)) {
        return msg('msgRequired', 'This field is required.');
    }
    if (isFluxCheckbox(el) || el.type === 'checkbox' || el.type === 'radio' || isEmpty(el)) {
        return null;
    }

    const value = 'lowercase' in el.dataset ? el.value.trim().toLowerCase() : el.value.trim();

    if (el.type === 'email' || el.dataset.msgEmail) {
        // The browser's own email check, same as type="email".
        const probe = document.createElement('input');
        probe.type = 'email';
        probe.value = value;
        if (!probe.checkValidity()) {
            return msg('msgEmail');
        }
    }
    if (el.hasAttribute('minlength') && chars(value) < Number(el.getAttribute('minlength'))) {
        return msg('msgMinlength');
    }
    if (el.hasAttribute('maxlength') && chars(value) > Number(el.getAttribute('maxlength'))) {
        return msg('msgMaxlength');
    }
    if (el.type === 'number' || el.type === 'date') {
        const toValue = (v) => (el.type === 'number' ? Number(v) : v);
        if (el.type === 'number' && (!/^-?\d+$/.test(value) || Number.isNaN(Number(value)))) {
            return msg('msgInteger', 'Enter a whole number.');
        }
        if (el.hasAttribute('min') && toValue(value) < toValue(el.getAttribute('min'))) {
            return msg('msgMin');
        }
        if (el.hasAttribute('max') && toValue(value) > toValue(el.getAttribute('max'))) {
            return msg('msgMax');
        }
    }
    if (el.dataset.in !== undefined && !el.dataset.in.split(',').includes(value)) {
        return msg('msgIn');
    }
    if (el.dataset.pattern && !new RegExp(el.dataset.pattern).test(value)) {
        return msg('msgPattern');
    }
    if (el.dataset.password) {
        const problem = passwordProblem(el);
        if (problem) {
            return problem;
        }
    }
    if (el.dataset.confirmed) {
        const other = formOf(el)?.querySelector(`[name="${CSS.escape(el.dataset.confirmed)}"], [wire\\:model="${CSS.escape(el.dataset.confirmed)}"]`);
        if (other && other.value !== el.value) {
            return msg('msgConfirmed');
        }
    }
    if (el.dataset.phone !== undefined && phoneLibraryReady() && !isValidMobile(value, countryFor(el))) {
        return msg('msgPhone');
    }

    return null;
}

function container(el) {
    return el.closest('[data-phone-field], [data-flux-field], [data-field], fieldset') ?? el.parentElement;
}

function errorId(el) {
    return `${(el.id || fieldName(el)).replace(/[^\w-]/g, '-')}-client-error`;
}

export function showProblem(el, message) {
    const box = container(el);
    const id = errorId(el);
    let note = box.querySelector(`[data-client-error="${CSS.escape(id)}"]`);

    if (!message) {
        note?.remove();
        el.removeAttribute('aria-invalid');

        return;
    }

    // A fresh browser message replaces the server's message for this field.
    box.querySelectorAll('[data-flux-error], [data-server-error]').forEach((old) => old.remove());

    if (!note) {
        note = document.createElement('p');
        note.dataset.clientError = id;
        note.id = id;
        note.setAttribute('role', 'alert');
        note.className = 'mt-1 text-sm font-medium text-red-700';
        box.appendChild(note);
    }
    note.textContent = message;
    el.setAttribute('aria-invalid', 'true');
    el.setAttribute('aria-describedby', id);
}

function fieldsOf(form) {
    return [...form.querySelectorAll(FIELD_SELECTOR)].filter(
        (el) => !el.hasAttribute('disabled') && !SKIP_TYPES.includes(el.type) && !el.closest('[aria-hidden="true"], template'),
    );
}

/**
 * Checks every field, shows the messages, and returns true when all pass.
 */
export function checkForm(form) {
    let first = null;
    const seenRadios = new Set();

    for (const el of fieldsOf(form)) {
        if (el.type === 'radio') {
            if (seenRadios.has(el.name)) {
                continue;
            }
            seenRadios.add(el.name);
        }
        const problem = problemWith(el);
        showProblem(el, problem);
        first ??= problem ? el : null;
    }

    if (first) {
        first.focus({ preventScroll: true });
        first.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }

    return first === null;
}

const passed = new WeakSet();

function onSubmit(event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-checks')) {
        return;
    }
    if (passed.has(form)) {
        passed.delete(form);

        return;
    }

    const block = () => {
        event.preventDefault();
        event.stopImmediatePropagation();
    };

    if (form.querySelector('[data-phone]') && !phoneLibraryReady()) {
        // Wait for the phone library, then send the form again.
        block();
        loadPhoneLibrary()
            .catch(() => null)
            .then(() => {
                if (checkForm(form)) {
                    passed.add(form);
                    form.requestSubmit(event.submitter && event.submitter.form === form ? event.submitter : undefined);
                }
            });

        return;
    }

    if (!checkForm(form)) {
        block();
    }
}

function recheck(event) {
    let el = event.target.closest?.(FIELD_SELECTOR);
    if (!el || !formOf(el)?.hasAttribute('data-checks')) {
        return;
    }
    if (el.type === 'radio') {
        el = formOf(el).querySelector(`input[type="radio"][name="${CSS.escape(el.name)}"]`);
    }
    // Only re-check a field already showing a message, so nobody is told
    // off while still typing.
    if (el.getAttribute('aria-invalid') === 'true') {
        showProblem(el, problemWith(el));
    }
}

function onFocusIn(event) {
    if (event.target.closest?.('form[data-checks]')?.querySelector('[data-phone]')) {
        loadPhoneLibrary().catch(() => null);
    }
}

export function initFormChecks() {
    if (window.formChecksReady) {
        return;
    }
    window.formChecksReady = true;
    // Capture phase, so this runs before Livewire's own submit handler.
    document.addEventListener('submit', onSubmit, true);
    document.addEventListener('input', recheck);
    document.addEventListener('change', recheck);
    document.addEventListener('focusin', onFocusIn);
}
