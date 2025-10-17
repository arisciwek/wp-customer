<?php
/**
 * Company Invoice Validator
 *
 * @package     WP_Customer
 * @subpackage  Validators/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Validators/Company/CompanyInvoiceValidator.php
 *
 * Description: Validator untuk memvalidasi operasi terkait Company invoices
 *              - Validasi invoice creation
 *              - Validasi invoice updates
 *              - Validasi payment marking
 *              - Validasi customer eligibility
 *
 * Dependencies:
 * - CompanyInvoiceModel untuk check data
 * - CustomerModel untuk validasi customer
 */

namespace WPCustomer\Validators\Company;

use WPCustomer\Models\Company\CompanyInvoiceModel;
use WPCustomer\Models\Customer\CustomerModel;
use WPCustomer\Models\Company\CompanyModel;

class CompanyInvoiceValidator {
    private $invoice_model;
    private $customer_model;
    private $company_model;

    public function __construct() {
        $this->invoice_model = new CompanyInvoiceModel();
        $this->customer_model = new CustomerModel();
        $this->company_model = new CompanyModel();
    }

    /**
     * Validate invoice creation data
     *
     * @param array $invoice_data Invoice data to validate
     * @return bool|WP_Error True if valid or WP_Error with reason
     */
    public function validateInvoiceCreation($invoice_data) {
        // Check required fields
        $required_fields = ['customer_id', 'amount', 'due_date'];

        foreach ($required_fields as $field) {
            if (empty($invoice_data[$field])) {
                return new \WP_Error(
                    'missing_field',
                    sprintf(__('Field %s harus diisi', 'wp-customer'), $field)
                );
            }
        }

        // Validate customer exists
        $customer = $this->customer_model->find($invoice_data['customer_id']);
        if (!$customer) {
            return new \WP_Error(
                'invalid_customer',
                __('Customer tidak ditemukan', 'wp-customer')
            );
        }

        // Validate amount
        if (!is_numeric($invoice_data['amount']) || $invoice_data['amount'] <= 0) {
            return new \WP_Error(
                'invalid_amount',
                __('Jumlah invoice harus lebih besar dari 0', 'wp-customer')
            );
        }

        // Validate due date
        if (!$this->isValidDate($invoice_data['due_date'])) {
            return new \WP_Error(
                'invalid_due_date',
                __('Format tanggal jatuh tempo tidak valid', 'wp-customer')
            );
        }

        // Check if due date is in the future
        if (strtotime($invoice_data['due_date']) <= time()) {
            return new \WP_Error(
                'past_due_date',
                __('Tanggal jatuh tempo harus di masa depan', 'wp-customer')
            );
        }

        // Validate branch if provided
        if (!empty($invoice_data['branch_id'])) {
            $branch_validation = $this->validateBranch($invoice_data['branch_id'], $invoice_data['customer_id']);
            if (is_wp_error($branch_validation)) {
                return $branch_validation;
            }
        }

        return true;
    }

    /**
     * Validate invoice update data
     *
     * @param int $invoice_id Invoice ID
     * @param array $update_data Data to update
     * @return bool|WP_Error True if valid or WP_Error with reason
     */
    public function validateInvoiceUpdate($invoice_id, $update_data) {
        // Check if invoice exists
        $invoice = $this->invoice_model->find($invoice_id);
        if (!$invoice) {
            return new \WP_Error(
                'invoice_not_found',
                __('Invoice tidak ditemukan', 'wp-customer')
            );
        }

        // Validate amount if provided
        if (isset($update_data['amount'])) {
            if (!is_numeric($update_data['amount']) || $update_data['amount'] <= 0) {
                return new \WP_Error(
                    'invalid_amount',
                    __('Jumlah invoice harus lebih besar dari 0', 'wp-customer')
                );
            }
        }

        // Validate due date if provided
        if (isset($update_data['due_date'])) {
            if (!$this->isValidDate($update_data['due_date'])) {
                return new \WP_Error(
                    'invalid_due_date',
                    __('Format tanggal jatuh tempo tidak valid', 'wp-customer')
                );
            }

            // Check if due date is in the future (only if invoice is not paid)
            if ($invoice->status !== 'paid' && strtotime($update_data['due_date']) <= time()) {
                return new \WP_Error(
                    'past_due_date',
                    __('Tanggal jatuh tempo harus di masa depan', 'wp-customer')
                );
            }
        }

        // Validate status if provided
        if (isset($update_data['status'])) {
            $allowed_statuses = ['pending', 'paid', 'overdue', 'cancelled'];
            if (!in_array($update_data['status'], $allowed_statuses)) {
                return new \WP_Error(
                    'invalid_status',
                    __('Status invoice tidak valid', 'wp-customer')
                );
            }

            // Additional validation for status changes
            $status_validation = $this->validateStatusChange($invoice, $update_data['status']);
            if (is_wp_error($status_validation)) {
                return $status_validation;
            }
        }

        return true;
    }

