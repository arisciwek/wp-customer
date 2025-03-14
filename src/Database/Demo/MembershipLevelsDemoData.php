<?php
/**
 * Membership Levels Demo Data Generator
 *
 * @package     WP_Customer
 * @subpackage  Database/Demo
 * @version     1.0.2
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Database/Demo/MembershipLevelsDemoData.php
 *
 * Description: Generate demo data untuk membership levels.
 *              Sets up base configuration untuk:
 *              - Free membership (1 staff max)
 *              - Regular membership (2 staff max)
 *              - Priority membership (5 staff max)
 *              - Ultimate membership (unlimited staff)
 *              
 * Dependencies:
 * - AbstractDemoData base class
 * - CustomerDemoDataHelperTrait
 * - MembershipLevelsDB untuk definisi tabel
 * - MembershipFeaturesDB untuk referensi fitur
 * 
 * Changelog:
 * 1.0.2 - 2025-02-14
 * - Updated capabilities JSON structure
 * - Added group-based feature organization
 * - Enhanced level configurations
 * 
 * 1.0.1 - 2025-02-11
 * - Added trial and grace periods
 * - Enhanced capabilities structure
 * - Added resource limits
 * 
 * 1.0.0 - 2025-01-27: 12:00
 * - Initial version
 */ 

namespace WPCustomer\Database\Demo;

use WPCustomer\Models\Membership\MembershipLevelModel;

class MembershipLevelsDemoData extends AbstractDemoData {
    use CustomerDemoDataHelperTrait;

    private $membershipLevelModel;
    
    public function __construct() {
        parent::__construct();
        $this->membershipLevelModel = new MembershipLevelModel();
    }

