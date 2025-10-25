<?php
/**
 * Companies Model
 *
 * CRUD operations for companies (branches)
 * Fires action hooks for extensibility
 *
 * @package WPCustomer
 * @subpackage Models\Companies
 * @since 1.1.0
 * @author arisciwek
 */

namespace WPCustomer\Models\Companies;

defined('ABSPATH') || exit;

/**
 * CompaniesModel class
 *
 * Handles database operations for companies (branches)
 * Table: wp_app_customer_branches
 *
 * @since 1.1.0
 */
class CompaniesModel {

    /**
     * Database instance
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Table name
     *
     * @var string
     */
    private $table;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_branches';
    }

    /**
     * Find company by ID
     *
     * @param int $id Company ID
     * @return object|null Company data or null if not found
     */
    public function find($id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d",
            $id
        );

        return $this->wpdb->get_row($sql);
    }

    /**
     * Create new company
     *
     * Fires: wp_customer_company_created action
     *
     * @param array $data Company data
     * @return int|false Company ID on success, false on failure
     */
    public function create($data) {
        // Prepare data for insert
        $insert_data = $this->prepare_data_for_save($data);

        // Add created_by if not set
        if (!isset($insert_data['created_by'])) {
            $insert_data['created_by'] = get_current_user_id();
        }

        // Insert into database
        $result = $this->wpdb->insert(
            $this->table,
            $insert_data,
            $this->get_format_for_data($insert_data)
        );

        if ($result === false) {
            return false;
        }

        $company_id = $this->wpdb->insert_id;

        /**
         * Fires after company created
         *
         * @param int $company_id Company ID
         * @param array $company_data Company data
         *
         * @since 1.1.0
         */
        do_action('wp_customer_company_created', $company_id, $insert_data);

        return $company_id;
    }

    /**
     * Update company
     *
     * Fires: wp_customer_company_updated action
     *
     * @param int $id Company ID
     * @param array $data Company data to update
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        // Get old data for comparison
        $old_data = $this->find($id);

        if (!$old_data) {
            return false;
        }

        // Prepare data for update
        $update_data = $this->prepare_data_for_save($data);

        // Update database
        $result = $this->wpdb->update(
            $this->table,
            $update_data,
            ['id' => $id],
            $this->get_format_for_data($update_data),
            ['%d']
        );

        if ($result === false) {
            return false;
        }

        // Get new data
        $new_data = $this->find($id);

        /**
         * Fires after company updated
         *
         * @param int $company_id Company ID
         * @param object $old_data Old company data
         * @param object $new_data New company data
         *
         * @since 1.1.0
         */
        do_action('wp_customer_company_updated', $id, $old_data, $new_data);

        return true;
    }

    /**
     * Delete company
     *
     * Fires: wp_customer_company_before_delete and wp_customer_company_deleted actions
     *
     * @param int $id Company ID
     * @param bool $hard_delete True for permanent deletion, false for soft delete
     * @return bool True on success, false on failure
     */
    public function delete($id, $hard_delete = false) {
        // Get company data before deletion
        $company_data = $this->find($id);

        if (!$company_data) {
            return false;
        }

        /**
         * Fires before company deleted
         *
         * Allows other code to:
         * - Prevent deletion (throw exception)
         * - Clean up related data
         * - Log deletion
         *
         * @param int $company_id Company ID
         * @param object $company_data Company data
         *
         * @since 1.1.0
         */
        do_action('wp_customer_company_before_delete', $id, $company_data);

        // Perform deletion
        if ($hard_delete) {
            // Hard delete - remove from database
            $result = $this->wpdb->delete(
                $this->table,
                ['id' => $id],
                ['%d']
            );
        } else {
            // Soft delete - set status to inactive
            // (Or set deleted_at if you implement soft delete column)
            $result = $this->wpdb->update(
                $this->table,
                ['status' => 'inactive'],
                ['id' => $id],
                ['%s'],
                ['%d']
            );
        }

        if ($result === false) {
            return false;
        }

        /**
         * Fires after company deleted
         *
         * @param int $company_id Company ID
         * @param object $company_data Company data (before deletion)
         * @param bool $is_hard_delete Whether it was hard delete
         *
         * @since 1.1.0
         */
        do_action('wp_customer_company_deleted', $id, $company_data, $hard_delete);

        return true;
    }

    /**
     * Get companies by customer ID
     *
     * @param int $customer_id Customer ID
     * @return array Array of company objects
     */
    public function get_by_customer($customer_id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table}
             WHERE customer_id = %d
             ORDER BY type ASC, name ASC",
            $customer_id
        );

        return $this->wpdb->get_results($sql);
    }

    /**
     * Get companies by agency ID
     *
     * @param int $agency_id Agency ID
     * @return array Array of company objects
     */
    public function get_by_agency($agency_id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table}
             WHERE agency_id = %d
             ORDER BY name ASC",
            $agency_id
        );

        return $this->wpdb->get_results($sql);
    }

    /**
     * Get companies by inspector ID
     *
     * @param int $inspector_id Inspector ID
     * @return array Array of company objects
     */
    public function get_by_inspector($inspector_id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table}
             WHERE inspector_id = %d
             ORDER BY name ASC",
            $inspector_id
        );

        return $this->wpdb->get_results($sql);
    }

    /**
     * Get statistics
     *
     * @return array Statistics data
     */
    public function get_statistics() {
        $stats = [];

        // Total companies
        $stats['total'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table}"
        );

        // Active companies
        $stats['active'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table} WHERE status = 'active'"
        );

        // Inactive companies
        $stats['inactive'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table} WHERE status = 'inactive'"
        );

        // Companies by type
        $stats['pusat'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table} WHERE type = 'pusat'"
        );

        $stats['cabang'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table} WHERE type = 'cabang'"
        );

        // Total customers with companies
        $stats['customers_count'] = $this->wpdb->get_var(
            "SELECT COUNT(DISTINCT customer_id) FROM {$this->table}"
        );

        // Total agencies managing companies
        $stats['agencies_count'] = $this->wpdb->get_var(
            "SELECT COUNT(DISTINCT agency_id) FROM {$this->table} WHERE agency_id IS NOT NULL"
        );

        return $stats;
    }

    /**
     * Prepare data for save (insert/update)
     *
     * @param array $data Raw data
     * @return array Prepared data
     */
    private function prepare_data_for_save($data) {
        $allowed_fields = [
            'customer_id',
            'code',
            'name',
            'type',
            'nitku',
            'postal_code',
            'latitude',
            'longitude',
            'address',
            'phone',
            'email',
            'provinsi_id',
            'regency_id',
            'agency_id',
            'division_id',
            'user_id',
            'inspector_id',
            'created_by',
            'status'
        ];

        $prepared = [];

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $prepared[$field] = $data[$field];
            }
        }

        return $prepared;
    }

    /**
     * Get format array for wpdb insert/update
     *
     * @param array $data Data array
     * @return array Format array (%s, %d, etc.)
     */
    private function get_format_for_data($data) {
        $format = [];

        $int_fields = [
            'customer_id',
            'provinsi_id',
            'regency_id',
            'agency_id',
            'division_id',
            'user_id',
            'inspector_id',
            'created_by'
        ];

        $float_fields = [
            'latitude',
            'longitude'
        ];

        foreach ($data as $key => $value) {
            if (in_array($key, $int_fields)) {
                $format[] = '%d';
            } elseif (in_array($key, $float_fields)) {
                $format[] = '%f';
            } else {
                $format[] = '%s';
            }
        }

        return $format;
    }
}
