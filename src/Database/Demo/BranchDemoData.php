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
 * - app_branches
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

defined('ABSPATH') || exit;

class BranchDemoData extends AbstractDemoData {
    use CustomerDemoDataHelperTrait;

    private $branch_ids = [];
    private $used_nitku = [];
    private $used_emails = [];
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

        $this->branch_users = WPUserGenerator::$branch_users;    }

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
            $this->branch_users = WPUserGenerator::$branch_users;  // Perubahan di sini
            if (empty($this->branch_users)) {
                throw new \Exception('Branch admin users not found');
            }

            // Validasi tabel provinces & regencies
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

            return true;

        } catch (\Exception $e) {
            $this->debug('Validation failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function generate(): void {
        if (!$this->isDevelopmentMode()) {
            $this->debug('Cannot generate data - not in development mode');
            throw new \Exception('Development mode is not enabled. Please enable it in settings first.');
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
                    "SELECT * FROM {$this->wpdb->prefix}app_branches 
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
                    "SELECT COUNT(*) FROM {$this->wpdb->prefix}app_branches 
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

            if ($generated_count === 0) {
                $this->debug('No new branches were generated - all branches already exist');
            } else {
                // Reset auto increment only if we added new data
                $this->wpdb->query(
                    "ALTER TABLE {$this->wpdb->prefix}app_branches AUTO_INCREMENT = " . 
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

        $regency_name = $this->getRegencyName($customer->regency_id);
        
        $branch_data = [
            'customer_id' => $customer->id,
            'name' => sprintf('%s Kantor Pusat', $customer->name),
            'type' => 'pusat',
            'nitku' => $this->generateNITKU(),
            'postal_code' => $this->generatePostalCode(),
            'latitude' => $this->generateLatitude(),
            'longitude' => $this->generateLongitude(),
            'address' => $this->generateAddress($regency_name),
            'phone' => $this->generatePhone(),
            'email' => $this->generateEmail($customer->name, 'pusat'),
            'provinsi_id' => $customer->provinsi_id,
            'regency_id' => $customer->regency_id,
            'user_id' => $branch_user_id,                  // Branch admin user
            'created_by' => $customer->user_id,            // Customer owner user
            'status' => 'active'
        ];

        $branch_id = $this->branchModel->create($branch_data);
        if (!$branch_id) {
            throw new \Exception("Failed to create pusat branch for customer: {$customer->id}");
        }

        $this->branch_ids[] = $branch_id;
        $this->debug("Created pusat branch for customer {$customer->name}");
    }

    /**
     * Generate cabang branches
     */
    private function generateCabangBranches($customer): void {
        // Generate 1-2 cabang per customer
        $cabang_count = rand(1, 2);
        $used_provinces = [$customer->provinsi_id];
        
        for ($i = 0; $i < $cabang_count; $i++) {
            // Get cabang admin user ID
            $cabang_key = 'cabang' . ($i + 1);
            if (!isset($this->branch_users[$customer->id][$cabang_key])) {
                $this->debug("No admin user found for {$cabang_key} of customer {$customer->id}, skipping...");
                continue;
            }
            $cabang_user = $this->branch_users[$customer->id][$cabang_key];
            
            // Get random province (different from used provinces)
            $provinsi_id = $this->getRandomProvinceExcept($customer->provinsi_id);
            while (in_array($provinsi_id, $used_provinces)) {
                $provinsi_id = $this->getRandomProvinceExcept($customer->provinsi_id);
            }
            $used_provinces[] = $provinsi_id;
            
            // Get random regency from selected province
            $regency_id = $this->getRandomRegencyId($provinsi_id);
            $regency_name = $this->getRegencyName($regency_id);

            $branch_data = [
                'customer_id' => $customer->id,
                'name' => sprintf('%s Cabang %s', 
                                $customer->name, 
                                $regency_name),
                'type' => 'cabang',
                'nitku' => $this->generateNITKU(),
                'postal_code' => $this->generatePostalCode(),
                'latitude' => $this->generateLatitude(),
                'longitude' => $this->generateLongitude(),
                'address' => $this->generateAddress($regency_name),
                'phone' => $this->generatePhone(),
                'email' => $this->generateEmail($customer->name, $cabang_key),
                'provinsi_id' => $provinsi_id,
                'regency_id' => $regency_id,
                'user_id' => $cabang_user['id'],           // Branch admin user
                'created_by' => $customer->user_id,        // Customer owner user
                'status' => 'active'
            ];

            $branch_id = $this->branchModel->create($branch_data);
            if (!$branch_id) {
                throw new \Exception("Failed to create cabang branch for customer: {$customer->id}");
            }

            $this->branch_ids[] = $branch_id;
            $this->debug("Created cabang branch for customer {$customer->name} in {$regency_name}");
        }
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

    private function generateLatitude(): float {
        return rand(-6000000, -5000000) / 100000;
    }

    private function generateLongitude(): float {
        return rand(106000000, 107000000) / 100000;
    }

    private function generatePhone(): string {
        return sprintf('%s%s', 
            rand(0, 1) ? '021-' : '022-',
            rand(1000000, 9999999)
        );
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
}
