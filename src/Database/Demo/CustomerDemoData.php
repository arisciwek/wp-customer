<?php
/**
 * Customer Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/Data/CustomerDemoData.php
 *
 * Description: Generate customer demo data dengan:
 *              - Data perusahaan dengan format yang valid
 *              - Integrasi dengan WordPress user
 *              - Data wilayah dari Provinces/Regencies
 *              - Validasi dan tracking data unik
 *              - Static ID enforcement via wp_customer_customer_before_insert hook (TODO-3098)
 *              - REFACTORED: Uses shared AbstractDemoData from wp-app-core (TODO-2201)
 *
 * Changelog:
 * 1.1.0 - 2025-01-13 (TODO-2201: Abstract Demo Data Pattern)
 * - BREAKING: Now extends WPAppCore\Database\Demo\AbstractDemoData (shared)
 * - BREAKING: Uses WPAppCore\Database\Demo\WPUserGenerator (shared)
 * - Added: initModels() method to initialize customerModel and cache
 * - Code reduction: Eliminates duplicate AbstractDemoData (120 lines)
 * - Code reduction: Eliminates duplicate WPUserGenerator (435 lines)
 * - Pattern: Child plugin implements initModels(), validate(), generate()
 * - Compatible with wp-app-core v2.0.1+ AbstractDemoData
 *
 * 1.0.16 - 2025-11-09 (FIX: Hook name mismatch)
 * - CRITICAL FIX: Updated hook name from 'wp_customer_before_insert' to 'wp_customer_customer_before_insert'
 * - Reason: AbstractCrudModel uses pattern "{plugin}_{entity}_before_insert"
 * - Pattern: wp_customer (plugin) + customer (entity) = wp_customer_customer_before_insert
 * - Updated in: add_filter(), remove_all_filters() (2 places), docblock comments
 * - Result: Static ID enforcement now works correctly via hook
 *
 * 1.0.15 - 2025-11-09 (FIX: wp-app-core cache contract issue - COMPLETE)
 * - CRITICAL FIX: Direct wpdb delete instead of Model delete in cleanup
 * - Root cause: AbstractCacheManager returns null (not false) on cache miss
 * - This breaks AbstractCrudModel find() which expects false for cache miss
 * - Added wp_cache_flush() at cleanup start
 * - Fixed hook parameters: fetch customer_data before triggering hooks
 * - Result: Cleanup works perfectly on multiple runs
 *
 * 1.0.14 - 2025-11-09 (FIX: Cleanup rollback issue)
 * - CRITICAL FIX: Commit cleanup immediately before generation
 * - Prevents cleanup rollback when generation fails
 * - Cleanup is now permanent even if subsequent validation fails
 *
 * 1.0.13 - 2025-11-04 (FIX: Use province_id instead of provinsi_code)
 * - CRITICAL FIX: Changed getRandomProvinceWithAgency() to use ID-based JOIN
 * - Updated JOIN condition: p.id = a.province_id (instead of p.code = a.provinsi_code)
 * - Matches current AgenciesDB schema (ID-based FKs)
 *
 * 1.0.12 - 2025-11-01 (TODO-3098)
 * - Updated createCustomerViaRuntimeFlow() to accept $static_id parameter
 * - Uses wp_customer_customer_before_insert hook to force static customer IDs
 * - Uses wp_customer_branch_before_insert hook to force static branch pusat IDs
 * - Fixes ID overlap issue between pusat (auto-increment) and cabang (static)
 * - Fixes duplicate employee email on regeneration (explicit cleanup in generate())
 * - Tests full production code flow (Validator → Model → Hook)
 * - Ensures predictable test data (customer_id=3, branch_pusat_id=18)
 */

namespace WPCustomer\Database\Demo;

