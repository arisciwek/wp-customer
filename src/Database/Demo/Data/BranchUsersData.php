<?php
/**
 * Branch Users Data
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo/Data
 * @version     1.0.10
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/Data/BranchUsersData.php
 *
 * Description: Static branch user data for demo generation.
 *              Used by WPUserGenerator and BranchDemoData.
 *              Names generated from $name_collection (different from CustomerUsersData).
 */

namespace WPCustomer\Database\Demo\Data;

defined('ABSPATH') || exit;

class BranchUsersData {
    /**
     * Name collection for generating unique branch admin names
     * All names must use words from this collection only
     * MUST BE DIFFERENT from CustomerUsersData collection
     */
    private static $name_collection = [
        'Agus', 'Bayu', 'Dedi', 'Eka', 'Feri', 'Hadi',
        'Imam', 'Jaka', 'Kiki', 'Lina', 'Maya', 'Nita',
        'Oki', 'Pandu', 'Ratna', 'Sinta', 'Taufik', 'Udin',
        'Vera', 'Wawan', 'Yudi', 'Zahra', 'Arif', 'Bella',
        'Candra', 'Dika', 'Elsa', 'Faisal', 'Gani', 'Hilda',
        'Irwan', 'Jihan', 'Kirana', 'Lukman', 'Mira', 'Nadia',
        'Oki', 'Putra', 'Rani', 'Sari'
    ];

    /**
     * Static branch user data
     * Names generated from $name_collection (2 words combination)
     * Each name is unique and uses only words from the collection
     */
    public static $data = [
        1 => [  // PT Maju Bersama
            'pusat' => ['id' => 12, 'username' => 'agus_bayu', 'display_name' => 'Agus Bayu'],
            'cabang1' => ['id' => 13, 'username' => 'dedi_eka', 'display_name' => 'Dedi Eka'],
            'cabang2' => ['id' => 14, 'username' => 'feri_hadi', 'display_name' => 'Feri Hadi']
        ],
        2 => [  // CV Teknologi Nusantara
            'pusat' => ['id' => 15, 'username' => 'imam_jaka', 'display_name' => 'Imam Jaka'],
            'cabang1' => ['id' => 16, 'username' => 'kiki_lina', 'display_name' => 'Kiki Lina'],
            'cabang2' => ['id' => 17, 'username' => 'maya_nita', 'display_name' => 'Maya Nita']
        ],
        3 => [  // PT Sinar Abadi
            'pusat' => ['id' => 18, 'username' => 'oki_pandu', 'display_name' => 'Oki Pandu'],
            'cabang1' => ['id' => 19, 'username' => 'ratna_sinta', 'display_name' => 'Ratna Sinta'],
            'cabang2' => ['id' => 20, 'username' => 'taufik_udin', 'display_name' => 'Taufik Udin']
        ],
        4 => [  // PT Global Teknindo
            'pusat' => ['id' => 21, 'username' => 'vera_wawan', 'display_name' => 'Vera Wawan'],
            'cabang1' => ['id' => 22, 'username' => 'yudi_zahra', 'display_name' => 'Yudi Zahra'],
            'cabang2' => ['id' => 23, 'username' => 'arif_bella', 'display_name' => 'Arif Bella']
        ],
        5 => [  // CV Mitra Solusi
            'pusat' => ['id' => 24, 'username' => 'candra_dika', 'display_name' => 'Candra Dika'],
            'cabang1' => ['id' => 25, 'username' => 'elsa_faisal', 'display_name' => 'Elsa Faisal'],
            'cabang2' => ['id' => 26, 'username' => 'gani_hilda', 'display_name' => 'Gani Hilda']
        ],
        6 => [  // PT Karya Digital
            'pusat' => ['id' => 27, 'username' => 'irwan_jihan', 'display_name' => 'Irwan Jihan'],
            'cabang1' => ['id' => 28, 'username' => 'kirana_lukman', 'display_name' => 'Kirana Lukman'],
            'cabang2' => ['id' => 29, 'username' => 'mira_nadia', 'display_name' => 'Mira Nadia']
        ],
        7 => [  // PT Bumi Perkasa
            'pusat' => ['id' => 30, 'username' => 'putra_rani', 'display_name' => 'Putra Rani'],
            'cabang1' => ['id' => 31, 'username' => 'sari_agus', 'display_name' => 'Sari Agus'],
            'cabang2' => ['id' => 32, 'username' => 'bayu_dedi', 'display_name' => 'Bayu Dedi']
        ],
        8 => [  // CV Cipta Kreasi
            'pusat' => ['id' => 33, 'username' => 'eka_feri', 'display_name' => 'Eka Feri'],
            'cabang1' => ['id' => 34, 'username' => 'hadi_imam', 'display_name' => 'Hadi Imam'],
            'cabang2' => ['id' => 35, 'username' => 'jaka_kiki', 'display_name' => 'Jaka Kiki']
        ],
        9 => [  // PT Meta Inovasi
            'pusat' => ['id' => 36, 'username' => 'lina_maya', 'display_name' => 'Lina Maya'],
            'cabang1' => ['id' => 37, 'username' => 'nita_pandu', 'display_name' => 'Nita Pandu'],
            'cabang2' => ['id' => 38, 'username' => 'ratna_taufik', 'display_name' => 'Ratna Taufik']
        ],
        10 => [  // PT Delta Sistem
            'pusat' => ['id' => 39, 'username' => 'udin_vera', 'display_name' => 'Udin Vera'],
            'cabang1' => ['id' => 40, 'username' => 'wawan_yudi', 'display_name' => 'Wawan Yudi'],
            'cabang2' => ['id' => 41, 'username' => 'zahra_arif', 'display_name' => 'Zahra Arif']
        ]
    ];

