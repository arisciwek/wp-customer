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

use WPCustomer\Database\Demo\Data\BranchUsersData;
use WPCustomer\Controllers\Branch\BranchController;

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
        $this->branch_users = BranchUsersData::$data;
        $this->branchController = new BranchController();
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
        if (!$this->isDevelopmentMode()) {
            $this->debug('Cannot generate data - not in development mode');
            throw new \Exception('Development mode is not enabled. Please enable it in settings first.');
        }
        
        if ($this->shouldClearData()) {
            // Delete existing branches
            $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_branches WHERE id > 0");
            
            // Reset auto increment
            $this->wpdb->query("ALTER TABLE {$this->wpdb->prefix}app_branches AUTO_INCREMENT = 1");
            
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

        // Generate WordPress user dulu
        $userGenerator = new WPUserGenerator();
        
        // Ambil data user dari branch_users
        $user_data = $this->branch_users[$customer->id]['pusat'];
        
        // Generate WP User
        $wp_user_id = $userGenerator->generateUser([
            'id' => $user_data['id'],
            'username' => $user_data['username'],
            'display_name' => $user_data['display_name'],
            'role' => 'customer'  // atau role khusus untuk branch admin
        ]);

        if (!$wp_user_id) {
            throw new \Exception("Failed to create WordPress user for branch admin: {$user_data['display_name']}");
        }

        $regency_name = $this->getRegencyName($customer->regency_id);
        $location = $this->generateValidLocation();
        
        $branch_data = [
            'customer_id' => $customer->id,
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
            'regency_id' => $customer->regency_id,
            'user_id' => $branch_user_id,                  // Branch admin user
            'created_by' => $customer->user_id,            // Customer owner user
            'status' => 'active'
        ];
    
        $branch_id = $this->branchController->createDemoBranch($branch_data);

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
                'role' => 'customer'  // atau role khusus untuk branch admin
            ]);
            
            if (!$wp_user_id) {
                throw new \Exception("Failed to create WordPress user for branch admin: {$user_data['display_name']}");
            }

            // Get random province (different from used provinces)
            $provinsi_id = $this->getRandomProvinceExcept($customer->provinsi_id);
            while (in_array($provinsi_id, $used_provinces)) {
                $provinsi_id = $this->getRandomProvinceExcept($customer->provinsi_id);
            }
            $used_provinces[] = $provinsi_id;
            
            // Get random regency from selected province
            $regency_id = $this->getRandomRegencyId($provinsi_id);
            $regency_name = $this->getRegencyName($regency_id);
            $location = $this->generateValidLocation();

            $branch_data = [
                'customer_id' => $customer->id,
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
                'regency_id' => $regency_id,
                'user_id' => $wp_user_id,  // Gunakan WP user yang baru dibuat
                'created_by' => $customer->user_id,        // Customer owner user
                'status' => 'active'
            ];

            $branch_id = $this->branchController->createDemoBranch($branch_data);
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


}
