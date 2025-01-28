<?php
/**
 * Customer Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.0.0
 * @author      arisciwek
 *
 * Description: Generate customer demo data dengan:
 *              - Data perusahaan dengan format yang valid
 *              - Integrasi dengan WordPress user
 *              - Data wilayah dari Provinces/Regencies
 *              - Validasi dan tracking data unik
 */

namespace WPCustomer\Database\Demo;

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

    // Data statis customer
    private static $customers = [
        ['id' => 1, 'name' => 'PT Maju Bersama','provinsi_id' => '16', 'regency_id' => '34'],
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
        $this->customer_users = WPUserGenerator::$customer_users;
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

            // Get customer users mapping from WPUserGenerator
            $this->customer_users = WPUserGenerator::$customer_users;  // Tambah ini
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

        foreach (self::$customers as $customer) {
            try {
                // 1. Cek existing customer dan user_id nya
                $existing_customer = $this->wpdb->get_row(
                    $this->wpdb->prepare(
                        "SELECT * FROM {$this->wpdb->prefix}app_customers WHERE id = %d",
                        $customer['id']
                    )
                );

                // Ambil data user yang seharusnya dari WPUserGenerator
                $expected_user_data = $this->customer_users[$customer['id'] - 1];
                $expected_user_id = $expected_user_data['id'];

                if ($existing_customer) {
                    // Cek apakah user_id sama dengan yang diharapkan
                    if ($existing_customer->user_id == $expected_user_id) {
                        $this->debug("Customer exists with ID: {$customer['id']} and correct user_id, skipping...");
                        continue;
                    } else {
                        $this->debug("Customer exists with ID: {$customer['id']} but different user_id. Expected: {$expected_user_id}, Found: {$existing_customer->user_id}. Regenerating...");
                        
                        // Hapus data customer yang lama
                        $this->wpdb->delete(
                            $this->wpdb->prefix . 'app_customers',
                            ['id' => $customer['id']],
                            ['%d']
                        );
                    }
                }

                // 2. Generate atau regenerate user
                $user_id = $userGenerator->generateUser([
                    'id' => $expected_user_data['id'],
                    'username' => $expected_user_data['username'],
                    'display_name' => $expected_user_data['display_name'],
                    'role' => 'customer'
                ]);

                if (!$user_id) {
                    throw new \Exception("Failed to create WordPress user for customer: {$customer['name']}");
                }

                // Store user_id untuk referensi
                self::$user_ids[$customer['id']] = $user_id;

                // 3. Generate customer data baru
                $provinsi_id = isset($customer['provinsi_id']) ? 
                    $customer['provinsi_id'] : 
                    $this->getRandomProvinceId();

                $regency_id = isset($customer['regency_id']) ? 
                    $customer['regency_id'] : 
                    $this->getRandomRegencyId($provinsi_id);

                // Validate location relationship
                if (!$this->validateLocation($provinsi_id, $regency_id)) {
                    throw new \Exception("Invalid province-regency relationship: Province {$provinsi_id}, Regency {$regency_id}");
                }

                $customer_data = [
                    'id' => $customer['id'],
                    'code' => $this->customerModel->generateCustomerCode(),
                    'name' => $customer['name'],
                    'npwp' => $this->generateNPWP(),
                    'nib' => $this->generateNIB(),
                    'provinsi_id' => $provinsi_id,
                    'regency_id' => $regency_id,
                    'created_by' => 1,
                    'user_id' => $user_id,
                    'status' => 'active'
                ];

                // Insert customer baru
                $result = $this->wpdb->insert(
                    $this->wpdb->prefix . 'app_customers',
                    $customer_data
                );

                if ($result === false) {
                    throw new \Exception($this->wpdb->last_error);
                }

                // Track customer ID
                self::$customer_ids[] = $customer['id'];

                $this->debug("Created/Updated customer: {$customer['name']} with ID: {$customer['id']} and WP User ID: {$user_id}");

            } catch (\Exception $e) {
                $this->debug("Error processing customer {$customer['name']}: " . $e->getMessage());
                throw $e;
            }
        }

        // Reset auto_increment
        $this->wpdb->query(
            "ALTER TABLE {$this->wpdb->prefix}app_customers AUTO_INCREMENT = 11"
        );
    }

    /**
     * Get array of generated customer IDs
     */
    public function getCustomerIds(): array {
        return self::$customer_ids;
    }

    /**
     * Get array of generated user IDs
     */
    public function getUserIds(): array {
        return self::$user_ids;
    }
}
