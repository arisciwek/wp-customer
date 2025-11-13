<?php
/**
 * Branch Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.0.17
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/BranchDemoData.php
 *
 * Description: Generate branch demo data dengan:
 *              - Kantor pusat (type = pusat) untuk setiap customer
 *              - Cabang (type = cabang) dengan lokasi yang berbeda
 *              - Format kode sesuai BranchModel::generateBranchCode()
 *              - Data lokasi dari wi_provinces dan wi_regencies
 *              - Branch memiliki 1 kantor pusat dan 1-2 cabang
 *              - Location data terintegrasi dengan trait
 *              - Tracking unique values (NITKU, email)
 *              - Error handling dan validasi
 *
 * Dependencies:
 * - AbstractDemoData                : Base class untuk demo data generation
 * - CustomerDemoDataHelperTrait     : Shared helper methods
 * - CustomerModel                   : Get customer data
 * - BranchModel                     : Generate branch code & save data
 * - WP Database (wi_provinces, wi_regencies)
 *
 * Database Design:
 * - app_customer_branches
 *   * id             : Primary key
 *   * customer_id    : Foreign key ke customer
 *   * code           : Format Format kode: TTTT-RRXxRRXx-RR (13 karakter)
 *   *                  TTTT-RRXxRRXx adalah kode customer (12 karakter)
 *   *                  Tanda hubung '-' (1 karakter)
 *   *                  RR adalah 2 digit random number
 *   * name           : Nama branch
 *   * type           : enum('cabang','pusat')
 *   * nitku          : Nomor Identitas Tempat Kegiatan Usaha
 *   * province_id    : Foreign key ke wi_provinces
 *   * regency_id     : Foreign key ke wi_regencies
 *   * user_id        : Foreign key ke wp_users
 *   * status         : enum('active','inactive')
 *
 * Usage Example:
 * ```php
 * $branchDemo = new BranchDemoData($customer_ids, $user_ids);
 * $branchDemo->run();
 * $branch_ids = $branchDemo->getBranchIds();
 * ```
 *
 * Order of operations:
 * 1. Validate customer_ids dan user_ids
 * 2. Validate provinces & regencies tables
 * 3. Generate pusat branch setiap customer
 * 4. Generate cabang branches (1-2 per customer)
 * 5. Track generated branch IDs
 *
 * Changelog:
 * 1.0.17 - 2025-11-13 (FIX: User creation permission issue + cleanup flow)
 * - CRITICAL FIX: Create all branch users at start of generate() method
 * - Follows CustomerDemoData pattern: users created before branch generation
 * - Removes user creation from generateCabangBranches() and generateExtraBranches()
 * - Step 1: Create cabang users (cabang1, cabang2 for each customer)
 * - Step 1b: Create extra branch users (for assign inspector testing)
 * - Step 2: Generate branches using existing users
 * - Fixes "Current user cannot create users" error in web context
 * - Fixes "Maaf, nama pengguna tersebut sudah ada!" error for extra branches
 * - User creation now happens in correct WordPress context with proper permissions
 * - CLEANUP: ALWAYS delete cabang branches before generation (via Model, triggers HOOK)
 * - CLEANUP: Keeps pusat branches intact (created by CustomerDemoData)
 * - CLEANUP: Ensures clean state for demo data on every run
 * - PERFORMANCE: Increased timeout to 600s and memory to 256M
 * - Added detailed progress logging for debugging timeout issues
 *
 * 1.0.16 - 2025-11-13 (FIX: SQL error in getRandomDivisionWithJurisdictions)
 * - CRITICAL FIX: Fixed SQL column reference a.provinsi_code → a.province_id
 * - Removed unnecessary JOIN to wi_provinces table (province_id already in agencies)
 * - Line 1280: Changed INNER JOIN wi_provinces p ON a.provinsi_code = p.code
 * - Now: SELECT a.province_id directly from app_agencies table
 * - Fixes "Unknown column 'a.provinsi_code'" error during branch demo generation
 * - Matches agency table schema (uses province_id, not provinsi_code)
 *
 * 1.0.15 - 2025-11-09 (FIX: wp-app-core cache contract issue)
 * - CRITICAL FIX: Added wp_cache_flush() at start of validate()
 * - CRITICAL FIX: Replace Model->find() with direct wpdb query (2 locations)
 * - Reason: AbstractCacheManager returns null (not false) on cache miss
 * - This causes find() to return null even when customer exists in database
 * - Line 176-179: Direct query in validate() for customer check
 * - Line 327-330: Direct query in generate() for customer check
 * - Result: Branch demo data generation now works correctly
 *
 * 1.0.14 - 2025-11-04 (FIX: Phone number format validation)
 * - CRITICAL FIX: Updated generatePhone() to match validator requirements
 * - Format: 08xxxxxxxxxx (08 followed by 8-13 digits, total 10-15 digits)
 * - Uses real Indonesian mobile operator prefixes (Telkomsel, Indosat, XL, Three)
 * - Generates random 8-11 digits after operator prefix
 * - Fixes "Format telepon tidak valid" error during branch creation
 * - Removes invalid formats: +62 prefix, landline numbers
 *
 * 1.0.13 - 2025-11-04 (FIX: Use province_id/regency_id instead of codes)
 * - CRITICAL FIX: Changed all demo helper methods from code-based to ID-based
 * - Updated generateAgencyID(): province_id instead of provinsi_code
 * - Updated generateDivisionID(): regency_id instead of regency_code (2 locations)
 * - Updated getRandomProvinceWithAgency(): p.id = a.province_id
 * - Updated getRandomProvinceWithAgencyExcept(): p.id = a.province_id
 * - Updated getRandomRegencyFromDivisionJurisdictions(): r.id = j.jurisdiction_regency_id
 * - Matches current AgenciesDB/DivisionsDB/JurisdictionsDB schema (ID-based FKs)
 * - Fixes demo data generation errors during branch creation
 *
 * 1.0.12 - 2025-11-01 (TODO-3098)
 * - Updated createBranchViaRuntimeFlow() to support optional static ID parameter
 * - Uses wp_customer_branch_before_insert hook to force static IDs for cabang branches
 * - Removed unused private static $branches variable (dead code cleanup)
 * - Branch pusat static ID enforced via CustomerDemoData hook (no changes here)
 * - Fixes user cleanup: delete by both ID and username (handles auto-increment IDs)
 * - Tests full production code flow (Validator → Model → Hook)
 * - Pattern consistent with CustomerDemoData static ID enforcement
 *
 * 1.0.0 - 2024-01-27
 * - Initial version
 * - Added integration with wi_provinces and wi_regencies
 * - Added location validation and tracking
 * - Added documentation and usage examples
 */