    /**
     * Validate marking invoice as paid
     *
     * @param int $invoice_id Invoice ID
     * @param string $payment_date Payment date (optional)
     * @return bool|WP_Error True if valid or WP_Error with reason
     */
    public function validateMarkAsPaid($invoice_id, $payment_date = '') {
        // Check if invoice exists
        $invoice = $this->invoice_model->find($invoice_id);
        if (!$invoice) {
            return new \WP_Error(
                'invoice_not_found',
                __('Invoice tidak ditemukan', 'wp-customer')
            );
        }

        // Check if already paid
        if ($invoice->status === 'paid') {
            return new \WP_Error(
                'already_paid',
                __('Invoice sudah lunas', 'wp-customer')
            );
        }

        // Validate payment date if provided
        if (!empty($payment_date)) {
            if (!$this->isValidDate($payment_date)) {
                return new \WP_Error(
                    'invalid_payment_date',
                    __('Format tanggal pembayaran tidak valid', 'wp-customer')
                );
            }

            // Payment date should not be in the future
            if (strtotime($payment_date) > time()) {
                return new \WP_Error(
                    'future_payment_date',
                    __('Tanggal pembayaran tidak boleh di masa depan', 'wp-customer')
                );
            }
        }

        return true;
    }

    /**
     * Validate invoice deletion
     *
     * @param int $invoice_id Invoice ID
     * @return bool|WP_Error True if valid or WP_Error with reason
     */
    public function validateInvoiceDeletion($invoice_id) {
        // Check if invoice exists
        $invoice = $this->invoice_model->find($invoice_id);
        if (!$invoice) {
            return new \WP_Error(
                'invoice_not_found',
                __('Invoice tidak ditemukan', 'wp-customer')
            );
        }

        // Check if invoice is paid
        if ($invoice->status === 'paid') {
            return new \WP_Error(
                'cannot_delete_paid_invoice',
                __('Invoice yang sudah lunas tidak dapat dihapus', 'wp-customer')
            );
        }

        return true;
    }

    /**
     * Validate customer eligibility for invoice operations
     *
     * @param int $customer_id Customer ID
     * @return bool|WP_Error True if eligible or WP_Error with reason
     */
    public function validateCustomerEligibility($customer_id) {
        // Check if customer exists
        $customer = $this->customer_model->find($customer_id);
        if (!$customer) {
            return new \WP_Error(
                'invalid_customer',
                __('Customer tidak ditemukan', 'wp-customer')
            );
        }

        // Check if customer is active
        if (isset($customer->status) && $customer->status !== 'active') {
            return new \WP_Error(
                'inactive_customer',
                __('Customer tidak aktif', 'wp-customer')
            );
        }

        return true;
    }

    /**
     * Validate branch belongs to customer
     *
     * @param int $branch_id Branch ID
     * @param int $customer_id Customer ID
     * @return bool|WP_Error True if valid or WP_Error with reason
     */
    private function validateBranch($branch_id, $customer_id) {
        $branch = $this->invoice_model->getBranchData($branch_id);
        if (!$branch) {
            return new \WP_Error(
                'invalid_branch',
                __('Branch tidak ditemukan', 'wp-customer')
            );
        }

        if ($branch->customer_id != $customer_id) {
            return new \WP_Error(
                'branch_not_owned',
                __('Branch tidak dimiliki oleh customer ini', 'wp-customer')
            );
        }

        return true;
    }

