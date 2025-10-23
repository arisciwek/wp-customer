<?php
/**
* Membership Features & Groups Table Schema
*
* @package     WP_Customer
* @subpackage  Database/Tables
* @version     1.0.11
* @author      arisciwek
* 
* Path: /wp-customer/src/Database/Tables/CustomerMembershipFeaturesDB.php
* 
* Description: Mendefinisikan struktur tabel feature dan group untuk membership.
*              Includes:
*              - Tabel groups untuk mengelompokkan fitur
*              - Tabel features dengan foreign key ke groups
*              - Index dan constraints untuk optimasi query
*              - Support untuk JSON metadata
*              
* Dependencies:
* - WordPress database ($wpdb)
* - MySQL 5.7+ untuk JSON support
* 
* Changelog:
* 1.1.2 - 2025-02-14
* - Added groups table
* - Updated features table with group foreign key
* - Added indexes for optimization
* - Enhanced documentation
* 
* 1.0.1 - 2025-02-11
* - Updated to use JSON metadata structure
* - Enhanced feature grouping system
* - Added more detailed feature attributes
* - Improved error handling
* 
* 1.0.0 - 2025-01-27
* - Initial version
*/

namespace WPCustomer\Database\Tables;

defined('ABSPATH') || exit;

class CustomerMembershipFeaturesDB {

    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabel untuk groups
        $table_groups = $wpdb->prefix . 'app_customer_membership_feature_groups';
        $table_features = $wpdb->prefix . 'app_customer_membership_features';

        return "
            CREATE TABLE {$table_groups} (
                id bigint(20) UNSIGNED NOT NULL auto_increment,
                name varchar(50) NOT NULL,
                slug varchar(50) NOT NULL,
                capability_group varchar(50) NOT NULL,
                description text NULL,
                sort_order int NOT NULL DEFAULT 0,
                created_by bigint(20) NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                status enum('active','inactive') NOT NULL DEFAULT 'active',
                PRIMARY KEY  (id),
                UNIQUE KEY slug (slug),
                KEY status (status),
                KEY capability_group (capability_group),
                KEY sort_order_index (sort_order)
            ) $charset_collate;

            CREATE TABLE {$table_features} (
                id bigint(20) UNSIGNED NOT NULL auto_increment,
                field_name varchar(50) NOT NULL,
                group_id bigint(20) UNSIGNED NOT NULL,
                metadata JSON NOT NULL,
                settings JSON NOT NULL,
                sort_order int NOT NULL DEFAULT 0,
                created_by bigint(20) NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                status enum('active','inactive') NOT NULL DEFAULT 'active',
                PRIMARY KEY  (id),
                UNIQUE KEY field_name (field_name),
                KEY status (status),
                KEY group_id_index (group_id),
                KEY created_by_index (created_by),
                KEY sort_order_index (sort_order)
            ) $charset_collate;";
    }

}
