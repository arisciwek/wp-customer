<?php
/**
 * Customer Employee Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.0.12
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/CustomerEmployeeDemoData.php
 *
 * Changelog:
 * 1.0.12 - 2025-11-01 (TODO-3098)
 * - Updated createEmployeeViaRuntimeFlow() to support optional static ID parameter
 * - Uses wp_customer_employee_before_insert hook to force static IDs when needed
 * - Tests full production code flow (Validator → Model → Hook)
 * - Pattern consistent with CustomerDemoData and BranchDemoData
 */

namespace WPCustomer\Database\Demo;

use WPCustomer\Database\Demo\Data\CustomerEmployeeUsersData;
use WPCustomer\Validators\Employee\CustomerEmployeeValidator;
use WPCustomer\Models\Employee\CustomerEmployeeModel;

defined('ABSPATH') || exit;

class CustomerEmployeeDemoData extends AbstractDemoData {
    use CustomerDemoDataHelperTrait;

    private $employeeValidator;
    private $employeeModel;
    private $wpUserGenerator;
    private static $employee_users;

    public function __construct() {
        parent::__construct();
        $this->wpUserGenerator = new WPUserGenerator();
        self::$employee_users = CustomerEmployeeUsersData::$data;
        $this->employeeValidator = new CustomerEmployeeValidator();
        $this->employeeModel = new CustomerEmployeeModel();
    }

    protected function validate(): bool {
        try {
            // CRITICAL: Flush cache to avoid wp-app-core cache contract issue
            wp_cache_flush();

            // 1. Validasi table exists
            $table_exists = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}app_customer_employees'"
            );

            if (!$table_exists) {
                throw new \Exception('Employee table does not exist');
            }

