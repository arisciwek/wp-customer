<?php
/**
 * General Settings Tab
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Settings
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/settings/tab-general.php
 *
 * Description: General settings tab template following wp-app-core pattern
 *              Display settings, cache, API, and system configurations
 *
 * Changelog:
 * 2.0.0 - 2025-01-13 (TODO-2198)
 * - BREAKING: Complete refactor to match wp-app-core pattern
 * - Added proper form structure with hidden inputs
 * - Removed submit button (moved to page level)
 * - Added sections structure (Display, Cache, API, System)
 * - Updated settings fields to match CustomerGeneralSettingsModel
 * - Added proper styling
 * 1.0.0 - 2024-01-07
 * - Initial version
 */

if (!defined('ABSPATH')) {
    die;
}

// $settings is passed from controller
?>

<div class="wp-customer-settings-general">
    <form method="post" action="options.php" id="wp-customer-general-settings-form">
        <?php settings_fields('wp_customer_settings'); ?>
        <input type="hidden" name="reset_to_defaults" value="0">
        <input type="hidden" name="current_tab" value="general">
        <input type="hidden" name="saved_tab" value="general">

        <!-- Display Settings Section -->
        <div class="settings-section">
            <h2><?php _e('Pengaturan Tampilan', 'wp-customer'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="records_per_page"><?php _e('Data Per Halaman', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <select name="wp_customer_settings[records_per_page]" id="records_per_page">
                            <option value="5" <?php selected($settings['records_per_page'] ?? 15, 5); ?>>5</option>
                            <option value="10" <?php selected($settings['records_per_page'] ?? 15, 10); ?>>10</option>
                            <option value="15" <?php selected($settings['records_per_page'] ?? 15, 15); ?>>15</option>
                            <option value="25" <?php selected($settings['records_per_page'] ?? 15, 25); ?>>25</option>
                            <option value="50" <?php selected($settings['records_per_page'] ?? 15, 50); ?>>50</option>
                            <option value="100" <?php selected($settings['records_per_page'] ?? 15, 100); ?>>100</option>
                        </select>
                        <p class="description"><?php _e('Jumlah data yang ditampilkan per halaman di tabel', 'wp-customer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="datatables_language"><?php _e('Bahasa Tabel', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <select name="wp_customer_settings[datatables_language]" id="datatables_language">
                            <option value="id" <?php selected($settings['datatables_language'] ?? 'id', 'id'); ?>>Bahasa Indonesia</option>
                            <option value="en" <?php selected($settings['datatables_language'] ?? 'id', 'en'); ?>>English</option>
                        </select>
                        <p class="description"><?php _e('Bahasa yang digunakan untuk datatables', 'wp-customer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="display_format"><?php _e('Format Tampilan', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <select name="wp_customer_settings[display_format]" id="display_format">
                            <option value="hierarchical" <?php selected($settings['display_format'] ?? 'hierarchical', 'hierarchical'); ?>><?php _e('Hierarki (dengan induk-anak)', 'wp-customer'); ?></option>
                            <option value="flat" <?php selected($settings['display_format'] ?? 'hierarchical', 'flat'); ?>><?php _e('Datar (tanpa hierarki)', 'wp-customer'); ?></option>
                        </select>
                        <p class="description"><?php _e('Format tampilan data customer', 'wp-customer'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Cache Settings Section -->
        <div class="settings-section">
            <h2><?php _e('Pengaturan Cache', 'wp-customer'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="enable_caching"><?php _e('Aktifkan Cache', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_customer_settings[enable_caching]"
                                   id="enable_caching"
                                   value="1"
                                   <?php checked($settings['enable_caching'] ?? true, 1); ?>>
                            <?php _e('Aktifkan caching untuk meningkatkan performa', 'wp-customer'); ?>
                        </label>
                        <p class="description"><?php _e('Cache akan mempercepat loading data', 'wp-customer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="cache_duration"><?php _e('Durasi Cache', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <select name="wp_customer_settings[cache_duration]" id="cache_duration">
                            <option value="3600" <?php selected($settings['cache_duration'] ?? 43200, 3600); ?>><?php _e('1 Jam', 'wp-customer'); ?></option>
                            <option value="7200" <?php selected($settings['cache_duration'] ?? 43200, 7200); ?>><?php _e('2 Jam', 'wp-customer'); ?></option>
                            <option value="21600" <?php selected($settings['cache_duration'] ?? 43200, 21600); ?>><?php _e('6 Jam', 'wp-customer'); ?></option>
                            <option value="43200" <?php selected($settings['cache_duration'] ?? 43200, 43200); ?>><?php _e('12 Jam', 'wp-customer'); ?></option>
                            <option value="86400" <?php selected($settings['cache_duration'] ?? 43200, 86400); ?>><?php _e('24 Jam', 'wp-customer'); ?></option>
                        </select>
                        <p class="description"><?php _e('Durasi cache sebelum data di-refresh', 'wp-customer'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- API Settings Section -->
        <div class="settings-section">
            <h2><?php _e('Pengaturan API', 'wp-customer'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="enable_api"><?php _e('Aktifkan API', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_customer_settings[enable_api]"
                                   id="enable_api"
                                   value="1"
                                   <?php checked($settings['enable_api'] ?? false, 1); ?>>
                            <?php _e('Aktifkan REST API untuk plugin ini', 'wp-customer'); ?>
                        </label>
                        <p class="description"><?php _e('Aktifkan jika Anda ingin menggunakan API eksternal', 'wp-customer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="api_key"><?php _e('API Key', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               name="wp_customer_settings[api_key]"
                               id="api_key"
                               value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>"
                               class="regular-text">
                        <p class="description"><?php _e('API key untuk autentikasi eksternal (optional)', 'wp-customer'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- System Settings Section -->
        <div class="settings-section">
            <h2><?php _e('Pengaturan Sistem', 'wp-customer'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="log_enabled"><?php _e('Logging', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_customer_settings[log_enabled]"
                                   id="log_enabled"
                                   value="1"
                                   <?php checked($settings['log_enabled'] ?? false, 1); ?>>
                            <?php _e('Aktifkan logging untuk debugging', 'wp-customer'); ?>
                        </label>
                        <p class="description"><?php _e('Log akan disimpan untuk troubleshooting', 'wp-customer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="enable_hard_delete_branch"><?php _e('Hard Delete Branch', 'wp-customer'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="wp_customer_settings[enable_hard_delete_branch]"
                                   id="enable_hard_delete_branch"
                                   value="1"
                                   <?php checked($settings['enable_hard_delete_branch'] ?? false, 1); ?>>
                            <?php _e('Aktifkan penghapusan permanen untuk branch', 'wp-customer'); ?>
                        </label>
                        <p class="description">
                            <strong><?php _e('PERINGATAN:', 'wp-customer'); ?></strong>
                            <?php _e('Untuk production: gunakan soft delete (OFF). Untuk demo/testing: gunakan hard delete (ON)', 'wp-customer'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
    </form>

    <!-- DEPRECATED: Per-tab buttons moved to page level (settings-page.php) -->
</div>

<style>
.settings-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.settings-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e5e5;
}
</style>
