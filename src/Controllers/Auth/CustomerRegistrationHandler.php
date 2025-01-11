<?php
/**
 * Customer Registration Handler
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Auth
 * @version     1.0.0
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

defined('ABSPATH') || exit;

class CustomerRegistrationHandler {
    
    public function handle_registration() {
        check_ajax_referer('wp_customer_register', 'register_nonce');
        
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $name = sanitize_text_field($_POST['name']);
        $nib = sanitize_text_field($_POST['nib']);
        $npwp = $this->format_npwp(sanitize_text_field($_POST['npwp']));

        // Validasi dasar
        if (empty($username) || empty($email) || empty($password) || 
            empty($name) || empty($nib) || empty($npwp)) {
            wp_send_json_error([
                'message' => __('Semua field wajib diisi.', 'wp-customer')
            ]);
        }

        // Validasi format NPWP
        if (!$this->validate_npwp($npwp)) {
            wp_send_json_error([
                'message' => __('Format NPWP tidak valid.', 'wp-customer')
            ]);
        }

        // Cek username dan email
        if (username_exists($username)) {
            wp_send_json_error([
                'message' => __('Username sudah digunakan.', 'wp-customer')
            ]);
        }

        if (email_exists($email)) {
            wp_send_json_error([
                'message' => __('Email sudah terdaftar.', 'wp-customer')
            ]);
        }

        // Cek NIB dan NPWP di database
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customers';
        
        $existing_nib = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table_name WHERE nib = %s", $nib)
        );
        
        if ($existing_nib) {
            wp_send_json_error([
                'message' => __('NIB sudah terdaftar.', 'wp-customer')
            ]);
        }

        $existing_npwp = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table_name WHERE npwp = %s", $npwp)
        );
        
        if ($existing_npwp) {
            wp_send_json_error([
                'message' => __('NPWP sudah terdaftar.', 'wp-customer')
            ]);
        }

        // Buat user WordPress
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error([
                'message' => $user_id->get_error_message()
            ]);
        }

        // Tambahkan role customer
        $user = new \WP_User($user_id);
        $user->set_role('customer');

        // Generate kode customer
        $code = $this->generate_customer_code();

        // Insert data customer
        $customer_data = [
            'code' => $code,
            'name' => $name,
            'nib' => $nib,
            'npwp' => $npwp,
            'user_id' => $user_id,
            'created_by' => $user_id
        ];

        $inserted = $wpdb->insert($table_name, $customer_data);

        if ($inserted === false) {
            // Log error detail
            error_log('WP Customer Insert Error: ' . $wpdb->last_error);
            error_log('Customer Data: ' . print_r($customer_data, true));

            // Rollback - hapus user jika insert customer gagal
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($user_id);
            
            wp_send_json_error([
                'message' => __('Gagal membuat akun customer.', 'wp-customer')
            ]);
        }

        wp_send_json_success([
            'message' => __('Registrasi berhasil! Silakan login.', 'wp-customer'),
            'redirect' => wp_login_url()
        ]);
    }

    private function format_npwp($npwp) {
        // Remove non-digits
        $numbers = preg_replace('/\D/', '', $npwp);
        
        // Format to XX.XXX.XXX.X-XXX.XXX
        if (strlen($numbers) === 15) {
            return substr($numbers, 0, 2) . '.' .
                   substr($numbers, 2, 3) . '.' .
                   substr($numbers, 5, 3) . '.' .
                   substr($numbers, 8, 1) . '-' .
                   substr($numbers, 9, 3) . '.' .
                   substr($numbers, 12, 3);
        }
        
        return $npwp;
    }

    private function validate_npwp($npwp) {
        // Check if NPWP matches the format: XX.XXX.XXX.X-XXX.XXX
        return (bool) preg_match('/^\d{2}\.\d{3}\.\d{3}\.\d{1}\-\d{3}\.\d{3}$/', $npwp);
    }

    private function generate_customer_code() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customers';
        
        // Ambil kode terakhir
        $last_code = $wpdb->get_var("SELECT code FROM $table_name ORDER BY id DESC LIMIT 1");
        
        if (!$last_code) {
            return '01';
        }

        // Generate kode baru
        $next_number = intval($last_code) + 1;
        return str_pad($next_number, 2, '0', STR_PAD_LEFT);
    }
}
