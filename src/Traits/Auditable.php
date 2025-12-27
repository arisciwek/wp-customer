<?php
/**
 * Auditable Trait
 *
 * @package     WP_Customer
 * @subpackage  Traits
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Traits/Auditable.php
 *
 * Description: Trait untuk auto-tracking perubahan data ke audit log.
 *              Digunakan di Model classes untuk automatic logging.
 *              Detects field changes dan simpan ke app_customer_audit_logs.
 *
 * Usage:
 * ```php
 * use WPCustomer\Traits\Auditable;
 *
 * class CustomerModel {
 *     use Auditable;
 *
 *     protected $auditable_type = 'customer'; // Required: entity type
 *     protected $auditable_excluded = ['updated_at']; // Optional: fields to exclude
 *
 *     public function update($id, $data) {
 *         $old_data = $this->get($id);
 *
 *         // Perform update
 *         $result = $wpdb->update(...);
 *
 *         if ($result) {
 *             $this->logAudit('updated', $id, $old_data, $data);
 *         }
 *
 *         return $result;
 *     }
 * }
 * ```
 *
 * Methods:
 * - logAudit()          : Log an audit event
 * - getChangedFields()  : Detect which fields changed
 * - getCurrentUserId()  : Get current user ID
 * - getClientIp()       : Get client IP address
 * - getUserAgent()      : Get client user agent
 *
 * Changelog:
 * 1.0.0 - 2025-12-28
 * - Initial version
 * - Auto-detect changed fields
 * - Support created, updated, deleted, restored events
 * - Auto-capture user_id, ip_address, user_agent
 * - Exclude specified fields from tracking
 */

namespace WPCustomer\Traits;

use WPCustomer\Helpers\AuditLogger;

defined('ABSPATH') || exit;

trait Auditable {

