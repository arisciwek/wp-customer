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
            'pusat' => ['id' => 12, 'username' => 'bambang_sutrisno', 'display_name' => 'Bambang Sutrisno'],
            'cabang1' => ['id' => 13, 'username' => 'citra_dewi', 'display_name' => 'Citra Dewi'],
            'cabang2' => ['id' => 14, 'username' => 'dani_hermawan', 'display_name' => 'Dani Hermawan']
        ],
        2 => [  // CV Teknologi Nusantara
            'pusat' => ['id' => 15, 'username' => 'eka_purnama', 'display_name' => 'Eka Purnama'],
            'cabang1' => ['id' => 16, 'username' => 'fajar_ramadhan', 'display_name' => 'Fajar Ramadhan'],
            'cabang2' => ['id' => 17, 'username' => 'gita_lestari', 'display_name' => 'Gita Lestari']
        ],
        3 => [  // PT Sinar Abadi
            'pusat' => ['id' => 18, 'username' => 'hendri_wijaya', 'display_name' => 'Hendri Wijaya'],
            'cabang1' => ['id' => 19, 'username' => 'indah_sari', 'display_name' => 'Indah Sari'],
            'cabang2' => ['id' => 20, 'username' => 'joko_santoso', 'display_name' => 'Joko Santoso']
        ],
        4 => [  // PT Global Teknindo
            'pusat' => ['id' => 21, 'username' => 'kartika_sari', 'display_name' => 'Kartika Sari'],
            'cabang1' => ['id' => 22, 'username' => 'lukman_hakim', 'display_name' => 'Lukman Hakim'],
            'cabang2' => ['id' => 23, 'username' => 'mira_pratiwi', 'display_name' => 'Mira Pratiwi']
        ],
        5 => [  // CV Mitra Solusi
            'pusat' => ['id' => 24, 'username' => 'nanda_kusuma', 'display_name' => 'Nanda Kusuma'],
            'cabang1' => ['id' => 25, 'username' => 'oki_wibowo', 'display_name' => 'Oki Wibowo'],
            'cabang2' => ['id' => 26, 'username' => 'putri_maharani', 'display_name' => 'Putri Maharani']
        ],
        6 => [  // PT Karya Digital
            'pusat' => ['id' => 27, 'username' => 'raden_aditya', 'display_name' => 'Raden Aditya'],
            'cabang1' => ['id' => 28, 'username' => 'sinta_dewi', 'display_name' => 'Sinta Dewi'],
            'cabang2' => ['id' => 29, 'username' => 'tomi_gunawan', 'display_name' => 'Tomi Gunawan']
        ],
        7 => [  // PT Bumi Perkasa
            'pusat' => ['id' => 30, 'username' => 'utami_wahyuni', 'display_name' => 'Utami Wahyuni'],
            'cabang1' => ['id' => 31, 'username' => 'vino_pratama', 'display_name' => 'Vino Pratama'],
            'cabang2' => ['id' => 32, 'username' => 'wulan_sari', 'display_name' => 'Wulan Sari']
        ],
        8 => [  // CV Cipta Kreasi
            'pusat' => ['id' => 33, 'username' => 'yanto_santoso', 'display_name' => 'Yanto Santoso'],
            'cabang1' => ['id' => 34, 'username' => 'zara_putri', 'display_name' => 'Zara Putri'],
            'cabang2' => ['id' => 35, 'username' => 'agung_nugroho', 'display_name' => 'Agung Nugroho']
        ],
        9 => [  // PT Meta Inovasi
            'pusat' => ['id' => 36, 'username' => 'bagus_wicaksono', 'display_name' => 'Bagus Wicaksono'],
            'cabang1' => ['id' => 37, 'username' => 'candra_wijaya', 'display_name' => 'Candra Wijaya'],
            'cabang2' => ['id' => 38, 'username' => 'dina_puspita', 'display_name' => 'Dina Puspita']
        ],
        10 => [  // PT Delta Sistem
            'pusat' => ['id' => 39, 'username' => 'edi_susanto', 'display_name' => 'Edi Susanto'],
            'cabang1' => ['id' => 40, 'username' => 'fani_hartanti', 'display_name' => 'Fani Hartanti'],
            'cabang2' => ['id' => 41, 'username' => 'guntur_prasetyo', 'display_name' => 'Guntur Prasetyo']
        ]
    ];
}
