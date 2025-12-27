<?php
/**
 * Audit Logger Helper
 *
 * @package     WP_Customer
 * @subpackage  Helpers
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Helpers/AuditLogger.php
 *
 * Description: Helper class untuk logging dan retrieving audit logs.
 *              Provides static methods untuk manual logging.
 *              Query methods untuk get history per entity, user, atau event.
 *
 * Usage:
 * ```php
 * use WPCustomer\Helpers\AuditLogger;
 *
 * // Manual logging
 * AuditLogger::log([
 *     'auditable_type' => 'customer',
 *     'auditable_id' => 123,
 *     'event' => 'updated',
 *     'old_values' => ['name' => 'PT ABC'],
 *     'new_values' => ['name' => 'PT XYZ']
 * ]);
 *
 * // Get entity history
 * $history = AuditLogger::getEntityHistory('customer', 123);
 *
 * // Get user activity
 * $activity = AuditLogger::getUserActivity(5, ['limit' => 50]);
 *
 * // Search logs
 * $logs = AuditLogger::query([
 *     'auditable_type' => 'branch',
 *     'event' => 'updated',
 *     'date_from' => '2025-01-01'
 * ]);
 * ```
 *
 * Methods:
 * - log()               : Save audit log
 * - getEntityHistory()  : Get history for specific entity
 * - getUserActivity()   : Get activity for specific user
 * - query()             : Advanced search
 * - getRecentActivity() : Get recent activity across all entities
 * - formatLogEntry()    : Format log entry for display
 *
 * Changelog:
 * 1.0.0 - 2025-12-28
 * - Initial version
 * - Manual logging support
 * - Query methods for retrieving history
 * - Format helpers for display
 * - JSON encode/decode for values
 */

namespace WPCustomer\Helpers;

defined('ABSPATH') || exit;

class AuditLogger {

    /**
     * Table name constant
     */
    private static function get_table_name(): string {
        global $wpdb;
        return $wpdb->prefix . 'app_customer_audit_logs';
    }

