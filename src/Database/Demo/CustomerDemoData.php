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

    // Data statis customer
    private static $customers = [
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
     * Validasi sebelum generate data
     */
    protected function validate(): bool {
        try {
            // Validasi tabel provinces & regencies tersedia
            $provinces_exist = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}wi_provinces'"
            );
            if (!$provinces_exist) {
                throw new \Exception('Tabel provinces tidak ditemukan');
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

        $userGenerator = new WPUserGenerator();

        foreach (self::$customers as $customer) {
            try {
                // 1. Cek existing customer
                $existing_customer = $this->wpdb->get_row(
                    $this->wpdb->prepare(
                        "SELECT * FROM {$this->wpdb->prefix}app_customers WHERE id = %d",
                        $customer['id']
                    )
                );

                if ($existing_customer) {
                    $this->debug("Customer exists with ID: {$customer['id']}, skipping...");
                    continue;
                }

                // 2. Cek WP User
                $wp_user_id = 11 + $customer['id'];
                $existing_user = get_user_by('ID', $wp_user_id);
                $owner_name = $this->generatePersonName();

                if ($existing_user) {
                    if ($existing_user->display_name !== $owner_name) {
                        wp_update_user([
                            'ID' => $wp_user_id,
                            'display_name' => $owner_name
                        ]);
                        $this->debug("Updated user {$wp_user_id} name to: {$owner_name}");
                    }
                } else {
                    $user_id = $userGenerator->generateUser([
                        'id' => $wp_user_id,
                        'display_name' => $owner_name,
                        'role' => 'customer'
                    ]);

                    if (!$user_id) {
                        throw new \Exception("Failed to create WordPress user for customer: {$customer['name']}");
                    }
                }

                // Store user_id untuk referensi
                self::$user_ids[$customer['id']] = $wp_user_id;

                // 3. Generate customer data baru
                $customer_data = [
                    'id' => $customer['id'],
                    'code' => $this->customerModel->generateCustomerCode(),
                    'name' => $customer['name'],
                    'npwp' => $this->generateNPWP(),
                    'nib' => $this->generateNIB(),
                    'provinsi_id' => $this->getRandomProvinceId(),
                    'regency_id' => $this->getRandomRegencyId($provinsi_id),
                    'created_by' => 1,
                    'user_id' => $wp_user_id,
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

                $this->debug("Created customer: {$customer['name']} with ID: {$customer['id']} and WP User ID: {$wp_user_id}");

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