namespace WPCustomer\Database\Demo;

use WPAppCore\Database\Demo\AbstractDemoData;  // TODO-2201: Shared from wp-app-core
use WPAppCore\Database\Demo\WPUserGenerator;   // TODO-2201: Shared from wp-app-core
use WPCustomer\Database\Demo\Data\BranchUsersData;
use WPCustomer\Controllers\Branch\BranchController;
use WPAgency\Database\Demo\Data\AgencyEmployeeUsersData;

defined('ABSPATH') || exit;

class BranchDemoData extends AbstractDemoData {
    use CustomerDemoDataHelperTrait;

    private $branch_ids = [];
    private $used_nitku = [];
    private $used_emails = [];
    private $used_inspectors = []; // agency_id => array of used inspector_ids
    private $customer_ids;
    private $user_ids;
    protected $branch_users = [];

    // Models initialized in initModels()
    protected $customerModel;
    protected $branchModel;

    public function __construct() {
        parent::__construct();
        $this->customer_ids = [];
        $this->user_ids = [];
        $this->branch_users = BranchUsersData::$data;
    }

    /**
     * Initialize plugin-specific models
     * Required by wp-app-core AbstractDemoData (TODO-2201)
     *
     * @return void
     */
    public function initModels(): void {
        if (class_exists('WPCustomer\Models\Customer\CustomerModel')) {
            $this->customerModel = new \WPCustomer\Models\Customer\CustomerModel();
        }

        if (class_exists('WPCustomer\Models\Branch\BranchModel')) {
            $this->branchModel = new \WPCustomer\Models\Branch\BranchModel();
        }
    }

