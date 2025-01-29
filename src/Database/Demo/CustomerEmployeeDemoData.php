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
use WPCustomer\Models\Employee\CustomerEmployeeModel;

defined('ABSPATH') || exit;

class CustomerEmployeeDemoData extends AbstractDemoData {
    private $employeeModel;
    private $wpUserGenerator;
    private static $employee_users;

    public function __construct() {
        parent::__construct();
        $this->employeeModel = new CustomerEmployeeModel();
        $this->wpUserGenerator = new WPUserGenerator();
        self::$employee_users = CustomerEmployeeUsersData::$data;
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
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}app_branches"
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
        $this->debug('Starting employee data generation');

        try {
            // Clear existing data if in development mode
            if ($this->shouldClearData()) {
                $this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_customer_employees");
                $this->debug('Cleared existing employee data');
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
		        "SELECT * FROM {$this->wpdb->prefix}app_branches 
		         WHERE customer_id = %d AND type = 'pusat'",
		        $customer->id
		    ));

		    if (!$pusat_branch) continue;

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
		
        // 2. Branch admins (ID 12-41)
        for ($id = 12; $id <= 41; $id++) {
            $branch = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}app_branches WHERE user_id = %d",
                $id
            ));

            if (!$branch) continue;

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

            // Create employee record with department assignments
            $this->createEmployeeRecord(
                $user_data['customer_id'],
                $user_data['branch_id'],
                $user_id,
                $user_data['departments']
            );
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

            $result = $this->wpdb->insert(
                $this->wpdb->prefix . 'app_customer_employees',
                $employee_data
            );

            if ($result === false) {
                throw new \Exception($this->wpdb->last_error);
            }

            $this->debug("Created employee record for: {$wp_user->display_name}");

        } catch (\Exception $e) {
            $this->debug("Error creating employee record: " . $e->getMessage());
            throw $e;
        }
    }

    private function generatePhone(): string {
        return sprintf('08%d', rand(100000000, 999999999));
    }
}

