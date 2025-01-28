CustomerEmployeeDemoData.php

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
 *
 * Description: Generate employee demo data dengan:
 *              - User WordPress statis (ID 2-51)
 *              - Mapping customer owner (ID 2-11)
 *              - Mapping branch owner (ID 12-31)
 *              - Staff per department (ID 32-51)
 *              - Data employee terintegrasi dengan branch
 *              - Validasi dan tracking data unik
 *              - Error handling dan logging
 *              
 * Dependencies:
 * - AbstractDemoData                : Base class untuk demo data generation
 * - CustomerModel                   : Get customer data
 * - BranchModel                     : Get branch data per customer
 * - CustomerEmployeeModel           : Insert employee data
 * - WP Database ($wpdb)
 * 
 * Database Design:
 * - app_customer_employees
 *   * id             : Primary key
 *   * customer_id    : Foreign key ke customer
 *   * branch_id      : Foreign key ke branch
 *   * user_id        : Foreign key ke wp_users
 *   * name           : Nama karyawan
 *   * position       : Jabatan
 *   * finance        : Department finance (boolean)
 *   * operation      : Department operation (boolean)
 *   * legal          : Department legal (boolean)
 *   * purchase       : Department purchase (boolean)
 *   * email          : Email karyawan
 *   * phone          : Nomor telepon
 *   * created_by     : Foreign key ke wp_users
 *   * status         : enum('active','inactive')
 *
 * Usage Example:
 * ```php
 * $employeeDemo = new CustomerEmployeeDemoData();
 * $employeeDemo->run();
 * ```
 *
 * Changelog:
 * 1.0.0 - 2024-01-27
 * - Initial version
 * - Added static user data
 * - Added customer-branch integration
 * - Added department distribution
 */

namespace WPCustomer\Database\Demo;

use WPCustomer\Models\Employee\CustomerEmployeeModel;

defined('ABSPATH') || exit;

class CustomerEmployeeDemoData extends AbstractDemoData {
    use CustomerDemoDataHelperTrait;