    /**
     * Validasi data sebelum generate
     */
        protected function validate(): bool {
            try {
                // CRITICAL: Flush cache to avoid wp-app-core cache contract issue
                // (AbstractCacheManager returns null vs false causing find() to fail)
                wp_cache_flush();

                // Get all active customer IDs from model
                $this->customer_ids = $this->customerModel->getAllCustomerIds();
                if (empty($this->customer_ids)) {
                    throw new \Exception('No active customers found in database');
                }

                // Get branch admin users mapping from WPUserGenerator
                $this->branch_users = BranchUsersData::$data;
                if (empty($this->branch_users)) {
                    throw new \Exception('Branch admin users not found');
                }

                // 1. Validasi keberadaan tabel
                $provinces_exist = $this->wpdb->get_var(
                    "SHOW TABLES LIKE '{$this->wpdb->prefix}wi_provinces'"
                );
                if (!$provinces_exist) {
                    throw new \Exception('Provinces table not found');
                }

                $regencies_exist = $this->wpdb->get_var(
                    "SHOW TABLES LIKE '{$this->wpdb->prefix}wi_regencies'"
                );
                if (!$regencies_exist) {
                    throw new \Exception('Regencies table not found');
                }

                // 2. Validasi ketersediaan data provinsi & regency
                $province_count = $this->wpdb->get_var("
                    SELECT COUNT(*) 
                    FROM {$this->wpdb->prefix}wi_provinces
                ");
                if ($province_count == 0) {
                    throw new \Exception('No provinces data found');
                }

                $regency_count = $this->wpdb->get_var("
                    SELECT COUNT(*) 
                    FROM {$this->wpdb->prefix}wi_regencies
                ");
                if ($regency_count == 0) {
                    throw new \Exception('No regencies data found');
                }

                // 3. Validasi data wilayah untuk setiap customer
                foreach ($this->customer_ids as $customer_id) {
                    // Direct query to avoid cache contract issue
                    $customer = $this->wpdb->get_row($this->wpdb->prepare(
                        "SELECT * FROM {$this->wpdb->prefix}app_customers WHERE id = %d",
                        $customer_id
                    ));
                    if (!$customer) {
                        throw new \Exception("Customer not found: {$customer_id}");
                    }

                    // Jika customer punya data wilayah, validasi relasinya
                    if ($customer->province_id && $customer->regency_id) {
                        // Cek provinsi ada
                        $province = $this->wpdb->get_row($this->wpdb->prepare("
                            SELECT * FROM {$this->wpdb->prefix}wi_provinces 
                            WHERE id = %d",
                            $customer->province_id
                        ));
                        if (!$province) {
                            throw new \Exception("Invalid province ID for customer {$customer_id}: {$customer->province_id}");
                        }

                        // Cek regency ada dan berelasi dengan provinsi
                        $regency = $this->wpdb->get_row($this->wpdb->prepare("
                            SELECT r.*, p.name as province_name 
                            FROM {$this->wpdb->prefix}wi_regencies r
                            JOIN {$this->wpdb->prefix}wi_provinces p ON r.province_id = p.id
                            WHERE r.id = %d AND r.province_id = %d",
                            $customer->regency_id,
                            $customer->province_id
                        ));
                        if (!$regency) {
                            throw new \Exception("Invalid regency ID {$customer->regency_id} for province {$customer->province_id}");
                        }

                        $this->debug(sprintf(
                            "Validated location for customer %d: %s, %s %s",
                            $customer_id,
                            $province->name,
                            $regency->type,
                            $regency->name
                        ));
                    }
                }

                $this->debug('All location data validated successfully');
                return true;

            } catch (\Exception $e) {
                $this->debug('Validation failed: ' . $e->getMessage());
                return false;
            }
        }

    protected function generate(): void {
        // Increase max execution time for batch operations
        // Branch generation with user creation can take significant time
        set_time_limit(600); // 600 seconds = 10 minutes
        ini_set('max_execution_time', '600');
        ini_set('memory_limit', '256M');

        if (!$this->isDevelopmentMode()) {
            $this->debug('Cannot generate data - not in development mode');
            throw new \Exception('Development mode is not enabled. Please enable it in settings first.');
        }

        // Initialize WPUserGenerator for cleanup
        $userGenerator = new WPUserGenerator();

        // ALWAYS cleanup existing cabang branches before generation
        // This ensures clean state for demo data (pusat branches are kept)
        error_log("[BranchDemoData] === Cleanup: Deleting existing cabang branches ===");

        // Enable hard delete temporarily untuk demo cleanup
        $original_settings = get_option('wp_customer_general_options', []);
        $cleanup_settings = array_merge($original_settings, ['enable_hard_delete_branch' => true]);
        update_option('wp_customer_general_options', $cleanup_settings);
        error_log("[BranchDemoData] Enabled hard delete mode for cleanup");

        // Delete cabang branches via Model (triggers HOOK for cascade cleanup)
        // Only delete type='cabang', keep type='pusat' (created by CustomerDemoData)
        $cabang_branches = $this->wpdb->get_results(
            "SELECT id FROM {$this->wpdb->prefix}app_customer_branches WHERE type = 'cabang'",
            ARRAY_A
        );

        $deleted_branches = 0;
        foreach ($cabang_branches as $branch) {
            if ($this->branchModel->delete($branch['id'])) {
                $deleted_branches++;
            }
        }
        error_log("[BranchDemoData] Deleted {$deleted_branches} cabang branches (HOOK handles employees)");

        // Restore original settings
        update_option('wp_customer_general_options', $original_settings);
        error_log("[BranchDemoData] Restored original delete mode");

        // Clean up existing demo users if shouldClearData is enabled
        if ($this->shouldClearData()) {
            error_log("[BranchDemoData] === Cleanup mode enabled - Deleting existing demo users ===");

            // Collect all branch admin user IDs from BranchUsersData
            $user_ids_to_delete = [];

            // Regular branch users (pusat + cabang for each customer)
            foreach ($this->branch_users as $customer_id => $branches) {
                // Skip pusat deletion - only delete cabang users
                if (isset($branches['cabang1'])) {
                    $user_ids_to_delete[] = $branches['cabang1']['id'];

                    // Also delete by username if exists with different ID
                    $existing_user = get_user_by('login', $branches['cabang1']['username']);
                    if ($existing_user && !in_array($existing_user->ID, $user_ids_to_delete)) {
                        $user_ids_to_delete[] = $existing_user->ID;
                    }
                }
                if (isset($branches['cabang2'])) {
                    $user_ids_to_delete[] = $branches['cabang2']['id'];

                    // Also delete by username if exists with different ID
                    $existing_user = get_user_by('login', $branches['cabang2']['username']);
                    if ($existing_user && !in_array($existing_user->ID, $user_ids_to_delete)) {
                        $user_ids_to_delete[] = $existing_user->ID;
                    }
                }
            }

            // Extra branch users - delete by both ID and username (TODO-3098 fix)
            // Users might exist with auto-increment IDs instead of static IDs
            $extra_users = BranchUsersData::$extra_branch_users;
            foreach ($extra_users as $user_data) {
                $user_ids_to_delete[] = $user_data['id'];

                // Also delete by username (if exists with different ID)
                $existing_user = get_user_by('login', $user_data['username']);
                if ($existing_user && !in_array($existing_user->ID, $user_ids_to_delete)) {
                    $user_ids_to_delete[] = $existing_user->ID;
                    error_log("[BranchDemoData] Found extra user with auto-increment ID: {$existing_user->ID} (username: {$user_data['username']})");
                }
            }

            error_log("[BranchDemoData] User IDs to clean: " . json_encode($user_ids_to_delete));

            $deleted_users = $userGenerator->deleteUsers($user_ids_to_delete);
            error_log("[BranchDemoData] Cleaned up {$deleted_users} existing demo users");
            $this->debug("Cleaned up {$deleted_users} users and {$deleted_branches} branches before generation");
        }

        // TAMBAHKAN DI SINI
        if (!$this->validate()) {
            throw new \Exception('Pre-generation validation failed');
        }

        $generated_count = 0;

        // Initialize WPUserGenerator untuk create users dengan static ID
        $userGenerator = new WPUserGenerator();

        try {
            // Step 1: Create all branch users first (like CustomerDemoData pattern)
            $this->debug("=== Step 1: Creating branch users ===");
            $user_count = 0;

            foreach ($this->customer_ids as $customer_id) {
                if (!isset($this->branch_users[$customer_id])) {
                    continue;
                }

                // Create cabang users (cabang1 and cabang2)
                foreach (['cabang1', 'cabang2'] as $cabang_key) {
                    if (!isset($this->branch_users[$customer_id][$cabang_key])) {
                        continue;
                    }

                    $user_data = $this->branch_users[$customer_id][$cabang_key];

                    try {
                        $this->debug("Creating user {$user_data['username']} (ID: {$user_data['id']})...");

                        $user_id = $userGenerator->generateUser([
                            'id' => $user_data['id'],
                            'username' => $user_data['username'],
                            'display_name' => $user_data['display_name'],
                            'role' => 'customer'
                        ]);

                        if ($user_id) {
                            // Add customer_branch_admin role
                            $user = get_user_by('ID', $user_id);
                            if ($user) {
                                $role_exists = get_role('customer_branch_admin');
                                if (!$role_exists) {
                                    add_role('customer_branch_admin', __('Customer Branch Admin', 'wp-customer'), []);
                                }
                                $user->add_role('customer_branch_admin');
                                $user_count++;
                                $this->debug("✓ Created user '{$user_data['username']}' (ID: {$user_id})");
                            }
                        }
                    } catch (\Exception $e) {
                        $this->debug("✗ Failed to create user '{$user_data['username']}': " . $e->getMessage());
                        throw $e;
                    }
                }
            }

            $this->debug("=== Completed: Created {$user_count} branch users ===");

            // Step 1b: Create extra branch users
            $this->debug("=== Step 1b: Creating extra branch users ===");
            $extra_users = BranchUsersData::$extra_branch_users;
            $extra_user_count = 0;

            foreach ($extra_users as $user_data) {
                try {
                    $this->debug("Creating extra user {$user_data['username']} (ID: {$user_data['id']})...");

                    $user_id = $userGenerator->generateUser([
                        'id' => $user_data['id'],
                        'username' => $user_data['username'],
                        'display_name' => $user_data['display_name'],
                        'role' => 'customer'
                    ]);

                    if ($user_id) {
                        // Add customer_branch_admin role
                        $user = get_user_by('ID', $user_id);
                        if ($user) {
                            $role_exists = get_role('customer_branch_admin');
                            if (!$role_exists) {
                                add_role('customer_branch_admin', __('Customer Branch Admin', 'wp-customer'), []);
                            }
                            $user->add_role('customer_branch_admin');
                            $extra_user_count++;
                            $this->debug("✓ Created extra user '{$user_data['username']}' (ID: {$user_id})");
                        }
                    }
                } catch (\Exception $e) {
                    $this->debug("✗ Failed to create extra user '{$user_data['username']}': " . $e->getMessage());
                    throw $e;
                }
            }

            $this->debug("=== Completed: Created {$extra_user_count} extra branch users ===");

            // Step 2: Generate branches (users already exist)
            $this->debug("=== Step 2: Generating cabang branches ===");

            foreach ($this->customer_ids as $customer_id) {
                // Direct query to avoid cache contract issue
                $customer = $this->wpdb->get_row($this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->prefix}app_customers WHERE id = %d",
                    $customer_id
                ));
                if (!$customer) {
                    $this->debug("Customer not found: {$customer_id}");
                    continue;
                }

                if (!isset($this->branch_users[$customer_id])) {
                    $this->debug("No branch admin users found for customer {$customer_id}, skipping...");
                    continue;
                }

                // Skip pusat branch generation - auto-created via wp_customer_created HOOK
                // Pusat branch is created by AutoEntityCreator when customer is created
                // Static ID is enforced via hook in CustomerDemoData (TODO-3098)
                $this->debug("Pusat branch for customer {$customer_id} auto-created via HOOK with static ID");

                // Check for existing cabang branches
                $existing_cabang_count = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->wpdb->prefix}app_customer_branches
                     WHERE customer_id = %d AND type = 'cabang'",
                    $customer_id
                ));

