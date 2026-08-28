<?php
/**
 * ============================================
 * Input Validation Helper
 * ============================================
 * Provides input validation and sanitization.
 * ============================================
 */

/**
 * Validator class for form input validation
 */
class Validator
{
    private $errors = [];
    private $sanitized = [];
    private $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Validate required field
     */
    public function required($field, $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = trim($this->data[$field] ?? '');
        if (empty($value)) {
            $this->errors[$field][] = "$label is required.";
        } else {
            $this->sanitized[$field] = $value;
        }
        return $this;
    }

    /**
     * Validate email
     */
    public function email($field, $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = trim($this->data[$field] ?? '');
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "$label must be a valid email address.";
        }
        return $this;
    }

    /**
     * Validate numeric
     */
    public function numeric($field, $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = trim($this->data[$field] ?? '');
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field][] = "$label must be a number.";
        }
        return $this;
    }

    /**
     * Validate min length
     */
    public function minLength($field, $min, $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = trim($this->data[$field] ?? '');
        if (!empty($value) && strlen($value) < $min) {
            $this->errors[$field][] = "$label must be at least $min characters.";
        }
        return $this;
    }

    /**
     * Validate max length
     */
    public function maxLength($field, $max, $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = trim($this->data[$field] ?? '');
        if (!empty($value) && strlen($value) > $max) {
            $this->errors[$field][] = "$label must not exceed $max characters.";
        }
        return $this;
    }

    /**
     * Validate phone number
     */
    public function phone($field, $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = trim($this->data[$field] ?? '');
        if (!empty($value) && !preg_match('/^[0-9+\-\s()]{7,20}$/', $value)) {
            $this->errors[$field][] = "$label must be a valid phone number.";
        }
        return $this;
    }

    /**
     * Validate date
     */
    public function date($field, $format = 'Y-m-d', $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = trim($this->data[$field] ?? '');
        if (!empty($value)) {
            $d = \DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                $this->errors[$field][] = "$label must be a valid date ($format).";
            }
        }
        return $this;
    }

    /**
     * Check if validation passes
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get all errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function firstError(): string
    {
        foreach ($this->errors as $field => $msgs) {
            return $msgs[0];
        }
        return '';
    }

    /**
     * Get sanitized data
     */
    public function getSanitized(): array
    {
        return $this->sanitized;
    }
}