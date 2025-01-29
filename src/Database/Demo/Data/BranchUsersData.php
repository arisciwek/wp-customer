<?php
/**
 * Branch Users Data
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo/Data
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/Data/BranchUsersData.php
 *
 * Description: Static branch user data for demo generation.
 *              Used by WPUserGenerator and BranchDemoData.
 */

namespace WPCustomer\Database\Demo\Data;

defined('ABSPATH') || exit;

class BranchUsersData {
    public static $data = [
        1 => [  // PT Maju Bersama
            'pusat' => ['id' => 12, 'username' => 'maju_pusat', 'display_name' => 'Admin Pusat Maju Bersama'],
            'cabang1' => ['id' => 13, 'username' => 'maju_cabang1', 'display_name' => 'Admin Cabang 1 Maju Bersama'],
            'cabang2' => ['id' => 14, 'username' => 'maju_cabang2', 'display_name' => 'Admin Cabang 2 Maju Bersama']
        ],
        2 => [  // CV Teknologi Nusantara
            'pusat' => ['id' => 15, 'username' => 'teknologi_pusat', 'display_name' => 'Admin Pusat Teknologi Nusantara'],
            'cabang1' => ['id' => 16, 'username' => 'teknologi_cabang1', 'display_name' => 'Admin Cabang 1 Teknologi Nusantara'],
            'cabang2' => ['id' => 17, 'username' => 'teknologi_cabang2', 'display_name' => 'Admin Cabang 2 Teknologi Nusantara']
        ],
        3 => [  // PT Sinar Abadi
            'pusat' => ['id' => 18, 'username' => 'sinar_pusat', 'display_name' => 'Admin Pusat Sinar Abadi'],
            'cabang1' => ['id' => 19, 'username' => 'sinar_cabang1', 'display_name' => 'Admin Cabang 1 Sinar Abadi'],
            'cabang2' => ['id' => 20, 'username' => 'sinar_cabang2', 'display_name' => 'Admin Cabang 2 Sinar Abadi']
        ],
        4 => [  // PT Global Teknindo
            'pusat' => ['id' => 21, 'username' => 'global_pusat', 'display_name' => 'Admin Pusat Global Teknindo'],
            'cabang1' => ['id' => 22, 'username' => 'global_cabang1', 'display_name' => 'Admin Cabang 1 Global Teknindo'],
            'cabang2' => ['id' => 23, 'username' => 'global_cabang2', 'display_name' => 'Admin Cabang 2 Global Teknindo']
        ],
        5 => [  // CV Mitra Solusi
            'pusat' => ['id' => 24, 'username' => 'mitra_pusat', 'display_name' => 'Admin Pusat Mitra Solusi'],
            'cabang1' => ['id' => 25, 'username' => 'mitra_cabang1', 'display_name' => 'Admin Cabang 1 Mitra Solusi'],
            'cabang2' => ['id' => 26, 'username' => 'mitra_cabang2', 'display_name' => 'Admin Cabang 2 Mitra Solusi']
        ],
        6 => [  // PT Karya Digital
            'pusat' => ['id' => 27, 'username' => 'karya_pusat', 'display_name' => 'Admin Pusat Karya Digital'],
            'cabang1' => ['id' => 28, 'username' => 'karya_cabang1', 'display_name' => 'Admin Cabang 1 Karya Digital'],
            'cabang2' => ['id' => 29, 'username' => 'karya_cabang2', 'display_name' => 'Admin Cabang 2 Karya Digital']
        ],
        7 => [  // PT Bumi Perkasa
            'pusat' => ['id' => 30, 'username' => 'bumi_pusat', 'display_name' => 'Admin Pusat Bumi Perkasa'],
            'cabang1' => ['id' => 31, 'username' => 'bumi_cabang1', 'display_name' => 'Admin Cabang 1 Bumi Perkasa'],
            'cabang2' => ['id' => 32, 'username' => 'bumi_cabang2', 'display_name' => 'Admin Cabang 2 Bumi Perkasa']
        ],
        8 => [  // CV Cipta Kreasi
            'pusat' => ['id' => 33, 'username' => 'cipta_pusat', 'display_name' => 'Admin Pusat Cipta Kreasi'],
            'cabang1' => ['id' => 34, 'username' => 'cipta_cabang1', 'display_name' => 'Admin Cabang 1 Cipta Kreasi'],
            'cabang2' => ['id' => 35, 'username' => 'cipta_cabang2', 'display_name' => 'Admin Cabang 2 Cipta Kreasi']
        ],
        9 => [  // PT Meta Inovasi
            'pusat' => ['id' => 36, 'username' => 'meta_pusat', 'display_name' => 'Admin Pusat Meta Inovasi'],
            'cabang1' => ['id' => 37, 'username' => 'meta_cabang1', 'display_name' => 'Admin Cabang 1 Meta Inovasi'],
            'cabang2' => ['id' => 38, 'username' => 'meta_cabang2', 'display_name' => 'Admin Cabang 2 Meta Inovasi']
        ],
        10 => [  // PT Delta Sistem
            'pusat' => ['id' => 39, 'username' => 'delta_pusat', 'display_name' => 'Admin Pusat Delta Sistem'],
            'cabang1' => ['id' => 40, 'username' => 'delta_cabang1', 'display_name' => 'Admin Cabang 1 Delta Sistem'],
            'cabang2' => ['id' => 41, 'username' => 'delta_cabang2', 'display_name' => 'Admin Cabang 2 Delta Sistem']
        ]
    ];
}
