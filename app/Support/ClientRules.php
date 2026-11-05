<?php

namespace App\Support;

use App\Rules\MobileNumber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\View\ComponentAttributeBag;

/**
 * Turns a field's server validation rules into HTML attributes, so the
 * browser checks the same things with the same messages before the form is
 * sent (resources/js/form-checks.js). The server still checks everything.
 *
 *     <flux:input name="email" :attributes="ClientRules::for('email', $rules['email'])" />
 */
final class ClientRules
{
    /**
     * @param  array<int, mixed>|string  $rules
     * @param  array<string, string>  $messages  the same custom messages the server uses
     */
    public static function for(string $field, array|string $rules, array $messages = [], ?string $attribute = null): ComponentAttributeBag
    {
        return new ComponentAttributeBag(self::attributes($field, $rules, $messages, $attribute));
    }

    /**
     * @param  array<int, mixed>|string  $rules
     * @param  array<string, string>  $messages
     * @return array<string, string|int|bool>
     */
    public static function attributes(string $field, array|string $rules, array $messages = [], ?string $attribute = null): array
    {
        $rules = is_string($rules) ? explode('|', $rules) : $rules;
        $numeric = collect($rules)->contains(fn (mixed $rule) => in_array($rule, ['integer', 'numeric'], true));
        $kind = $numeric ? 'numeric' : 'string';
        $message = fn (string $rule, array $replace = [], ?string $key = null): string => self::message($field, $rule, $messages, $attribute, $replace, $key);
        $out = [];

        foreach ($rules as $rule) {
            if ($rule instanceof RequiredIf) {
                $rule = (is_callable($rule->condition) ? ($rule->condition)() : $rule->condition) ? 'required' : null;
            }

            if ($rule instanceof MobileNumber) {
                $out['data-phone'] = $rule->countryField;
                $out['data-msg-phone'] = MobileNumber::message();

                continue;
            }

            if ($rule instanceof Password) {
                $out += self::passwordAttributes($rule, $message);

                continue;
            }

            if (! is_string($rule)) {
                continue;
            }

            [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

            switch ($name) {
                case 'required':
                case 'accepted':
                    $out['required'] = true;
                    $out['data-msg-required'] = $message($name);
                    break;
                case 'email':
                    $out['data-msg-email'] = $message('email');
                    break;
                case 'max':
                    $out[$numeric ? 'max' : 'maxlength'] = (int) $parameter;
                    $out[$numeric ? 'data-msg-max' : 'data-msg-maxlength'] = $message('max', ['max' => $parameter], "max.{$kind}");
                    break;
                case 'min':
                    $out[$numeric ? 'min' : 'minlength'] = (int) $parameter;
                    $out[$numeric ? 'data-msg-min' : 'data-msg-minlength'] = $message('min', ['min' => $parameter], "min.{$kind}");
                    break;
                case 'between':
                    [$min, $max] = explode(',', (string) $parameter);
                    $text = $message('between', ['min' => $min, 'max' => $max], "between.{$kind}");
                    $out += $numeric
                        ? ['min' => (int) $min, 'max' => (int) $max, 'data-msg-min' => $text, 'data-msg-max' => $text]
                        : ['minlength' => (int) $min, 'maxlength' => (int) $max, 'data-msg-minlength' => $text, 'data-msg-maxlength' => $text];
                    break;
                case 'digits':
                    $out['inputmode'] = 'numeric';
                    $out['data-pattern'] = '^\d{'.(int) $parameter.'}$';
                    $out['data-msg-pattern'] = $message('digits', ['digits' => $parameter]);
                    break;
                case 'regex':
                    $out['data-pattern'] = self::jsPattern((string) $parameter);
                    $out['data-msg-pattern'] = $message('regex');
                    break;
                case 'after':
                    if ($parameter === 'today') {
                        $out['min'] = Carbon::tomorrow()->toDateString();
                        $out['data-msg-min'] = $message('after', ['date' => $parameter]);
                    }
                    break;
                case 'in':
                    $out['data-in'] = (string) $parameter;
                    $out['data-msg-in'] = $message('in');
                    break;
                case 'confirmed':
                    $out['data-confirmed'] = $field.'_confirmation';
                    $out['data-msg-confirmed'] = $message('confirmed');
                    break;
            }
        }

        return $out;
    }

    /**
     * @param  callable(string, array<string, mixed>=, string|null=): string  $message
     * @return array<string, string|int>
     */
    private static function passwordAttributes(Password $rule, callable $message): array
    {
        $applied = $rule->appliedRules();
        $out = [
            'minlength' => (int) $applied['min'],
            'data-msg-minlength' => $message('min', ['min' => $applied['min']], 'min.string'),
        ];

        $checks = array_keys(array_filter([
            'mixed' => $applied['mixedCase'],
            'letters' => $applied['letters'],
            'numbers' => $applied['numbers'],
            'symbols' => $applied['symbols'],
        ]));
        if ($checks !== []) {
            $out['data-password'] = implode(' ', $checks);
            foreach ($checks as $check) {
                $out["data-msg-password-{$check}"] = $message('password', [], "password.{$check}");
            }
        }

        return $out;
    }

    /**
     * The same lookup order as Laravel: "field.rule", then "rule", then the
     * language file.
     *
     * @param  array<string, string>  $messages
     * @param  array<string, mixed>  $replace
     */
    private static function message(string $field, string $rule, array $messages, ?string $attribute, array $replace = [], ?string $key = null): string
    {
        $text = $messages["{$field}.{$rule}"] ?? $messages[$rule] ?? __('validation.'.($key ?? $rule));
        $name = $attribute ?? (trans()->has("validation.attributes.{$field}") ? __("validation.attributes.{$field}") : str_replace('_', ' ', Str::snake($field)));

        $replace = ['attribute' => $name, ...$replace];
        foreach ($replace as $search => $value) {
            $text = str_replace(
                [':'.$search, ':'.Str::ucfirst($search), ':'.Str::upper($search)],
                [(string) $value, Str::ucfirst((string) $value), Str::upper((string) $value)],
                $text,
            );
        }

        return $text;
    }

    /**
     * A PHP regex such as /^[a-z]+$/ as a JavaScript RegExp source.
     */
    private static function jsPattern(string $regex): string
    {
        $delimiter = $regex[0];

        return substr($regex, 1, strrpos($regex, $delimiter) - 1);
    }
}
