<?php
/**
* Select List Hooks Class
*
* @package     WP_Customer
* @subpackage  Hooks
* @version     1.0.10
* @author      arisciwek
*
* Path: /wp-customer/src/Hooks/SelectListHooks.php
*
* Description: Hooks untuk mengelola select list customer dan kabupaten.
*              Menyediakan filter dan action untuk render select lists.
*              Includes dynamic loading untuk kabupaten berdasarkan customer.
*              Terintegrasi dengan cache system.
*
* Hooks yang tersedia:
* - wp_customer_get_customer_options (filter)
* - wp_customer_get_branch_options (filter) 
* - wp_customer_customer_select (action)
* - wp_customer_branch_select (action)
*
* Changelog:
* 1.0.0 - 2024-01-06
* - Initial implementation
* - Added customer options filter
* - Added branch options filter
* - Added select rendering actions
* - Added cache integration
*/


namespace WPCustomer\Hooks;

use WPCustomer\Models\CustomerModel;
use WPCustomer\Models\Branch\BranchModel;
use WPCustomer\Cache\WPCache;

class SelectListHooks {
    private $customer_model;
    private $branch_model;
    private $cache;
    private $debug_mode;

    public function __construct() {
        $this->customer_model = new CustomerModel();
        $this->branch_model = new BranchModel();
        $this->cache = new WPCache();
        $this->debug_mode = apply_filters('wp_customer_debug_mode', false);
        
        $this->registerHooks();
    }

    private function registerHooks() {
        // Register filters
        add_filter('wp_customer_get_customer_options', [$this, 'getCustomerOptions'], 10, 2);
        add_filter('wp_customer_get_branch_options', [$this, 'getBranchOptions'], 10, 3);
        
        // Register actions
        add_action('wp_customer_customer_select', [$this, 'renderCustomerSelect'], 10, 2);
        add_action('wp_customer_branch_select', [$this, 'renderBranchSelect'], 10, 3);
        
        // Register AJAX handlers
        add_action('wp_ajax_get_branch_options', [$this, 'handleAjaxBranchOptions']);
        add_action('wp_ajax_nopriv_get_branch_options', [$this, 'handleAjaxBranchOptions']);
    }

    /**
     * Get customer options with caching
     */
    public function getCustomerOptions(array $default_options = [], bool $include_empty = true): array {
        try {
            $cache_key = 'customer_options_' . md5(serialize($default_options) . $include_empty);
            
            // Try to get from cache first
            $options = $this->cache->get($cache_key);
            if (false !== $options) {
                $this->debugLog('Retrieved customer options from cache');
                return $options;
            }

            $options = $default_options;
            
            if ($include_empty) {
                $options[''] = __('Pilih Customer', 'wp-customer');
            }

            $customers = $this->customer_model->getAllCustomers();
            foreach ($customers as $customer) {
                $options[$customer->id] = esc_html($customer->name);
            }

            // Cache the results
            $this->cache->set($cache_key, $options);
            $this->debugLog('Cached new customer options');

            return $options;

        } catch (\Exception $e) {
            $this->logError('Error getting customer options: ' . $e->getMessage());
            return $default_options;
        }
    }

    /**
     * Get branch options with caching
     */
    public function getBranchOptions(array $default_options = [], ?int $customer_id = null, bool $include_empty = true): array {
        try {
            if ($customer_id) {
                $cache_key = "branch_options_{$customer_id}_" . md5(serialize($default_options) . $include_empty);
                
                // Try cache first
                $options = $this->cache->get($cache_key);
                if (false !== $options) {
                    $this->debugLog("Retrieved branch options for customer {$customer_id} from cache");
                    return $options;
                }
            }

            $options = $default_options;
            
            if ($include_empty) {
                $options[''] = __('Pilih Cabang', 'wp-customer');
            }

            if ($customer_id) {
                $branches = $this->branch_model->getByCustomer($customer_id);
                foreach ($branches as $branch) {
                    $options[$branch->id] = esc_html($branch->name);
                }

                // Cache the results
                $this->cache->set($cache_key, $options);
                $this->debugLog("Cached new branch options for customer {$customer_id}");
            }

            return $options;

        } catch (\Exception $e) {
            $this->logError('Error getting branch options: ' . $e->getMessage());
            return $default_options;
        }
    }

    /**
     * Render customer select element
     */
    public function renderCustomerSelect(array $attributes = [], ?int $selected_id = null): void {
        try {
            $default_attributes = [
                'name' => 'customer_id',
                'id' => 'customer_id',
                'class' => 'wp-customer-customer-select'
            ];

            $attributes = wp_parse_args($attributes, $default_attributes);
            $options = $this->getCustomerOptions();

            $this->renderSelect($attributes, $options, $selected_id);

        } catch (\Exception $e) {
            $this->logError('Error rendering customer select: ' . $e->getMessage());
            echo '<p class="error">' . esc_html__('Error loading customer selection', 'wp-customer') . '</p>';
        }
    }

    /**
     * Render branch select element
     */
    public function renderBranchSelect(array $attributes = [], ?int $customer_id = null, ?int $selected_id = null): void {
        try {
            $default_attributes = [
                'name' => 'branch_id',
                'id' => 'branch_id',
                'class' => 'wp-customer-branch-select'
            ];

            $attributes = wp_parse_args($attributes, $default_attributes);
            $options = $this->getBranchOptions([], $customer_id);

            $this->renderSelect($attributes, $options, $selected_id);

        } catch (\Exception $e) {
            $this->logError('Error rendering branch select: ' . $e->getMessage());
            echo '<p class="error">' . esc_html__('Error loading branch selection', 'wp-customer') . '</p>';
        }
    }

    /**
     * Handle AJAX request for branch options
     */
    public function handleAjaxBranchOptions(): void {
        try {
            if (!check_ajax_referer('wp_customer_select_nonce', 'nonce', false)) {
                throw new \Exception('Invalid security token');
            }

            $customer_id = isset($_POST['customer_id']) ? absint($_POST['customer_id']) : 0;
            if (!$customer_id) {
                throw new \Exception('Invalid customer ID');
            }

            $options = $this->getBranchOptions([], $customer_id);
            $html = $this->generateOptionsHtml($options);

            wp_send_json_success(['html' => $html]);

        } catch (\Exception $e) {
            $this->logError('AJAX Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Gagal memuat data cabang', 'wp-customer')
            ]);
        }
    }

    /**
     * Helper method to render select element
     */
    private function renderSelect(array $attributes, array $options, ?int $selected_id): void {
        ?>
        <select <?php echo $this->buildAttributes($attributes); ?>>
            <?php foreach ($options as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" 
                    <?php selected($selected_id, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Generate HTML for select options
     */
    private function generateOptionsHtml(array $options): string {
        $html = '';
        foreach ($options as $value => $label) {
            $html .= sprintf(
                '<option value="%s">%s</option>',
                esc_attr($value),
                esc_html($label)
            );
        }
        return $html;
    }

    /**
     * Build HTML attributes string
     */
    private function buildAttributes(array $attributes): string {
        $html = '';
        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html .= sprintf(' %s', esc_attr($key));
                }
            } else {
                $html .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
            }
        }
        return $html;
    }

    /**
     * Debug logging
     */
    private function debugLog(string $message): void {
        if ($this->debug_mode) {
            error_log('WP Select Debug: ' . $message);
        }
    }

    /**
     * Error logging
     */
    private function logError(string $message): void {
        error_log('WP Select Error: ' . $message);
    }
}