use WPAppCore\Database\Demo\AbstractDemoData;  // TODO-2201: Shared from wp-app-core
use WPAppCore\Database\Demo\WPUserGenerator;   // TODO-2201: Shared from wp-app-core
use WPCustomer\Database\Demo\Data\CustomerUsersData;
use WPCustomer\Database\Demo\Data\BranchUsersData;
use WPCustomer\Validators\CustomerValidator;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Cache\CustomerCacheManager;

defined('ABSPATH') || exit;

class CustomerDemoData extends AbstractDemoData {
    use CustomerDemoDataHelperTrait;

    private static $customer_ids = [];
    private static $user_ids = [];
    private static $used_emails = [];
    public $used_names = [];
    public $used_npwp = [];
    public $used_nib = [];
    protected $customer_users = [];
    private $customerValidator;

    // TODO-2201: Properties for wp-app-core AbstractDemoData pattern
    protected $customerModel;
    protected $cache;

    // Data statis customer
    private static $customers = [
        //['id' => 1, 'name' => 'PT Maju Bersama', 'provinsi_id' => '16', 'regency_id' => '34'],
        ['id' => 1, 'name' => 'PT Maju Bersama'],
        ['id' => 2, 'name' => 'CV Teknologi Nusantara'],
        ['id' => 3, 'name' => 'PT Sinar Abadi'],
        ['id' => 4, 'name' => 'PT Global Teknindo'],
        ['id' => 5, 'name' => 'CV Mitra Solusi'],
        ['id' => 6, 'name' => 'PT Karya Digital'],
        ['id' => 7, 'name' => 'PT Bumi Perkasa'],
        ['id' => 8, 'name' => 'CV Cipta Kreasi'],
        ['id' => 9, 'name' => 'PT Meta Inovasi'],
        ['id' => 10, 'name' => 'PT Delta Sistem']
    ];

    /**
     * Constructor to initialize properties
     */
    public function __construct() {
        parent::__construct();
        $this->customer_users = CustomerUsersData::$data;
        $this->customerValidator = new CustomerValidator();
    }

    /**
     * Initialize plugin-specific models and cache managers
     * Required by wp-app-core AbstractDemoData (TODO-2201)
     *
     * @return void
     */
    public function initModels(): void {
        $this->cache = new CustomerCacheManager();

        if (class_exists('WPCustomer\Models\Customer\CustomerModel')) {
            $this->customerModel = new CustomerModel();
        }
    }

    /**
     * Override run() to cleanup BEFORE parent transaction
     */
    public function run(): bool {
        // Ensure models are initialized
        $this->initModels();

        // IMPORTANT: Cleanup BEFORE transaction to prevent rollback
        if ($this->isDevelopmentMode() && $this->shouldClearData()) {
            $this->cleanupDemoData();
        }

        // Now call parent run() which starts transaction and calls generate()
        return parent::run();
    }