            // 2. Validasi customer & branch data exists
            $customer_count = $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}app_customers"
            );
            
            if ($customer_count == 0) {
                throw new \Exception('No customers found - please generate customer data first');
            }

            $branch_count = $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}app_customer_branches"
            );
            
            if ($branch_count == 0) {
                throw new \Exception('No branches found - please generate branch data first');
            }

            // 3. Validasi static data untuk employee users
            if (empty(self::$employee_users)) {
                throw new \Exception('Employee users data not found');
            }

            return true;

        } catch (\Exception $e) {
            $this->debug('Validation failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function generate(): void {
        // Increase max execution time for batch operations
        // Employee generation with WP user creation can take significant time
        ini_set('max_execution_time', '300'); // 300 seconds = 5 minutes

        $this->debug('Starting employee data generation');

        // CRITICAL: Flush cache again before generation to ensure validators work
        wp_cache_flush();

        try {
            // Clear existing data via HOOK-based deletion (Task-2170)
            if ($this->shouldClearData()) {
                error_log("[EmployeeDemoData] === Cleanup mode enabled - Deleting existing demo employees via HOOK ===");

                // 1. Enable hard delete temporarily
                $original_settings = get_option('wp_customer_general_options', []);
                $cleanup_settings = array_merge($original_settings, ['enable_hard_delete_branch' => true]);
                update_option('wp_customer_general_options', $cleanup_settings);
                error_log("[EmployeeDemoData] Temporarily enabled hard delete mode");

                // 2. Get existing demo employees (employees created by demo system)
                // We identify demo employees by checking if their user_id is in demo user range
                $demo_employees = $this->wpdb->get_col(
                    "SELECT id FROM {$this->wpdb->prefix}app_customer_employees
                     WHERE user_id >= 2 AND user_id <= 129"
                );
                error_log("[EmployeeDemoData] Found " . count($demo_employees) . " demo employees to clean");

                // 3. Delete via Model (triggers HOOK → EmployeeCleanupHandler)
                $deleted_count = 0;
                foreach ($demo_employees as $employee_id) {
                    if ($this->employeeModel->delete($employee_id)) {
                        $deleted_count++;
                    }
                }
                error_log("[EmployeeDemoData] Deleted {$deleted_count} demo employees via Model+HOOK");

                // 4. Restore original settings
                update_option('wp_customer_general_options', $original_settings);
                error_log("[EmployeeDemoData] Restored original hard delete setting");

                // 5. Clean up demo users
                $employee_user_ids = array_keys(self::$employee_users);
                if (!empty($employee_user_ids)) {
                    $force_delete = true; // Force delete in development mode
                    $deleted_users = $this->wpUserGenerator->deleteUsers($employee_user_ids, $force_delete);
                    error_log("[EmployeeDemoData] Cleaned up {$deleted_users} demo users");
                }

                $this->debug("Cleaned up {$deleted_count} employees and {$deleted_users} users before generation");
            }

            // Tahap 1: Generate dari user yang sudah ada (customer owners & branch admins)
            $this->generateExistingUserEmployees();

            // Tahap 2: Generate dari CustomerEmployeeUsersData
            $this->generateNewEmployees();

            $this->debug('Employee generation completed');

        } catch (\Exception $e) {
            $this->debug('Error generating employees: ' . $e->getMessage());
            throw $e;
        }
    }

    private function generateExistingUserEmployees(): void {

		// For customer owners (ID 2-11)
		for ($id = 2; $id <= 11; $id++) {
		    $customer = $this->wpdb->get_row($this->wpdb->prepare(
		        "SELECT * FROM {$this->wpdb->prefix}app_customers WHERE user_id = %d",
		        $id
		    ));

		    if (!$customer) continue;

		    // Ambil branch pusat untuk assign owner
		    $pusat_branch = $this->wpdb->get_row($this->wpdb->prepare(
		        "SELECT * FROM {$this->wpdb->prefix}app_customer_branches
		         WHERE customer_id = %d AND type = 'pusat'",
		        $customer->id
		    ));

		    if (!$pusat_branch) continue;

		    // Task-2170 Review-01: Assign multiple roles via direct wp_capabilities update
		    // This prevents duplicate wp_capabilities entries that occur with add_role()
		    global $wpdb;

		    // Ensure customer_employee role exists
		    if (!get_role('customer_employee')) {
		        add_role('customer_employee', __('Customer Employee', 'wp-customer'), []);
		    }

		    // Update wp_capabilities with multiple roles (customer + customer_admin + customer_employee)
		    $wpdb->update(
		        $wpdb->usermeta,
		        ['meta_value' => serialize(['customer' => true, 'customer_admin' => true, 'customer_employee' => true])],
		        ['user_id' => $customer->user_id, 'meta_key' => 'wp_capabilities'],
		        ['%s'],
		        ['%d', '%s']
		    );

		    // Clear user cache
		    wp_cache_delete($customer->user_id, 'user_meta');
		    clean_user_cache($customer->user_id);

		    error_log("[EmployeeDemoData] Updated roles for customer owner user {$customer->user_id}: customer + customer_admin + customer_employee");

		    // Create employee record for owner di branch pusat
		    $this->createEmployeeRecord(
		        $customer->id,
		        $pusat_branch->id,
		        $customer->user_id,
		        [
		            'finance' => true,
		            'operation' => true,
		            'legal' => true,
		            'purchase' => true
		        ]
		    );
		}

        // 2. Branch admins (ID 12-69: regular 12-41 + extra branches 50-69)
        for ($id = 12; $id <= 69; $id++) {
            $branch = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}app_customer_branches WHERE user_id = %d",
                $id
            ));

            if (!$branch) continue;

            // Task-2170 Review-01: Assign multiple roles via direct wp_capabilities update
            // This prevents duplicate wp_capabilities entries that occur with add_role()
            global $wpdb;

            // Ensure customer_employee role exists
            if (!get_role('customer_employee')) {
                add_role('customer_employee', __('Customer Employee', 'wp-customer'), []);
            }

            // Update wp_capabilities with multiple roles (customer + customer_branch_admin + customer_employee)
            $wpdb->update(
                $wpdb->usermeta,
                ['meta_value' => serialize(['customer' => true, 'customer_branch_admin' => true, 'customer_employee' => true])],
                ['user_id' => $branch->user_id, 'meta_key' => 'wp_capabilities'],
                ['%s'],
                ['%d', '%s']
            );

            // Clear user cache
            wp_cache_delete($branch->user_id, 'user_meta');
            clean_user_cache($branch->user_id);

            error_log("[EmployeeDemoData] Updated roles for branch admin user {$branch->user_id}: customer + customer_branch_admin + customer_employee");

            // Branch admin gets all department access for their branch
            $this->createEmployeeRecord(
                $branch->customer_id,
                $branch->id,
                $branch->user_id,
                [
                    'finance' => true,
                    'operation' => true,
                    'legal' => true,
                    'purchase' => true
                ]
            );
        }
    }

    private function generateNewEmployees(): void {
        // Task-2171: Build dynamic ID mapping (static data ID → actual database ID)
        // Static data uses customer_id 1-10, but actual IDs may be 231-240 (auto-increment)
        $customer_id_map = $this->buildCustomerIdMap();
        $branch_id_map = $this->buildBranchIdMap();

        foreach (self::$employee_users as $user_data) {
            // Map static data IDs to actual database IDs
            $static_customer_id = $user_data['customer_id'];
            $static_branch_id = $user_data['branch_id'];

            $actual_customer_id = $customer_id_map[$static_customer_id] ?? null;
            $actual_branch_id = $branch_id_map[$static_branch_id] ?? null;

            if (!$actual_customer_id || !$actual_branch_id) {
                $this->debug("Skipping user {$user_data['id']}: customer/branch mapping not found (static: customer={$static_customer_id}, branch={$static_branch_id})");
                continue;
            }

            // Generate WordPress user first
            $user_id = $this->wpUserGenerator->generateUser([
                'id' => $user_data['id'],
                'username' => $user_data['username'],
                'display_name' => $user_data['display_name'],
                'role' => $user_data['role']
            ]);

            if (!$user_id) {
                $this->debug("Failed to create WP user: {$user_data['username']}");
                continue;
            }

            // Task-2170 Review-01: Assign multiple roles via direct wp_capabilities update
            // This prevents duplicate wp_capabilities entries that occur with add_role()
            global $wpdb;

            // Ensure customer_employee role exists
            if (!get_role('customer_employee')) {
                add_role('customer_employee', __('Customer Employee', 'wp-customer'), []);
            }

            // Update wp_capabilities with both roles (customer + customer_employee)
            $wpdb->update(
                $wpdb->usermeta,
                ['meta_value' => serialize(['customer' => true, 'customer_employee' => true])],
                ['user_id' => $user_id, 'meta_key' => 'wp_capabilities'],
                ['%s'],
                ['%d', '%s']
            );

            // Clear user cache
            wp_cache_delete($user_id, 'user_meta');
            clean_user_cache($user_id);

            $this->debug("Updated roles for user {$user_id} ({$user_data['display_name']}): customer + customer_employee");

            // Create employee record with ACTUAL database IDs (not static data IDs)
            // Pass static employee ID from user_data for predictable test data (TODO-3098)
            $this->createEmployeeRecord(
                $actual_customer_id,
                $actual_branch_id,
                $user_id,
                $user_data['departments'],
                $user_data['id']  // Static employee ID (70-129)
            );
        }
    }

    /**
     * Build customer ID mapping from static data to actual database
     *
     * Task-2171: Static data uses customer_id 1-10, but actual database IDs
     * may be 231-240 (due to auto-increment). This method creates mapping.
     *
     * @return array Map of [static_id => actual_id]
     */
    private function buildCustomerIdMap(): array {
        // Get demo customers ordered by user_id (which maps to static sequence)
        $demo_customers = $this->wpdb->get_results(
            "SELECT id, user_id FROM {$this->wpdb->prefix}app_customers
             WHERE reg_type = 'generate'
             ORDER BY user_id ASC"
        );

        $map = [];
        foreach ($demo_customers as $index => $customer) {
            // user_id 2-11 maps to static customer_id 1-10
            $static_customer_id = $customer->user_id - 1;
            $map[$static_customer_id] = $customer->id;
        }

        return $map;
    }

    /**
     * Build branch ID mapping from static data to actual database
     *
     * Task-2171: Static data uses branch_id based on sequence, but actual
     * database IDs may differ. This method creates mapping.
     *
     * @return array Map of [static_id => actual_id]
     */
    private function buildBranchIdMap(): array {
        // Get demo branches (type='pusat') ordered by customer_id
        $demo_branches = $this->wpdb->get_results(
            "SELECT b.id, b.customer_id, c.user_id
             FROM {$this->wpdb->prefix}app_customer_branches b
             INNER JOIN {$this->wpdb->prefix}app_customers c ON b.customer_id = c.id
             WHERE c.reg_type = 'generate' AND b.type = 'pusat'
             ORDER BY c.user_id ASC"
        );

        $map = [];
        foreach ($demo_branches as $index => $branch) {
            // Static branch_id 1-10 maps to pusat branches in sequence
            $static_branch_id = $index + 1;
            $map[$static_branch_id] = $branch->id;
        }

        return $map;
    }