    /**
     * Extra branch user data for testing assign inspector functionality
     * IDs start from 50 to avoid conflicts with regular branch users
     * Generate up to 20 extra users for extra branches
     */
    public static $extra_branch_users = [
        ['id' => 50, 'username' => 'bella_candra', 'display_name' => 'Bella Candra'],
        ['id' => 51, 'username' => 'dika_elsa', 'display_name' => 'Dika Elsa'],
        ['id' => 52, 'username' => 'faisal_gani', 'display_name' => 'Faisal Gani'],
        ['id' => 53, 'username' => 'hilda_irwan', 'display_name' => 'Hilda Irwan'],
        ['id' => 54, 'username' => 'jihan_kirana', 'display_name' => 'Jihan Kirana'],
        ['id' => 55, 'username' => 'nadia_putra', 'display_name' => 'Nadia Putra'],
        ['id' => 56, 'username' => 'rani_bayu', 'display_name' => 'Rani Bayu'],
        ['id' => 57, 'username' => 'agus_dedi', 'display_name' => 'Agus Dedi'],
        ['id' => 58, 'username' => 'feri_imam', 'display_name' => 'Feri Imam'],
        ['id' => 59, 'username' => 'jaka_lina', 'display_name' => 'Jaka Lina'],
        ['id' => 60, 'username' => 'maya_oki', 'display_name' => 'Maya Oki'],
        ['id' => 61, 'username' => 'pandu_sinta', 'display_name' => 'Pandu Sinta'],
        ['id' => 62, 'username' => 'taufik_vera', 'display_name' => 'Taufik Vera'],
        ['id' => 63, 'username' => 'wawan_zahra', 'display_name' => 'Wawan Zahra'],
        ['id' => 64, 'username' => 'arif_dika', 'display_name' => 'Arif Dika'],
        ['id' => 65, 'username' => 'elsa_gani', 'display_name' => 'Elsa Gani'],
        ['id' => 66, 'username' => 'hilda_kirana', 'display_name' => 'Hilda Kirana'],
        ['id' => 67, 'username' => 'lukman_nadia', 'display_name' => 'Lukman Nadia'],
        ['id' => 68, 'username' => 'putra_sari', 'display_name' => 'Putra Sari'],
        ['id' => 69, 'username' => 'bayu_hadi', 'display_name' => 'Bayu Hadi']
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
     * @param string $name Full name to validate (e.g., "Agus Bayu")
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
