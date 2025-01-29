<?php
/**
 * Customer Users Data
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo/Data
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/Data/CustomerUsersData.php
 *
 * Description: Static customer user data for demo generation.
 *              Used by WPUserGenerator and CustomerDemoData.
 */

namespace WPCustomer\Database\Demo\Data;

defined('ABSPATH') || exit;

class CustomerUsersData {
    public static $data = [
        ['id' => 2, 'username' => 'budi_santoso', 'display_name' => 'Budi Santoso', 'role' => 'customer'],
        ['id' => 3, 'username' => 'dewi_kartika', 'display_name' => 'Dewi Kartika', 'role' => 'customer'],
        ['id' => 4, 'username' => 'ahmad_hidayat', 'display_name' => 'Ahmad Hidayat', 'role' => 'customer'],
        ['id' => 5, 'username' => 'siti_rahayu', 'display_name' => 'Siti Rahayu', 'role' => 'customer'],
        ['id' => 6, 'username' => 'rudi_hermawan', 'display_name' => 'Rudi Hermawan', 'role' => 'customer'],
        ['id' => 7, 'username' => 'nina_kusuma', 'display_name' => 'Nina Kusuma', 'role' => 'customer'],
        ['id' => 8, 'username' => 'eko_prasetyo', 'display_name' => 'Eko Prasetyo', 'role' => 'customer'],
        ['id' => 9, 'username' => 'maya_wijaya', 'display_name' => 'Maya Wijaya', 'role' => 'customer'],
        ['id' => 10, 'username' => 'dian_pertiwi', 'display_name' => 'Dian Pertiwi', 'role' => 'customer'],
        ['id' => 11, 'username' => 'agus_suryanto', 'display_name' => 'Agus Suryanto', 'role' => 'customer']
    ];
}