/**
     * Create employee via runtime flow (simulating real production flow)
     *
     * This method replicates the exact flow that happens in production:
     * 1. Validate data via EmployeeValidator::validateForm()
     * 2. Create employee via EmployeeModel::create()
     * 3. Fire wp_customer_employee_created HOOK (extensibility point)
     * 4. Cache invalidation (handled by Model)
     *
     * Task-2170: Runtime Flow Synchronization
     *
     * @param array $employee_data Employee data
     * @return int|null Employee ID or null on failure
     * @throws \Exception On validation or creation error
     */
    /**
     * Create employee via runtime flow with optional static ID enforcement
     *
     * Uses full production code path:
     * 1. CustomerEmployeeValidator::validateForm() - validates input
     * 2. CustomerEmployeeModel::create() - inserts to database
     * 3. Hook 'wp_customer_employee_created' - extensibility point
     *
     * Demo-specific behavior via hook (optional):
     * - If $static_id provided, hooks into 'wp_customer_employee_before_insert' to force ID
     * - Removes hook after creation to not affect other operations
     *
     * @since 1.0.12 (TODO-3098)
     * @param array $employee_data Employee data
     * @param int|null $static_id Optional static ID to force (demo data)
     * @return int|null Employee ID or null on failure
     * @throws \Exception on failure
     */
    private function createEmployeeViaRuntimeFlow(array $employee_data, ?int $static_id = null): ?int {
        error_log("[EmployeeDemoData] === createEmployeeViaRuntimeFlow START ===");
        if ($static_id !== null) {
            error_log("[EmployeeDemoData] Static ID requested: {$static_id}");
        }
        error_log("[EmployeeDemoData] Employee data: " . json_encode([
            'name' => $employee_data['name'],
            'user_id' => $employee_data['user_id'],
            'customer_id' => $employee_data['customer_id'],
            'branch_id' => $employee_data['branch_id'],
            'position' => $employee_data['position']
        ]));

        try {
            // 1. Validate data using EmployeeValidator (production code testing!)
            $validation_errors = $this->employeeValidator->validateForm($employee_data);

            if (!empty($validation_errors)) {
                $error_msg = implode(', ', $validation_errors);
                error_log("[EmployeeDemoData] Validation failed: {$error_msg}");
                throw new \Exception($error_msg);
            }

            error_log("[EmployeeDemoData] ✓ Validation passed");

            // 2. Hook to force static ID (demo-specific behavior, optional)
            if ($static_id !== null) {
                add_filter('wp_customer_employee_before_insert', function($insertData, $original_data) use ($static_id) {
                    global $wpdb;

                    // Delete existing record with static ID if exists (idempotent)
                    $wpdb->delete(
                        $wpdb->prefix . 'app_customer_employees',
                        ['id' => $static_id],
                        ['%d']
                    );

                    // Force static ID
                    $insertData['id'] = $static_id;

                    error_log("[EmployeeDemoData] ✓ Forcing static ID {$static_id} for: {$insertData['name']}");

                    return $insertData;
                }, 10, 2);
            }

            // 3. Create employee using EmployeeModel::create() (production code!)
            // This triggers wp_customer_employee_created HOOK (extensibility point)
            $employee_id = $this->employeeModel->create($employee_data);

            // Remove hook after use (don't affect subsequent operations)
            if ($static_id !== null) {
                remove_all_filters('wp_customer_employee_before_insert');
            }

            if (!$employee_id) {
                error_log("[EmployeeDemoData] EmployeeModel::create() returned NULL");
                throw new \Exception('Failed to create employee via Model');
            }

            $log_msg = "✓ Employee created with ID: {$employee_id}";
            if ($static_id !== null) {
                $log_msg .= " [STATIC ID: {$static_id}]";
            }
            error_log("[EmployeeDemoData] {$log_msg}");
            error_log("[EmployeeDemoData] ✓ HOOK wp_customer_employee_created triggered");

            // 4. Cache invalidation is handled automatically by EmployeeModel::create()
            // No need to manually invalidate cache here

            error_log("[EmployeeDemoData] === createEmployeeViaRuntimeFlow COMPLETED ===");

            return $static_id !== null ? $static_id : $employee_id;

        } catch (\Exception $e) {
            // Clean up hook on error
            if ($static_id !== null) {
                remove_all_filters('wp_customer_employee_before_insert');
            }
            error_log("[EmployeeDemoData] ERROR in createEmployeeViaRuntimeFlow: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create employee record
     *
     * @param int $customer_id Actual customer ID from database
     * @param int $branch_id Actual branch ID from database
     * @param int $user_id WordPress user ID
     * @param array $departments Department permissions
     * @param int|null $static_employee_id Optional static employee ID (TODO-3098)
     * @return void
     * @throws \Exception on failure
     */
    private function createEmployeeRecord(
        int $customer_id,
        int $branch_id,
        int $user_id,
        array $departments,
        ?int $static_employee_id = null
    ): void {
        try {
            $wp_user = get_userdata($user_id);
            if (!$wp_user) {
                throw new \Exception("WordPress user not found: {$user_id}");
            }

            $keterangan = [];
            if ($user_id >= 2 && $user_id <= 11) $keterangan[] = 'Admin Pusat';
            if ($user_id >= 12 && $user_id <= 41) $keterangan[] = 'Admin Cabang';
            if ($departments['finance']) $keterangan[] = 'Finance';
            if ($departments['operation']) $keterangan[] = 'Operation';
            if ($departments['legal']) $keterangan[] = 'Legal';
            if ($departments['purchase']) $keterangan[] = 'Purchase';

            $employee_data = [
                'customer_id' => $customer_id,
                'branch_id' => $branch_id,
                'user_id' => $user_id,
                'name' => $wp_user->display_name,
                'position' => 'Staff',
                'email' => $wp_user->user_email,
                'phone' => $this->generatePhone(),
                'finance' => $departments['finance'] ?? false,
                'operation' => $departments['operation'] ?? false,
                'legal' => $departments['legal'] ?? false,
                'purchase' => $departments['purchase'] ?? false,
                'keterangan' => implode(', ', $keterangan),
                'created_by' => 1,
                'status' => 'active'
            ];

            // Create employee via runtime flow with static ID (Task-2170, TODO-3098)
            // Validator → Model → HOOK (same as production)
            // Pass static employee ID for predictable test data
            $employee_id = $this->createEmployeeViaRuntimeFlow($employee_data, $static_employee_id);

            if (!$employee_id) {
                throw new \Exception('Failed to create employee via runtime flow');
            }

            $log_msg = "Created employee record for: {$wp_user->display_name} via runtime flow";
            if ($static_employee_id !== null) {
                $log_msg .= " [STATIC ID: {$static_employee_id}]";
            }
            $this->debug($log_msg);

        } catch (\Exception $e) {
            $this->debug("Error creating employee record: " . $e->getMessage());
            throw $e;
        }
    }

    private function generatePhone(): string {
        return sprintf('08%d', rand(100000000, 999999999));
    }
}

