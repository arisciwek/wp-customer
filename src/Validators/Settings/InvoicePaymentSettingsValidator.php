<?php
/**
 * Invoice Payment Settings Validator
 *
 * @package     WP_Customer
 * @subpackage  Validators/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Validators/Settings/InvoicePaymentSettingsValidator.php
 *
 * Description: Validator untuk invoice & payment settings
 *              Mengikuti standardized architecture pattern (TODO-2198)
 *
 * Changelog:
 * 1.0.0 - 2025-01-13
 * - Initial version following TODO-2198 pattern
 * - Extends AbstractSettingsValidator from wp-app-core
 * - Validation rules untuk invoice dan payment settings
 */

namespace WPCustomer\Validators\Settings;

use WPAppCore\Validators\Abstract\AbstractSettingsValidator;

defined('ABSPATH') || exit;

class InvoicePaymentSettingsValidator extends AbstractSettingsValidator {

    /**
     * Get text domain for translations
     *
     * @return string
     */
    protected function getTextDomain(): string {
        return 'wp-customer';
    }

    /**
     * Get validation rules
     *
     * @return array
     */
    protected function getRules(): array {
        return [
            'invoice_due_days' => [
                'required' => true,
                'type' => 'integer',
                'min' => 1,
                'max' => 365,
            ],
            'invoice_prefix' => [
                'required' => true,
                'type' => 'string',
                'max_length' => 10,
                'pattern' => '/^[A-Z0-9]+$/',
            ],
            'invoice_number_format' => [
                'required' => true,
                'type' => 'string',
                'allowed_values' => ['YYYYMM', 'YYYYMMDD', 'YYMM', 'YYMMDD'],
            ],
            'invoice_currency' => [
                'required' => true,
                'type' => 'string',
                'max_length' => 10,
            ],
            'invoice_tax_percentage' => [
                'required' => true,
                'type' => 'float',
                'min' => 0,
                'max' => 100,
            ],
            'invoice_sender_email' => [
                'required' => false,
                'type' => 'email',
            ],
            'payment_methods' => [
                'required' => true,
                'type' => 'array',
                'min_items' => 1,
                'allowed_values' => ['transfer_bank', 'virtual_account', 'kartu_kredit', 'e_wallet'],
            ],
            'payment_confirmation_required' => [
                'required' => true,
                'type' => 'boolean',
            ],
            'payment_auto_approve_threshold' => [
                'required' => true,
                'type' => 'float',
                'min' => 0,
            ],
            'payment_reminder_days' => [
                'required' => true,
                'type' => 'array',
                'min_items' => 1,
                'item_type' => 'integer',
                'item_min' => 1,
                'item_max' => 365,
            ],
        ];
    }

    /**
     * Get custom error messages
     *
     * @return array
     */
    protected function getMessages(): array {
        return [
            'invoice_due_days.required' => __('Jatuh tempo invoice harus diisi.', 'wp-customer'),
            'invoice_due_days.min' => __('Jatuh tempo minimal 1 hari.', 'wp-customer'),
            'invoice_due_days.max' => __('Jatuh tempo maksimal 365 hari.', 'wp-customer'),

            'invoice_prefix.required' => __('Prefix invoice harus diisi.', 'wp-customer'),
            'invoice_prefix.max_length' => __('Prefix invoice maksimal 10 karakter.', 'wp-customer'),
            'invoice_prefix.pattern' => __('Prefix invoice hanya boleh huruf kapital dan angka.', 'wp-customer'),

            'invoice_number_format.required' => __('Format nomor invoice harus dipilih.', 'wp-customer'),
            'invoice_number_format.allowed_values' => __('Format nomor invoice tidak valid.', 'wp-customer'),

            'invoice_currency.required' => __('Mata uang harus diisi.', 'wp-customer'),
            'invoice_currency.max_length' => __('Mata uang maksimal 10 karakter.', 'wp-customer'),

            'invoice_tax_percentage.required' => __('Persentase PPN harus diisi.', 'wp-customer'),
            'invoice_tax_percentage.min' => __('Persentase PPN minimal 0%.', 'wp-customer'),
            'invoice_tax_percentage.max' => __('Persentase PPN maksimal 100%.', 'wp-customer'),

            'invoice_sender_email.email' => __('Format email pengirim tidak valid.', 'wp-customer'),

            'payment_methods.required' => __('Metode pembayaran harus dipilih.', 'wp-customer'),
            'payment_methods.min_items' => __('Minimal harus ada 1 metode pembayaran.', 'wp-customer'),
            'payment_methods.allowed_values' => __('Metode pembayaran tidak valid.', 'wp-customer'),

            'payment_confirmation_required.required' => __('Pengaturan konfirmasi pembayaran harus diisi.', 'wp-customer'),

            'payment_auto_approve_threshold.required' => __('Threshold auto-approve harus diisi.', 'wp-customer'),
            'payment_auto_approve_threshold.min' => __('Threshold minimal 0.', 'wp-customer'),

            'payment_reminder_days.required' => __('Jadwal reminder harus diisi.', 'wp-customer'),
            'payment_reminder_days.min_items' => __('Minimal harus ada 1 jadwal reminder.', 'wp-customer'),
            'payment_reminder_days.item_min' => __('Jadwal reminder minimal H-1.', 'wp-customer'),
            'payment_reminder_days.item_max' => __('Jadwal reminder maksimal H-365.', 'wp-customer'),
        ];
    }

