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
 *              User IDs: 70-129
 *              Names generated from $name_collection (different from Customer & Branch collections)
 */

namespace WPCustomer\Database\Demo\Data;

defined('ABSPATH') || exit;

class CustomerEmployeeUsersData {
    // Constants for user ID ranges
    const USER_ID_START = 70;
    const USER_ID_END = 129;

    /**
     * Name collection for generating unique employee names
     * All names must use words from this collection only
     * MUST BE DIFFERENT from CustomerUsersData and BranchUsersData collections
     */
    private static $name_collection = [
        'Abdul', 'Amir', 'Anwar', 'Asep', 'Bambang', 'Bagas',
        'Cahya', 'Cindy', 'Danu', 'Dimas', 'Erna', 'Erik',
        'Farhan', 'Fitria', 'Galuh', 'Gema', 'Halim', 'Hendra',
        'Indah', 'Iwan', 'Joko', 'Jenni', 'Khalid', 'Kania',
        'Laras', 'Lutfi', 'Mulyadi', 'Marina', 'Novianti', 'Nur',
        'Oky', 'Olivia', 'Prabu', 'Priska', 'Qomar', 'Qonita',
        'Reza', 'Riana', 'Salim', 'Silvia', 'Teguh', 'Tiara',
        'Usman', 'Umi', 'Vikri', 'Vivi', 'Wahyu', 'Widya',
        'Yayan', 'Yesi', 'Zulkifli', 'Zainal', 'Ayu', 'Bima',
        'Citra', 'Doni', 'Evi', 'Fitra', 'Gunawan', 'Hani'
    ];

