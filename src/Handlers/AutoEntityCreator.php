<?php
/**
 * Auto Entity Creator Handler
 *
 * @package     WP_Customer
 * @subpackage  Handlers
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Handlers/AutoEntityCreator.php
 *
 * Description: Handler untuk auto-create entity terkait via hooks.
 *              - Hook wp_customer_created: Auto-create branch pusat
 *              - Hook wp_customer_branch_created: Auto-create employee
 *              Menjamin konsistensi user_id antara customer, branch, dan employee.
 *
 * Hooks:
 * - wp_customer_created(customer_id, data)
 * - wp_customer_branch_created(branch_id, data)
 *
 * Changelog:
 * 1.0.0 - 2025-01-20 (Task-2165)
 * - Initial implementation
 * - Added handleCustomerCreated() for auto branch pusat creation
 * - Added handleBranchCreated() for auto employee creation
 * - Added validation and duplicate prevention
 */

namespace WPCustomer\Handlers;

use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Models\Employee\CustomerEmployeeModel;

defined('ABSPATH') || exit;

class AutoEntityCreator {

    private CustomerModel $customerModel;
    private BranchModel $branchModel;
    private CustomerEmployeeModel $employeeModel;

    public function __construct() {
        $this->customerModel = new CustomerModel();
        $this->branchModel = new BranchModel();
        $this->employeeModel = new CustomerEmployeeModel();
    }

    /**
     * Handle customer created hook
     * Auto-create branch pusat for new customer
     *
     * @param int $customer_id Customer ID
     * @param array $customer_data Customer data from insert
     * @return void
     */
    public function handleCustomerCreated(int $customer_id, array $customer_data): void {
        try {
            // Validasi: Customer harus punya user_id
            if (empty($customer_data['user_id'])) {
                $this->log("Skip auto-create branch pusat for customer {$customer_id}: no user_id");
                return;
            }

            // Validasi: Customer harus punya provinsi_id dan regency_id
            if (empty($customer_data['provinsi_id']) || empty($customer_data['regency_id'])) {
                $this->log("Skip auto-create branch pusat for customer {$customer_id}: no provinsi_id or regency_id");
                return;
            }

            // Cek apakah branch pusat sudah ada
            $existing_pusat = $this->branchModel->findPusatByCustomer($customer_id);
            if ($existing_pusat) {
                $this->log("Skip auto-create branch pusat for customer {$customer_id}: pusat branch already exists (ID: {$existing_pusat->id})");
                return;
            }

            // Get agency_id and division_id from location
            $location_ids = $this->branchModel->getAgencyAndDivisionIds(
                $customer_data['provinsi_id'],
                $customer_data['regency_id']
            );

            // Get inspector_id from division (with fallback to province's agency)
            $inspector_id = $this->branchModel->getInspectorId(
                $customer_data['provinsi_id'],
                $location_ids['division_id']
            );

            // Get regency name for branch name
            global $wpdb;
            $regency = $wpdb->get_row($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}wi_regencies WHERE id = %d",
                $customer_data['regency_id']
            ));

            $regency_name = $regency ? $regency->name : 'Pusat';

            // Prepare branch data
            $branch_data = [
                'customer_id' => $customer_id,
                'name' => sprintf('%s Cabang %s', $customer_data['name'], $regency_name),
                'type' => 'pusat',
                'user_id' => $customer_data['user_id'],
                'provinsi_id' => $customer_data['provinsi_id'],
                'regency_id' => $customer_data['regency_id'],
                'agency_id' => $location_ids['agency_id'],
                'division_id' => $location_ids['division_id'],
                'inspector_id' => $inspector_id,
                'nitku' => null,
                'postal_code' => null,
                'latitude' => null,
                'longitude' => null,
                'address' => null,
                'phone' => null,
                'email' => null,
                'created_by' => $customer_data['user_id'],
                'status' => 'active'
            ];

            // Create branch
            $branch_id = $this->branchModel->create($branch_data);

            if ($branch_id) {
                $this->log("Auto-created branch pusat (ID: {$branch_id}) for customer {$customer_id}");
                // Note: Cache invalidation handled by BranchModel::create()
            } else {
                $this->log("Failed to auto-create branch pusat for customer {$customer_id}", 'error');
            }

        } catch (\Exception $e) {
            $this->log("Error auto-creating branch pusat for customer {$customer_id}: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Handle branch created hook
     * Auto-create employee for new branch
     *
     * @param int $branch_id Branch ID
     * @param array $branch_data Branch data from insert
     * @return void
     */
    public function handleBranchCreated(int $branch_id, array $branch_data): void {
        try {
            // Validasi: Branch harus punya user_id
            if (empty($branch_data['user_id'])) {
                $this->log("Skip auto-create employee for branch {$branch_id}: no user_id");
                return;
            }

            // Get branch data from database
            $branch = $this->branchModel->find($branch_id);
            if (!$branch) {
                $this->log("Skip auto-create employee for branch {$branch_id}: branch not found", 'error');
                return;
            }

            // Cek apakah employee sudah ada dengan kombinasi customer_id + branch_id + user_id
            global $wpdb;
            $existing_employee = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_employees
                 WHERE customer_id = %d AND branch_id = %d AND user_id = %d",
                $branch->customer_id,
                $branch_id,
                $branch_data['user_id']
            ));

            if ($existing_employee > 0) {
                $this->log("Skip auto-create employee for branch {$branch_id}: employee already exists for user {$branch_data['user_id']}");
                return;
            }

            // Get user data from WordPress
            $user = get_userdata($branch_data['user_id']);
            if (!$user) {
                $this->log("Skip auto-create employee for branch {$branch_id}: user {$branch_data['user_id']} not found", 'error');
                return;
            }

            // Determine position based on branch type and user role
            $customer = $this->customerModel->find($branch->customer_id);
            $position = 'Branch Manager';

            // If user is customer owner (user_id matches customer user_id)
            if ($customer && $customer->user_id == $branch_data['user_id']) {
                $position = 'Admin';
            }

            // Prepare employee data
            $employee_data = [
                'customer_id' => $branch->customer_id,
                'branch_id' => $branch_id,
                'user_id' => $branch_data['user_id'],
                'name' => $user->display_name,
                'position' => $position,
                'finance' => 1,
                'operation' => 1,
                'legal' => 1,
                'purchase' => 1,
                'keterangan' => 'Auto-created from branch',
                'email' => $user->user_email,
                'phone' => '-',
                'created_by' => $branch_data['created_by'] ?? $branch_data['user_id'],
                'status' => 'active'
            ];

            // Create employee
            $employee_id = $this->employeeModel->create($employee_data);

            if ($employee_id) {
                $this->log("Auto-created employee (ID: {$employee_id}) for branch {$branch_id}, user {$branch_data['user_id']}");

                // Invalidate statistics cache (simple approach - flush cache group)
                // This will invalidate all customer stats cache
                wp_cache_flush_group('wp_customer');
            } else {
                $this->log("Failed to auto-create employee for branch {$branch_id}", 'error');
            }

        } catch (\Exception $e) {
            $this->log("Error auto-creating employee for branch {$branch_id}: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Log debug messages
     *
     * @param string $message Log message
     * @param string $level Log level (info|error)
     * @return void
     */
    private function log(string $message, string $level = 'info'): void {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $prefix = '[AutoEntityCreator]';
        $formatted_message = sprintf('%s %s', $prefix, $message);

        if ($level === 'error') {
            error_log('ERROR: ' . $formatted_message);
        } else {
            error_log($formatted_message);
        }
    }
}