    /**
     * Validate data
     *
     * @param array $data Data to validate
     * @return bool
     */
    public function validate(array $data): bool {
        $this->errors = [];
        $rules = $this->getRules();
        $messages = $this->getMessages();

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            // Required check
            if (isset($fieldRules['required']) && $fieldRules['required']) {
                if ($value === null || $value === '') {
                    $this->errors[$field] = $messages["{$field}.required"] ?? "Field {$field} is required.";
                    continue;
                }
            }

            // Skip further validation if value is empty and not required
            if ($value === null || $value === '') {
                continue;
            }

            // Type validation
            if (isset($fieldRules['type'])) {
                if (!$this->validateType($value, $fieldRules['type'])) {
                    $this->errors[$field] = "Field {$field} must be of type {$fieldRules['type']}.";
                    continue;
                }
            }

            // Min/Max for numbers
            if (isset($fieldRules['min']) && is_numeric($value) && $value < $fieldRules['min']) {
                $this->errors[$field] = $messages["{$field}.min"] ?? "Field {$field} must be at least {$fieldRules['min']}.";
                continue;
            }
            if (isset($fieldRules['max']) && is_numeric($value) && $value > $fieldRules['max']) {
                $this->errors[$field] = $messages["{$field}.max"] ?? "Field {$field} must not exceed {$fieldRules['max']}.";
                continue;
            }

            // Max length for strings
            if (isset($fieldRules['max_length']) && is_string($value) && strlen($value) > $fieldRules['max_length']) {
                $this->errors[$field] = $messages["{$field}.max_length"] ?? "Field {$field} must not exceed {$fieldRules['max_length']} characters.";
                continue;
            }

            // Pattern validation
            if (isset($fieldRules['pattern']) && is_string($value) && !preg_match($fieldRules['pattern'], $value)) {
                $this->errors[$field] = $messages["{$field}.pattern"] ?? "Field {$field} format is invalid.";
                continue;
            }

            // Allowed values
            if (isset($fieldRules['allowed_values'])) {
                if (is_array($value)) {
                    // For arrays, check each item
                    foreach ($value as $item) {
                        if (!in_array($item, $fieldRules['allowed_values'])) {
                            $this->errors[$field] = $messages["{$field}.allowed_values"] ?? "Field {$field} contains invalid values.";
                            break;
                        }
                    }
                } else {
                    // For single values
                    if (!in_array($value, $fieldRules['allowed_values'])) {
                        $this->errors[$field] = $messages["{$field}.allowed_values"] ?? "Field {$field} value is not allowed.";
                        continue;
                    }
                }
            }

            // Array validation
            if (isset($fieldRules['type']) && $fieldRules['type'] === 'array') {
                if (isset($fieldRules['min_items']) && count($value) < $fieldRules['min_items']) {
                    $this->errors[$field] = $messages["{$field}.min_items"] ?? "Field {$field} must have at least {$fieldRules['min_items']} items.";
                    continue;
                }

                // Validate array items
                if (isset($fieldRules['item_type'])) {
                    foreach ($value as $item) {
                        if (!$this->validateType($item, $fieldRules['item_type'])) {
                            $this->errors[$field] = "Field {$field} contains items of wrong type.";
                            break;
                        }

                        // Item min/max
                        if (isset($fieldRules['item_min']) && $item < $fieldRules['item_min']) {
                            $this->errors[$field] = $messages["{$field}.item_min"] ?? "Field {$field} items must be at least {$fieldRules['item_min']}.";
                            break;
                        }
                        if (isset($fieldRules['item_max']) && $item > $fieldRules['item_max']) {
                            $this->errors[$field] = $messages["{$field}.item_max"] ?? "Field {$field} items must not exceed {$fieldRules['item_max']}.";
                            break;
                        }
                    }
                }
            }

            // Email validation
            if (isset($fieldRules['type']) && $fieldRules['type'] === 'email' && !empty($value)) {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = $messages["{$field}.email"] ?? "Field {$field} must be a valid email address.";
                    continue;
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate type
     *
     * @param mixed $value Value to check
     * @param string $type Expected type
     * @return bool
     */
    private function validateType($value, string $type): bool {
        switch ($type) {
            case 'integer':
                return is_int($value) || (is_numeric($value) && intval($value) == $value);
            case 'float':
                return is_float($value) || is_numeric($value);
            case 'string':
                return is_string($value);
            case 'boolean':
                return is_bool($value) || in_array($value, [0, 1, '0', '1', true, false], true);
            case 'array':
                return is_array($value);
            case 'email':
                return is_string($value);
            default:
                return true;
        }
    }
}