    /**
     * Insert default membership levels
     */
    protected function insertDefaultLevels() {
       global $wpdb;
       $table_name = $wpdb->prefix . 'app_customer_membership_levels';
       $current_user_id = get_current_user_id();

        $defaults = [
            [
                'name' => 'Gratis',
                'slug' => 'gratis', 
                'description' => 'Paket gratis dengan maksimal 1 staff',
                'available_periods' => '1',
                'default_period' => 1,
                'price_per_month' => 0,
                'is_trial_available' => 1,
                'trial_days' => 7,
                'grace_period_days' => 3,
                'sort_order' => 1,
                'capabilities' => json_encode([
                    'staff' => [
                        'can_add_staff' => [
                            'field' => 'can_add_staff',
                            'value' => true,
                            'settings' => [],
                            'label' => 'Dapat Menambah Staff'
                        ]
                    ],
                    'data' => [
                        'can_export' => [
                            'field' => 'can_export',
                            'value' => false,
                            'settings' => [],
                            'label' => 'Dapat Export Data'
                        ],
                        'can_bulk_import' => [
                            'field' => 'can_bulk_import',
                            'value' => false,
                            'settings' => [],
                            'label' => 'Dapat Melakukan Bulk Import'
                        ]
                    ],
                    'resources' => [
                        'max_staff' => [
                            'field' => 'max_staff',
                            'value' => 1,
                            'settings' => [],
                            'label' => 'Maksimal Staff'
                        ],
                        'max_departments' => [
                            'field' => 'max_departments',
                            'value' => 1,
                            'settings' => [],
                            'label' => 'Maksimal Departemen'
                        ]
                    ],
                    'communication' => [
                        'email_notifications' => [
                            'field' => 'email_notifications',
                            'value' => true,
                            'settings' => [],
                            'label' => 'Notifikasi Email'
                        ],
                        'dashboard_notifications' => [
                            'field' => 'dashboard_notifications',
                            'value' => false,
                            'settings' => [],
                            'label' => 'Notifikasi Dashboard'
                        ]
                    ]
                ]),
                'settings' => json_encode([
                    'payment' => [
                        'available_methods' => [
                            'field' => 'available_methods',
                            'value' => [],
                            'label' => 'Metode Pembayaran yang Tersedia',
                            'settings' => []
                        ],
                        'min_payment_period' => [
                            'field' => 'min_payment_period',
                            'value' => 0,
                            'label' => 'Periode Pembayaran Minimum',
                            'settings' => []
                        ],
                        'max_payment_period' => [
                            'field' => 'max_payment_period',
                            'value' => 0,
                            'label' => 'Periode Pembayaran Maksimum',
                            'settings' => []
                        ]
                    ],
                    'customization' => [
                        'can_customize_email_template' => [
                            'field' => 'can_customize_email_template',
                            'value' => false,
                            'label' => 'Dapat Mengkustomisasi Template Email',
                            'settings' => []
                        ],
                        'can_customize_invoice' => [
                            'field' => 'can_customize_invoice',
                            'value' => false,
                            'label' => 'Dapat Mengkustomisasi Invoice',
                            'settings' => []
                        ]
                    ]
                ]),
                'created_by' => $current_user_id,
                'status' => 'active'
            ],
            [
                'name' => 'Reguler',
                'slug' => 'reguler',
                'description' => 'Paket dasar dengan maksimal 2 staff',
                'available_periods' => '1',
                'default_period' => 1,
                'price_per_month' => 50000,
                'is_trial_available' => 1,
                'trial_days' => 7,
                'grace_period_days' => 3,
                'sort_order' => 2,
                'capabilities' => json_encode([
                    'staff' => [
                        'can_add_staff' => [
                            'field' => 'can_add_staff',
                            'value' => true,
                            'settings' => [],
                            'label' => 'Dapat Menambah Staff'
                        ]
                    ],
                    'data' => [
                        'can_export' => [
                            'field' => 'can_export',
                            'value' => true,
                            'settings' => [],
                            'label' => 'Dapat Export Data'
                        ],
                        'can_bulk_import' => [
                            'field' => 'can_bulk_import',
                            'value' => false,
                            'settings' => [],
                            'label' => 'Dapat Melakukan Bulk Import'
                        ]
                    ],
                    'resources' => [
                        'max_staff' => [
                            'field' => 'max_staff',
                            'value' => 2,
                            'settings' => [],
                            'label' => 'Maksimal Staff'
                        ],
                        'max_departments' => [
                            'field' => 'max_departments',
                            'value' => 2,
                            'settings' => [],
                            'label' => 'Maksimal Departemen'
                        ]
                    ],
                    'communication' => [
                        'email_notifications' => [
                            'field' => 'email_notifications',
                            'value' => true,
                            'settings' => [],
                            'label' => 'Notifikasi Email'
                        ],
                        'dashboard_notifications' => [
                            'field' => 'dashboard_notifications',
                            'value' => true,
                            'settings' => [],
                            'label' => 'Notifikasi Dashboard'
                        ]
                    ]
                ]),
                'settings' => json_encode([
                        'payment' => [
                            'available_methods' => [
                                'field' => 'available_methods',
                                'value' => ['bank_transfer'],
                                'label' => 'Metode Pembayaran yang Tersedia',
                                'settings' => []
                            ],
                            'min_payment_period' => [
                                'field' => 'min_payment_period',
                                'value' => 1,
                                'label' => 'Periode Pembayaran Minimum',
                                'settings' => []
                            ],
                            // ...dan seterusnya
                        ],
                        'customization' => [
                            'can_customize_email_template' => [
                                'field' => 'can_customize_email_template',
                                'value' => false,
                                'label' => 'Dapat Mengkustomisasi Template Email',
                                'settings' => []
                            ],
                            // ...dan seterusnya
                        ]
                    ]),
                'created_by' => $current_user_id,
                'status' => 'active'
            ],
            [
                'name' => 'Prioritas',
                'slug' => 'prioritas',
                'description' => 'Paket menengah dengan maksimal 5 staff',
                'available_periods' => '1',
                'default_period' => 1,
                'price_per_month' => 100000,
                'is_trial_available' => 1,
                'trial_days' => 7,
                'grace_period_days' => 5,
                'sort_order' => 3,
                'capabilities' => json_encode([
                    'staff' => [
                        'can_add_staff' => [
                            'field' => 'can_add_staff',
                            'value' => true,
                            'label' => 'Dapat Menambah Staff',
                            'settings' => []
                        ]
                    ],
                    'data' => [
                        'can_export' => [
                            'field' => 'can_export',
                            'value' => true,
                            'label' => 'Dapat Export Data',
                            'settings' => []
                        ],
                        'can_bulk_import' => [
                            'field' => 'can_bulk_import',
                            'value' => true,
                            'label' => 'Dapat Melakukan Bulk Import',
                            'settings' => []
                        ]
                    ],
                    'resources' => [
                        'max_staff' => [
                            'field' => 'max_staff',
                            'value' => 5,
                            'label' => 'Maksimal Staff',
                            'settings' => []
                        ],
                        'max_departments' => [
                            'field' => 'max_departments',
                            'value' => 3,
                            'label' => 'Maksimal Departemen',
                            'settings' => []
                        ]
                    ],
                    'communication' => [
                        'email_notifications' => [
                            'field' => 'email_notifications',
                            'value' => true,
                            'label' => 'Notifikasi Email',
                            'settings' => []
                        ],
                        'dashboard_notifications' => [
                            'field' => 'dashboard_notifications',
                            'value' => true,
                            'label' => 'Notifikasi Dashboard',
                            'settings' => []
                        ]
                    ]
                ]),
                'settings' => json_encode([
                    'payment' => [
                        'available_methods' => [
                            'field' => 'available_methods',
                            'value' => ['bank_transfer', 'credit_card'],
                            'label' => 'Metode Pembayaran yang Tersedia',
                            'settings' => []
                        ],
                        'min_payment_period' => [
                            'field' => 'min_payment_period',
                            'value' => 1,
                            'label' => 'Periode Pembayaran Minimum',
                            'settings' => []
                        ],
                        'max_payment_period' => [
                            'field' => 'max_payment_period',
                            'value' => 6,
                            'label' => 'Periode Pembayaran Maksimum',
                            'settings' => []
                        ],
                        'allow_installment' => [
                            'field' => 'allow_installment',
                            'value' => false,
                            'label' => 'Izinkan Pembayaran Cicilan',
                            'settings' => []
                        ]
                    ],
                    'customization' => [
                        'can_customize_email_template' => [
                            'field' => 'can_customize_email_template',
                            'value' => true,
                            'label' => 'Dapat Mengkustomisasi Template Email',
                            'settings' => []
                        ],
                        'can_customize_invoice' => [
                            'field' => 'can_customize_invoice',
                            'value' => true,
                            'label' => 'Dapat Mengkustomisasi Invoice',
                            'settings' => []
                        ],
                        'can_use_custom_domain' => [
                            'field' => 'can_use_custom_domain',
                            'value' => false,
                            'label' => 'Dapat Menggunakan Domain Kustom',
                            'settings' => []
                        ]
                    ]
                ]),
                'created_by' => $current_user_id,
                'status' => 'active'
            ],
            [
                'name' => 'Utama',
                'slug' => 'utama',
                'description' => 'Paket premium tanpa batasan staff',
                'available_periods' => '6',
                'default_period' => 1,
                'price_per_month' => 200000,
                'is_trial_available' => 0,
                'trial_days' => 0,
                'grace_period_days' => 7,
                'sort_order' => 4,
                'capabilities' => json_encode([
                    'staff' => [
                        'can_add_staff' => [
                            'field' => 'can_add_staff',
                            'value' => true,
                            'settings' => [],
                            'label' => 'Dapat Menambah Staff'
                        ]
                    ],
                    'data' => [
                        'can_export' => [
                            'field' => 'can_export',
                            'value' => true,
                            'settings' => [],
                            'label' => 'Dapat Export Data'
                        ],
                        'can_bulk_import' => [
                            'field' => 'can_bulk_import',
                            'value' => true,
                            'settings' => [],
                            'label' => 'Dapat Melakukan Bulk Import'
                        ]
                    ],
                    'resources' => [
                        'max_staff' => [
                            'field' => 'max_staff',
                            'value' => -1,
                            'settings' => [],
                            'label' => 'Maksimal Staff'
                        ],
                        'max_departments' => [
                            'field' => 'max_departments',
                            'value' => -1,
                            'settings' => [],
                            'label' => 'Maksimal Departemen'
                        ]
                    ],
                    'communication' => [
                        'email_notifications' => [
                            'field' => 'email_notifications',
                            'value' => true,
                            'settings' => [],
                            'label' => 'Notifikasi Email'
                        ],
                        'dashboard_notifications' => [
                            'field' => 'dashboard_notifications',
                            'value' => true,
                            'settings' => [],
                            'label' => 'Notifikasi Dashboard'
                        ]
                    ]
                ]),
                'settings' => json_encode([
                    'payment' => [
                        'available_methods' => [
                            'field' => 'available_methods',
                            'value' => ['bank_transfer', 'credit_card'],
                            'label' => 'Metode Pembayaran yang Tersedia',
                            'settings' => []
                        ],
                        'min_payment_period' => [
                            'field' => 'min_payment_period',
                            'value' => 1,
                            'label' => 'Periode Pembayaran Minimum',
                            'settings' => []
                        ],
                        'max_payment_period' => [
                            'field' => 'max_payment_period',
                            'value' => 6,
                            'label' => 'Periode Pembayaran Maksimum',
                            'settings' => []
                        ]
                    ],
                    'customization' => [
                        'can_customize_email_template' => [
                            'field' => 'can_customize_email_template',
                            'value' => true,
                            'label' => 'Dapat Mengkustomisasi Template Email',
                            'settings' => []
                        ],
                        'can_customize_invoice' => [
                            'field' => 'can_customize_invoice',
                            'value' => true,
                            'label' => 'Dapat Mengkustomisasi Invoice',
                            'settings' => []
                        ],
                        'can_use_custom_domain' => [
                            'field' => 'can_use_custom_domain',
                            'value' => false,
                            'label' => 'Dapat Menggunakan Domain Kustom',
                            'settings' => []
                        ]
                    ]
                ]),
                'created_by' => $current_user_id,
                'status' => 'active'
            ]
        ];

       foreach ($defaults as $level) {
           $wpdb->insert($table_name, $level);
       }
    }

