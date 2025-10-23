<?php
/**
 * Company Invoice Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/CompanyInvoiceDemoData.php
 *
 * Description: Generate demo invoice data untuk company invoices.
 *              Creates 1-2 random invoices per active branch with:
 *              - Link to membership and level
 *              - Random status (pending, pending_payment, paid, cancelled)
 *              - Amount based on level price x period
 *              - 12-month discount (10 months payment)
 *              - Payment records for paid invoices
 *
 * Dependencies:
 * - CompanyInvoiceController : Handle invoice operations
 * - CustomerMembershipModel  : Get membership data
 * - MembershipLevelModel     : Get level pricing
 * - BranchModel              : Get branch data
 *
 * Order of operations:
 * 1. Validate prerequisites (development mode, tables, data)
 * 2. Get all active branches with memberships
 * 3. For each branch:
 *    - Generate 1-2 invoices
 *    - Link to membership upgrade if exists
 *    - Calculate amount with discount
 *    - Set random status
 *    - Create payment if paid
 *
 * Changelog:
 * 1.0.0 - 2025-01-10
 * - Initial version
 * - Added invoice generation for branches
 * - Added membership upgrade support
 * - Added payment record creation
 * - Added 12-month discount logic
 */

namespace WPCustomer\Database\Demo;

use WPCustomer\Controllers\Company\CompanyInvoiceController;
use WPCustomer\Models\Membership\CustomerMembershipModel;
use WPCustomer\Models\Membership\MembershipLevelModel;
use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Models\Customer\CustomerModel;

defined('ABSPATH') || exit;

class CompanyInvoiceDemoData extends AbstractDemoData {
    use CustomerDemoDataHelperTrait;

    private $levelModel;
    private $invoiceController;
    protected $branchModel;
    protected $customerModel;
    protected $membershipModel;

    private $invoice_ids = [];
    private $payment_ids = [];
    private $used_invoice_numbers = [];

    public function __construct() {
        parent::__construct();
        $this->levelModel = new MembershipLevelModel();
        $this->branchModel = new BranchModel();
        $this->customerModel = new CustomerModel();
        $this->membershipModel = new CustomerMembershipModel();
        $this->invoiceController = new CompanyInvoiceController();
    }

