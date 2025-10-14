<?php
/**
 * Customer Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/Data/CustomerDemoData.php
 * 
 * Description: Generate customer demo data dengan:
 *              - Data perusahaan dengan format yang valid
 *              - Integrasi dengan WordPress user
 *              - Data wilayah dari Provinces/Regencies
 *              - Validasi dan tracking data unik
 */

namespace WPCustomer\Database\Demo;

use WPCustomer\Database\Demo\Data\CustomerUsersData;
use WPCustomer\Database\Demo\Data\BranchUsersData;
use WPCustomer\Controllers\CustomerController;

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
    private $customerController;

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
        $this->customerController = new CustomerController();
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

    protected function generate(): void {
        if (!$this->isDevelopmentMode()) {
            $this->debug('Cannot generate data - not in development mode');
            return;
        }

        // Inisialisasi WPUserGenerator dan simpan reference ke static data
        $userGenerator = new WPUserGenerator();

        // Clean up existing demo users if shouldClearData is enabled
        if ($this->shouldClearData()) {
            error_log("[CustomerDemoData] === Cleanup mode enabled - Deleting existing demo users ===");

            // Get all user IDs from CustomerUsersData
            $user_ids_to_delete = array_column($this->customer_users, 'id');
            error_log("[CustomerDemoData] User IDs to clean: " . json_encode($user_ids_to_delete));

            $deleted = $userGenerator->deleteUsers($user_ids_to_delete);
            error_log("[CustomerDemoData] Cleaned up {$deleted} existing demo users");
            $this->debug("Cleaned up {$deleted} existing demo users before generation");
        }

        foreach (self::$customers as $customer) {
            try {
                // 1. Cek existing customer
                $existing_customer = $this->wpdb->get_row(
                    $this->wpdb->prepare(
                        "SELECT c.* FROM {$this->wpdb->prefix}app_customers c 
                         INNER JOIN {$this->wpdb->users} u ON c.user_id = u.ID 
                         WHERE c.id = %d",
                        $customer['id']
                    )
                );

                if ($existing_customer) {
                    if ($this->shouldClearData()) {
                        // Delete existing customer if shouldClearData is true
                        $this->wpdb->delete(
                            $this->wpdb->prefix . 'app_customers',
                            ['id' => $customer['id']],
                            ['%d']
                        );
                        $this->debug("Deleted existing customer with ID: {$customer['id']}");
                    } else {
                        $this->debug("Customer exists with ID: {$customer['id']}, skipping...");
                        continue;
                    }
                }

                // 2. Cek dan buat WP User jika belum ada
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

                if ($this->shouldClearData()) {
                    // Delete existing customer if user WP not  exists
                    $this->wpdb->delete(
                        $this->wpdb->prefix . 'app_customers',
                        ['id' => $customer['id']],
                        ['%d']
                    );
                    
                    $this->debug("Deleted existing customer with ID: {$customer['id']}");
                }

                // Prepare customer data according to schema
                $customer_data = [
                    'id' => $customer['id'],
                    'code' => $this->customerModel->generateCustomerCode(),
                    'name' => $customer['name'],
                    'npwp' => $this->generateNPWP(),
                    'nib' => $this->generateNIB(),
                    'status' => 'active',
                    'provinsi_id' => $provinsi_id ?: null,
                    'regency_id' => $regency_id ?: null,
                    'user_id' => $user_id,
                    'created_by' => 1,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ];

                error_log("[CustomerDemoData] Creating customer with data: " . json_encode([
                    'id' => $customer_data['id'],
                    'name' => $customer_data['name'],
                    'user_id' => $customer_data['user_id'],
                    'provinsi_id' => $customer_data['provinsi_id'],
                    'regency_id' => $customer_data['regency_id']
                ]));

                // Use createDemoCustomer instead of create
                if (!$this->customerController->createDemoCustomer($customer_data)) {
                    error_log("[CustomerDemoData] ERROR: Failed to create customer with fixed ID");
                    throw new \Exception("Failed to create customer with fixed ID");
                }

                error_log("[CustomerDemoData] Successfully created customer ID {$customer['id']}");

                // Track customer ID
                self::$customer_ids[] = $customer['id'];

                $this->debug("Created customer: {$customer['name']} with fixed ID: {$customer['id']} and WP User ID: {$user_id}");

            } catch (\Exception $e) {
                $this->debug("Error processing customer {$customer['name']}: " . $e->getMessage());
                throw $e;
            }
        }

        // Add cache handling after bulk generation
        foreach (self::$customer_ids as $customer_id) {
            $this->cache->invalidateCustomerCache($customer_id);
            $this->cache->delete('customer_total_count', get_current_user_id());
            $this->cache->invalidateDataTableCache('customer_list');
        }

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
     * Get array of generated user IDs
     */
    public function getUserIds(): array {
        return self::$user_ids;
    }
}
