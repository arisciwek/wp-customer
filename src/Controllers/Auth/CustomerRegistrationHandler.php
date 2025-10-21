<?php
/**
 * Customer Registration Handler
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Auth
 * @version     1.0.10
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Auth/CustomerRegistrationHandler.php
 *
 * Description: Handler untuk memproses registrasi customer baru.
 *              Menangani pembuatan user WordPress dan data customer,
 *              termasuk validasi field unik (username, email, NIB, NPWP).
 *              Mengimplementasikan rollback jika terjadi error.
 *
 * Dependencies:
 * - WordPress user functions
 * - wp_customer_settings
 * - Customers table
 *
 * Changelog:
 * 1.0.0 - 2024-01-11
 * - Initial version
 * - Added customer registration handler
 * - Added unique field validation
 * - Added customer code generator
 * - Added rollback functionality
 */

namespace WPCustomer\Controllers\Auth;

use WPCustomer\Controllers\CustomerController;
use WPCustomer\Validators\CustomerValidator;

defined('ABSPATH') || exit;

class CustomerRegistrationHandler {

    private CustomerController $customerController;
    private CustomerValidator $validator;

    public function __construct() {
        $this->customerController = new CustomerController();
        $this->validator = new CustomerValidator();
    }

    public function handle_registration() {
        check_ajax_referer('wp_customer_register', 'register_nonce');

        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $name = sanitize_text_field($_POST['name']);
        $nib = $this->validator->formatNib(sanitize_text_field($_POST['nib']));
        $npwp = $this->validator->formatNpwp(sanitize_text_field($_POST['npwp']));
        $provinsi_id = isset($_POST['provinsi_id']) ? (int)$_POST['provinsi_id'] : 0;
        $regency_id = isset($_POST['regency_id']) ? (int)$_POST['regency_id'] : 0;

        // Validasi dasar
        if (empty($username) || empty($email) || empty($password) ||
            empty($name) || empty($nib) || empty($npwp) ||
            empty($provinsi_id) || empty($regency_id)) {
            wp_send_json_error([
                'message' => __('Semua field wajib diisi.', 'wp-customer')
            ]);
        }

        // Validasi format NPWP
        if (!$this->validator->validateNpwpFormat($npwp)) {
            wp_send_json_error([
                'message' => __('Format NPWP tidak valid. Format: XX.XXX.XXX.X-XXX.XXX', 'wp-customer')
            ]);
        }

        // Validasi format NIB
        if (!$this->validator->validateNibFormat($nib)) {
            wp_send_json_error([
                'message' => __('Format NIB tidak valid. Harus 13 digit.', 'wp-customer')
            ]);
        }

        // Cek username
        if (username_exists($username)) {
            wp_send_json_error([
                'message' => __('Username sudah digunakan.', 'wp-customer')
            ]);
        }

        // Task-2165: Call shared method in CustomerController
        try {
            $data = [
                'username' => $username,
                'password' => $password,
                'email' => $email,
                'name' => $name,
                'nib' => $nib,
                'npwp' => $npwp,
                'provinsi_id' => $provinsi_id,
                'regency_id' => $regency_id,
                'status' => 'active',
                'reg_type' => 'self' // Mark as self-register
            ];

            // Call shared method (created_by = null, means self-created)
            $result = $this->customerController->createCustomerWithUser($data, null);

            wp_send_json_success([
                'message' => __('Registrasi berhasil! Silakan login.', 'wp-customer'),
                'redirect' => wp_login_url()
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}
