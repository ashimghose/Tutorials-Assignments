import { initFormChecks } from './form-checks';
import { initPhoneFields } from './phone-field';

// Browser checks that match the server's rules, and the country code
// dropdown next to mobile numbers.
initFormChecks();
initPhoneFields();
