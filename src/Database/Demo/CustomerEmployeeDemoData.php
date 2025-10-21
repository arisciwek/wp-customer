<?php
/**
 * Customer Employee Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: /wp-customer/src/Database/Demo/CustomerEmployeeDemoData.php
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
        foreach (self::$employee_users as $user_data) {
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

            // Create employee record with department assignments
            $this->createEmployeeRecord(
                $user_data['customer_id'],
                $user_data['branch_id'],
                $user_id,
                $user_data['departments']
            );
        }
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
    private function createEmployeeViaRuntimeFlow(array $employee_data): ?int {
        error_log("[EmployeeDemoData] === createEmployeeViaRuntimeFlow START ===");
        error_log("[EmployeeDemoData] Employee data: " . json_encode([
            'name' => $employee_data['name'],
            'user_id' => $employee_data['user_id'],
            'customer_id' => $employee_data['customer_id'],
            'branch_id' => $employee_data['branch_id'],
            'position' => $employee_data['position']
        ]));

        try {
            // 1. Validate data using EmployeeValidator (simulating real runtime validation)
            $validation_errors = $this->employeeValidator->validateForm($employee_data);

            if (!empty($validation_errors)) {
                $error_msg = implode(', ', $validation_errors);
                error_log("[EmployeeDemoData] Validation failed: {$error_msg}");
                throw new \Exception($error_msg);
            }

            error_log("[EmployeeDemoData] ✓ Validation passed");

            // 2. Create employee using EmployeeModel::create()
            // This triggers wp_customer_employee_created HOOK (extensibility point)
            $employee_id = $this->employeeModel->create($employee_data);

            if (!$employee_id) {
                error_log("[EmployeeDemoData] EmployeeModel::create() returned NULL");
                throw new \Exception('Failed to create employee via Model');
            }

            error_log("[EmployeeDemoData] ✓ Employee created with ID: {$employee_id}");
            error_log("[EmployeeDemoData] ✓ HOOK wp_customer_employee_created triggered");

            // 3. Cache invalidation is handled automatically by EmployeeModel::create()
            // No need to manually invalidate cache here

            error_log("[EmployeeDemoData] === createEmployeeViaRuntimeFlow COMPLETED ===");

            return $employee_id;

        } catch (\Exception $e) {
            error_log("[EmployeeDemoData] ERROR in createEmployeeViaRuntimeFlow: " . $e->getMessage());
            throw $e;
        }
    }

    private function createEmployeeRecord(
        int $customer_id,
        int $branch_id,
        int $user_id,
        array $departments
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

            // Create employee via runtime flow (Task-2170)
            // Validator → Model → HOOK (same as production)
            $employee_id = $this->createEmployeeViaRuntimeFlow($employee_data);

            if (!$employee_id) {
                throw new \Exception('Failed to create employee via runtime flow');
            }

            $this->debug("Created employee record for: {$wp_user->display_name} via runtime flow");

        } catch (\Exception $e) {
            $this->debug("Error creating employee record: " . $e->getMessage());
            throw $e;
        }
    }

    private function generatePhone(): string {
        return sprintf('08%d', rand(100000000, 999999999));
    }
}

