<?php
/**
 * Demo Data Generator Tab Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/settings/tab-demo-data.php
 */


/* 
1. Jika kedua opsi TIDAK dicentang:
   - Development Mode: ❌ 
   - Clear demo data: ❌
   - Hasil: Data TIDAK akan dihapus
   - Alasan: Development mode tidak aktif, jadi langsung ke fallback konstanta WP_CUSTOMER_DEVELOPMENT yang bernilai false

2. Jika HANYA Development Mode dicentang:
   - Development Mode: ✅
   - Clear demo data: ❌
   - Hasil: Data TIDAK akan dihapus
   - Alasan: Meskipun development mode aktif, clear_data_on_deactivate tidak dicentang

3. Jika HANYA Clear demo data dicentang:
   - Development Mode: ❌
   - Clear demo data: ✅
   - Hasil: Data TIDAK akan dihapus
   - Alasan: Development mode tidak aktif, jadi clear_data_on_deactivate tidak akan diperiksa dan langsung ke fallback konstanta

4. Jika KEDUA opsi dicentang:
   - Development Mode: ✅
   - Clear demo data: ✅
   - Hasil: Data AKAN dihapus
   - Alasan: Development mode aktif dan clear_data_on_deactivate juga aktif

Kesimpulannya:
- Data hanya akan dihapus jika KEDUA opsi dicentang
- Ini membuat sistem menjadi "double safety" - harus mengaktifkan development mode terlebih dahulu sebelum dapat menghapus data
- Jika salah satu saja tidak dicentang, data tidak akan dihapus
- Konstanta WP_CUSTOMER_DEVELOPMENT hanya digunakan sebagai fallback jika development mode tidak diaktifkan melalui UI

*/

if (!defined('ABSPATH')) {
    die;
}

// Verify nonce and capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}
?>

<div class="wrap">
    <div id="demo-data-messages"></div>
    <div class="demo-data-section">
        <h3><?php _e('Generate Demo Data', 'wp-customer'); ?></h3>
        <p class="description">
            <?php _e('Generate demo data for testing purposes. Each button will generate specific type of data.', 'wp-customer'); ?>
        </p>

        <div class="demo-data-grid">
            <!-- Feature Groups -->
            <div class="demo-data-card">
                <h4><?php _e('Membership Feature Groups', 'wp-customer'); ?></h4>
                <p><?php _e('Generate feature group definitions for membership capabilities.', 'wp-customer'); ?></p>
                <button type="button" 
                        class="button button-primary customer-generate-demo-data" 
                        data-type="membership-groups"
                        data-nonce="<?php echo wp_create_nonce('generate_demo_membership-groups'); ?>">
                    <?php _e('Generate Feature Groups', 'wp-customer'); ?>
                </button>
            </div>

            <!-- Membership Features -->
            <div class="demo-data-card">
                <h4><?php _e('Membership Features', 'wp-customer'); ?></h4>
                <p><?php _e('Generate membership feature definitions for capabilities and limits.', 'wp-customer'); ?></p>
                <button type="button" 
                        class="button button-primary customer-generate-demo-data" 
                        data-type="membership-features"
                        data-requires="membership-groups"
                        data-check-nonce="<?php echo wp_create_nonce('check_demo_membership-groups'); ?>"
                        data-nonce="<?php echo wp_create_nonce('generate_demo_membership-features'); ?>">
                    <?php _e('Generate Membership Features', 'wp-customer'); ?>
                </button>
            </div>
            
            <!-- Membership Levels -->
            <div class="demo-data-card">
                <h4><?php _e('Membership Levels', 'wp-customer'); ?></h4>
                <p><?php _e('Generate default membership levels configuration.', 'wp-customer'); ?></p>
                <button type="button" 
                        class="button button-primary customer-generate-demo-data" 
                        data-type="membership-level"
                        data-requires="membership-features"
                        data-check-nonce="<?php echo wp_create_nonce('check_demo_membership-features'); ?>"
                        data-nonce="<?php echo wp_create_nonce('generate_demo_membership-level'); ?>">
                    <?php _e('Generate Membership Levels', 'wp-customer'); ?>
                </button>
            </div>

            <!-- Customers -->
            <div class="demo-data-card">
                <h4><?php _e('Customers', 'wp-customer'); ?></h4>
                <p><?php _e('Generate sample customer data with WordPress users.', 'wp-customer'); ?></p>
                <button type="button" 
                        class="button button-primary customer-generate-demo-data" 
                        data-type="customer"
                        data-nonce="<?php echo wp_create_nonce('generate_demo_customer'); ?>">
                    <?php _e('Generate Customers', 'wp-customer'); ?>
                </button>
            </div>

            <!-- Branches -->
            <div class="demo-data-card">
                <h4><?php _e('Branches', 'wp-customer'); ?></h4>
                <p><?php _e('Generate branch offices for existing customers.', 'wp-customer'); ?></p>
                <button type="button" 
                        class="button button-primary customer-generate-demo-data" 
                        data-type="branch"
                        data-requires="customer"
                        data-check-nonce="<?php echo wp_create_nonce('check_demo_customer'); ?>"
                        data-nonce="<?php echo wp_create_nonce('generate_demo_branch'); ?>">
                    <?php _e('Generate Branches', 'wp-customer'); ?>
                </button>
            </div>

            <!-- Employees -->
            <div class="demo-data-card">
                <h4><?php _e('Employees', 'wp-customer'); ?></h4>
                <p><?php _e('Generate employee data for branches.', 'wp-customer'); ?></p>
                <button type="button" 
                        class="button button-primary customer-generate-demo-data" 
                        data-type="employee"
                        data-requires="branch"
                        data-check-nonce="<?php echo wp_create_nonce('check_demo_branch'); ?>"
                        data-nonce="<?php echo wp_create_nonce('generate_demo_employee'); ?>">
                    <?php _e('Generate Employees', 'wp-customer'); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="development-settings-section" style="margin-top: 30px;">
        <h3><?php _e('Development Settings', 'wp-customer'); ?></h3>
        <form method="post" action="options.php">
            <?php 
            settings_fields('wp_customer_development_settings');
            $dev_settings = get_option('wp_customer_development_settings', array(
                'enable_development' => 0,
                'clear_data_on_deactivate' => 0
            ));
            ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Development Mode', 'wp-customer'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="wp_customer_development_settings[enable_development]" 
                                   value="1" 
                                   <?php checked($dev_settings['enable_development'], 1); ?>>
                            <?php _e('Enable development mode', 'wp-customer'); ?>
                        </label>
                        <p class="description">
                            <?php _e('When enabled, this overrides WP_CUSTOMER_DEVELOPMENT constant.', 'wp-customer'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Data Cleanup', 'wp-customer'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="wp_customer_development_settings[clear_data_on_deactivate]" 
                                   value="1" 
                                   <?php checked($dev_settings['clear_data_on_deactivate'], 1); ?>>
                            <?php _e('Clear demo data on plugin deactivation', 'wp-customer'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Warning: When enabled, all demo data will be permanently deleted when the plugin is deactivated.', 'wp-customer'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>

</div>