    // Data statis user
    private static $users = [
        // Owner Customer (10 orang)
        ['id' => 2, 'name' => 'Budi Santoso', 'email' => 'budi.santoso', 'type' => 'customer_owner', 'customer_id' => 1],
        ['id' => 3, 'name' => 'Dewi Kartika', 'email' => 'dewi.kartika', 'type' => 'customer_owner', 'customer_id' => 2],
        ['id' => 4, 'name' => 'Ahmad Hidayat', 'email' => 'ahmad.hidayat', 'type' => 'customer_owner', 'customer_id' => 3],
        ['id' => 5, 'name' => 'Siti Rahayu', 'email' => 'siti.rahayu', 'type' => 'customer_owner', 'customer_id' => 4],
        ['id' => 6, 'name' => 'Rudi Hermawan', 'email' => 'rudi.hermawan', 'type' => 'customer_owner', 'customer_id' => 5],
        ['id' => 7, 'name' => 'Nina Kusuma', 'email' => 'nina.kusuma', 'type' => 'customer_owner', 'customer_id' => 6],
        ['id' => 8, 'name' => 'Eko Prasetyo', 'email' => 'eko.prasetyo', 'type' => 'customer_owner', 'customer_id' => 7],
        ['id' => 9, 'name' => 'Maya Wijaya', 'email' => 'maya.wijaya', 'type' => 'customer_owner', 'customer_id' => 8],
        ['id' => 10, 'name' => 'Dian Pertiwi', 'email' => 'dian.pertiwi', 'type' => 'customer_owner', 'customer_id' => 9],
        ['id' => 11, 'name' => 'Agus Suryanto', 'email' => 'agus.suryanto', 'type' => 'customer_owner', 'customer_id' => 10],

        // Branch Owner (20 orang)
        ['id' => 12, 'name' => 'Hendra Gunawan', 'email' => 'hendra.gunawan', 'type' => 'branch_owner'],
        ['id' => 13, 'name' => 'Sri Wahyuni', 'email' => 'sri.wahyuni', 'type' => 'branch_owner'],
        ['id' => 14, 'name' => 'Bambang Kusuma', 'email' => 'bambang.kusuma', 'type' => 'branch_owner'],
        ['id' => 15, 'name' => 'Linda Safitri', 'email' => 'linda.safitri', 'type' => 'branch_owner'],
        ['id' => 16, 'name' => 'Ari Wibowo', 'email' => 'ari.wibowo', 'type' => 'branch_owner'],
        ['id' => 17, 'name' => 'Ratna Sari', 'email' => 'ratna.sari', 'type' => 'branch_owner'],
        ['id' => 18, 'name' => 'Joko Santoso', 'email' => 'joko.santoso', 'type' => 'branch_owner'],
        ['id' => 19, 'name' => 'Wati Susanti', 'email' => 'wati.susanti', 'type' => 'branch_owner'],
        ['id' => 20, 'name' => 'Dedi Kurniawan', 'email' => 'dedi.kurniawan', 'type' => 'branch_owner'],
        ['id' => 21, 'name' => 'Rina Hartati', 'email' => 'rina.hartati', 'type' => 'branch_owner'],
        ['id' => 22, 'name' => 'Tono Prasetya', 'email' => 'tono.prasetya', 'type' => 'branch_owner'],
        ['id' => 23, 'name' => 'Yuli Astuti', 'email' => 'yuli.astuti', 'type' => 'branch_owner'],
        ['id' => 24, 'name' => 'Firman Saputra', 'email' => 'firman.saputra', 'type' => 'branch_owner'],
        ['id' => 25, 'name' => 'Lia Permata', 'email' => 'lia.permata', 'type' => 'branch_owner'],
        ['id' => 26, 'name' => 'Doni Hermanto', 'email' => 'doni.hermanto', 'type' => 'branch_owner'],
        ['id' => 27, 'name' => 'Sari Indah', 'email' => 'sari.indah', 'type' => 'branch_owner'],
        ['id' => 28, 'name' => 'Wahyu Hidayat', 'email' => 'wahyu.hidayat', 'type' => 'branch_owner'],
        ['id' => 29, 'name' => 'Nia Kurnia', 'email' => 'nia.kurnia', 'type' => 'branch_owner'],
        ['id' => 30, 'name' => 'Reza Firmansyah', 'email' => 'reza.firmansyah', 'type' => 'branch_owner'],
        ['id' => 31, 'name' => 'Dewi Lestari', 'email' => 'dewi.lestari', 'type' => 'branch_owner'],

        // Department Staff (20 orang)
        ['id' => 32, 'name' => 'Andi Susanto', 'email' => 'andi.susanto', 'type' => 'finance'],
        ['id' => 33, 'name' => 'Rina Fitriani', 'email' => 'rina.fitriani', 'type' => 'operation'],
        ['id' => 34, 'name' => 'Bambang Nugroho', 'email' => 'bambang.nugroho', 'type' => 'legal'],
        ['id' => 35, 'name' => 'Yanti Setiawan', 'email' => 'yanti.setiawan', 'type' => 'purchase'],
        ['id' => 36, 'name' => 'Hadi Santoso', 'email' => 'hadi.santoso', 'type' => 'finance'],
        ['id' => 37, 'name' => 'Rita Susanti', 'email' => 'rita.susanti', 'type' => 'operation'],
        ['id' => 38, 'name' => 'Toni Wijaya', 'email' => 'toni.wijaya', 'type' => 'legal'],
        ['id' => 39, 'name' => 'Siska Permata', 'email' => 'siska.permata', 'type' => 'purchase'],
        ['id' => 40, 'name' => 'Denny Hidayat', 'email' => 'denny.hidayat', 'type' => 'finance'],
        ['id' => 41, 'name' => 'Fani Kusumo', 'email' => 'fani.kusumo', 'type' => 'operation'],
        ['id' => 42, 'name' => 'Irfan Hakim', 'email' => 'irfan.hakim', 'type' => 'legal'],
        ['id' => 43, 'name' => 'Laras Wati', 'email' => 'laras.wati', 'type' => 'purchase'],
        ['id' => 44, 'name' => 'Surya Dharma', 'email' => 'surya.dharma', 'type' => 'finance'],
        ['id' => 45, 'name' => 'Putri Indah', 'email' => 'putri.indah', 'type' => 'operation'],
        ['id' => 46, 'name' => 'Rizal Efendi', 'email' => 'rizal.efendi', 'type' => 'legal'],
        ['id' => 47, 'name' => 'Nadia Sari', 'email' => 'nadia.sari', 'type' => 'purchase'],
        ['id' => 48, 'name' => 'Guntur Prakoso', 'email' => 'guntur.prakoso', 'type' => 'finance'],
        ['id' => 49, 'name' => 'Mega Puspita', 'email' => 'mega.puspita', 'type' => 'operation'],
        ['id' => 50, 'name' => 'Fajar Sidiq', 'email' => 'fajar.sidiq', 'type' => 'legal'],
        ['id' => 51, 'name' => 'Indah Pertiwi', 'email' => 'indah.pertiwi', 'type' => 'purchase']
    ];