    /**
     * Log an audit event
     *
     * @param string $event Event type (created, updated, deleted, restored)
     * @param int $auditable_id Entity ID
     * @param array|object|null $old_data Old values (null for created event)
     * @param array|object|null $new_data New values (null for deleted event)
     * @return int|false Audit log ID or false on failure
     */
    protected function logAudit(string $event, int $auditable_id, $old_data = null, $new_data = null) {
        // Validate entity type is set
        if (empty($this->auditable_type)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Auditable] auditable_type property not set in " . get_class($this));
            }
            return false;
        }

        // Convert objects to arrays
        $old_array = is_object($old_data) ? (array) $old_data : $old_data;
        $new_array = is_object($new_data) ? (array) $new_data : $new_data;

        // For update events, only log changed fields
        if ($event === 'updated' && $old_array && $new_array) {
            $changed = $this->getChangedFields($old_array, $new_array);

            // If no fields changed, skip logging
            if (empty($changed['old']) && empty($changed['new'])) {
                return false;
            }

            $old_values = $changed['old'];
            $new_values = $changed['new'];
        } else {
            // For created/deleted events, log all fields
            $old_values = $old_array;
            $new_values = $new_array;
        }

        // Resolve reference fields to readable values
        $old_values = $this->resolveReferences($old_values);
        $new_values = $this->resolveReferences($new_values);

        // Use AuditLogger helper to save
        return AuditLogger::log([
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $auditable_id,
            'event' => $event,
            'old_values' => $old_values,
            'new_values' => $new_values,
            'user_id' => $this->getCurrentUserId(),
            'ip_address' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent()
        ]);
    }

    /**
     * Get changed fields between old and new data
     *
     * @param array $old_data Old values
     * @param array $new_data New values
     * @return array ['old' => [...], 'new' => [...]]
     */
    protected function getChangedFields(array $old_data, array $new_data): array {
        $changed_old = [];
        $changed_new = [];

        // Get excluded fields (e.g., updated_at, timestamps)
        $excluded = $this->auditable_excluded ?? ['updated_at', 'created_at'];

        // Check for changed values
        foreach ($new_data as $key => $new_value) {
            // Skip excluded fields
            if (in_array($key, $excluded)) {
                continue;
            }

            // Skip if key doesn't exist in old data
            if (!array_key_exists($key, $old_data)) {
                continue;
            }

            $old_value = $old_data[$key];

            // Compare values (handle null, empty string, etc.)
            if ($this->valuesAreDifferent($old_value, $new_value)) {
                $changed_old[$key] = $old_value;
                $changed_new[$key] = $new_value;
            }
        }

        return [
            'old' => $changed_old,
            'new' => $changed_new
        ];
    }

    /**
     * Check if two values are different
     *
     * @param mixed $old_value Old value
     * @param mixed $new_value New value
     * @return bool True if different
     */
    private function valuesAreDifferent($old_value, $new_value): bool {
        // Handle null comparisons
        if ($old_value === null && $new_value === null) {
            return false;
        }

        if ($old_value === null || $new_value === null) {
            return true;
        }

        // Handle numeric comparisons (string '123' vs int 123)
        if (is_numeric($old_value) && is_numeric($new_value)) {
            return (string) $old_value !== (string) $new_value;
        }

        // Standard comparison
        return $old_value !== $new_value;
    }

    /**
     * Get current user ID
     *
     * @return int User ID (0 if not logged in)
     */
    protected function getCurrentUserId(): int {
        return get_current_user_id() ?: 0;
    }

    /**
     * Get client IP address
     *
     * @return string|null IP address (IPv4 or IPv6)
     */
    protected function getClientIp(): ?string {
        $ip = null;

        // Check for proxy headers
        $headers = [
            'HTTP_CF_CONNECTING_IP', // CloudFlare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // Handle multiple IPs (X-Forwarded-For can contain multiple IPs)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                break;
            }
        }

        // Validate IP address
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return null;
    }

    /**
     * Get client user agent
     *
     * @return string|null User agent string (max 255 chars)
     */
    protected function getUserAgent(): ?string {
        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            // Limit to 255 characters to match DB field length
            return substr($_SERVER['HTTP_USER_AGENT'], 0, 255);
        }

        return null;
    }

    /**
     * Get audit history for this entity instance
     *
     * @param int $auditable_id Entity ID
     * @param array $args Optional query arguments
     * @return array Audit log entries
     */
    protected function getAuditHistory(int $auditable_id, array $args = []): array {
        if (empty($this->auditable_type)) {
            return [];
        }

        return AuditLogger::getEntityHistory($this->auditable_type, $auditable_id, $args);
    }

    /**
     * Resolve reference fields to readable values
     *
     * Converts foreign key IDs to human-readable labels.
     * Model should define $auditable_references property with field mappings.
     *
     * Format: 'field_name' => ['table' => 'table_name', 'key' => 'id_column', 'label' => 'name_column']
     *
     * @param array|null $values Field values
     * @return array|null Resolved values
     */
    protected function resolveReferences($values): ?array {
        if (empty($values) || !is_array($values)) {
            return $values;
        }

        // Check if model defines reference mappings
        if (!property_exists($this, 'auditable_references')) {
            return $values;
        }

        if (empty($this->auditable_references) || !is_array($this->auditable_references)) {
            return $values;
        }

        global $wpdb;

        foreach ($this->auditable_references as $field => $config) {
            // Skip if field not in values or is null/empty
            if (!isset($values[$field]) || $values[$field] === null || $values[$field] === '') {
                continue;
            }

            $id = $values[$field];

            // Query reference table for label
            $table = $wpdb->prefix . $config['table'];
            $key = $config['key'];
            $label = $config['label'];

            $label_value = $wpdb->get_var($wpdb->prepare(
                "SELECT {$label} FROM {$table} WHERE {$key} = %d LIMIT 1",
                $id
            ));

            // Store as "ID|Label" format for easy parsing in JavaScript
            if ($label_value) {
                $values[$field] = $id . '|' . $label_value;
            }
        }

        return $values;
    }
}