    /**
     * Validate status change
     *
     * @param object $invoice Current invoice data
     * @param string $new_status New status
     * @return bool|WP_Error True if valid or WP_Error with reason
     */
    private function validateStatusChange($invoice, $new_status) {
        // Cannot change status of cancelled invoice
        if ($invoice->status === 'cancelled' && $new_status !== 'cancelled') {
            return new \WP_Error(
                'cannot_change_cancelled',
                __('Status invoice yang sudah dibatalkan tidak dapat diubah', 'wp-customer')
            );
        }

        // Paid invoices can only be changed to cancelled (for refunds)
        if ($invoice->status === 'paid' && !in_array($new_status, ['paid', 'cancelled'])) {
            return new \WP_Error(
                'cannot_change_paid_status',
                __('Status invoice yang sudah lunas hanya dapat diubah menjadi dibatalkan', 'wp-customer')
            );
        }

        return true;
    }

    /**
     * Check if date string is valid
     *
     * @param string $date Date string
     * @return bool True if valid date
     */
    private function isValidDate($date) {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Validate user access to view invoice list
     *
     * @return bool|WP_Error True if valid or WP_Error with reason
     */
    public function canViewInvoiceList() {
        $user_id = get_current_user_id();

        // Check if user is logged in
        if (!$user_id) {
            return new \WP_Error(
                'not_logged_in',
                __('Anda harus login terlebih dahulu', 'wp-customer')
            );
        }

        // Check basic capability
        if (!current_user_can('view_customer_membership_invoice_list')) {
            return new \WP_Error(
                'no_permission',
                __('Anda tidak memiliki akses untuk melihat daftar invoice', 'wp-customer')
            );
        }

        return true;
    }

    /**
     * Validate user access to view specific invoice
     *
     * @param int $invoice_id Invoice ID
     * @return bool|WP_Error True if valid or WP_Error with reason
     */
    public function canViewInvoice($invoice_id) {
        $user_id = get_current_user_id();

        // Check if user is logged in
        if (!$user_id) {
            return new \WP_Error(
                'not_logged_in',
                __('Anda harus login terlebih dahulu', 'wp-customer')
            );
        }

        // Check basic capability
        if (!current_user_can('view_customer_membership_invoice_detail')) {
            return new \WP_Error(
                'no_permission',
                __('Anda tidak memiliki akses untuk melihat detail invoice', 'wp-customer')
            );
        }

        // Get invoice
        $invoice = $this->invoice_model->find($invoice_id);
        if (!$invoice) {
            return new \WP_Error(
                'invoice_not_found',
                __('Invoice tidak ditemukan', 'wp-customer')
            );
        }

        // Get user relation to determine access
        $relation = $this->customer_model->getUserRelation(0);

        // Admin can access all invoices
        if ($relation['is_admin']) {
            return true;
        }

        // Get branch data to validate access
        $branch = $this->invoice_model->getBranchData($invoice->branch_id);
        if (!$branch) {
            return new \WP_Error(
                'invalid_invoice',
                __('Data invoice tidak valid', 'wp-customer')
            );
        }

        // Customer Admin: can access invoices for branches under their customer
        if ($relation['is_customer_admin']) {
            $customer = $this->customer_model->find($branch->customer_id);
            if ($customer && $customer->user_id == $user_id) {
                return true;
            }
        }

        // Customer Branch Admin: can only access invoices for their branch
        if ($relation['is_customer_branch_admin']) {
            if ($branch->user_id == $user_id) {
                return true;
            }
        }

        // Employee: can only access invoices for the branch they work in
        if ($relation['is_customer_employee']) {
            global $wpdb;
            $employee_branch = $wpdb->get_var($wpdb->prepare(
                "SELECT branch_id FROM {$wpdb->prefix}app_customer_employees
                 WHERE user_id = %d AND status = 'active' LIMIT 1",
                $user_id
            ));

            if ($employee_branch && $employee_branch == $invoice->branch_id) {
                return true;
            }
        }

        return new \WP_Error(
            'access_denied',
            __('Anda tidak memiliki akses untuk melihat invoice ini', 'wp-customer')
        );
    }

    /**
     * Validate user access to create invoice
     *
     * @param int $branch_id Branch ID for the invoice
     * @return bool|WP_Error True if valid or WP_Error with reason
     */
    public function canCreateInvoice($branch_id) {
        $user_id = get_current_user_id();

        // Check if user is logged in
        if (!$user_id) {
            return new \WP_Error(
                'not_logged_in',
                __('Anda harus login terlebih dahulu', 'wp-customer')
            );
        }

        // Check basic capability
        if (!current_user_can('create_customer_membership_invoice')) {
            return new \WP_Error(
                'no_permission',
                __('Anda tidak memiliki akses untuk membuat invoice', 'wp-customer')
            );
        }

        // Get branch data
        $branch = $this->invoice_model->getBranchData($branch_id);
        if (!$branch) {
            return new \WP_Error(
                'invalid_branch',
                __('Branch tidak ditemukan', 'wp-customer')
            );
        }

        // Get user relation to determine access
        $relation = $this->customer_model->getUserRelation(0);

        // Admin can create invoices for any branch
        if ($relation['is_admin']) {
            return true;
        }

        // Customer Admin: can create invoices for branches under their customer
        if ($relation['is_customer_admin']) {
            $customer = $this->customer_model->find($branch->customer_id);
            if ($customer && $customer->user_id == $user_id) {
                return true;
            }
        }

        return new \WP_Error(
            'access_denied',
            __('Anda tidak memiliki akses untuk membuat invoice untuk branch ini', 'wp-customer')
        );
    }

    /**
     * Validate user access to edit invoice
     *
     * @param int $invoice_id Invoice ID
     * @return bool|WP_Error True if valid or WP_Error with reason
     */
    public function canEditInvoice($invoice_id) {
        $user_id = get_current_user_id();

        // Check if user is logged in
        if (!$user_id) {
            return new \WP_Error(
                'not_logged_in',
                __('Anda harus login terlebih dahulu', 'wp-customer')
            );
        }

        // Get invoice
        $invoice = $this->invoice_model->find($invoice_id);
        if (!$invoice) {
            return new \WP_Error(
                'invoice_not_found',
                __('Invoice tidak ditemukan', 'wp-customer')
            );
        }

        // Get user relation to determine access
        $relation = $this->customer_model->getUserRelation(0);

        // Get branch data
        $branch = $this->invoice_model->getBranchData($invoice->branch_id);
        if (!$branch) {
            return new \WP_Error(
                'invalid_invoice',
                __('Data invoice tidak valid', 'wp-customer')
            );
        }

        // Admin can edit all invoices
        if ($relation['is_admin'] && current_user_can('edit_all_customer_membership_invoices')) {
            return true;
        }

        // Customer Admin: can edit invoices for branches under their customer
        if ($relation['is_customer_admin'] && current_user_can('edit_all_customer_membership_invoices')) {
            $customer = $this->customer_model->find($branch->customer_id);
            if ($customer && $customer->user_id == $user_id) {
                return true;
            }
        }

        // Customer Branch Admin: can edit invoices for their branch
        if ($relation['is_customer_branch_admin'] && current_user_can('edit_own_customer_membership_invoice')) {
            if ($branch->user_id == $user_id) {
                return true;
            }
        }

        return new \WP_Error(
            'access_denied',
            __('Anda tidak memiliki akses untuk mengedit invoice ini', 'wp-customer')
        );
    }

    /**
     * Validate user access to delete invoice
     *
     * @param int $invoice_id Invoice ID
     * @return bool|WP_Error True if valid or WP_Error with reason
     */
    public function canDeleteInvoice($invoice_id) {
        $user_id = get_current_user_id();

        // Check if user is logged in
        if (!$user_id) {
            return new \WP_Error(
                'not_logged_in',
                __('Anda harus login terlebih dahulu', 'wp-customer')
            );
        }

        // Check basic capability
        if (!current_user_can('delete_customer_membership_invoice')) {
            return new \WP_Error(
                'no_permission',
                __('Anda tidak memiliki akses untuk menghapus invoice', 'wp-customer')
            );
        }

        // Get invoice
        $invoice = $this->invoice_model->find($invoice_id);
        if (!$invoice) {
            return new \WP_Error(
                'invoice_not_found',
                __('Invoice tidak ditemukan', 'wp-customer')
            );
        }

        // Get user relation to determine access
        $relation = $this->customer_model->getUserRelation(0);

        // Only admin can delete invoices
        if (!$relation['is_admin']) {
            return new \WP_Error(
                'access_denied',
                __('Hanya administrator yang dapat menghapus invoice', 'wp-customer')
            );
        }

        return true;
    }
}

