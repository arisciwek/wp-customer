<?php
/**
 * Customer Users Data
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo/Data
 * @version     1.0.10
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
    /**
     * Name collection for generating unique customer admin names
     * All names must use words from this collection only
     */
    private static $name_collection = [
        'Andi', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fajar',
        'Gita', 'Hari', 'Indra', 'Joko', 'Kartika', 'Lestari',
        'Mawar', 'Nina', 'Omar', 'Putri', 'Qori', 'Rini',
        'Sari', 'Tono', 'Umar', 'Vina', 'Wati', 'Yanto'
    ];

    /**
     * Static customer user data
     * Names generated from $name_collection (2 words combination)
     * Each name is unique and uses only words from the collection
     */
    public static $data = [
        ['id' => 2, 'username' => 'andi_budi', 'display_name' => 'Andi Budi', 'role' => 'customer'],
        ['id' => 3, 'username' => 'citra_dewi', 'display_name' => 'Citra Dewi', 'role' => 'customer'],
        ['id' => 4, 'username' => 'eko_fajar', 'display_name' => 'Eko Fajar', 'role' => 'customer'],
        ['id' => 5, 'username' => 'gita_hari', 'display_name' => 'Gita Hari', 'role' => 'customer'],
        ['id' => 6, 'username' => 'indra_joko', 'display_name' => 'Indra Joko', 'role' => 'customer'],
        ['id' => 7, 'username' => 'kartika_lestari', 'display_name' => 'Kartika Lestari', 'role' => 'customer'],
        ['id' => 8, 'username' => 'mawar_nina', 'display_name' => 'Mawar Nina', 'role' => 'customer'],
        ['id' => 9, 'username' => 'omar_putri', 'display_name' => 'Omar Putri', 'role' => 'customer'],
        ['id' => 10, 'username' => 'qori_rini', 'display_name' => 'Qori Rini', 'role' => 'customer'],
        ['id' => 11, 'username' => 'sari_tono', 'display_name' => 'Sari Tono', 'role' => 'customer']
    ];

    /**
     * Get name collection
     *
     * @return array Collection of name words
     */
    public static function getNameCollection(): array {
        return self::$name_collection;
    }

    /**
     * Validate if a name uses only words from collection
     *
     * @param string $name Full name to validate (e.g., "Andi Budi")
     * @return bool True if all words are from collection
     */
    public static function isValidName(string $name): bool {
        $words = explode(' ', $name);
        foreach ($words as $word) {
            if (!in_array($word, self::$name_collection)) {
                return false;
            }
        }
        return true;
    }
}
