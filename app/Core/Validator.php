<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors === [] ? null : (string) reset($this->errors);
    }

    public function ok(): bool
    {
        return $this->errors === [];
    }

    public function fail(string $field, string $message): self
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function required(string $field, mixed $value, string $label = ''): self
    {
        $label = $label !== '' ? $label : ucfirst(str_replace('_', ' ', $field));
        if (is_array($value)) {
            if ($value === []) {
                return $this->fail($field, $label . ' is required.');
            }
            return $this;
        }
        if (trim((string) $value) === '') {
            return $this->fail($field, $label . ' is required.');
        }
        return $this;
    }

    public function email(string $field, string $value, bool $required = true): self
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return $required ? $this->fail($field, 'Please enter a valid email address.') : $this;
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL) || strlen($value) > 120) {
            return $this->fail($field, 'Please enter a valid email address.');
        }
        return $this;
    }

    public function name(string $field, string $value): self
    {
        $value = trim($value);
        $len = mb_strlen($value);
        if ($len < 2) {
            return $this->fail($field, 'Please enter your name.');
        }
        if ($len > 80) {
            return $this->fail($field, 'Name is too long.');
        }
        return $this;
    }

    public function phone(string $field, string $value, bool $required = true): self
    {
        $value = trim($value);
        if ($value === '') {
            return $required ? $this->fail($field, 'Please enter a phone number.') : $this;
        }
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return $this->fail($field, 'Please enter a valid phone number.');
        }
        return $this;
    }

    public function password(string $field, string $value, int $min = 6): self
    {
        if (strlen($value) < $min) {
            return $this->fail($field, 'Password must be at least ' . $min . ' characters.');
        }
        if (strlen($value) > 72) {
            return $this->fail($field, 'Password is too long.');
        }
        return $this;
    }

    public static function normalizePostcode(string $postcode): string
    {
        $postcode = strtoupper(trim($postcode));
        return trim(preg_replace('/\s+/', ' ', $postcode) ?? $postcode);
    }

    public static function normalizePrefix(string $prefix): string
    {
        return strtoupper(preg_replace('/\s+/', '', $prefix) ?? '');
    }

    /** UK outward code such as BB8, BB11, E1, SW1A. */
    public function postcodePrefix(string $field, string $value): self
    {
        $value = self::normalizePrefix($value);
        if ($value === '') {
            return $this->fail($field, 'Please choose a postal area.');
        }
        if (!preg_match('/^[A-Z]{1,2}\d{1,2}[A-Z]?$/', $value)) {
            return $this->fail($field, 'Enter a valid postal area (e.g. BB11).');
        }
        return $this;
    }

    /** Full UK postcode, or an outward prefix when $allowPrefix is true. */
    public function ukPostcode(string $field, string $value, bool $allowPrefix = true): self
    {
        $value = self::normalizePostcode($value);
        if ($value === '') {
            return $this->fail($field, 'Please enter a postcode.');
        }
        $compact = preg_replace('/\s+/', '', $value) ?? '';
        if (preg_match('/^[A-Z]{1,2}\d[A-Z\d]?\d[A-Z]{2}$/', $compact)) {
            return $this;
        }
        if ($allowPrefix && preg_match('/^[A-Z]{1,2}\d{1,2}[A-Z]?$/', $compact)) {
            return $this;
        }
        return $this->fail($field, 'Please enter a valid UK postcode.');
    }

    public function money(string $field, mixed $value, string $label = 'Amount', bool $allowZero = true): self
    {
        if (!is_numeric($value)) {
            return $this->fail($field, $label . ' must be a number.');
        }
        $n = (float) $value;
        if ($n < 0 || (!$allowZero && $n <= 0)) {
            return $this->fail($field, $label . ' must be ' . ($allowZero ? 'zero or more' : 'greater than zero') . '.');
        }
        if ($n > 9999) {
            return $this->fail($field, $label . ' is too high.');
        }
        return $this;
    }

    public function intRange(string $field, mixed $value, int $min, int $max, string $label = 'Value'): self
    {
        if (!is_numeric($value)) {
            return $this->fail($field, $label . ' must be a number.');
        }
        $n = (int) $value;
        if ($n < $min || $n > $max) {
            return $this->fail($field, $label . ' must be between ' . $min . ' and ' . $max . '.');
        }
        return $this;
    }

    public function address(string $field, string $value): self
    {
        $value = trim($value);
        if (mb_strlen($value) < 5) {
            return $this->fail($field, 'Please enter a full delivery address.');
        }
        if (mb_strlen($value) > 250) {
            return $this->fail($field, 'Address is too long.');
        }
        return $this;
    }
}