    /**
     * Validate prerequisites before generation
     */
    protected function validate(): bool {
        try {
            if (!$this->isDevelopmentMode()) {
                throw new \Exception('Development mode is not enabled');
            }

            // Check tables exist
            global $wpdb;
            $tables = [
                'app_customer_invoices',
                'app_customer_payments',
                'app_customer_memberships',
                'app_customer_membership_levels',
                'app_customer_branches'
            ];

            foreach ($tables as $table) {
                if (!$wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'")) {
                    throw new \Exception("Table {$table} not found");
                }
            }

            // Check for existing branches
            $branch_count = $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_branches
                WHERE status = 'active'
            ");
            if ($branch_count == 0) {
                throw new \Exception('No active branches found');
            }

            // Check for existing memberships
            $membership_count = $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}app_customer_memberships
            ");
            if ($membership_count == 0) {
                throw new \Exception('No memberships found. Please generate membership demo data first.');
            }

            return true;

        } catch (\Exception $e) {
            $this->debug('Validation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate invoice demo data
     */
    protected function generate(): void {
        if ($this->shouldClearData()) {
            $this->clearExistingData();
        }

        global $wpdb;
        $current_date = current_time('mysql');

        try {
            // Get all active branches with their memberships and levels
            $branches = $wpdb->get_results("
                SELECT
                    b.*,
                    c.user_id as customer_user_id,
                    m.id as membership_id,
                    m.level_id as current_level_id,
                    m.period_months,
                    m.upgrade_to_level_id,
                    ml.price_per_month
                FROM {$wpdb->prefix}app_customer_branches b
                JOIN {$wpdb->prefix}app_customers c ON b.customer_id = c.id
                JOIN {$wpdb->prefix}app_customer_memberships m ON m.branch_id = b.id
                JOIN {$wpdb->prefix}app_customer_membership_levels ml ON ml.id = m.level_id
                WHERE b.status = 'active'
            ");

            foreach ($branches as $branch) {
                // Random number of invoices: 1 or 2
                $invoice_count = rand(1, 2);

                for ($i = 0; $i < $invoice_count; $i++) {
                    $this->createInvoice($branch, $current_date);
                }
            }

            $this->debug('Invoice generation completed. Total invoices: ' . count($this->invoice_ids) . ', Total payments: ' . count($this->payment_ids));

        } catch (\Exception $e) {
            $this->debug('Error in invoice generation: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create single invoice for branch
     */
    private function createInvoice($branch, $current_date): void {
        global $wpdb;

        // Determine invoice type, from_level, and target level
        $invoice_type = 'other';
        $from_level_id = $branch->current_level_id; // Always start from current level
        $target_level_id = $branch->current_level_id;

        // 30% chance to create upgrade invoice if upgrade_to_level_id exists
        if ($branch->upgrade_to_level_id && rand(1, 100) <= 30) {
            $invoice_type = 'membership_upgrade';
            $target_level_id = $branch->upgrade_to_level_id;
            // from_level_id stays as current_level_id (upgrade scenario)

            // Get target level pricing
            $target_level = $wpdb->get_row($wpdb->prepare("
                SELECT price_per_month FROM {$wpdb->prefix}app_customer_membership_levels
                WHERE id = %d", $target_level_id
            ));
            $price_per_month = $target_level ? $target_level->price_per_month : $branch->price_per_month;
        } else {
            // Regular renewal invoice - same level
            $invoice_type = rand(0, 1) ? 'renewal' : 'other';
            $target_level_id = $branch->current_level_id;
            $from_level_id = $branch->current_level_id; // Same as target for renewal
            $price_per_month = $branch->price_per_month;
        }

        // Random period months
        $period_months = [1, 3, 6, 12][array_rand([1, 3, 6, 12])];

        // Calculate amount with 12-month discount
        if ($period_months == 12) {
            $amount = $price_per_month * 10; // 2 months discount
        } else {
            $amount = $price_per_month * $period_months;
        }

        // Random invoice status (35% pending, 15% pending_payment, 45% paid, 5% cancelled)
        $rand = rand(1, 100);
        if ($rand <= 35) {
            $status = 'pending';
        } elseif ($rand <= 50) {
            $status = 'pending_payment';
        } elseif ($rand <= 95) {
            $status = 'paid';
        } else {
            $status = 'cancelled';
        }

        // Random due date (7-30 days from now)
        $due_days = rand(7, 30);
        $due_date = date('Y-m-d H:i:s', strtotime("+{$due_days} days"));

        // Paid date if status is paid
        $paid_date = null;
        if ($status === 'paid') {
            // Random payment date in last 14 days
            $paid_days_ago = rand(0, 14);
            $paid_date = date('Y-m-d H:i:s', strtotime("-{$paid_days_ago} days"));
        }

        // Generate unique invoice number
        $invoice_number = $this->generateInvoiceNumber();

        // Description
        $description = $this->generateDescription($invoice_type, $period_months);

        // Prepare invoice data
        $invoice_data = [
            'customer_id' => $branch->customer_id,
            'branch_id' => $branch->id,
            'membership_id' => $branch->membership_id,
            'from_level_id' => $from_level_id,
            'level_id' => $target_level_id,
            'invoice_type' => $invoice_type,
            'invoice_number' => $invoice_number,
            'amount' => $amount,
            'period_months' => $period_months,
            'status' => $status,
            'due_date' => $due_date,
            'paid_date' => $paid_date,
            'description' => $description,
            'created_by' => $branch->user_id ?: 1, // Use branch admin user_id, fallback to admin
            'created_at' => $current_date
        ];

        // Insert invoice
        $table_name = $wpdb->prefix . 'app_customer_invoices';
        $inserted = $wpdb->insert($table_name, $invoice_data);

        if (!$inserted) {
            throw new \Exception("Failed to create invoice for branch: {$branch->id}");
        }

        $invoice_id = $wpdb->insert_id;
        $this->invoice_ids[] = $invoice_id;
        $this->debug("Created invoice {$invoice_id} ({$invoice_number}) for branch {$branch->id} - Status: {$status}, Type: {$invoice_type}");

        // Create payment record if paid
        if ($status === 'paid') {
            $this->createPaymentRecord($invoice_id, $invoice_number, $amount, $paid_date, $branch);
        }
    }

    /**
     * Create payment record for paid invoice
     */
    private function createPaymentRecord($invoice_id, $invoice_number, $amount, $paid_date, $branch): void {
        global $wpdb;

        // Random payment method
        $payment_methods = ['transfer_bank', 'virtual_account', 'credit_card', 'cash'];
        $payment_method = $payment_methods[array_rand($payment_methods)];

        // Generate payment ID
        $payment_id = 'PAY-' . date('Ymd', strtotime($paid_date)) . '-' . sprintf('%05d', rand(10000, 99999));

        // Payment metadata
        $metadata = json_encode([
            'invoice_id' => $invoice_id,
            'invoice_number' => $invoice_number,
            'payment_method' => $payment_method,
            'payment_date' => $paid_date
        ]);

        $payment_data = [
            'payment_id' => $payment_id,
            'company_id' => $branch->customer_id,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'description' => "Payment for invoice {$invoice_number}",
            'metadata' => $metadata,
            'status' => 'completed',
            'created_at' => $paid_date,
            'updated_at' => $paid_date
        ];

        $table_name = $wpdb->prefix . 'app_customer_payments';
        $inserted = $wpdb->insert($table_name, $payment_data);

        if ($inserted) {
            $this->payment_ids[] = $wpdb->insert_id;
            $this->debug("Created payment record {$payment_id} for invoice {$invoice_number}");
        }
    }

    /**
     * Generate unique invoice number
     * Format: INV-YYYYMMDD-XXXXX
     */
    private function generateInvoiceNumber(): string {
        do {
            $date_part = date('Ymd');
            $random_part = sprintf('%05d', rand(10000, 99999));
            $invoice_number = "INV-{$date_part}-{$random_part}";
        } while (in_array($invoice_number, $this->used_invoice_numbers));

        $this->used_invoice_numbers[] = $invoice_number;
        return $invoice_number;
    }

    /**
     * Generate invoice description
     */
    private function generateDescription($invoice_type, $period_months): string {
        $descriptions = [
            'membership_upgrade' => [
                "Upgrade membership ke level yang lebih tinggi untuk periode {$period_months} bulan",
                "Pembayaran upgrade paket membership periode {$period_months} bulan",
                "Invoice untuk peningkatan level membership selama {$period_months} bulan"
            ],
            'renewal' => [
                "Perpanjangan membership periode {$period_months} bulan",
                "Pembayaran renewal membership untuk {$period_months} bulan",
                "Invoice perpanjangan paket membership {$period_months} bulan"
            ],
            'other' => [
                "Pembayaran membership periode {$period_months} bulan",
                "Invoice membership untuk periode {$period_months} bulan",
                "Tagihan membership {$period_months} bulan"
            ]
        ];

        $type_descriptions = $descriptions[$invoice_type] ?? $descriptions['other'];
        return $type_descriptions[array_rand($type_descriptions)];
    }

    /**
     * Clear existing invoice and payment data
     */
    private function clearExistingData(): void {
        global $wpdb;

        try {
            // Clear payments first (foreign key dependency)
            $wpdb->query("DELETE FROM {$wpdb->prefix}app_customer_payments WHERE id > 0");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}app_customer_payments AUTO_INCREMENT = 1");

            // Clear invoices
            $wpdb->query("DELETE FROM {$wpdb->prefix}app_customer_invoices WHERE id > 0");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}app_customer_invoices AUTO_INCREMENT = 1");

            $this->debug('Existing invoice and payment data cleared');
        } catch (\Exception $e) {
            $this->debug('Error clearing existing data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get generated invoice IDs
     */
    public function getInvoiceIds(): array {
        return $this->invoice_ids;
    }

    /**
     * Get generated payment IDs
     */
    public function getPaymentIds(): array {
        return $this->payment_ids;
    }
}
