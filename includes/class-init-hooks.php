<?php
/**
 * Init Hooks Class
 *
 * @package     WP_Customer
 * @subpackage  Includes
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/includes/class-init-hooks.php
 *
 * Description: Mendefinisikan semua hooks dan filters yang dibutuhkan
 *              oleh plugin saat inisialisasi. Termasuk URL rewrite,
 *              template, shortcodes, dan AJAX handlers.
 *
 * Changelog:
 * 1.0.0 - 2024-01-11
 * - Initial version
 * - Added registration hooks
 * - Added shortcode registration
 */
use WPCustomer\Controllers\Auth\CustomerRegistrationHandler;

class WP_Customer_Init_Hooks {

    public function init() {
        // Query vars
        add_filter('query_vars', [$this, 'add_query_vars']);

        // Templates
        add_action('template_redirect', [$this, 'handle_template_redirect']);

        // Shortcodes
        add_action('init', [$this, 'register_shortcodes']);

        // AJAX handlers
        add_action('wp_ajax_nopriv_wp_customer_register', [$this, 'handle_registration']);
    }

    /**
     * Load plugin textdomain untuk i18n/l10n.
     * Sesuaikan $rel_path jika folder languages Anda berbeda lokasi.
     */
    public function load_textdomain() {
        // Asumsi struktur: wp-customer/languages/wp-customer-id_ID.mo
        // Karena file ini berada di includes/, kita naik 1 level ke root plugin:
        // plugin-basename(__FILE__) => "wp-customer/includes/class-init-hooks.php"
        // dirname(..., 2) => "wp-customer"
        $rel_path = dirname(plugin_basename(__FILE__), 2) . '/languages/';
        load_plugin_textdomain(
            'wp-customer',
            false,
            $rel_path
        );
    }
    
    public function register_shortcodes() {
        add_shortcode('customer_register_form', array($this, 'render_register_form'));
    }

    public function render_register_form() {
        if (is_user_logged_in()) {
            return '<p>' . __('Anda sudah login.', 'wp-customer') . '</p>';
        }
        
        ob_start();
        include WP_CUSTOMER_PATH . 'src/Views/templates/auth/register.php';
        return ob_get_clean();
    }


    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'wp_customer_register';
        return $vars;
    }

    /**
     * Handle template redirects
     */
public function handle_template_redirect() {
    // Ignore favicon requests
    if (strpos($_SERVER['REQUEST_URI'], 'favicon.ico') !== false) {
        return;
    }

    if (get_query_var('wp_customer_register') !== '') {
        error_log('Loading registration template...');
        
        if (is_user_logged_in()) {
            error_log('User is logged in, redirecting...');
            wp_redirect(home_url());
            exit;
        }
        
        error_log('Including template from: ' . WP_CUSTOMER_PATH . 'src/Views/templates/auth/template-register.php');
        include WP_CUSTOMER_PATH . 'src/Views/templates/auth/template-register.php';
        exit;
    }
}

    /**
     * Handle registration AJAX
     * Delegate to CustomerRegistrationHandler
     */
    public function handle_registration() {
        $handler = new CustomerRegistrationHandler();
        $handler->handle_registration();
    }
}
