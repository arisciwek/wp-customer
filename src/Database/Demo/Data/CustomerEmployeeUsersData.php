<?php
/**
 * Employee Users Data
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo/Data
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/Data/CustomerEmployeeUsersData.php
 *
 * Description: Static employee user data for demo generation.
 *              Used by WPUserGenerator and CustomerEmployeeDemoData.
 *              60 users total (2 per branch × 30 branches)
 *              User IDs: 42-101
 */

namespace WPCustomer\Database\Demo\Data;

defined('ABSPATH') || exit;

class CustomerEmployeeUsersData {
    // Constants for user ID ranges
    const USER_ID_START = 42;
    const USER_ID_END = 101;

    public static $data = [
        // Customer 1 (PT Maju Bersama) - Branch 1 (Pusat)
        42 => [
            'id' => 42,
            'customer_id' => 1,
            'branch_id' => 1,
            'username' => 'finance_maju1',
            'display_name' => 'Aditya Pratama',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        43 => [
            'id' => 43,
            'customer_id' => 1,
            'branch_id' => 1,
            'username' => 'legal_maju1',
            'display_name' => 'Sarah Wijaya',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Customer 1 (PT Maju Bersama) - Branch 2 (Cabang 1)
        44 => [
            'id' => 44,
            'customer_id' => 1,
            'branch_id' => 2,
            'username' => 'finance_maju2',
            'display_name' => 'Bima Setiawan',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        45 => [
            'id' => 45,
            'customer_id' => 1,
            'branch_id' => 2,
            'username' => 'operation_maju2',
            'display_name' => 'Diana Puspita',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        // Customer 1 (PT Maju Bersama) - Branch 3 (Cabang 2)
        46 => [
            'id' => 46,
            'customer_id' => 1,
            'branch_id' => 3,
            'username' => 'operation_maju3',
            'display_name' => 'Eko Wibowo',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        47 => [
            'id' => 47,
            'customer_id' => 1,
            'branch_id' => 3,
            'username' => 'finance_maju3',
            'display_name' => 'Fina Sari',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Customer 2 (CV Teknologi Nusantara) - Branch 1 (Pusat)
        48 => [
            'id' => 48,
            'customer_id' => 2,
            'branch_id' => 4,
            'username' => 'legal_tekno1',
            'display_name' => 'Gunawan Santoso',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        49 => [
            'id' => 49,
            'customer_id' => 2,
            'branch_id' => 4,
            'username' => 'finance_tekno1',
            'display_name' => 'Hana Permata',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Customer 2 (CV Teknologi Nusantara) - Branch 2 (Cabang 1)
        50 => [
            'id' => 50,
            'customer_id' => 2,
            'branch_id' => 5,
            'username' => 'operation_tekno2',
            'display_name' => 'Irfan Hakim',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        51 => [
            'id' => 51,
            'customer_id' => 2,
            'branch_id' => 5,
            'username' => 'purchase_tekno2',
            'display_name' => 'Julia Putri',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Customer 2 (CV Teknologi Nusantara) - Branch 3 (Cabang 2)
        52 => [
            'id' => 52,
            'customer_id' => 2,
            'branch_id' => 6,
            'username' => 'finance_tekno3',
            'display_name' => 'Krisna Wijaya',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        53 => [
            'id' => 53,
            'customer_id' => 2,
            'branch_id' => 6,
            'username' => 'legal_tekno3',
            'display_name' => 'Luna Safitri',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Customer 3 (PT Sinar Abadi) - Branch 1 (Pusat)
        54 => [
            'id' => 54,
            'customer_id' => 3,
            'branch_id' => 7,
            'username' => 'operation_sinar1',
            'display_name' => 'Mario Gunawan',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        55 => [
            'id' => 55,
            'customer_id' => 3,
            'branch_id' => 7,
            'username' => 'finance_sinar1',
            'display_name' => 'Nadia Kusuma',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Customer 3 (PT Sinar Abadi) - Branch 2 (Cabang 1)
        56 => [
            'id' => 56,
            'customer_id' => 3,
            'branch_id' => 8,
            'username' => 'legal_sinar2',
            'display_name' => 'Oscar Pradana',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        57 => [
            'id' => 57,
            'customer_id' => 3,
            'branch_id' => 8,
            'username' => 'operation_sinar2',
            'display_name' => 'Putri Handayani',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Customer 3 (PT Sinar Abadi) - Branch 3 (Cabang 2)
        58 => [
            'id' => 58,
            'customer_id' => 3,
            'branch_id' => 9,
            'username' => 'finance_sinar3',
            'display_name' => 'Qori Rahman',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        59 => [
            'id' => 59,
            'customer_id' => 3,
            'branch_id' => 9,
            'username' => 'legal_sinar3',
            'display_name' => 'Ratih Purnama',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        // Customer 4 (PT Global Teknindo) - Branch 1 (Pusat)
        60 => [
            'id' => 60,
            'customer_id' => 4,
            'branch_id' => 10,
            'username' => 'operation_global1',
            'display_name' => 'Surya Pratama',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        61 => [
            'id' => 61,
            'customer_id' => 4,
            'branch_id' => 10,
            'username' => 'finance_global1',
            'display_name' => 'Tania Wijaya',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Customer 6 (PT Karya Digital) - Branch 1 (Pusat)
        72 => [
            'id' => 72,
            'customer_id' => 6,
            'branch_id' => 16,
            'username' => 'finance_karya1',
            'display_name' => 'Eko Santoso',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        73 => [
            'id' => 73,
            'customer_id' => 6,
            'branch_id' => 16,
            'username' => 'legal_karya1',
            'display_name' => 'Fitri Wulandari',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Customer 6 (PT Karya Digital) - Branch 2 (Cabang 1)
        74 => [
            'id' => 74,
            'customer_id' => 6,
            'branch_id' => 17,
            'username' => 'operation_karya2',
            'display_name' => 'Galih Prasetyo',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        75 => [
            'id' => 75,
            'customer_id' => 6,
            'branch_id' => 17,
            'username' => 'finance_karya2',
            'display_name' => 'Hesti Kusuma',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Customer 6 (PT Karya Digital) - Branch 3 (Cabang 2)
        76 => [
            'id' => 76,
            'customer_id' => 6,
            'branch_id' => 18,
            'username' => 'legal_karya3',
            'display_name' => 'Indra Wijaya',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        77 => [
            'id' => 77,
            'customer_id' => 6,
            'branch_id' => 18,
            'username' => 'operation_karya3',
            'display_name' => 'Jasmine Putri',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Customer 7 (PT Bumi Perkasa) - Branch 1 (Pusat)
        78 => [
            'id' => 78,
            'customer_id' => 7,
            'branch_id' => 19,
            'username' => 'finance_bumi1',
            'display_name' => 'Kevin Sutanto',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        79 => [
            'id' => 79,
            'customer_id' => 7,
            'branch_id' => 19,
            'username' => 'legal_bumi1',
            'display_name' => 'Lina Permata',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Customer 7 (PT Bumi Perkasa) - Branch 2 (Cabang 1)
        80 => [
            'id' => 80,
            'customer_id' => 7,
            'branch_id' => 20,
            'username' => 'operation_bumi2',
            'display_name' => 'Michael Wirawan',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        81 => [
            'id' => 81,
            'customer_id' => 7,
            'branch_id' => 20,
            'username' => 'finance_bumi2',
            'display_name' => 'Nadira Sari',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Customer 7 (PT Bumi Perkasa) - Branch 3 (Cabang 2)
        82 => [
            'id' => 82,
            'customer_id' => 7,
            'branch_id' => 21,
            'username' => 'legal_bumi3',
            'display_name' => 'Oscar Putra',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        83 => [
            'id' => 83,
            'customer_id' => 7,
            'branch_id' => 21,
            'username' => 'operation_bumi3',
            'display_name' => 'Patricia Dewi',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Customer 8 (CV Cipta Kreasi) - Branch 1 (Pusat)
        84 => [
            'id' => 84,
            'customer_id' => 8,
            'branch_id' => 22,
            'username' => 'finance_cipta1',
            'display_name' => 'Qori Susanto',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        85 => [
            'id' => 85,
            'customer_id' => 8,
            'branch_id' => 22,
            'username' => 'legal_cipta1',
            'display_name' => 'Rahma Wati',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Customer 8 (CV Cipta Kreasi) - Branch 2 (Cabang 1)
        86 => [
            'id' => 86,
            'customer_id' => 8,
            'branch_id' => 23,
            'username' => 'operation_cipta2',
            'display_name' => 'Surya Darma',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        87 => [
            'id' => 87,
            'customer_id' => 8,
            'branch_id' => 23,
            'username' => 'finance_cipta2',
            'display_name' => 'Tania Putri',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Customer 8 (CV Cipta Kreasi) - Branch 3 (Cabang 2)
        88 => [
            'id' => 88,
            'customer_id' => 8,
            'branch_id' => 24,
            'username' => 'legal_cipta3',
            'display_name' => 'Umar Prasetyo',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        89 => [
            'id' => 89,
            'customer_id' => 8,
            'branch_id' => 24,
            'username' => 'operation_cipta3',
            'display_name' => 'Vina Kusuma',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Customer 9 (PT Meta Inovasi) - Branch 1 (Pusat)
        90 => [
            'id' => 90,
            'customer_id' => 9,
            'branch_id' => 25,
            'username' => 'finance_meta1',
            'display_name' => 'Wayan Sudiarta',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        91 => [
            'id' => 91,
            'customer_id' => 9,
            'branch_id' => 25,
            'username' => 'legal_meta1',
            'display_name' => 'Xena Maharani',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Customer 9 (PT Meta Inovasi) - Branch 2 (Cabang 1)
        92 => [
            'id' => 92,
            'customer_id' => 9,
            'branch_id' => 26,
            'username' => 'operation_meta2',
            'display_name' => 'Yoga Pratama',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        93 => [
            'id' => 93,
            'customer_id' => 9,
            'branch_id' => 26,
            'username' => 'finance_meta2',
            'display_name' => 'Zahra Permata',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Customer 9 (PT Meta Inovasi) - Branch 3 (Cabang 2)
        94 => [
            'id' => 94,
            'customer_id' => 9,
            'branch_id' => 27,
            'username' => 'legal_meta3',
            'display_name' => 'Adi Wijaya',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        95 => [
            'id' => 95,
            'customer_id' => 9,
            'branch_id' => 27,
            'username' => 'operation_meta3',
            'display_name' => 'Bella Safina',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Customer 10 (PT Delta Sistem) - Branch 1 (Pusat)
        96 => [
            'id' => 96,
            'customer_id' => 10,
            'branch_id' => 28,
            'username' => 'finance_delta1',
            'display_name' => 'Candra Kusuma',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        97 => [
            'id' => 97,
            'customer_id' => 10,
            'branch_id' => 28,
            'username' => 'legal_delta1',
            'display_name' => 'Devi Puspita',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Customer 10 (PT Delta Sistem) - Branch 2 (Cabang 1)
        98 => [
            'id' => 98,
            'customer_id' => 10,
            'branch_id' => 29,
            'username' => 'operation_delta2',
            'display_name' => 'Eka Prasetya',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        99 => [
            'id' => 99,
            'customer_id' => 10,
            'branch_id' => 29,
            'username' => 'finance_delta2',
            'display_name' => 'Farah Sari',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Customer 10 (PT Delta Sistem) - Branch 3 (Cabang 2)
        100 => [
            'id' => 100,
            'customer_id' => 10,
            'branch_id' => 30,
            'username' => 'legal_delta3',
            'display_name' => 'Galang Wicaksono',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        101 => [
            'id' => 101,
            'customer_id' => 10,
            'branch_id' => 30,
            'username' => 'operation_delta3',
            'display_name' => 'Hana Pertiwi',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
    ];
}
