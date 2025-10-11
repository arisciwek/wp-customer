<?php
/**
 * Customer Branch Details Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Branch/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/branch/partials/_customer_branch_details.php
 *
 * Description: Template untuk menampilkan detail cabang customer.
 *              Includes export actions (PDF, DOCX),
 *              informasi lengkap cabang, dan data terkait.
 *
 * Changelog:
 * 1.0.0 - 2024-12-10
 * - Initial release
 * - Added branch details display
 * - Added export functionality
 */

defined('ABSPATH') || exit;
?>

<div id="customer-details" class="tab-content">
    <div class="export-actions">
        <button type="button" class="button wp-mpdf-customer-detail-export-pdf">
            <span class="dashicons dashicons-pdf"></span>
            <?php _e('Generate PDF', 'wp-customer'); ?>
        </button>
        <button type="button" class="button wp-docgen-customer-detail-expot-document">
            <span class="dashicons dashicons-media-document"></span>
            <?php _e('Export DOCX', 'wp-customer'); ?>
        </button>
        <button type="button" class="button wp-docgen-customer-detail-expot-pdf">
            <span class="dashicons dashicons-pdf"></span>
            <?php _e('Export PDF', 'wp-customer'); ?>
        </button>
    </div>

    <!-- Main Content Grid -->
    <div class="meta-info customer-details-grid">
        <!-- Basic Information -->
        <div class="postbox">
            <h3 class="hndle">
                <span class="dashicons dashicons-building"></span>
                <?php _e('Informasi Dasar', 'wp-customer'); ?>
            </h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Nama Customer', 'wp-customer'); ?></th>
                        <td><span id="customer-name"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Kode Customer', 'wp-customer'); ?></th>
                        <td><span id="customer-code"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('NPWP', 'wp-customer'); ?></th>
                        <td><span id="customer-npwp"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('NIB', 'wp-customer'); ?></th>
                        <td><span id="customer-nib"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'wp-customer'); ?></th>
                        <td><span id="customer-status" class="status-badge"></span></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Location Information -->
        <div class="postbox">
            <h3 class="hndle">
                <span class="dashicons dashicons-location"></span>
                <?php _e('Lokasi Kantor Pusat', 'wp-customer'); ?>
            </h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Alamat', 'wp-customer'); ?></th>
                        <td><span id="customer-pusat-address"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Kode Pos', 'wp-customer'); ?></th>
                        <td><span id="customer-pusat-postal-code"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Provinsi', 'wp-customer'); ?></th>
                        <td><span id="customer-province"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Kabupaten/Kota', 'wp-customer'); ?></th>
                        <td><span id="customer-regency"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Koordinat', 'wp-customer'); ?></th>
                        <td>
                            <span id="customer-coordinates"></span>
                            <a href="#" id="customer-google-maps-link" target="_blank" class="button button-small" style="margin-left: 10px;">
                                <span class="dashicons dashicons-location"></span>
                                <?php _e('Lihat di Google Maps', 'wp-customer'); ?>
                            </a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="postbox">
            <h3 class="hndle">
                <span class="dashicons dashicons-businessperson"></span>
                <?php _e('Informasi Tambahan', 'wp-customer'); ?>
            </h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Admin', 'wp-customer'); ?></th>
                        <td><span id="customer-owner"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Jumlah Cabang', 'wp-customer'); ?></th>
                        <td><span id="customer-branch-count">0</span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Jumlah Karyawan', 'wp-customer'); ?></th>
                        <td><span id="customer-employee-count">0</span></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Timeline Information -->
        <div class="postbox">
            <h3 class="hndle">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e('Timeline', 'wp-customer'); ?>
            </h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Dibuat Oleh', 'wp-customer'); ?></th>
                        <td><span id="customer-created-by"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Tanggal Dibuat', 'wp-customer'); ?></th>
                        <td><span id="customer-created-at"></span></td>
                    </tr>
                    <tr>
                        <th><?php _e('Terakhir Diupdate', 'wp-customer'); ?></th>
                        <td><span id="customer-updated-at"></span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
