// Mobile number checks with Google's libphonenumber, the same library the
// server uses (app/Support/Phone.php). The library is large, so it loads only
// when a page has a phone field and the visitor starts using the form.

let util = null;
let types = null;
let formats = null;
let loading = null;

export function loadPhoneLibrary() {
    loading ??= import('google-libphonenumber').then((module) => {
        const lib = module.default ?? module;
        util = lib.PhoneNumberUtil.getInstance();
        types = lib.PhoneNumberType;
        formats = lib.PhoneNumberFormat;
    });

    return loading;
}

export function phoneLibraryReady() {
    return util !== null;
}

/**
 * The parsed number when it is a valid mobile, or null. Mirrors Phone::parse().
 */
export function parseMobile(number, country) {
    const value = (number ?? '').trim();
    if (!util || value === '' || [...value].length > 30) {
        return null;
    }

    try {
        const parsed = util.parse(value, (country || 'IN').toUpperCase());
        if (!util.isValidNumber(parsed)) {
            return null;
        }
        const type = util.getNumberType(parsed);

        return type === types.MOBILE || type === types.FIXED_LINE_OR_MOBILE ? parsed : null;
    } catch {
        return null;
    }
}

export function isValidMobile(number, country) {
    return parseMobile(number, country) !== null;
}

export function regionOf(parsed) {
    return util.getRegionCodeForNumber(parsed);
}

/**
 * The number as people write it after the country code: 98765 43210.
 */
export function withoutCountryCode(parsed) {
    return util.format(parsed, formats.INTERNATIONAL).replace(/^\+\d+\s*/, '').trim();
}

export function exampleMobile(country) {
    if (!util) {
        return null;
    }
    try {
        const example = util.getExampleNumberForType(country, types.MOBILE);

        return example ? withoutCountryCode(example) : null;
    } catch {
        return null;
    }
}