    private $current_branch_owner_index = 0;
    private $current_staff_index = 0;
    private $used_emails = [];

    /**
     * Validasi sebelum generate data
     */
    protected function validate(): bool {
        try {
            // Validasi WP users exist
            foreach (self::$users as $user) {
                $wp_user = get_user_by('ID', $user['id']);
                if (!$wp_user) {
                    throw new \Exception("WordPress user not found: {$user['id']}");
                }
            }

            // Validasi customer data
            foreach (range(1, 10) as $customer_id) {
                $customer = $this->customerModel->find($customer_id);
                if (!$customer) {
                    throw new \Exception("Customer not found: {$customer_id}");
                }
            }

            return true;

        } catch (\Exception $e) {
            $this->debug('Validation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate employee demo data
     */
    protected function generate(): void {
        // Clear existing data
        $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_customer_employees");
        
        // Generate untuk setiap customer
        foreach (range(1, 10) as $customer_id) {
            $this->debug("Generating employees for customer {$customer_id}");
            
            // Get semua branch untuk customer ini
            $branches = $this->branchModel->getByCustomer($customer_id);
            if (empty($branches)) {
                $this->debug("No branches found for customer {$customer_id}");
                continue;
            }

            // Generate employee untuk setiap branch
            foreach ($branches as $branch) {
                $this->generateBranchEmployees($customer_id, $branch->id);
            }
        }

        $this->debug('Employee generation completed');
    }

    /**
     * Generate employee untuk satu branch
     */
    /*
    private function generateBranchEmployees(int $customer_id, int $branch_id): void {
        // Assign branch owner
        $branch_owner = $this->getNextBranchOwner();
        $this->createEmployee([
            'customer_id' => $customer_id,
            'branch_id' => $branch_id,
            'user_id' => $branch_owner['id'],
            'name' => $branch_owner['name'],
            'position' => 'Branch Manager',
            'email' => $this->generateEmail($branch_owner),
            'phone' => $this->generatePhone(),
            'finance' => false,
            'operation' => false,
            'legal' => false,
            'purchase' => false
        ]);

        // Generate staff untuk setiap department
        $departments = ['finance', 'operation', 'legal', 'purchase'];
        foreach ($departments as $dept) {
            $staff = $this->getNextStaff($dept);
            if (!$staff) {
                $this->debug("No more staff available for department: {$dept}");
                continue;
            }

            $this->createEmployee([
                'customer_id' => $customer_id,
                'branch_id' => $branch_id,
                'user_id' => $staff['id'],
                'name' => $staff['name'],
                'position' => ucf
            ];
		}
	}
	*/
	
	/**
	 * Generate employee untuk satu branch dengan batch insert
	 */
	private function generateBranchEmployees(int $customer_id, int $branch_id): void {
	    $employees_data = [];

	    // Assign branch owner
	    $branch_owner = $this->getNextBranchOwner();
	    $employees_data[] = [
	        'customer_id' => $customer_id,
	        'branch_id' => $branch_id,
	        'user_id' => $branch_owner['id'],
	        'name' => $branch_owner['name'],
	        'position' => 'Branch Manager',
	        'email' => $this->generateEmail($branch_owner),
	        'phone' => $this->generatePhone(),
	        'finance' => false,  // Menambahkan field sesuai dengan yang diperlukan
	        'operation' => false,
	        'legal' => false,
	        'purchase' => false
	    ];

	    // Generate staff untuk setiap department
	    $departments = ['finance', 'operation', 'legal', 'purchase'];
	    foreach ($departments as $dept) {
	        $staff = $this->getNextStaff($dept);
	        if (!$staff) {
	            $this->debug("No more staff available for department: {$dept}");
	            continue;
	        }

	        $employees_data[] = [
	            'customer_id' => $customer_id,
	            'branch_id' => $branch_id,
	            'user_id' => $staff['id'],
	            'name' => $staff['name'],
	            'position' => ucfirst($dept) . ' Staff', // Menambahkan posisi sesuai dengan department
	            'email' => $this->generateEmail($staff),
	            'phone' => $this->generatePhone(),
	            'finance' => $dept === 'finance',
	            'operation' => $dept === 'operation',
	            'legal' => $dept === 'legal',
	            'purchase' => $dept === 'purchase'
	        ];
	    }

	    // Panggil createEmployeesBatch untuk batch insert
	    $this->createEmployeesBatch($employees_data, 10);
	}


	/**
	 * Get branch owner berikutnya dari array users
	 */
	private function getNextBranchOwner(): ?array {
	    // Ambil semua branch owner
	    $branch_owners = array_filter(self::$users, function($user) {
	        return $user['type'] === 'branch_owner';
	    });

	    // Dapatkan branch owner berdasarkan index
	    $branch_owners = array_values($branch_owners);
	    if (!isset($branch_owners[$this->current_branch_owner_index])) {
	        return null;
	    }

	    $owner = $branch_owners[$this->current_branch_owner_index];
	    $this->current_branch_owner_index++;
	    return $owner;
	}

	/**
	 * Get staff berikutnya untuk department tertentu
	 */
	private function getNextStaff(string $department): ?array {
	    // Ambil semua staff untuk department ini
	    $staff = array_filter(self::$users, function($user) use ($department) {
	        return $user['type'] === $department;
	    });

	    // Dapatkan staff berdasarkan index
	    $staff = array_values($staff);
	    if (!isset($staff[$this->current_staff_index])) {
	        return null;
	    }

	    $employee = $staff[$this->current_staff_index];
	    $this->current_staff_index++;
	    return $employee;
	}

	/**
	 * Generate email unik untuk employee
	 */
	private function generateEmail(array $user): string {
	    $domains = ['gmail.com', 'yahoo.com', 'hotmail.com'];
	    
	    do {
	        $email = sprintf('%s@%s',
	            $user['email'],
	            $domains[array_rand($domains)]
	        );
	    } while (in_array($email, $this->used_emails));
	    
	    $this->used_emails[] = $email;
	    return $email;
	}

	/**
	 * Generate nomor telepon
	 */
	private function generatePhone(): string {
	    return sprintf('08%d', rand(100000000, 999999999));
	}

	/**
	 * Bulk create employee records in batches
	 */
	private function createEmployeesBatch(array $employees_data, int $batch_size = 10): void {
	    $counter = 0;

	    foreach ($employees_data as $data) {
	        // Panggil metode createEmployee untuk setiap data karyawan
	        try {
	            $this->createEmployee($data);
	        } catch (\Exception $e) {
	            // Log error jika gagal menambah karyawan
	            $this->debug('Error: ' . $e->getMessage());
	        }

	        $counter++;

	        // Jika sudah mencapai batch size, beri jeda sejenak untuk menghindari time-out
	        if ($counter % $batch_size === 0) {
	            // Tunggu 2 detik sebelum melanjutkan ke batch berikutnya
	            sleep(2);
	        }
	    }

	    $this->debug("Batch insert completed: {$counter} employees added.");
	}

	/**
	 * Create employee record
	 */
	private function createEmployee(array $data): void {
	    // Pastikan data yang dibutuhkan ada di array data
	    $employee_data = [
	        'customer_id' => $data['customer_id'],
	        'branch_id' => $data['branch_id'],
	        'user_id' => $data['user_id'],
	        'name' => $data['name'],
	        'position' => $data['position'],
	        'email' => $data['email'],
	        'phone' => $data['phone'],
	        'finance' => $data['type'] === 'finance',
	        'operation' => $data['type'] === 'operation',
	        'legal' => $data['type'] === 'legal',
	        'purchase' => $data['type'] === 'purchase',
	        'created_by' => 1,
	        'status' => 'active'
	    ];

	    $result = $this->wpdb->insert(
	        $this->wpdb->prefix . 'app_customer_employees',
	        $employee_data
	    );

	    if ($result === false) {
	        throw new \Exception('Failed to create employee: ' . $this->wpdb->last_error);
	    }

	    $this->debug("Created employee: {$data['name']} for branch: {$data['branch_id']}");
	}

}