    private function getDefaultSettings($level_type) {
        $base_settings = [
            'payment' => [
                'available_methods' => ['bank_transfer'],
                'min_payment_period' => 1,
                'allow_installment' => false
            ],
            'upgrade_rules' => [
                'allow_upgrade' => true,
                'allow_downgrade' => false,
                'pro_rate_calculation' => true
            ],
            'auto_renewal' => [
                'enabled' => true,
                'reminder_days' => [7, 3, 1],
                'grace_period_action' => 'suspend'
            ],
            'customization' =>[
                'can_customize_invoice' => true,
                'can_use_custom_domain' => true,
                'can_customize_email_template' =>true
            ]
        ];

        switch($level_type) {
            case 'gratis':
                return array_merge($base_settings, [
                    'payment' => [
                        'available_methods' => [],
                        'min_payment_period' => 0,
                        'max_payment_period' => 0
                    ],
                    'customization' => [
                        'can_customize_email_template' => false,
                        'can_customize_invoice' => false
                    ]
                ]);
                
            case 'reguler':
                return array_merge($base_settings, [
                    'payment' => [
                        'available_methods' => ['bank_transfer'],
                        'max_payment_period' => 3
                    ],
                    'customization' => [
                        'can_customize_email_template' => false,
                        'can_customize_invoice' => true
                    ]
                ]);
                
            case 'utama':
                return array_merge($base_settings, [
                    'payment' => [
                        'available_methods' => ['bank_transfer', 'credit_card'],
                        'max_payment_period' => 12,
                        'allow_installment' => true
                    ],
                    'customization' => [
                        'can_customize_email_template' => true,
                        'can_customize_invoice' => true,
                        'can_use_custom_domain' => true
                    ]
                ]);
                
            default:
                return $base_settings;
        }
    }
    
    private function generateCapabilities($data) {
        $capabilities = [];
        
        foreach ($data as $group => $features) {
            $capabilities[$group] = [];
            foreach ($features as $feature => $value) {
                $capabilities[$group][$feature] = [
                    'field' => $feature,
                    'value' => $value,
                    'settings' => []
                ];
            }
        }

        return json_encode($capabilities);
    }

    // Method lain tidak berubah
    protected function validate(): bool {
        try {
            if (!$this->isDevelopmentMode()) {
                $this->debug('Development mode is not enabled');
                return false;
            }

            $table_exists = $this->wpdb->get_var(
                "SHOW TABLES LIKE '{$this->wpdb->prefix}app_customer_membership_levels'"
            );
            
            if (!$table_exists) {
                throw new \Exception('Membership levels table does not exist');
            }

            return true;
        } catch (\Exception $e) {
            $this->debug('Validation failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function generate(): bool {
        try {
            if ($this->shouldClearData('membership_levels')) {
                $this->clearExistingData('membership_levels');
            }
            
            $this->insertDefaultLevels();
            $this->debug('Default membership levels inserted');

            return true;
        } catch (\Exception $e) {
            $this->debug('Generation failed: ' . $e->getMessage());
            return false;
        }
    }
}