    /**
     * Log an audit event
     *
     * @param array $data Audit log data
     *        Required: auditable_type, auditable_id, event
     *        Optional: old_values, new_values, user_id, ip_address, user_agent
     * @return int|false Insert ID or false on failure
     */
    public static function log(array $data) {
        global $wpdb;

        // Validate required fields
        $required = ['auditable_type', 'auditable_id', 'event'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[AuditLogger] Missing required field: {$field}");
                }
                return false;
            }
        }

        // Validate event type
        $valid_events = ['created', 'updated', 'deleted', 'restored'];
        if (!in_array($data['event'], $valid_events)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[AuditLogger] Invalid event type: {$data['event']}");
            }
            return false;
        }

        // Prepare data for insert
        $insert_data = [
            'auditable_type' => $data['auditable_type'],
            'auditable_id' => (int) $data['auditable_id'],
            'event' => $data['event'],
            'old_values' => isset($data['old_values']) ? wp_json_encode($data['old_values']) : null,
            'new_values' => isset($data['new_values']) ? wp_json_encode($data['new_values']) : null,
            'user_id' => isset($data['user_id']) ? (int) $data['user_id'] : get_current_user_id(),
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ];

        // Insert into database
        $result = $wpdb->insert(
            self::get_table_name(),
            $insert_data,
            ['%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s']
        );

        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[AuditLogger] Failed to insert audit log: " . $wpdb->last_error);
            }
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get audit history for specific entity
     *
     * @param string $auditable_type Entity type (customer, branch, etc)
     * @param int $auditable_id Entity ID
     * @param array $args Optional query arguments
     *        - limit: Number of records (default: 50)
     *        - offset: Offset for pagination (default: 0)
     *        - event: Filter by event type
     *        - user_id: Filter by user
     *        - date_from: Filter from date (Y-m-d H:i:s)
     *        - date_to: Filter to date (Y-m-d H:i:s)
     * @return array Audit log entries
     */
    public static function getEntityHistory(string $auditable_type, int $auditable_id, array $args = []): array {
        global $wpdb;

        $defaults = [
            'limit' => 50,
            'offset' => 0,
            'event' => null,
            'user_id' => null,
            'date_from' => null,
            'date_to' => null,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['auditable_type = %s', 'auditable_id = %d'];
        $where_values = [$auditable_type, $auditable_id];

        // Add optional filters
        if ($args['event']) {
            $where[] = 'event = %s';
            $where_values[] = $args['event'];
        }

        if ($args['user_id']) {
            $where[] = 'user_id = %d';
            $where_values[] = (int) $args['user_id'];
        }

        if ($args['date_from']) {
            $where[] = 'created_at >= %s';
            $where_values[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where[] = 'created_at <= %s';
            $where_values[] = $args['date_to'];
        }

        // Build query
        $where_clause = implode(' AND ', $where);
        $where_values[] = (int) $args['limit'];
        $where_values[] = (int) $args['offset'];

        $query = "SELECT * FROM " . self::get_table_name() . "
                  WHERE {$where_clause}
                  ORDER BY created_at DESC
                  LIMIT %d OFFSET %d";

        $results = $wpdb->get_results(
            $wpdb->prepare($query, $where_values),
            ARRAY_A
        );

        return self::formatResults($results);
    }

    /**
     * Get activity log for specific user
     *
     * @param int $user_id User ID
     * @param array $args Optional query arguments (same as getEntityHistory)
     * @return array Audit log entries
     */
    public static function getUserActivity(int $user_id, array $args = []): array {
        global $wpdb;

        $defaults = [
            'limit' => 50,
            'offset' => 0,
            'auditable_type' => null,
            'event' => null,
            'date_from' => null,
            'date_to' => null,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['user_id = %d'];
        $where_values = [$user_id];

        // Add optional filters
        if ($args['auditable_type']) {
            $where[] = 'auditable_type = %s';
            $where_values[] = $args['auditable_type'];
        }

        if ($args['event']) {
            $where[] = 'event = %s';
            $where_values[] = $args['event'];
        }

        if ($args['date_from']) {
            $where[] = 'created_at >= %s';
            $where_values[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where[] = 'created_at <= %s';
            $where_values[] = $args['date_to'];
        }

        // Build query
        $where_clause = implode(' AND ', $where);
        $where_values[] = (int) $args['limit'];
        $where_values[] = (int) $args['offset'];

        $query = "SELECT * FROM " . self::get_table_name() . "
                  WHERE {$where_clause}
                  ORDER BY created_at DESC
                  LIMIT %d OFFSET %d";

        $results = $wpdb->get_results(
            $wpdb->prepare($query, $where_values),
            ARRAY_A
        );

        return self::formatResults($results);
    }

    /**
     * Advanced query for audit logs
     *
     * @param array $args Query arguments (same as getUserActivity + auditable_id)
     * @return array Audit log entries
     */
    public static function query(array $args = []): array {
        global $wpdb;

        $defaults = [
            'limit' => 50,
            'offset' => 0,
            'auditable_type' => null,
            'auditable_id' => null,
            'event' => null,
            'user_id' => null,
            'date_from' => null,
            'date_to' => null,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = [];
        $where_values = [];

        // Add filters
        if ($args['auditable_type']) {
            $where[] = 'auditable_type = %s';
            $where_values[] = $args['auditable_type'];
        }

        if ($args['auditable_id']) {
            $where[] = 'auditable_id = %d';
            $where_values[] = (int) $args['auditable_id'];
        }

        if ($args['event']) {
            $where[] = 'event = %s';
            $where_values[] = $args['event'];
        }

        if ($args['user_id']) {
            $where[] = 'user_id = %d';
            $where_values[] = (int) $args['user_id'];
        }

        if ($args['date_from']) {
            $where[] = 'created_at >= %s';
            $where_values[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where[] = 'created_at <= %s';
            $where_values[] = $args['date_to'];
        }

        // Build query
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $where_values[] = (int) $args['limit'];
        $where_values[] = (int) $args['offset'];

        $query = "SELECT * FROM " . self::get_table_name() . "
                  {$where_clause}
                  ORDER BY created_at DESC
                  LIMIT %d OFFSET %d";

        $results = $wpdb->get_results(
            $wpdb->prepare($query, $where_values),
            ARRAY_A
        );

        return self::formatResults($results);
    }

    /**
     * Get recent activity across all entities
     *
     * @param int $limit Number of records (default: 20)
     * @return array Recent audit log entries
     */
    public static function getRecentActivity(int $limit = 20): array {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . "
             ORDER BY created_at DESC
             LIMIT %d",
            $limit
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        return self::formatResults($results);
    }

    /**
     * Format query results
     *
     * @param array $results Raw results from database
     * @return array Formatted results with decoded JSON
     */
    private static function formatResults(array $results): array {
        return array_map([self::class, 'formatLogEntry'], $results);
    }

    /**
     * Format single log entry
     *
     * @param array $entry Raw log entry
     * @return array Formatted entry with decoded JSON
     */
    public static function formatLogEntry(array $entry): array {
        // Decode JSON values
        if (!empty($entry['old_values'])) {
            $entry['old_values'] = json_decode($entry['old_values'], true);
        }

        if (!empty($entry['new_values'])) {
            $entry['new_values'] = json_decode($entry['new_values'], true);
        }

        return $entry;
    }

    /**
     * Get count of audit logs
     *
     * @param array $args Query arguments (without limit/offset)
     * @return int Total count
     */
    public static function count(array $args = []): int {
        global $wpdb;

        $where = [];
        $where_values = [];

        // Add filters (same as query method)
        if (!empty($args['auditable_type'])) {
            $where[] = 'auditable_type = %s';
            $where_values[] = $args['auditable_type'];
        }

        if (!empty($args['auditable_id'])) {
            $where[] = 'auditable_id = %d';
            $where_values[] = (int) $args['auditable_id'];
        }

        if (!empty($args['event'])) {
            $where[] = 'event = %s';
            $where_values[] = $args['event'];
        }

        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $where_values[] = (int) $args['user_id'];
        }

        if (!empty($args['date_from'])) {
            $where[] = 'created_at >= %s';
            $where_values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'created_at <= %s';
            $where_values[] = $args['date_to'];
        }

        // Build query
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "SELECT COUNT(*) FROM " . self::get_table_name() . " {$where_clause}";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return (int) $wpdb->get_var($query);
    }
}