    public static $data = [
        // Customer 1 (PT Maju Bersama) - Branch 1 (Pusat)
        70 => [
            'id' => 70,
            'customer_id' => 1,
            'branch_id' => 1,
            'username' => 'finance_maju_1',
            'display_name' => 'Abdul Amir',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        71 => [
            'id' => 71,
            'customer_id' => 1,
            'branch_id' => 1,
            'username' => 'legal_maju_1',
            'display_name' => 'Anwar Asep',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],

        // Customer 1 (PT Maju Bersama) - Branch 2 (Cabang 1)
        72 => [
            'id' => 72,
            'customer_id' => 1,
            'branch_id' => 2,
            'username' => 'operation_maju_2',
            'display_name' => 'Bambang Bagas',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        73 => [
            'id' => 73,
            'customer_id' => 1,
            'branch_id' => 2,
            'username' => 'purchase_maju_2',
            'display_name' => 'Cahya Cindy',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],

        // Customer 1 (PT Maju Bersama) - Branch 3 (Cabang 2)
        74 => [
            'id' => 74,
            'customer_id' => 1,
            'branch_id' => 3,
            'username' => 'finance_maju_3',
            'display_name' => 'Danu Dimas',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        75 => [
            'id' => 75,
            'customer_id' => 1,
            'branch_id' => 3,
            'username' => 'legal_maju_3',
            'display_name' => 'Erna Erik',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],

        // Customer 2 (CV Teknologi Nusantara) - Branch 4 (Pusat)
        76 => [
            'id' => 76,
            'customer_id' => 2,
            'branch_id' => 4,
            'username' => 'operation_tekno_4',
            'display_name' => 'Farhan Fitria',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        77 => [
            'id' => 77,
            'customer_id' => 2,
            'branch_id' => 4,
            'username' => 'purchase_tekno_4',
            'display_name' => 'Galuh Gema',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],

        // Customer 2 (CV Teknologi Nusantara) - Branch 5 (Cabang 1)
        78 => [
            'id' => 78,
            'customer_id' => 2,
            'branch_id' => 5,
            'username' => 'finance_tekno_5',
            'display_name' => 'Halim Hendra',
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
            'customer_id' => 2,
            'branch_id' => 5,
            'username' => 'legal_tekno_5',
            'display_name' => 'Indah Iwan',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],

        // Customer 2 (CV Teknologi Nusantara) - Branch 6 (Cabang 2)
        80 => [
            'id' => 80,
            'customer_id' => 2,
            'branch_id' => 6,
            'username' => 'operation_tekno_6',
            'display_name' => 'Joko Jenni',
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
            'customer_id' => 2,
            'branch_id' => 6,
            'username' => 'purchase_tekno_6',
            'display_name' => 'Khalid Kania',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],

        // Customer 3 (PT Sinar Abadi) - Branch 7 (Pusat)
        82 => [
            'id' => 82,
            'customer_id' => 3,
            'branch_id' => 7,
            'username' => 'finance_sinar_7',
            'display_name' => 'Laras Lutfi',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        83 => [
            'id' => 83,
            'customer_id' => 3,
            'branch_id' => 7,
            'username' => 'legal_sinar_7',
            'display_name' => 'Mulyadi Marina',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],

        // Customer 3 (PT Sinar Abadi) - Branch 8 (Cabang 1)
        84 => [
            'id' => 84,
            'customer_id' => 3,
            'branch_id' => 8,
            'username' => 'operation_sinar_8',
            'display_name' => 'Novianti Nur',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        85 => [
            'id' => 85,
            'customer_id' => 3,
            'branch_id' => 8,
            'username' => 'purchase_sinar_8',
            'display_name' => 'Oky Olivia',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],

        // Customer 3 (PT Sinar Abadi) - Branch 9 (Cabang 2)
        86 => [
            'id' => 86,
            'customer_id' => 3,
            'branch_id' => 9,
            'username' => 'finance_sinar_9',
            'display_name' => 'Prabu Priska',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        87 => [
            'id' => 87,
            'customer_id' => 3,
            'branch_id' => 9,
            'username' => 'legal_sinar_9',
            'display_name' => 'Qomar Qonita',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],

        // Customer 4 (PT Global Teknindo) - Branch 10 (Pusat)
        88 => [
            'id' => 88,
            'customer_id' => 4,
            'branch_id' => 10,
            'username' => 'operation_global_10',
            'display_name' => 'Reza Riana',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        89 => [
            'id' => 89,
            'customer_id' => 4,
            'branch_id' => 10,
            'username' => 'purchase_global_10',
            'display_name' => 'Salim Silvia',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],

        // Customer 4 (PT Global Teknindo) - Branch 11 (Cabang 1)
        90 => [
            'id' => 90,
            'customer_id' => 4,
            'branch_id' => 11,
            'username' => 'finance_global_11',
            'display_name' => 'Teguh Tiara',
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
            'customer_id' => 4,
            'branch_id' => 11,
            'username' => 'legal_global_11',
            'display_name' => 'Usman Umi',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],

        // Customer 4 (PT Global Teknindo) - Branch 12 (Cabang 2)
        92 => [
            'id' => 92,
            'customer_id' => 4,
            'branch_id' => 12,
            'username' => 'operation_global_12',
            'display_name' => 'Vikri Vivi',
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
            'customer_id' => 4,
            'branch_id' => 12,
            'username' => 'purchase_global_12',
            'display_name' => 'Wahyu Widya',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],

        // Customer 5 (PT Mitra Sejahtera) - Branch 13 (Pusat)
        94 => [
            'id' => 94,
            'customer_id' => 5,
            'branch_id' => 13,
            'username' => 'finance_mitra_13',
            'display_name' => 'Yayan Yesi',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        95 => [
            'id' => 95,
            'customer_id' => 5,
            'branch_id' => 13,
            'username' => 'legal_mitra_13',
            'display_name' => 'Zulkifli Zainal',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],

        // Customer 5 (PT Mitra Sejahtera) - Branch 14 (Cabang 1)
        96 => [
            'id' => 96,
            'customer_id' => 5,
            'branch_id' => 14,
            'username' => 'operation_mitra_14',
            'display_name' => 'Ayu Bima',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        97 => [
            'id' => 97,
            'customer_id' => 5,
            'branch_id' => 14,
            'username' => 'purchase_mitra_14',
            'display_name' => 'Citra Doni',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],

        // Customer 5 (PT Mitra Sejahtera) - Branch 15 (Cabang 2)
        98 => [
            'id' => 98,
            'customer_id' => 5,
            'branch_id' => 15,
            'username' => 'finance_mitra_15',
            'display_name' => 'Evi Fitra',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        99 => [
            'id' => 99,
            'customer_id' => 5,
            'branch_id' => 15,
            'username' => 'legal_mitra_15',
            'display_name' => 'Gunawan Hani',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],

        // Customer 6 (PT Karya Digital) - Branch 16 (Pusat)
        100 => [
            'id' => 100,
            'customer_id' => 6,
            'branch_id' => 16,
            'username' => 'operation_karya_16',
            'display_name' => 'Abdul Bagas',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        101 => [
            'id' => 101,
            'customer_id' => 6,
            'branch_id' => 16,
            'username' => 'purchase_karya_16',
            'display_name' => 'Amir Cindy',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],

        // Customer 6 (PT Karya Digital) - Branch 17 (Cabang 1)
        102 => [
            'id' => 102,
            'customer_id' => 6,
            'branch_id' => 17,
            'username' => 'finance_karya_17',
            'display_name' => 'Anwar Dimas',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        103 => [
            'id' => 103,
            'customer_id' => 6,
            'branch_id' => 17,
            'username' => 'legal_karya_17',
            'display_name' => 'Asep Erik',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],

        // Customer 6 (PT Karya Digital) - Branch 18 (Cabang 2)
        104 => [
            'id' => 104,
            'customer_id' => 6,
            'branch_id' => 18,
            'username' => 'operation_karya_18',
            'display_name' => 'Bambang Fitria',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        105 => [
            'id' => 105,
            'customer_id' => 6,
            'branch_id' => 18,
            'username' => 'purchase_karya_18',
            'display_name' => 'Cahya Gema',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],

        // Customer 7 (PT Bumi Perkasa) - Branch 19 (Pusat)
        106 => [
            'id' => 106,
            'customer_id' => 7,
            'branch_id' => 19,
            'username' => 'finance_bumi_19',
            'display_name' => 'Danu Hendra',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        107 => [
            'id' => 107,
            'customer_id' => 7,
            'branch_id' => 19,
            'username' => 'legal_bumi_19',
            'display_name' => 'Erna Iwan',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],

        // Customer 7 (PT Bumi Perkasa) - Branch 20 (Cabang 1)
        108 => [
            'id' => 108,
            'customer_id' => 7,
            'branch_id' => 20,
            'username' => 'operation_bumi_20',
            'display_name' => 'Farhan Jenni',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        109 => [
            'id' => 109,
            'customer_id' => 7,
            'branch_id' => 20,
            'username' => 'purchase_bumi_20',
            'display_name' => 'Galuh Kania',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],

        // Customer 7 (PT Bumi Perkasa) - Branch 21 (Cabang 2)
        110 => [
            'id' => 110,
            'customer_id' => 7,
            'branch_id' => 21,
            'username' => 'finance_bumi_21',
            'display_name' => 'Halim Lutfi',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        111 => [
            'id' => 111,
            'customer_id' => 7,
            'branch_id' => 21,
            'username' => 'legal_bumi_21',
            'display_name' => 'Indah Marina',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],

        // Customer 8 (CV Cipta Kreasi) - Branch 22 (Pusat)
        112 => [
            'id' => 112,
            'customer_id' => 8,
            'branch_id' => 22,
            'username' => 'operation_cipta_22',
            'display_name' => 'Joko Nur',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        113 => [
            'id' => 113,
            'customer_id' => 8,
            'branch_id' => 22,
            'username' => 'purchase_cipta_22',
            'display_name' => 'Khalid Olivia',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],

        // Customer 8 (CV Cipta Kreasi) - Branch 23 (Cabang 1)
        114 => [
            'id' => 114,
            'customer_id' => 8,
            'branch_id' => 23,
            'username' => 'finance_cipta_23',
            'display_name' => 'Laras Priska',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        115 => [
            'id' => 115,
            'customer_id' => 8,
            'branch_id' => 23,
            'username' => 'legal_cipta_23',
            'display_name' => 'Mulyadi Qonita',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],

        // Customer 8 (CV Cipta Kreasi) - Branch 24 (Cabang 2)
        116 => [
            'id' => 116,
            'customer_id' => 8,
            'branch_id' => 24,
            'username' => 'operation_cipta_24',
            'display_name' => 'Novianti Riana',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        117 => [
            'id' => 117,
            'customer_id' => 8,
            'branch_id' => 24,
            'username' => 'purchase_cipta_24',
            'display_name' => 'Oky Silvia',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],

        // Customer 9 (PT Meta Inovasi) - Branch 25 (Pusat)
        118 => [
            'id' => 118,
            'customer_id' => 9,
            'branch_id' => 25,
            'username' => 'finance_meta_25',
            'display_name' => 'Prabu Tiara',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        119 => [
            'id' => 119,
            'customer_id' => 9,
            'branch_id' => 25,
            'username' => 'legal_meta_25',
            'display_name' => 'Qomar Umi',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],

        // Customer 9 (PT Meta Inovasi) - Branch 26 (Cabang 1)
        120 => [
            'id' => 120,
            'customer_id' => 9,
            'branch_id' => 26,
            'username' => 'operation_meta_26',
            'display_name' => 'Reza Vivi',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        121 => [
            'id' => 121,
            'customer_id' => 9,
            'branch_id' => 26,
            'username' => 'purchase_meta_26',
            'display_name' => 'Salim Widya',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],

        // Customer 9 (PT Meta Inovasi) - Branch 27 (Cabang 2)
        122 => [
            'id' => 122,
            'customer_id' => 9,
            'branch_id' => 27,
            'username' => 'finance_meta_27',
            'display_name' => 'Teguh Yesi',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        123 => [
            'id' => 123,
            'customer_id' => 9,
            'branch_id' => 27,
            'username' => 'legal_meta_27',
            'display_name' => 'Usman Zainal',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],

        // Customer 10 (PT Delta Sistem) - Branch 28 (Pusat)
        124 => [
            'id' => 124,
            'customer_id' => 10,
            'branch_id' => 28,
            'username' => 'operation_delta_28',
            'display_name' => 'Vikri Ayu',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        125 => [
            'id' => 125,
            'customer_id' => 10,
            'branch_id' => 28,
            'username' => 'purchase_delta_28',
            'display_name' => 'Wahyu Bima',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],

        // Customer 10 (PT Delta Sistem) - Branch 29 (Cabang 1)
        126 => [
            'id' => 126,
            'customer_id' => 10,
            'branch_id' => 29,
            'username' => 'finance_delta_29',
            'display_name' => 'Yayan Citra',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        127 => [
            'id' => 127,
            'customer_id' => 10,
            'branch_id' => 29,
            'username' => 'legal_delta_29',
            'display_name' => 'Zulkifli Doni',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],

        // Customer 10 (PT Delta Sistem) - Branch 30 (Cabang 2)
        128 => [
            'id' => 128,
            'customer_id' => 10,
            'branch_id' => 30,
            'username' => 'operation_delta_30',
            'display_name' => 'Abdul Fitra',
            'role' => 'customer',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        129 => [
            'id' => 129,
            'customer_id' => 10,
            'branch_id' => 30,
            'username' => 'purchase_delta_30',
            'display_name' => 'Amir Hani',
            'role' => 'customer',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
    ];

    /**
     * Get the name collection
     *
     * @return array
     */
    public static function getNameCollection() {
        return self::$name_collection;
    }

    /**
     * Validate if a name uses only words from the collection
     *
     * @param string $name The name to validate
     * @return bool
     */
    public static function isValidName($name) {
        $words = explode(' ', $name);
        foreach ($words as $word) {
            if (!in_array($word, self::$name_collection)) {
                return false;
            }
        }
        return true;
    }
}