                if ($existing_cabang_count > 0) {
                    $this->debug("Cabang branches exist for customer {$customer_id}, skipping...");
                } else {
                    $this->debug("Processing customer {$customer_id}: {$customer->name}");
                    $this->generateCabangBranches($customer);
                    $generated_count++;
                    $this->debug("✓ Completed cabang branches for customer {$customer_id}");
                }
            }

            $this->debug("=== Completed: Generated branches for {$generated_count} customers ===");

            // Generate extra branches for assign inspector
            $this->generateExtraBranches();

            if ($generated_count === 0) {
                $this->debug('No new branches were generated - all branches already exist');
            } else {
                // Reset auto increment only if we added new data
                $this->wpdb->query(
                    "ALTER TABLE {$this->wpdb->prefix}app_customer_branches AUTO_INCREMENT = " .
                    (count($this->branch_ids) + 1)
                );
                $this->debug("Branch generation completed. Total new branches processed: {$generated_count}");
            }

        } catch (\Exception $e) {
            $this->debug("Error in branch generation: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate kantor pusat
     */

    private function generatePusatBranch($customer, $branch_user_id): void {
        // Validate location data
        if (!$this->validateLocation($customer->province_id, $customer->regency_id)) {
            throw new \Exception("Invalid location for customer: {$customer->id}");
        }

        // Generate WordPress user dulu
        $userGenerator = new WPUserGenerator();

        // Ambil data user dari branch_users
        $user_data = $this->branch_users[$customer->id]['pusat'];

        // Generate WP User with specified ID
        $wp_user_id = $userGenerator->generateUser([
            'id' => $user_data['id'],
            'username' => $user_data['username'],
            'display_name' => $user_data['display_name'],
            'role' => 'customer'
        ]);

        if (!$wp_user_id) {
            throw new \Exception("Failed to create WordPress user for branch admin: {$user_data['display_name']}");
        }

        // Add customer_branch_admin role to user
        $user = get_user_by('ID', $wp_user_id);
        if ($user) {
            $role_exists = get_role('customer_branch_admin');
            if (!$role_exists) {
                add_role('customer_branch_admin', __('Customer Branch Admin', 'wp-customer'), []);
            }
            $user->add_role('customer_branch_admin');
            $this->debug("Added customer_branch_admin role to user {$wp_user_id} ({$user_data['display_name']})");
        }

        $regency_name = $this->getRegencyName($customer->regency_id);
        $location = $this->generateValidLocation();

        $agency_id = $this->generateAgencyID($customer->province_id);
        $division_id = $this->generateDivisionID($customer->regency_id);
        $inspector_id = $this->generateInspectorID($customer->province_id);

        $this->debug("Generated for pusat branch - agency_id: {$agency_id}, division_id: {$division_id}, inspector_id: {$inspector_id} for province_id: {$customer->province_id}, regency_id: {$customer->regency_id}");

        // Generate branch code: customer_code + random 2 digits
        $branch_code = $customer->code . '-' . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);

        $branch_data = [
            'customer_id' => $customer->id,
            'code' => $branch_code,
            'name' => sprintf('%s Cabang %s',
                            $customer->name,
                            $regency_name),
            'type' => 'pusat',
            'nitku' => $this->generateNITKU(),
            'postal_code' => $this->generatePostalCode(),
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'address' => $this->generateAddress($regency_name),
            'phone' => $this->generatePhone(),
            'email' => $this->generateEmail($customer->name, 'pusat'),
            'province_id' => $customer->province_id,
            'agency_id' => $agency_id,
            'regency_id' => $customer->regency_id,
            'division_id' => $division_id,
            'user_id' => $branch_user_id,                  // Branch admin user
            'inspector_id' => $inspector_id,
            'created_by' => $customer->user_id,            // Customer owner user
            'status' => 'active'
        ];
    
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'app_customer_branches',
            $branch_data,
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s']
        );

        if ($result === false) {
            throw new \Exception("Failed to create pusat branch for customer: {$customer->id} - " . $this->wpdb->last_error);
        }

        $branch_id = $this->wpdb->insert_id;

        $this->branch_ids[] = $branch_id;
        $this->debug("Created pusat branch for customer {$customer->name}");
    }

    /**
     * Create branch via runtime flow simulation
     * Replicates EXACT logic from BranchController::store() without AJAX/nonce
     *
     * @param int $customer_id Customer ID
     * @param array $branch_data Branch fields (name, type, nitku, etc)
     * @param array $admin_data Admin user fields (username, email, firstname, lastname)
     * @param int $current_user_id User ID who creates the branch (for created_by)
     * @param bool $auto_assign_inspector Auto-assign inspector (regular branches) or leave NULL (extra branches)
     * @return int Branch ID
     * @throws \Exception If validation fails or creation fails
     */
    /**
     * Create branch via runtime flow with optional static ID enforcement
     *
     * Uses full production code path:
     * 1. BranchValidator::validateCreate() - validates input
     * 2. BranchModel::create() - inserts to database
     * 3. Hook 'wp_customer_branch_created' - auto-creates employee
     *
     * Demo-specific behavior via hook (optional):
     * - If $static_id provided, hooks into 'wp_customer_branch_before_insert' to force ID
     * - Removes hook after creation to not affect other operations
     *
     * @since 1.0.12 (TODO-3098)
     * @param int $customer_id Customer ID
     * @param array $branch_data Branch data
     * @param array $admin_data Admin user data
     * @param int $current_user_id Current user ID
     * @param bool $auto_assign_inspector Auto-assign inspector or leave NULL
     * @param int|null $static_id Optional static ID to force (demo data)
     * @return int Branch ID
     * @throws \Exception on failure
     */
    private function createBranchViaRuntimeFlow(
        int $customer_id,
        array $branch_data,
        array $admin_data,
        int $current_user_id,
        bool $auto_assign_inspector = true,
        ?int $static_id = null
    ): int {
        // Initialize validator and model (same as Controller)
        $validator = new \WPCustomer\Validators\Branch\BranchValidator();
        $model = new \WPCustomer\Models\Branch\BranchModel();

        // Step 1: Check customer_id (line 538-541 from store())
        if (!$customer_id) {
            throw new \Exception('ID Customer tidak valid');
        }

        // Step 2: Check permission (line 544-546 from store())
        if (!$validator->canCreateBranch($customer_id)) {
            throw new \Exception('Anda tidak memiliki izin untuk menambah cabang');
        }

        // Step 3: Sanitize input (line 549-564 from store())
        $data = [
            'customer_id' => $customer_id,
            'name' => sanitize_text_field($branch_data['name'] ?? ''),
            'type' => sanitize_text_field($branch_data['type'] ?? ''),
            'nitku' => sanitize_text_field($branch_data['nitku'] ?? ''),
            'postal_code' => sanitize_text_field($branch_data['postal_code'] ?? ''),
            'latitude' => (float)($branch_data['latitude'] ?? 0),
            'longitude' => (float)($branch_data['longitude'] ?? 0),
            'address' => sanitize_text_field($branch_data['address'] ?? ''),
            'phone' => sanitize_text_field($branch_data['phone'] ?? ''),
            'email' => sanitize_email($branch_data['email'] ?? ''),
            'province_id' => isset($branch_data['province_id']) ? (int)$branch_data['province_id'] : null,
            'regency_id' => isset($branch_data['regency_id']) ? (int)$branch_data['regency_id'] : null,
            'created_by' => $current_user_id,
            'status' => 'active'
        ];

        // Step 4: Assign agency and division (line 567-575 from store())
        if ($data['province_id'] && $data['regency_id']) {
            try {
                $agencyDivision = $model->getAgencyAndDivisionIds($data['province_id'], $data['regency_id']);
                $data['agency_id'] = $agencyDivision['agency_id'];
                $data['division_id'] = $agencyDivision['division_id'];
            } catch (\Exception $e) {
                throw new \Exception('Gagal menentukan agency dan division: ' . $e->getMessage());
            }
        }

        // Step 4b: Auto-assign inspector for regular branches (simulate assign inspector action)
        // For extra branches, skip this step to leave inspector_id NULL for testing
        if ($auto_assign_inspector && $data['province_id']) {
            try {
                $inspector_id = $model->getInspectorId(
                    $data['province_id'],
                    $data['division_id'] ?? null
                );
                if ($inspector_id) {
                    $data['inspector_id'] = $inspector_id;
                    $this->debug("Auto-assigned inspector_id {$inspector_id} for branch in provinsi {$data['province_id']}, division {$data['division_id']}");
                } else {
                    $this->debug("No inspector found for provinsi {$data['province_id']}, division {$data['division_id']}, leaving NULL");
                }
            } catch (\Exception $e) {
                // Log but don't fail - inspector assignment is not critical
                $this->debug("Warning: Failed to auto-assign inspector: " . $e->getMessage());
            }
        }

        // Step 5: Validate branch creation data (line 578-581 from store())
        $create_errors = $validator->validateCreate($data);
        if (!empty($create_errors)) {
            throw new \Exception(reset($create_errors));
        }

        // Step 6: Validate branch type (line 584-587 from store())
        $type_validation = $validator->validateBranchTypeCreate($data['type'], $customer_id);
        if (!$type_validation['valid']) {
            throw new \Exception($type_validation['message']);
        }

        // Step 7: Use existing user_id or create new user (line 590-615 from store())
        if (!empty($admin_data['user_id'])) {
            // User already created (demo data dengan WPUserGenerator)
            $data['user_id'] = $admin_data['user_id'];
            $this->debug("Using existing user ID {$data['user_id']} for branch");

        } elseif (!empty($admin_data['email'])) {
            // Create new user (runtime flow untuk production simulation)
            $user_data = [
                'user_login' => sanitize_user($admin_data['username']),
                'user_email' => sanitize_email($admin_data['email']),
                'first_name' => sanitize_text_field($admin_data['firstname']),
                'last_name' => sanitize_text_field($admin_data['lastname'] ?? ''),
                'user_pass' => wp_generate_password(),
                'role' => 'customer'  // Base role for all plugin users
            ];

            // Apply same filter as production code (for consistency)
            $user_data = apply_filters(
                'wp_customer_branch_user_before_insert',
                $user_data,
                $data,
                'branch_admin'
            );

            // Handle static ID if requested
            $static_user_id = null;
            if (isset($user_data['ID'])) {
                $static_user_id = $user_data['ID'];
                unset($user_data['ID']);
            }

            $user_id = wp_insert_user($user_data);
            if (is_wp_error($user_id)) {
                throw new \Exception($user_id->get_error_message());
            }

            // Update to static ID if requested
            if ($static_user_id !== null && $static_user_id != $user_id) {
                $existing = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT ID FROM {$this->wpdb->users} WHERE ID = %d",
                    $static_user_id
                ));

                if (!$existing) {
                    $this->wpdb->query('SET FOREIGN_KEY_CHECKS=0');
                    $this->wpdb->update($this->wpdb->users, ['ID' => $static_user_id], ['ID' => $user_id], ['%d'], ['%d']);
                    $this->wpdb->update($this->wpdb->usermeta, ['user_id' => $static_user_id], ['user_id' => $user_id], ['%d'], ['%d']);
                    $this->wpdb->query('SET FOREIGN_KEY_CHECKS=1');
                    $user_id = $static_user_id;
                    $this->debug("Updated user ID to static ID: {$static_user_id}");
                }
            }

            // Add customer_branch_admin role (dual-role pattern)
            $user = get_user_by('ID', $user_id);
            if ($user) {
                $user->add_role('customer_branch_admin');
            }

            $data['user_id'] = $user_id;

            // Skip email notification for demo data
            // wp_new_user_notification($user_id, null, 'user');
        }

        // Step 8: Hook to force static ID (demo-specific behavior, optional)
        if ($static_id !== null) {
            add_filter('wp_customer_branch_before_insert', function($insertData, $original_data) use ($static_id) {
                global $wpdb;

                // Delete existing record with static ID if exists (idempotent)
                $wpdb->delete(
                    $wpdb->prefix . 'app_customer_branches',
                    ['id' => $static_id],
                    ['%d']
                );

                // Force static ID
                $insertData['id'] = $static_id;

                error_log("[BranchDemoData] ✓ Forcing static ID {$static_id} for: {$insertData['name']}");

                return $insertData;
            }, 10, 2);
        }

        // Step 9: Save branch via BranchModel::create() (production code!)
        try {
            $branch_id = $model->create($data);

            // Remove hook after use (don't affect subsequent operations)
            if ($static_id !== null) {
                remove_all_filters('wp_customer_branch_before_insert');
            }

            if (!$branch_id) {
                if (!empty($user_id)) {
                    wp_delete_user($user_id); // Rollback user creation jika gagal
                }
                throw new \Exception('Gagal menambah cabang');
            }

            // Cache invalidation handled by Model

            $log_msg = "Created branch via runtime flow (ID: {$branch_id}) for customer {$customer_id}";
            if ($static_id !== null) {
                $log_msg .= " [STATIC ID: {$static_id}]";
            }
            $this->debug($log_msg);

            return $static_id !== null ? $static_id : $branch_id;

        } catch (\Exception $e) {
            // Clean up hook on error
            if ($static_id !== null) {
                remove_all_filters('wp_customer_branch_before_insert');
            }
            throw $e;
        }
    }

    /**
     * Generate cabang branches
     * Uses runtime flow simulation for full validation
     */
    private function generateCabangBranches($customer): void {
        $cabang_count = 2; // Selalu buat 2 cabang karena sudah ada 2 user cabang
        $used_provinces = [$customer->province_id];

        for ($i = 0; $i < $cabang_count; $i++) {
            // Get cabang admin user data
            $cabang_key = 'cabang' . ($i + 1);
            if (!isset($this->branch_users[$customer->id][$cabang_key])) {
                $this->debug("No admin user found for {$cabang_key} of customer {$customer->id}, skipping...");
                continue;
            }

            $user_data = $this->branch_users[$customer->id][$cabang_key];
            $user_id = $user_data['id']; // User sudah dibuat di generate() method

            // Get random province that has agency (different from used provinces)
            $province_id = $this->getRandomProvinceWithAgencyExcept($customer->province_id);
            while (in_array($province_id, $used_provinces)) {
                $province_id = $this->getRandomProvinceWithAgencyExcept($customer->province_id);
            }
            $used_provinces[] = $province_id;

            // Get random regency from selected province
            $regency_id = $this->getRandomRegencyId($province_id);
            $regency_name = $this->getRegencyName($regency_id);
            $location = $this->generateValidLocation();

            // Prepare branch data for runtime flow simulation
            $branch_data = [
                'name' => sprintf('%s Cabang %s', $customer->name, $regency_name),
                'type' => 'cabang',
                'nitku' => $this->generateNITKU(),
                'postal_code' => $this->generatePostalCode(),
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'address' => $this->generateAddress($regency_name),
                'phone' => $this->generatePhone(),
                'email' => $this->generateEmail($customer->name, $cabang_key),
                'province_id' => $province_id,
                'regency_id' => $regency_id,
            ];

            // Prepare admin data dengan user_id yang sudah dibuat
            $admin_data = [
                'user_id' => $user_id,  // Pass existing user_id
                'username' => $user_data['username'],
                'email' => $user_data['username'] . '@example.com',
                'firstname' => $user_data['display_name'],
                'lastname' => ''
            ];

            // Create branch via runtime flow (simulates BranchController::store())
            // Set current user to customer owner for permission check
            wp_set_current_user($customer->user_id);

            try {
                // Use user_id as static branch ID for predictable test data (TODO-3098)
                $branch_id = $this->createBranchViaRuntimeFlow(
                    $customer->id,
                    $branch_data,
                    $admin_data,
                    $customer->user_id,  // created_by = customer owner
                    true,                // auto_assign_inspector
                    $user_id             // static_id = user_id (12-41)
                );

                $this->branch_ids[] = $branch_id;

                $log_msg = "Created cabang branch via runtime flow (ID: {$branch_id}) for customer {$customer->name} in {$regency_name}";
                if ($branch_id === $user_id) {
                    $log_msg .= " [STATIC ID: {$user_id}]";
                }
                $this->debug($log_msg);

            } catch (\Exception $e) {
                error_log("[BranchDemoData] Failed to create cabang branch for customer {$customer->id}: " . $e->getMessage());
                $this->debug("Failed to create cabang branch: " . $e->getMessage());
                // Rollback: delete created user
                wp_delete_user($user_id);
                throw $e;
            } finally {
                // Restore no current user
                wp_set_current_user(0);
            }
        }
    }

    /**
     * Generate extra branches for testing assign inspector functionality
     * These branches will have inspector_id = NULL so they appear in New Company tab
     *
     * Uses runtime flow (createBranchViaRuntimeFlow) to test full validation chain.
     * Runtime flow does NOT auto-assign inspector_id, so it stays NULL naturally.
     */
    private function generateExtraBranches(): void {
        $this->debug("Generating extra branches for testing assign inspector...");

        // Get extra branch users from BranchUsersData
        $extra_users = BranchUsersData::$extra_branch_users;
        if (empty($extra_users)) {
            $this->debug("No extra branch users defined, skipping extra branch generation");
            return;
        }

        // Get all customers for random selection
        $customers = [];
        foreach ($this->customer_ids as $customer_id) {
            $customer = $this->customerModel->find($customer_id);
            if ($customer) {
                $customers[] = $customer;
            }
        }

        if (empty($customers)) {
            $this->debug("No customers available for extra branch generation");
            return;
        }

        $generated_extra = 0;

        // Generate extra branches using predefined users
        foreach ($extra_users as $user_data) {
            // Pick random customer
            $customer = $customers[array_rand($customers)];

            // Get a random division that has jurisdictions
            $division_data = $this->getRandomDivisionWithJurisdictions();
            if (!$division_data) {
                $this->debug("No division with jurisdictions found, skipping...");
                continue;
            }

            $division_id = $division_data['id'];
            $province_id = $division_data['province_id'];

            // Get a random regency from this division's jurisdictions that doesn't already have a branch for this customer
            $regency_id = null;
            $max_attempts = 10; // Prevent infinite loop
            $attempts = 0;

            while ($attempts < $max_attempts) {
                $candidate_regency_id = $this->getRandomRegencyFromDivisionJurisdictions($division_id);

                if (!$candidate_regency_id) {
                    break; // No more available regencies
                }

                // Check if this customer already has a branch in this regency
                $existing_branch = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT id FROM {$this->wpdb->prefix}app_customer_branches
                     WHERE customer_id = %d AND regency_id = %d",
                    $customer->id, $candidate_regency_id
                ));

                if (!$existing_branch) {
                    $regency_id = $candidate_regency_id;
                    break;
                }

                $attempts++;
            }

            if (!$regency_id) {
                $this->debug("Could not find available regency for customer {$customer->id} in division {$division_id} after {$max_attempts} attempts, skipping...");
                continue;
            }

            $regency_name = $this->getRegencyName($regency_id);
            $location = $this->generateValidLocation();

            // Prepare branch data for runtime flow simulation
            // Note: agency_id and division_id will be set by runtime flow via getAgencyAndDivisionIds()
            // Note: inspector_id will NOT be set by runtime flow (stays NULL for testing)
            $branch_data = [
                'name' => sprintf('%s Cabang %s', $customer->name, $regency_name),
                'type' => 'cabang',
                'nitku' => $this->generateNITKU(),
                'postal_code' => $this->generatePostalCode(),
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'address' => $this->generateAddress($regency_name),
                'phone' => $this->generatePhone(),
                'email' => $this->generateEmail($customer->name, 'extra' . ($generated_extra + 1)),
                'province_id' => $province_id,
                'regency_id' => $regency_id,
            ];

            // Prepare admin data with existing user_id (user already created in Step 1b)
            $admin_data = [
                'user_id' => $user_data['id'],  // Pass existing user_id
                'username' => $user_data['username'],
                'email' => $user_data['username'] . '@example.com',
                'firstname' => $user_data['display_name'],
                'lastname' => ''
            ];

            // Create branch via runtime flow (simulates BranchController::store())
            // Set current user to customer owner for permission check
            wp_set_current_user($customer->user_id);

            // This will:
            // 1. Validate permissions
            // 2. Sanitize input
            // 3. Call getAgencyAndDivisionIds() to set agency_id and division_id
            // 4. Skip auto-assign inspector (pass false to keep inspector_id NULL)
            // 5. Validate data
            // 6. Use existing user_id (user already created in Step 1b)
            // 7. Create branch via BranchModel::create()
            try {
                $branch_id = $this->createBranchViaRuntimeFlow(
                    $customer->id,
                    $branch_data,
                    $admin_data,
                    $customer->user_id,  // created_by = customer owner
                    false,  // auto_assign_inspector = false for extra branches
                    $user_data['id']  // static_id = user_id (50-69 from BranchUsersData::$extra_branch_users)
                );

                $this->branch_ids[] = $branch_id;
                $generated_extra++;

                $this->debug("Created extra branch via runtime flow (ID: {$branch_id}) for customer {$customer->name} in {$regency_name} (inspector_id = NULL)");

            } catch (\Exception $e) {
                $this->debug("Failed to create extra branch: " . $e->getMessage());
                continue;
            } finally {
                // Restore no current user
                wp_set_current_user(0);
            }
        }

        $this->debug("Extra branch generation completed. Generated {$generated_extra} branches with inspector_id = NULL");
    }

    /**
     * Helper method generators
     */
    private function generateNITKU(): string {
        do {
            $nitku = sprintf("%013d", rand(1000000000000, 9999999999999));
        } while (in_array($nitku, $this->used_nitku));
        
        $this->used_nitku[] = $nitku;
        return $nitku;
    }

    private function generatePostalCode(): string {
        return (string) rand(10000, 99999);
    }

    private function generatePhone(): string {
        // Format: 08xxxxxxxxxx (08 diikuti 8-13 digit angka, total 10-15 digit)
        // Contoh: 081234567890 (12 digit), 085678901234 (12 digit)

        // Indonesian mobile operator prefixes after 08
        $operators = [
            '11', '12', '13', // Telkomsel (0811, 0812, 0813)
            '21', '22', '23', // Indosat (0821, 0822, 0823)
            '31', '32', '33', // XL (0831, 0832, 0833)
            '51', '52', '53', // Indosat IM3 (0851, 0852, 0853)
            '56', '57', '58', // Indosat (0856, 0857, 0858)
            '95', '96', '97', '98', '99' // Three (0895, 0896, 0897, 0898, 0899)
        ];

        $operator = $operators[array_rand($operators)];

        // Generate 8-11 more digits (total will be 10-13 digits: 08 + 2 operator + 8-11 random)
        $remainingDigits = rand(8, 11);
        $randomNumber = str_pad((string)rand(0, pow(10, $remainingDigits) - 1), $remainingDigits, '0', STR_PAD_LEFT);

        return '08' . $operator . $randomNumber;
    }

    private function generateEmail($customer_name, $type): string {
        $domains = ['gmail.com', 'yahoo.com', 'hotmail.com'];
        
        do {
            $email = sprintf('%s.%s@%s',
                $type,
                strtolower(str_replace([' ', '.'], '', $customer_name)),
                $domains[array_rand($domains)]
            );
        } while (in_array($email, $this->used_emails));
        
        $this->used_emails[] = $email;
        return $email;
    }

    /**
     * Get array of generated branch IDs
     */
    public function getBranchIds(): array {
        return $this->branch_ids;
    }

    // Define location bounds untuk wilayah Indonesia
    private const LOCATION_BOUNDS = [
        'LAT_MIN' => -11.0,    // Batas selatan (Pulau Rote)
        'LAT_MAX' => 6.0,      // Batas utara (Sabang)
        'LONG_MIN' => 95.0,    // Batas barat (Pulau Weh)
        'LONG_MAX' => 141.0    // Batas timur (Pulau Merauke)
    ];

    /**
     * Generate random latitude dalam format decimal
     * dengan 8 digit di belakang koma
     */
    private function generateLatitude(): float {
        $min = self::LOCATION_BOUNDS['LAT_MIN'] * 100000000;
        $max = self::LOCATION_BOUNDS['LAT_MAX'] * 100000000;
        $randomInt = rand($min, $max);
        return $randomInt / 100000000;
    }

    /**
     * Generate random longitude dalam format decimal
     * dengan 8 digit di belakang koma
     */
    private function generateLongitude(): float {
        $min = self::LOCATION_BOUNDS['LONG_MIN'] * 100000000;
        $max = self::LOCATION_BOUNDS['LONG_MAX'] * 100000000;
        $randomInt = rand($min, $max);
        return $randomInt / 100000000;
    }

    /**
     * Helper method untuk format koordinat dengan 8 digit decimal
     */
    private function formatCoordinate(float $coordinate): string {
        return number_format($coordinate, 8, '.', '');
    }

    /**
     * Generate dan validasi koordinat
     */
    private function generateValidLocation(): array {
        $latitude = $this->generateLatitude();
        $longitude = $this->generateLongitude();

        return [
            'latitude' => $this->formatCoordinate($latitude),
            'longitude' => $this->formatCoordinate($longitude)
        ];
    }

    /**
     * Debug method untuk test hasil generate
     */
    private function debugLocation(): void {
        $location = $this->generateValidLocation();
        $this->debug(sprintf(
            "Generated location - Lat: %s, Long: %s",
            $location['latitude'],
            $location['longitude']
        ));
    }

    /**
     * Generate agency_id by province_id (ID-based FK)
     */
    private function generateAgencyID($province_id): int {
        // Find agency with matching province_id (ID-based FK)
        $agency_id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}app_agencies WHERE province_id = %d LIMIT 1",
            $province_id
        ));

        if (!$agency_id) {
            throw new \Exception("Agency not found for province ID: {$province_id}");
        }

        return (int) $agency_id;
    }

    /**
     * Generate division_id by regency_id (ID-based FK)
     */
    private function generateDivisionID($regency_id): ?int {
        // Find division with matching regency_id (ID-based FK)
        $division_id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}app_agency_divisions WHERE regency_id = %d LIMIT 1",
            $regency_id
        ));

        if ($division_id) {
            return (int) $division_id;
        }

        // Fallback: find any division from the same province (ID-based FK)
        $province_id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT province_id FROM {$this->wpdb->prefix}wi_regencies WHERE id = %d",
            $regency_id
        ));

        if ($province_id) {
            // Find agency by province_id (ID-based FK)
            $agency_id = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->wpdb->prefix}app_agencies WHERE province_id = %d LIMIT 1",
                $province_id
            ));

            if ($agency_id) {
                $division_id = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT id FROM {$this->wpdb->prefix}app_agency_divisions WHERE agency_id = %d LIMIT 1",
                    $agency_id
                ));

                return $division_id ? (int) $division_id : null;
            }
        }

        return null;
    }

    /**
     * Generate inspector_id from agency employees with role 'pengawas' in the same province
     * Ensures unique assignment within the same agency
     * Excludes users with 'admin_dinas' role
     */
    private function generateInspectorID($province_id): ?int {
        $agency_id = $this->generateAgencyID($province_id);

        // Initialize used inspectors for this agency if not set
        if (!isset($this->used_inspectors[$agency_id])) {
            $this->used_inspectors[$agency_id] = [];
        }

        // Get all pengawas employees from this agency
        // Roles: agency_pengawas, agency_pengawas_spesialis
        // IMPORTANT: SELECT ae.id (employee id), not ae.user_id, because FK is to wp_app_agency_employees(id)
        $pengawas_ids = $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT ae.id FROM {$this->wpdb->prefix}app_agency_employees ae
             JOIN {$this->wpdb->prefix}usermeta um ON ae.user_id = um.user_id
             WHERE ae.agency_id = %d
             AND ae.status = 'active'
             AND um.meta_key = %s
             AND (um.meta_value LIKE %s OR um.meta_value LIKE %s)",
            $agency_id,
            $this->wpdb->prefix . 'capabilities',
            '%"agency_pengawas"%',
            '%"agency_pengawas_spesialis"%'
        ));

        $this->debug("Pengawas employee IDs found for agency {$agency_id}: " . implode(', ', $pengawas_ids));

        // Find unused pengawas
        $available_pengawas = array_diff($pengawas_ids, $this->used_inspectors[$agency_id]);

        if (!empty($available_pengawas)) {
            // Pick the first available
            $inspector_employee_id = reset($available_pengawas);
            // Mark as used
            $this->used_inspectors[$agency_id][] = $inspector_employee_id;
            return (int) $inspector_employee_id;
        }

        // No available pengawas, return null
        return null;
    }

    /**
     * Get random province ID that has an agency, excluding a specific province
     */
    private function getRandomProvinceWithAgencyExcept(int $excluded_id): int {
        // Get all provinces that have agencies, excluding the specified one (ID-based FK)
        $provinces_with_agency = $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT DISTINCT p.id FROM {$this->wpdb->prefix}wi_provinces p
             INNER JOIN {$this->wpdb->prefix}app_agencies a ON p.id = a.province_id
             WHERE p.id != %d",
            $excluded_id
        ));

        if (empty($provinces_with_agency)) {
            // Fallback to any province with agency if no other options
            return $this->getRandomProvinceWithAgency();
        }

        return (int) $provinces_with_agency[array_rand($provinces_with_agency)];
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
     * Get a random division that has jurisdictions
     */
    private function getRandomDivisionWithJurisdictions(): ?array {
        // Get divisions that have jurisdictions
        $divisions = $this->wpdb->get_results(
            "SELECT DISTINCT d.id, d.agency_id, a.province_id
             FROM {$this->wpdb->prefix}app_agency_divisions d
             INNER JOIN {$this->wpdb->prefix}app_agencies a ON d.agency_id = a.id
             INNER JOIN {$this->wpdb->prefix}app_agency_jurisdictions j ON d.id = j.division_id"
        );

        if (empty($divisions)) {
            return null;
        }

        $division = $divisions[array_rand($divisions)];
        return [
            'id' => (int) $division->id,
            'agency_id' => (int) $division->agency_id,
            'province_id' => (int) $division->province_id
        ];
    }

    /**
     * Get a random regency from a division's jurisdictions (ID-based FK)
     */
    private function getRandomRegencyFromDivisionJurisdictions(int $division_id): ?int {
        $regency_ids = $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT r.id FROM {$this->wpdb->prefix}wi_regencies r
             INNER JOIN {$this->wpdb->prefix}app_agency_jurisdictions j ON r.id = j.jurisdiction_regency_id
             WHERE j.division_id = %d",
            $division_id
        ));

        if (empty($regency_ids)) {
            return null;
        }

        return (int) $regency_ids[array_rand($regency_ids)];
    }

    /**
     * Get regency name by ID
     */
    private function getRegencyName(int $regency_id): string {
        $regency_name = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT name FROM {$this->wpdb->prefix}wi_regencies WHERE id = %d",
            $regency_id
        ));

        return $regency_name ?: 'Unknown Regency';
    }

    /**
     * Generate address string
     */
    private function generateAddress(string $regency_name): string {
        $street_names = ['Jl. Sudirman', 'Jl. Thamrin', 'Jl. Gatot Subroto', 'Jl. Ahmad Yani', 'Jl. Diponegoro'];
        $street_number = rand(1, 999);

        return sprintf('%s No. %d, %s',
            $street_names[array_rand($street_names)],
            $street_number,
            $regency_name
        );
    }

    /**
     * Validate location data
     */
    private function validateLocation(?int $province_id, ?int $regency_id): bool {
        if (!$province_id || !$regency_id) {
            return false;
        }

        $regency = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT r.*, p.name as province_name
             FROM {$this->wpdb->prefix}wi_regencies r
             JOIN {$this->wpdb->prefix}wi_provinces p ON r.province_id = p.id
             WHERE r.id = %d AND r.province_id = %d",
            $regency_id, $province_id
        ));

        return $regency !== null;
    }

    /**
     * Get random regency ID from a province
     */
    private function getRandomRegencyId(int $province_id): int {
        $regency_ids = $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}wi_regencies WHERE province_id = %d",
            $province_id
        ));

        if (empty($regency_ids)) {
            throw new \Exception("No regencies found for province ID: {$province_id}");
        }

        return (int) $regency_ids[array_rand($regency_ids)];
    }
}

