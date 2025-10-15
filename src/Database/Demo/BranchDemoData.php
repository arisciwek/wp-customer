<?php
/**
 * Branch Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.0.0
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
 *   * provinsi_id    : Foreign key ke wi_provinces
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
 * 1.0.0 - 2024-01-27
 * - Initial version
 * - Added integration with wi_provinces and wi_regencies
 * - Added location validation and tracking
 * - Added documentation and usage examples
 */

namespace WPCustomer\Database\Demo;

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

    // Format nama branch
    private static $branches = [
        ['id' => 1, 'name' => '%s Kantor Pusat'],       // Kantor Pusat
        ['id' => 2, 'name' => '%s Cabang %s'],         // Cabang Regional
        ['id' => 3, 'name' => '%s Cabang %s']          // Cabang Area
    ];

    public function __construct() {
        parent::__construct();
        $this->customer_ids = [];
        $this->user_ids = [];
        $this->branch_users = BranchUsersData::$data;
    }

    /**
     * Validasi data sebelum generate
     */
        protected function validate(): bool {
            try {
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
                    $customer = $this->customerModel->find($customer_id);
                    if (!$customer) {
                        throw new \Exception("Customer not found: {$customer_id}");
                    }

                    // Jika customer punya data wilayah, validasi relasinya
                    if ($customer->provinsi_id && $customer->regency_id) {
                        // Cek provinsi ada
                        $province = $this->wpdb->get_row($this->wpdb->prepare("
                            SELECT * FROM {$this->wpdb->prefix}wi_provinces 
                            WHERE id = %d",
                            $customer->provinsi_id
                        ));
                        if (!$province) {
                            throw new \Exception("Invalid province ID for customer {$customer_id}: {$customer->provinsi_id}");
                        }

                        // Cek regency ada dan berelasi dengan provinsi
                        $regency = $this->wpdb->get_row($this->wpdb->prepare("
                            SELECT r.*, p.name as province_name 
                            FROM {$this->wpdb->prefix}wi_regencies r
                            JOIN {$this->wpdb->prefix}wi_provinces p ON r.province_id = p.id
                            WHERE r.id = %d AND r.province_id = %d",
                            $customer->regency_id,
                            $customer->provinsi_id
                        ));
                        if (!$regency) {
                            throw new \Exception("Invalid regency ID {$customer->regency_id} for province {$customer->provinsi_id}");
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
        ini_set('max_execution_time', '300'); // 300 seconds = 5 minutes

        if (!$this->isDevelopmentMode()) {
            $this->debug('Cannot generate data - not in development mode');
            throw new \Exception('Development mode is not enabled. Please enable it in settings first.');
        }

        // Initialize WPUserGenerator for cleanup
        $userGenerator = new WPUserGenerator();

        // Clean up existing demo users if shouldClearData is enabled
        if ($this->shouldClearData()) {
            error_log("[BranchDemoData] === Cleanup mode enabled - Deleting existing demo users ===");

            // Collect all branch admin user IDs from BranchUsersData
            $user_ids_to_delete = [];

            // Regular branch users (pusat + cabang for each customer)
            foreach ($this->branch_users as $customer_id => $branches) {
                if (isset($branches['pusat'])) {
                    $user_ids_to_delete[] = $branches['pusat']['id'];
                }
                if (isset($branches['cabang1'])) {
                    $user_ids_to_delete[] = $branches['cabang1']['id'];
                }
                if (isset($branches['cabang2'])) {
                    $user_ids_to_delete[] = $branches['cabang2']['id'];
                }
            }

            // Extra branch users
            $extra_users = BranchUsersData::$extra_branch_users;
            foreach ($extra_users as $user_data) {
                $user_ids_to_delete[] = $user_data['id'];
            }

            error_log("[BranchDemoData] User IDs to clean: " . json_encode($user_ids_to_delete));

            $deleted = $userGenerator->deleteUsers($user_ids_to_delete);
            error_log("[BranchDemoData] Cleaned up {$deleted} existing demo users");
            $this->debug("Cleaned up {$deleted} existing demo users before generation");

            // Delete existing branches
            $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_customer_branches WHERE id > 0");

            // Reset auto increment
            $this->wpdb->query("ALTER TABLE {$this->wpdb->prefix}app_customer_branches AUTO_INCREMENT = 1");

            $this->debug("Cleared existing branch data");
        }

        // TAMBAHKAN DI SINI
        if (!$this->validate()) {
            throw new \Exception('Pre-generation validation failed');
        }

        $generated_count = 0;

        try {
            // Get all active customers
            foreach ($this->customer_ids as $customer_id) {
                $customer = $this->customerModel->find($customer_id);
                if (!$customer) {
                    $this->debug("Customer not found: {$customer_id}");
                    continue;
                }

                if (!isset($this->branch_users[$customer_id])) {
                    $this->debug("No branch admin users found for customer {$customer_id}, skipping...");
                    continue;
                }

                // Check for existing pusat branch
                $existing_pusat = $this->wpdb->get_row($this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->prefix}app_customer_branches 
                     WHERE customer_id = %d AND type = 'pusat'",
                    $customer_id
                ));

                if ($existing_pusat) {
                    $this->debug("Pusat branch exists for customer {$customer_id}, skipping...");
                } else {
                    // Get pusat admin user ID
                    $pusat_user = $this->branch_users[$customer_id]['pusat'];
                    $this->debug("Using pusat admin user ID: {$pusat_user['id']} for customer {$customer_id}");
                    $this->generatePusatBranch($customer, $pusat_user['id']);
                    $generated_count++;
                }

                // Check for existing cabang branches
                $existing_cabang_count = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->wpdb->prefix}app_customer_branches 
                     WHERE customer_id = %d AND type = 'cabang'",
                    $customer_id
                ));

                if ($existing_cabang_count > 0) {
                    $this->debug("Cabang branches exist for customer {$customer_id}, skipping...");
                } else {
                    $this->generateCabangBranches($customer);
                    $generated_count++;
                }
            }

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
        if (!$this->validateLocation($customer->provinsi_id, $customer->regency_id)) {
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

        $agency_id = $this->generateAgencyID($customer->provinsi_id);
        $division_id = $this->generateDivisionID($customer->regency_id);
        $inspector_id = $this->generateInspectorID($customer->provinsi_id);

        $this->debug("Generated for pusat branch - agency_id: {$agency_id}, division_id: {$division_id}, inspector_id: {$inspector_id} for provinsi_id: {$customer->provinsi_id}, regency_id: {$customer->regency_id}");

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
            'provinsi_id' => $customer->provinsi_id,
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
     * Generate cabang branches
     */
    private function generateCabangBranches($customer): void {
        // Generate 1-2 cabang per customer
        //$cabang_count = rand(1, 2);

        $cabang_count = 2; // Selalu buat 2 cabang karena sudah ada 2 user cabang

        $used_provinces = [$customer->provinsi_id];
        $userGenerator = new WPUserGenerator();
        
        for ($i = 0; $i < $cabang_count; $i++) {
            // Get cabang admin user ID
            $cabang_key = 'cabang' . ($i + 1);
            if (!isset($this->branch_users[$customer->id][$cabang_key])) {
                $this->debug("No admin user found for {$cabang_key} of customer {$customer->id}, skipping...");
                continue;
            }

            // Generate WordPress user untuk cabang
            $user_data = $this->branch_users[$customer->id][$cabang_key];
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

            // Get random province that has agency (different from used provinces)
            $provinsi_id = $this->getRandomProvinceWithAgencyExcept($customer->provinsi_id);
            while (in_array($provinsi_id, $used_provinces)) {
                $provinsi_id = $this->getRandomProvinceWithAgencyExcept($customer->provinsi_id);
            }
            $used_provinces[] = $provinsi_id;
            
            // Get random regency from selected province
            $regency_id = $this->getRandomRegencyId($provinsi_id);
            $regency_name = $this->getRegencyName($regency_id);
            $location = $this->generateValidLocation();

            $agency_id = $this->generateAgencyID($provinsi_id);
            $division_id = $this->generateDivisionID($regency_id);
            $inspector_id = $this->generateInspectorID($provinsi_id);

            $this->debug("Generated for cabang branch - agency_id: {$agency_id}, division_id: {$division_id}, inspector_id: {$inspector_id} for provinsi_id: {$provinsi_id}, regency_id: {$regency_id}");

            // Generate branch code: customer_code + cabang number + random 2 digits
            $cabang_num = str_replace('cabang', '', $cabang_key);
            $branch_code = $customer->code . ' ' . $cabang_num . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);

            $branch_data = [
                'customer_id' => $customer->id,
                'code' => $branch_code,
                'name' => sprintf('%s Cabang %s',
                                $customer->name,
                                $regency_name),
                'type' => 'cabang',
                'nitku' => $this->generateNITKU(),
                'postal_code' => $this->generatePostalCode(),
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'address' => $this->generateAddress($regency_name),
                'phone' => $this->generatePhone(),
                'email' => $this->generateEmail($customer->name, $cabang_key),
                'provinsi_id' => $provinsi_id,
                'agency_id' => $agency_id,
                'regency_id' => $regency_id,
                'division_id' => $division_id,
                'user_id' => $wp_user_id,  // Gunakan WP user yang baru dibuat
                'inspector_id' => $inspector_id,
                'created_by' => $customer->user_id,        // Customer owner user
                'status' => 'active'
            ];

            $result = $this->wpdb->insert(
                $this->wpdb->prefix . 'app_customer_branches',
                $branch_data,
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s']
            );

            if ($result === false) {
                throw new \Exception("Failed to create cabang branch for customer: {$customer->id} - " . $this->wpdb->last_error);
            }

            $branch_id = $this->wpdb->insert_id;

            $this->branch_ids[] = $branch_id;
            $this->debug("Created cabang branch for customer {$customer->name} in {$regency_name}");
        }
    }

    /**
     * Generate extra branches for testing assign inspector functionality
     * These branches will have inspector_id = NULL so they appear in New Company tab
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

        $userGenerator = new WPUserGenerator();
        $generated_extra = 0;

        // Generate extra branches using predefined users
        foreach ($extra_users as $user_data) {
            // Pick random customer
            $customer = $customers[array_rand($customers)];

            // Generate WP User with predefined data
            $wp_user_id = $userGenerator->generateUser([
                'id' => $user_data['id'],
                'username' => $user_data['username'],
                'display_name' => $user_data['display_name'],
                'role' => 'customer'
            ]);

            if (!$wp_user_id) {
                $this->debug("Failed to create user for extra branch: {$user_data['display_name']}, skipping...");
                continue;
            }

            // Add customer_branch_admin role to user
            $user = get_user_by('ID', $wp_user_id);
            if ($user) {
                $role_exists = get_role('customer_branch_admin');
                if (!$role_exists) {
                    add_role('customer_branch_admin', __('Customer Branch Admin', 'wp-customer'), []);
                }
                $user->add_role('customer_branch_admin');
                $this->debug("Added customer_branch_admin role to extra branch user {$wp_user_id} ({$user_data['display_name']})");
            }

            // Get a random division that has jurisdictions
            $division_data = $this->getRandomDivisionWithJurisdictions();
            if (!$division_data) {
                $this->debug("No division with jurisdictions found, skipping...");
                continue;
            }

            $division_id = $division_data['id'];
            $agency_id = $division_data['agency_id'];
            $provinsi_id = $division_data['provinsi_id'];

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

            // Explicitly set inspector_id to NULL for testing
            $inspector_id = null;

            $this->debug("Generated extra branch - agency_id: {$agency_id}, division_id: {$division_id}, inspector_id: NULL for provinsi_id: {$provinsi_id}, regency_id: {$regency_id}");

            // Generate unique branch code for testing
            $branch_code = $customer->code . ' ' . str_pad($generated_extra + 1, 2, '0', STR_PAD_LEFT);

            $branch_data = [
                'customer_id' => $customer->id,
                'code' => $branch_code,
                'name' => sprintf('%s Cabang %s',
                                  $customer->name,
                                  $regency_name),
                'type' => 'cabang',
                'nitku' => $this->generateNITKU(),
                'postal_code' => $this->generatePostalCode(),
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'address' => $this->generateAddress($regency_name),
                'phone' => $this->generatePhone(),
                'email' => $this->generateEmail($customer->name, 'extra' . ($generated_extra + 1)),
                'provinsi_id' => $provinsi_id,
                'agency_id' => $agency_id,
                'regency_id' => $regency_id,
                'division_id' => $division_id,
                'user_id' => $wp_user_id,
                'inspector_id' => $inspector_id,  // NULL for testing assign inspector
                'created_by' => $customer->user_id,
                'status' => 'active'
            ];

            $result = $this->wpdb->insert(
                $this->wpdb->prefix . 'app_customer_branches',
                $branch_data,
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', null, '%s']  // inspector_id is null
            );

            if ($result === false) {
                $this->debug("Failed to create extra branch: " . $this->wpdb->last_error);
                continue;
            }

            $branch_id = $this->wpdb->insert_id;
            $this->branch_ids[] = $branch_id;
            $generated_extra++;

            $this->debug("Created extra branch {$branch_code} for customer {$customer->name} (inspector_id = NULL)");
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
        $isMobile = rand(0, 1) === 1;
        $prefix = rand(0, 1) ? '+62' : '0';
        
        if ($isMobile) {
            // Mobile format: +62/0 8xx xxxxxxxx
            return $prefix . '8' . rand(1, 9) . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        } else {
            // Landline format: +62/0 xxx xxxxxxx
            $areaCodes = ['21', '22', '24', '31', '711', '61', '411', '911']; // Jakarta, Bandung, Semarang, Surabaya, Palembang, etc
            $areaCode = $areaCodes[array_rand($areaCodes)];
            return $prefix . $areaCode . str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
        }
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
     * Generate agency_id by province_id
     */
    private function generateAgencyID($provinsi_id): int {
        // Get province code from id
        $province_code = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT code FROM {$this->wpdb->prefix}wi_provinces WHERE id = %d",
            $provinsi_id
        ));

        if (!$province_code) {
            throw new \Exception("Province not found for ID: {$provinsi_id}");
        }

        // Find agency with matching provinsi_code
        $agency_id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}app_agencies WHERE provinsi_code = %s LIMIT 1",
            $province_code
        ));

        if (!$agency_id) {
            throw new \Exception("Agency not found for province code: {$province_code}");
        }

        return (int) $agency_id;
    }

    /**
     * Generate division_id by regency_id
     */
    private function generateDivisionID($regency_id): ?int {
        // Get regency code from id
        $regency_code = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT code FROM {$this->wpdb->prefix}wi_regencies WHERE id = %d",
            $regency_id
        ));

        if (!$regency_code) {
            return null;
        }

        // Find division with matching regency_code
        $division_id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}app_agency_divisions WHERE regency_code = %s LIMIT 1",
            $regency_code
        ));

        if ($division_id) {
            return (int) $division_id;
        }

        // Fallback: find any division from the same province
        $province_id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT province_id FROM {$this->wpdb->prefix}wi_regencies WHERE id = %d",
            $regency_id
        ));

        if ($province_id) {
            $province_code = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT code FROM {$this->wpdb->prefix}wi_provinces WHERE id = %d",
                $province_id
            ));

            if ($province_code) {
                $agency_id = $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT id FROM {$this->wpdb->prefix}app_agencies WHERE provinsi_code = %s LIMIT 1",
                    $province_code
                ));

                if ($agency_id) {
                    $division_id = $this->wpdb->get_var($this->wpdb->prepare(
                        "SELECT id FROM {$this->wpdb->prefix}app_agency_divisions WHERE agency_id = %d LIMIT 1",
                        $agency_id
                    ));

                    return $division_id ? (int) $division_id : null;
                }
            }
        }

        return null;
    }

    /**
     * Generate inspector_id from agency employees with role 'pengawas' in the same province
     * Ensures unique assignment within the same agency
     * Excludes users with 'admin_dinas' role
     */
    private function generateInspectorID($provinsi_id): ?int {
        $agency_id = $this->generateAgencyID($provinsi_id);

        // Initialize used inspectors for this agency if not set
        if (!isset($this->used_inspectors[$agency_id])) {
            $this->used_inspectors[$agency_id] = [];
        }

        // Get all pengawas employees from this agency
        // Roles: agency_pengawas, agency_pengawas_spesialis
        $pengawas_ids = $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT ae.user_id FROM {$this->wpdb->prefix}app_agency_employees ae
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

        $this->debug("Pengawas IDs found for agency {$agency_id}: " . implode(', ', $pengawas_ids));

        // Find unused pengawas
        $available_pengawas = array_diff($pengawas_ids, $this->used_inspectors[$agency_id]);

        if (!empty($available_pengawas)) {
            // Pick the first available
            $inspector_user_id = reset($available_pengawas);
            // Mark as used
            $this->used_inspectors[$agency_id][] = $inspector_user_id;
            return (int) $inspector_user_id;
        }

        // No available pengawas, return null
        return null;
    }

    /**
     * Get random province ID that has an agency, excluding a specific province
     */
    private function getRandomProvinceWithAgencyExcept(int $excluded_id): int {
        // Get all provinces that have agencies, excluding the specified one
        $provinces_with_agency = $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT DISTINCT p.id FROM {$this->wpdb->prefix}wi_provinces p
             INNER JOIN {$this->wpdb->prefix}app_agencies a ON p.code = a.provinsi_code
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
        // Get all provinces that have agencies
        $provinces_with_agency = $this->wpdb->get_col(
            "SELECT DISTINCT p.id FROM {$this->wpdb->prefix}wi_provinces p
             INNER JOIN {$this->wpdb->prefix}app_agencies a ON p.code = a.provinsi_code"
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
            "SELECT DISTINCT d.id, d.agency_id, p.id as provinsi_id
             FROM {$this->wpdb->prefix}app_agency_divisions d
             INNER JOIN {$this->wpdb->prefix}app_agencies a ON d.agency_id = a.id
             INNER JOIN {$this->wpdb->prefix}wi_provinces p ON a.provinsi_code = p.code
             INNER JOIN {$this->wpdb->prefix}app_agency_jurisdictions j ON d.id = j.division_id"
        );

        if (empty($divisions)) {
            return null;
        }

        $division = $divisions[array_rand($divisions)];
        return [
            'id' => (int) $division->id,
            'agency_id' => (int) $division->agency_id,
            'provinsi_id' => (int) $division->provinsi_id
        ];
    }

    /**
     * Get a random regency from a division's jurisdictions
     */
    private function getRandomRegencyFromDivisionJurisdictions(int $division_id): ?int {
        $regency_ids = $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT r.id FROM {$this->wpdb->prefix}wi_regencies r
             INNER JOIN {$this->wpdb->prefix}app_agency_jurisdictions j ON r.code = j.jurisdiction_code
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
    private function validateLocation(?int $provinsi_id, ?int $regency_id): bool {
        if (!$provinsi_id || !$regency_id) {
            return false;
        }

        $regency = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT r.*, p.name as province_name
             FROM {$this->wpdb->prefix}wi_regencies r
             JOIN {$this->wpdb->prefix}wi_provinces p ON r.province_id = p.id
             WHERE r.id = %d AND r.province_id = %d",
            $regency_id, $provinsi_id
        ));

        return $regency !== null;
    }

    /**
     * Get random regency ID from a province
     */
    private function getRandomRegencyId(int $provinsi_id): int {
        $regency_ids = $this->wpdb->get_col($this->wpdb->prepare(
            "SELECT id FROM {$this->wpdb->prefix}wi_regencies WHERE province_id = %d",
            $provinsi_id
        ));

        if (empty($regency_ids)) {
            throw new \Exception("No regencies found for province ID: {$provinsi_id}");
        }

        return (int) $regency_ids[array_rand($regency_ids)];
    }
}