    /**
     * Cleanup demo data (executed BEFORE transaction)
     */
    private function cleanupDemoData(): void {
        error_log("[CustomerDemoData] === PRE-TRANSACTION Cleanup - Deleting existing demo customers ===");

        // 0. CRITICAL: Flush all caches first (fix for cache null vs false issue)
        wp_cache_flush();
        error_log("[CustomerDemoData] Flushed all caches");

        // 1. Enable hard delete temporarily
        $original_settings = get_option('wp_customer_general_options', []);
        $cleanup_settings = array_merge($original_settings, ['enable_hard_delete_branch' => true]);
        update_option('wp_customer_general_options', $cleanup_settings);
        error_log("[CustomerDemoData] Temporarily enabled hard delete mode");

        // 2. Get existing demo customers
        $demo_customers = $this->wpdb->get_col(
            "SELECT id FROM {$this->wpdb->prefix}app_customers WHERE reg_type = 'generate'"
        );
        error_log("[CustomerDemoData] Found " . count($demo_customers) . " demo customers to clean");

        // 3. Delete auto-created employees first
        if (!empty($demo_customers)) {
            $customer_ids_str = implode(',', array_map('intval', $demo_customers));
            $deleted_employees = $this->wpdb->query(
                "DELETE FROM {$this->wpdb->prefix}app_customer_employees
                 WHERE customer_id IN ({$customer_ids_str})"
            );
            error_log("[CustomerDemoData] Deleted {$deleted_employees} auto-created employees");
        }

        // 4. Delete customers directly via wpdb
        // NOTE: Cannot use Model->delete() due to wp-app-core cache contract issue
        // (AbstractCacheManager returns null on cache miss, breaking find() in delete())
        $deleted_count = 0;
        foreach ($demo_customers as $customer_id) {
            // Fetch customer data for hook
            $customer_data = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->prefix}app_customers WHERE id = %d",
                    $customer_id
                ),
                ARRAY_A
            );

            if (!$customer_data) {
                error_log("[CustomerDemoData] WARNING: Customer ID {$customer_id} not found, skipping");
                continue;
            }

            // Trigger before delete hook manually (correct hook name)
            do_action('wp_customer_customer_before_delete', $customer_id, $customer_data);

            // Direct delete from database
            $result = $this->wpdb->delete(
                $this->wpdb->prefix . 'app_customers',
                ['id' => $customer_id],
                ['%d']
            );

            if ($result !== false) {
                $deleted_count++;
                // Trigger after delete hook for cascade operations (hard delete mode, correct hook name)
                do_action('wp_customer_customer_deleted', $customer_id, $customer_data, true);
            } else {
                error_log("[CustomerDemoData] WARNING: Failed to delete customer ID {$customer_id}");
            }
        }
        error_log("[CustomerDemoData] Deleted {$deleted_count} demo customers (hooks triggered for cascade)");

        // 5. Clean up demo users
        $userGenerator = new WPUserGenerator();
        $user_ids_to_delete = array_column($this->customer_users, 'id');
        $deleted_users = $userGenerator->deleteUsers($user_ids_to_delete);
        error_log("[CustomerDemoData] Cleaned up {$deleted_users} demo users");

        // 6. Restore original settings
        update_option('wp_customer_general_options', $original_settings);
        error_log("[CustomerDemoData] Restored original hard delete setting");
        error_log("[CustomerDemoData] === Cleanup completed BEFORE transaction ===");
    }

    /**
     * Validasi sebelum generate data
     */
    protected function validate(): bool {
        try {
            // Validasi tabel provinces & regencies
            $provinces_exist = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}wi_provinces'"
            );
            if (!$provinces_exist) {
                throw new \Exception('Tabel provinces tidak ditemukan');
            }

            // Get customer users mapping
            if (empty($this->customer_users)) {
                throw new \Exception('Customer users not found');
            }

            $regencies_exist = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}wi_regencies'"
            );
            if (!$regencies_exist) {
                throw new \Exception('Tabel regencies tidak ditemukan');
            }

            // Cek data provinces & regencies tersedia
            $province_count = $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}wi_provinces"
            );
            if ($province_count == 0) {
                throw new \Exception('Data provinces kosong');
            }

            $regency_count = $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}wi_regencies"
            );
            if ($regency_count == 0) {
                throw new \Exception('Data regencies kosong');
            }

            return true;

        } catch (\Exception $e) {
            $this->debug('Validation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create customer via runtime flow (simulating real production flow)
     *
     * This method replicates the exact flow that happens in production:
     * 1. Validate data via CustomerValidator::validateForm()
     * 2. Create customer via CustomerModel::create()
     * 3. Fire wp_customer_created HOOK (auto-creates branch pusat + employee)
     * 4. Cache invalidation (handled by Model)
     *
     * @param array $customer_data Customer data
     * @return int|null Customer ID or null on failure
     * @throws \Exception On validation or creation error
     */
    /**
     * Create customer via runtime flow with static ID enforcement
     *
     * Uses full production code path:
     * 1. CustomerValidator::validateForm() - validates input
     * 2. CustomerModel::create() - inserts to database
     * 3. Hook 'wp_customer_created' - auto-creates branch pusat
     *
     * Demo-specific behavior via hook:
     * - Hooks into 'wp_customer_customer_before_insert' to force static ID
     * - Removes hook after creation to not affect other operations
     *
     * @since 1.0.12 (TODO-3098)
     * @param array $customer_data Customer data (name, npwp, nib, etc)
     * @param int $static_id Static ID from self::$customers array
     * @return int Customer ID (static ID)
     * @throws \Exception on failure
     */
    private function createCustomerViaRuntimeFlow(array $customer_data, int $static_id): int {
        error_log("[CustomerDemoData] === createCustomerViaRuntimeFlow START (Static ID: {$static_id}) ===");
        error_log("[CustomerDemoData] Customer data: " . json_encode([
            'name' => $customer_data['name'],
            'user_id' => $customer_data['user_id'],
            'provinsi_id' => $customer_data['provinsi_id'],
            'regency_id' => $customer_data['regency_id']
        ]));

        try {
            // 1. Validate data using CustomerValidator (production code testing!)
            $validation_errors = $this->customerValidator->validateForm($customer_data);

            if (!empty($validation_errors)) {
                $error_msg = implode(', ', $validation_errors);
                error_log("[CustomerDemoData] Validation failed: {$error_msg}");
                throw new \Exception($error_msg);
            }

            error_log("[CustomerDemoData] ✓ Validation passed");

            // 2. Hook to force static ID for customer (demo-specific behavior)
            add_filter('wp_customer_customer_before_insert', function($insert_data, $original_data) use ($static_id) {
                global $wpdb;

                // Delete existing record with static ID if exists (idempotent)
                $wpdb->delete(
                    $wpdb->prefix . 'app_customers',
                    ['id' => $static_id],
                    ['%d']
                );

                // Force static ID
                $insert_data['id'] = $static_id;

                error_log("[CustomerDemoData] ✓ Forcing customer static ID {$static_id} for: {$insert_data['name']}");

                return $insert_data;
            }, 10, 2);

            // 2b. Hook to force static ID for branch pusat (auto-created via wp_customer_created)
            // TODO-3098: Branch pusat uses user_id from BranchUsersData as static branch_id
            add_filter('wp_customer_branch_before_insert', function($insert_data, $original_data) use ($static_id) {
                global $wpdb;

                // Get pusat user_id from BranchUsersData
                $branch_users = \WPCustomer\Database\Demo\Data\BranchUsersData::$data;

                if (!isset($branch_users[$static_id]['pusat']['id'])) {
                    error_log("[CustomerDemoData] WARNING: No pusat user_id found for customer {$static_id}");
                    return $insert_data; // Skip static ID for branch
                }

                $pusat_user_id = $branch_users[$static_id]['pusat']['id'];

                // Delete existing branch with this static ID if exists (idempotent)
                $wpdb->delete(
                    $wpdb->prefix . 'app_customer_branches',
                    ['id' => $pusat_user_id],
                    ['%d']
                );

                // Force static ID for branch pusat
                $insert_data['id'] = $pusat_user_id;

                error_log("[CustomerDemoData] ✓ Forcing branch pusat static ID {$pusat_user_id} for customer {$static_id}");

                return $insert_data;
            }, 10, 2);

            // 3. Create customer using CustomerModel::create() (production code!)
            // This triggers wp_customer_created HOOK which auto-creates branch pusat + employee
            $customer_id = $this->customerModel->create($customer_data);

            // Remove hooks after use (don't affect subsequent operations)
            remove_all_filters('wp_customer_customer_before_insert');
            remove_all_filters('wp_customer_branch_before_insert');

            if (!$customer_id) {
                error_log("[CustomerDemoData] CustomerModel::create() returned NULL");
                throw new \Exception('Failed to create customer via Model');
            }

            error_log("[CustomerDemoData] ✓ Customer created with static ID: {$static_id}");
            error_log("[CustomerDemoData] ✓ HOOK wp_customer_created triggered - will auto-create branch pusat + employee");

            // 4. Cache invalidation is handled automatically by CustomerModel::create()
            // No need to manually invalidate cache here

            error_log("[CustomerDemoData] === createCustomerViaRuntimeFlow COMPLETED ===");

            return $static_id;

        } catch (\Exception $e) {
            // Clean up hooks on error
            remove_all_filters('wp_customer_customer_before_insert');
            remove_all_filters('wp_customer_branch_before_insert');
            error_log("[CustomerDemoData] ERROR in createCustomerViaRuntimeFlow: " . $e->getMessage());
            throw $e;
        }
    }

    protected function generate(): void {
        if (!$this->isDevelopmentMode()) {
            $this->debug('Cannot generate data - not in development mode');
            return;
        }

        // Cleanup already done in run() BEFORE transaction
        // Just proceed with generation
        $userGenerator = new WPUserGenerator();

        foreach (self::$customers as $customer) {
            try {
                // Cleanup already deleted old customers in run() - safe to proceed

                // 1. Create WP User for customer admin
                error_log("[CustomerDemoData] === Processing Customer ID: {$customer['id']} - {$customer['name']} ===");

                // Ambil data user dari static array
                $user_data = $this->customer_users[$customer['id'] - 1];
                error_log("[CustomerDemoData] User data from array: " . json_encode($user_data));

                $user_params = [
                    'id' => $user_data['id'],
                    'username' => $user_data['username'],
                    'display_name' => $user_data['display_name'],
                    'role' => 'customer'
                ];
                error_log("[CustomerDemoData] Calling generateUser with params: " . json_encode($user_params));

                $user_id = $userGenerator->generateUser($user_params);

                error_log("[CustomerDemoData] generateUser returned user_id: " . ($user_id ?: 'NULL/FALSE'));

                if (!$user_id) {
                    error_log("[CustomerDemoData] ERROR: Failed to create WordPress user for customer: {$customer['name']}");
                    throw new \Exception("Failed to create WordPress user for customer: {$customer['name']}");
                }

                // Store user_id untuk referensi
                self::$user_ids[$customer['id']] = $user_id;
                error_log("[CustomerDemoData] Stored user_id {$user_id} for customer ID {$customer['id']}");

                // 2b. Add customer_admin role to the user
                error_log("[CustomerDemoData] Adding customer_admin role to user {$user_id}");

                // Check if customer_admin role exists in WordPress
                $role_exists = get_role('customer_admin');
                error_log("[CustomerDemoData] customer_admin role exists: " . ($role_exists ? 'YES' : 'NO'));

                if (!$role_exists) {
                    error_log("[CustomerDemoData] customer_admin role not found, creating it...");
                    // Create the role if it doesn't exist
                    add_role(
                        'customer_admin',
                        __('Customer Admin', 'wp-customer'),
                        [] // Empty capabilities, will be set by PermissionModel
                    );
                    error_log("[CustomerDemoData] customer_admin role created");
                }

                $user = new \WP_User($user_id);

                // Get current roles
                $current_roles = $user->roles;
                error_log("[CustomerDemoData] Current roles before adding: " . json_encode($current_roles));

                // Add customer_admin role (this will not remove existing roles)
                $user->add_role('customer_admin');

                // Verify roles after adding
                $user = new \WP_User($user_id); // Refresh user object
                $updated_roles = $user->roles;
                error_log("[CustomerDemoData] Roles after adding customer_admin: " . json_encode($updated_roles));

                if (in_array('customer_admin', $updated_roles)) {
                    error_log("[CustomerDemoData] Successfully added customer_admin role to user {$user_id}");
                } else {
                    error_log("[CustomerDemoData] WARNING: Failed to add customer_admin role to user {$user_id}");
                }

                // 3. Generate customer data baru
                if (isset($customer['provinsi_id'])) {
                    $provinsi_id = (int)$customer['provinsi_id'];
                    // Pastikan regency sesuai dengan provinsi ini
                    $regency_id = isset($customer['regency_id']) ?
                        (int)$customer['regency_id'] :
                        $this->getRandomRegencyId($provinsi_id);
                } else {
                    // Get random province that has an agency
                    $provinsi_id = $this->getRandomProvinceWithAgency();
                    $regency_id = $this->getRandomRegencyId($provinsi_id);
                }

                // Validate location relationship
                if (!$this->validateLocation($provinsi_id, $regency_id)) {
                    throw new \Exception("Invalid province-regency relationship: Province {$provinsi_id}, Regency {$regency_id}");
                }

                // 4. Prepare customer data (without fixed ID - let Model auto-generate)
                $customer_data = [
                    'name' => $customer['name'],
                    'npwp' => $this->generateNPWP(),
                    'nib' => $this->generateNIB(),
                    'status' => 'active',
                    'provinsi_id' => $provinsi_id ?: null,
                    'regency_id' => $regency_id ?: null,
                    'user_id' => $user_id,
                    'reg_type' => 'generate'
                ];

                error_log("[CustomerDemoData] Creating customer with data: " . json_encode([
                    'name' => $customer_data['name'],
                    'user_id' => $customer_data['user_id'],
                    'provinsi_id' => $customer_data['provinsi_id'],
                    'regency_id' => $customer_data['regency_id'],
                    'reg_type' => $customer_data['reg_type'],
                    'static_id' => $customer['id']
                ]));

                // 5. Create customer via runtime flow with static ID (Validator → Model → HOOK)
                $customer_id = $this->createCustomerViaRuntimeFlow($customer_data, $customer['id']);

                if (!$customer_id) {
                    error_log("[CustomerDemoData] ERROR: Failed to create customer via runtime flow");
                    throw new \Exception("Failed to create customer: {$customer['name']}");
                }

                // Verify static ID was used
                if ($customer_id !== $customer['id']) {
                    error_log("[CustomerDemoData] WARNING: Expected ID {$customer['id']}, got {$customer_id}");
                }

                error_log("[CustomerDemoData] Successfully created customer ID {$customer_id} via runtime flow");
                error_log("[CustomerDemoData] HOOK wp_customer_created triggered - auto-created branch pusat and employee");

                // Track customer ID
                self::$customer_ids[] = $customer_id;

                $this->debug("Created customer: {$customer['name']} with ID: {$customer_id} and WP User ID: {$user_id}");

            } catch (\Exception $e) {
                $this->debug("Error processing customer {$customer['name']}: " . $e->getMessage());
                throw $e;
            }
        }

        // Cache invalidation handled by respective models during create()
        // No need to invalidate here - each model handles its own cache

        // Reset auto_increment
        $this->wpdb->query(
            "ALTER TABLE {$this->wpdb->prefix}app_customers AUTO_INCREMENT = 211"
        );
    }

    /**
     * Get array of generated customer IDs
     */
    public function getCustomerIds(): array {
        return self::$customer_ids;
    }

    /**
     * Get random province ID that has an agency
     */
    private function getRandomProvinceWithAgency(): int {
        // Get all provinces that have agencies (ID-based FK)
        $provinces_with_agency = $this->wpdb->get_col(
            "SELECT DISTINCT p.id FROM {$this->wpdb->prefix}wi_provinces p
             INNER JOIN {$this->wpdb->prefix}app_agencies a ON p.id = a.province_id"
        );

        if (empty($provinces_with_agency)) {
            throw new \Exception('No provinces with agencies found');
        }

        return (int) $provinces_with_agency[array_rand($provinces_with_agency)];
    }

    /**
     * Get array of generated user IDs
     */
    public function getUserIds(): array {
        return self::$user_ids;
    }
}
