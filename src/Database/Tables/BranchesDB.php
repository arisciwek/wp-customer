<?php
/**
 * Branches Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     1.0.12
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Tables/BranchesDB.php
 *
 * Description: Mendefinisikan struktur tabel branches.
 *              Table prefix yang digunakan adalah 'app_'.
 *              Includes field untuk integrasi wilayah.
 *              Menyediakan foreign key ke customers table.
 *
 * Fields:
 * - id             : Primary key
 * - customer_id    : Foreign key ke customer
 * - code           : Format 
 * - name           : Nama branch
 * - type           : Tipe wilayah (cabang)
 * - provinsi_id    : ID provinsi (required)
 * - regency_id     : ID cabang (required)
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 *
 * Foreign Keys:
 * - customer_id    : REFERENCES app_customers(id) ON DELETE CASCADE
 *
 * Changelog:
 * 1.0.12 - 2025-11-02
 * - Changed provinsi_id from NULL to NOT NULL (required field)
 * - Changed regency_id from NULL to NOT NULL (required field)
 *
 * 1.0.5 - 2025-10-06
 * - Removed unique constraint for agency_id + inspector_id (inspector can manage multiple branches)
 * 1.0.4 - 2024-10-01
 * - Added unique constraint for agency_id + inspector_id
 *
 * 1.0.3 - 2024-10-01
 * - Added agency_id, division_id and inspector_id columns
 *
 * 1.0.2 - 2024-10-01
 * - Added division_id and inspector_id columns
 *
 * 1.0.1 - 2024-01-19
 * - Modified code field to varchar(17) for new format BR-TTTTRRRR-NNN
 * - Added unique constraint for customer_id + code
 *
 * 1.0.0 - 2024-01-07
 * - Initial version
 */

namespace WPCustomer\Database\Tables;

defined('ABSPATH') || exit;

class BranchesDB {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_branches';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            customer_id bigint(20) UNSIGNED NOT NULL,
            code varchar(20) NOT NULL,
            name varchar(100) NOT NULL,
            type enum('cabang','pusat') NOT NULL,
            nitku varchar(20) NULL COMMENT 'Nomor Identitas Tempat Kegiatan Usaha',
            postal_code varchar(5) NULL COMMENT 'Kode pos',
            latitude decimal(10,8) NULL COMMENT 'Koordinat lokasi',
            longitude decimal(11,8) NULL COMMENT 'Koordinat lokasi',
            address text NULL,
            phone varchar(20) NULL,
            email varchar(100) NULL,
            provinsi_id bigint(20) UNSIGNED NOT NULL,
            agency_id bigint(20) UNSIGNED NOT NULL,
            regency_id bigint(20) UNSIGNED NOT NULL,
            division_id bigint(20) UNSIGNED NULL,
            user_id bigint(20) UNSIGNED NULL,
            inspector_id bigint(20) UNSIGNED NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('active','inactive') DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            UNIQUE KEY customer_name (customer_id, name),
            UNIQUE KEY customer_regency (customer_id, regency_id),
            KEY customer_id_index (customer_id),
            KEY created_by_index (created_by),
            KEY nitku_index (nitku),
            KEY postal_code_index (postal_code),
            KEY location_index (latitude, longitude)
        ) $charset_collate;";
    }
}
