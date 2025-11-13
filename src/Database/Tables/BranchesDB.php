<?php
/**
 * Branches Table Schema
 *
 * @package     WP_Customer
 * @subpackage  Database/Tables
 * @version     1.0.13
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
 * - type           : Tipe wilayah (cabang/pusat)
 * - nitku          : Nomor Identitas Tempat Kegiatan Usaha
 * - postal_code    : Kode pos
 * - latitude       : Koordinat lokasi
 * - longitude      : Koordinat lokasi
 * - address        : Alamat lengkap
 * - phone          : Nomor telepon
 * - email          : Email branch
 * - province_id    : ID provinsi (required)
 * - agency_id      : ID agency (nullable, diisi saat ada assignment)
 * - regency_id     : ID kabupaten/kota (required)
 * - division_id    : ID division agency (nullable, diisi saat ada assignment)
 * - user_id        : ID user pemilik branch
 * - inspector_id   : ID inspector dari agency (nullable, diisi saat ada assignment)
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 * - status         : Status branch (active/inactive)
 *
 * Foreign Keys:
 * - customer_id    : REFERENCES app_customers(id) ON DELETE CASCADE
 * - province_id    : REFERENCES wi_provinces(id) ON DELETE SET NULL
 * - regency_id     : REFERENCES wi_regencies(id) ON DELETE SET NULL
 * - agency_id      : REFERENCES app_agencies(id) ON DELETE SET NULL
 * - division_id    : REFERENCES app_agency_divisions(id) ON DELETE SET NULL
 * - inspector_id   : REFERENCES app_agency_employees(id) ON DELETE SET NULL
 *
 * Changelog:
 * 1.0.13 - 2025-01-04
 * - Changed agency_id from NOT NULL to NULL (assigned when branch gets agency assignment)
 *
 * 1.0.12 - 2025-11-02
 * - Changed province_id from NULL to NOT NULL (required field)
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
            province_id bigint(20) UNSIGNED NOT NULL,
            agency_id bigint(20) UNSIGNED NULL,
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

    /**
     * Add foreign key constraints yang tidak didukung oleh dbDelta
     * Harus dipanggil setelah tabel dibuat
     */
    public static function add_foreign_keys() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_customer_branches';

        $constraints = [
            // FK to app_customers
            [
                'name' => 'fk_branch_customer',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_branch_customer
                         FOREIGN KEY (customer_id)
                         REFERENCES {$wpdb->prefix}app_customers(id)
                         ON DELETE CASCADE"
            ],
            // FK to wi_provinces
            [
                'name' => 'fk_branch_province',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_branch_province
                         FOREIGN KEY (province_id)
                         REFERENCES {$wpdb->prefix}wi_provinces(id)
                         ON DELETE RESTRICT"
            ],
            // FK to wi_regencies
            [
                'name' => 'fk_branch_regency',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_branch_regency
                         FOREIGN KEY (regency_id)
                         REFERENCES {$wpdb->prefix}wi_regencies(id)
                         ON DELETE RESTRICT"
            ],
            // FK to app_agencies (cross-plugin, nullable)
            [
                'name' => 'fk_branch_agency',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_branch_agency
                         FOREIGN KEY (agency_id)
                         REFERENCES {$wpdb->prefix}app_agencies(id)
                         ON DELETE SET NULL"
            ],
            // FK to app_agency_divisions (cross-plugin, nullable)
            [
                'name' => 'fk_branch_division',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_branch_division
                         FOREIGN KEY (division_id)
                         REFERENCES {$wpdb->prefix}app_agency_divisions(id)
                         ON DELETE SET NULL"
            ],
            // FK to app_agency_employees (cross-plugin, nullable)
            [
                'name' => 'fk_branch_inspector',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_branch_inspector
                         FOREIGN KEY (inspector_id)
                         REFERENCES {$wpdb->prefix}app_agency_employees(id)
                         ON DELETE SET NULL"
            ]
        ];

        foreach ($constraints as $constraint) {
            // Check if constraint already exists
            $constraint_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                 AND TABLE_NAME = %s
                 AND CONSTRAINT_NAME = %s",
                $table_name,
                $constraint['name']
            ));

            // If constraint exists, drop it first
            if ($constraint_exists > 0) {
                $wpdb->query("ALTER TABLE {$table_name} DROP FOREIGN KEY `{$constraint['name']}`");
            }

            // Add foreign key constraint
            $result = $wpdb->query($constraint['sql']);
            if ($result === false) {
                error_log("[BranchesDB] Failed to add FK {$constraint['name']}: " . $wpdb->last_error);
            }
        }
    }
}
