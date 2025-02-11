<?php
/**
 * Customer Membership Model Class
 *
 * @package     WP_Customer
 * @subpackage  Models/Customer/CustomerMembershipLevel
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Models/Membership/CustomerMembershipLevelModel.php
 *
 * Description: Model untuk mengelola data customer membership level secara dinamis.
 *              Menggantikan sistem customer_membership yang sebelumnya hardcoded.
 *              Menggunakan database table untuk menyimpan konfigurasi level.
 *              Terintegrasi dengan caching untuk optimasi performa.
 *              
 * Database Table: {prefix}app_customer_membership_levels
 * Fields:
 * - id             : Primary key
 * - name           : Nama level membership (e.g., Regular, Priority, Utama)
 * - slug           : Slug unik untuk identifikasi level
 * - description    : Deskripsi level (nullable)
 * - max_staff      : Batas maksimal staff (-1 untuk unlimited)
 * - max_departments: Batas maksimal departemen (-1 untuk unlimited)
 * - capabilities   : JSON string untuk menyimpan capability configuration
 * - sort_order     : Urutan tampilan level
 * - status         : Status level (active/inactive)
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp terakhir update
 *
 * JSON Capabilities Format:
 * {
 *   "can_add_staff": boolean,
 *   "can_export": boolean,
 *   "can_bulk_import": boolean,
 *   "can_manage_departments": boolean,
 *   "can_view_reports": boolean,
 *   "can_manage_settings": boolean
 * }
 * 
 * Dependencies:
 * - WPCustomer\Cache\CustomerCacheManager untuk caching
 * - WordPress Options API untuk backward compatibility
 * - WordPress $wpdb untuk database operations
 *
 * Cache Implementation:
 * - Group: wp_customer
 * - Keys:
 *   - customer_membership_levels_all: Semua level yang aktif
 *   - customer_membership_level_{id}: Data single level
 *   - user_customer_membership_level_{user_id}: Level ID untuk user
 *
 * Migration Note:
 * Untuk migrasi dari sistem lama:
 * 1. Baca data dari wp_customer_membership_settings option
 * 2. Convert ke format database baru
 * 3. Maintain backward compatibility selama transisi
 *
 * Usage Example:
 * $customerMembershipLevel = new CustomerMembershipLevelModel();
 * $levels = $customerMembership->getAllLevels();
 * $capabilities = $customerMembership->getLevelCapabilities($level_id);
 * $has_access = $customerMembership->hasCapability($user_id, 'can_export');
 * 
 * Changelog:
 * 1.0.0 - 2024-02-08
 * - Initial version
 * - Added dynamic membership level management
 * - Added capability system
 * - Added caching integration
 * - Added backward compatibility layer
 */

namespace WPCustomer\Models\Membership;

use WPCustomer\Cache\CustomerCacheManager;

class CustomerMembershipModel {
    private $table;
    private $cache;
    private $membership_settings_key = 'wp_customer_membership_settings';
    
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'app_customer_membership_levels';
        $this->cache = new CustomerCacheManager();
    }

    /**
     * Get all customer_membership levels
     */
    public function getAllLevels(): array {
        // Check cache first
        $cached = $this->cache->get('customer_membership_levels', 'all');
        if ($cached !== null) {
            return $cached;
        }

        global $wpdb;
        $levels = $wpdb->get_results("
            SELECT * FROM {$this->table}
            WHERE status = 'active'
            ORDER BY sort_order ASC
        ");

        // Cache for 5 minutes
        $this->cache->set('customer_membership_levels', $levels, 300, 'all');
        
        return $levels;
    }

    /**
     * Get single customer_membership level
     */
    public function getLevel(int $id): ?object {
        // Check cache
        $cached = $this->cache->get('customer_membership_level', $id);
        if ($cached !== null) {
            return $cached;
        }

        global $wpdb;
        $level = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$this->table}
            WHERE id = %d
        ", $id));

        if ($level) {
            // Cache for 5 minutes
            $this->cache->set('customer_membership_level', $level, 300, $id);
        }

        return $level;
    }

    /**
     * Create new customer_membership level
     */
    public function create(array $data): int {
        global $wpdb;

        $defaults = [
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        $data = wp_parse_args($data, $defaults);

        // Handle capabilities as JSON
        if (isset($data['capabilities']) && is_array($data['capabilities'])) {
            $data['capabilities'] = json_encode($data['capabilities']);
        }

        $wpdb->insert($this->table, $data);
        $id = $wpdb->insert_id;

        // Clear cache
        $this->clearCache();

        return $id;
    }

    /**
     * Update customer_membership level
     */
    public function update(int $id, array $data): bool {
        global $wpdb;

        $data['updated_at'] = current_time('mysql');

        // Handle capabilities as JSON
        if (isset($data['capabilities']) && is_array($data['capabilities'])) {
            $data['capabilities'] = json_encode($data['capabilities']);
        }

        $result = $wpdb->update(
            $this->table,
            $data,
            ['id' => $id]
        );

        // Clear cache
        $this->clearCache();

        return $result !== false;
    }
 
    public function save($data, $id = null) {
        $capabilities = $this->buildCapabilitiesFromFeatures($data);
        
        $levelData = [
            'name' => $data['name'],
            'capabilities' => json_encode($capabilities),
            'updated_at' => current_time('mysql')
        ];

        if ($id) {
            return $this->update($id, $levelData);
        }
        return $this->create($levelData);
    }

    private function buildCapabilitiesFromFeatures($data) {
        $features = (new MembershipFeatureModel())->get_all_features_by_group();
        $capabilities = [];
        
        foreach ($features as $feature) {
            $group = $feature->field_group;
        $name = $feature->field_name;
        $capabilities[$group][$name] = !empty($data[$name]);
    }
    
    return $capabilities;
}

    /**
     * Delete customer_membership level
     */
    public function delete(int $id): bool {
        global $wpdb;

        // Soft delete by setting status to inactive
        $result = $wpdb->update(
            $this->table,
            ['status' => 'inactive'],
            ['id' => $id]
        );

        // Clear cache
        $this->clearCache();

        return $result !== false;
    }

    /**
     * Get customer_membership level capabilities
     */
    public function getLevelCapabilities(int $id): array {
        $level = $this->getLevel($id);
        if (!$level || empty($level->capabilities)) {
            return [];
        }

        return json_decode($level->capabilities, true) ?: [];
    }

    /**
     * Check if user has capability in their customer_membership level
     */
    public function hasCapability(int $user_id, string $capability): bool {
        $level_id = $this->getUserMembershipLevel($user_id);
        if (!$level_id) {
            return false;
        }

        $capabilities = $this->getLevelCapabilities($level_id);
        return !empty($capabilities[$capability]);
    }

    /**
     * Get user's current customer_membership level
     */
    public function getUserMembershipLevel(int $user_id): ?int {
        // Check cache
        $cached = $this->cache->get('user_customer_membership_level', $user_id);
        if ($cached !== null) {
            return $cached;
        }

        global $wpdb;
        $level_id = $wpdb->get_var($wpdb->prepare("
            SELECT customer_membership_level_id
            FROM {$wpdb->prefix}app_customers
            WHERE user_id = %d
            LIMIT 1
        ", $user_id));

        if ($level_id) {
            // Cache for 5 minutes
            $this->cache->set('user_customer_membership_level', (int)$level_id, 300, $user_id);
        }

        return $level_id ? (int)$level_id : null;
    }

    /**
     * Clear all customer_membership-related cache
     */
    private function clearCache(): void {
        $this->cache->delete('customer_membership_levels', 'all');
        // Could also clear other customer_membership-related caches here
    }

    /**
     * Get available capabilities
     */
    public function getAvailableCapabilities(): array {
        return [
            'can_add_staff' => __('Dapat menambah staff', 'wp-customer'),
            'can_export' => __('Dapat export data', 'wp-customer'),
            'can_bulk_import' => __('Dapat bulk import', 'wp-customer'),
            'can_manage_departments' => __('Dapat mengelola departemen', 'wp-customer'),
            'can_view_reports' => __('Dapat melihat laporan', 'wp-customer'),
            'can_manage_settings' => __('Dapat mengatur pengaturan', 'wp-customer')
        ];
    }

    /**
     * Get customer_membership level limits
     */
    public function getLevelLimits(int $id): array {
        $level = $this->getLevel($id);
        if (!$level) {
            return [
                'max_staff' => 2,
                'max_departments' => 1
            ];
        }

        return [
            'max_staff' => $level->max_staff,
            'max_departments' => $level->max_departments ?? 1
        ];
    }


    /**
     * Get membership settings including capabilities for a level
     */
    public function getMembershipData(int $customer_id): array {
        // Get membership settings
        $settings = get_option($this->membership_settings_key, []);

        // Get customer data untuk cek level
        $customer = $this->getCustomerLevel($customer_id);
        $level = $customer->membership_level ?? $settings['default_level'] ?? 'regular';

        return [
            'level' => $level,
            'max_staff' => $settings["{$level}_max_staff"] ?? 2,
            'capabilities' => [
                'can_add_staff' => $settings["{$level}_can_add_staff"] ?? false,
                'can_export' => $settings["{$level}_can_export"] ?? false,
                'can_bulk_import' => $settings["{$level}_can_bulk_import"] ?? false,
            ]
        ];
    }

    /**
     * Get customer's membership level
     */
    private function getCustomerLevel(int $customer_id): ?object {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("
            SELECT membership_level
            FROM {$wpdb->prefix}app_customers
            WHERE id = %d
        ", $customer_id));
    }

    /**
     * Setup default membership settings if not exists
     */
    public function setupMembershipDefaults(): bool {
        try {
            // Check if settings already exist
            if (!get_option($this->membership_settings_key)) {
                $default_settings = [
                    'regular_max_staff' => 2,
                    'regular_can_add_staff' => true,
                    'regular_can_export' => false,
                    'regular_can_bulk_import' => false,

                    'priority_max_staff' => 5,
                    'priority_can_add_staff' => true,
                    'priority_can_export' => true,
                    'priority_can_bulk_import' => false,

                    'utama_max_staff' => -1,
                    'utama_can_add_staff' => true,
                    'utama_can_export' => true,
                    'utama_can_bulk_import' => true,

                    'default_level' => 'regular'
                ];

                return add_option($this->membership_settings_key, $default_settings);
            }
            return true;
        } catch (\Exception $e) {
            error_log('Error setting up membership defaults: ' . $e->getMessage());
            return false;
        }
    }

	/**
	 * Insert default membership levels
	 * Matches schema from CustomerMembershipLevelsDB
	 */
    /*
	public function insertDefaultLevels(): bool {
	    global $wpdb;

	    try {
	        $current_user_id = get_current_user_id();
	        
	        $defaults = [
                [
                    'name' => 'Free',
                    'slug' => 'free',
                    'description' => 'Paket gratis dengan maksimal 2 staff',
                    'max_staff' => 2,
                    'max_departments' => 1,
                    'available_periods' => json_encode([1, 3, 6, 12]),
                    'default_period' => 1,
                    'price_per_month' => 50000,
                    'is_trial_available' => 0,
                    'grace_period_days' => -1,
                    'sort_order' => 1,
                    'capabilities' => json_encode([
                        'features' => [
                            'can_add_staff' => [
                                'field' => 'can_add_staff',
                                'label' => 'Menambah Staff',
                                'value' => true,
                                'icon' => 'dashicons-groups',
                                'css_class' => 'feature-enabled'
                            ],
                            'can_export' => [
                                'field' => 'can_export',
                                'label' => 'Export Data',
                                'value' => false,
                                'icon' => 'dashicons-download',
                                'css_class' => 'feature-disabled'
                            ],
                            'can_bulk_import' => [
                                'field' => 'can_bulk_import',
                                'label' => 'Import Massal',
                                'value' => false,
                                'icon' => 'dashicons-upload',
                                'css_class' => 'feature-disabled'
                            ]
                        ],
                        'limits' => [
                            'max_staff' => 2,
                            'max_departments' => 1,
                            'max_active_projects' => 3
                        ],
                        'notifications' => [
                            'email' => true,
                            'dashboard' => false,
                            'push' => false
                        ]
                    ]),
                    'created_by' => $current_user_id,
                    'created_at' => current_time('mysql'),
                    'status' => 'active'
                ],
                [
                    'name' => 'Regular',
                    'slug' => 'regular',
                    'description' => 'Paket dasar dengan maksimal 2 staff',
                    'max_staff' => 2,
                    'max_departments' => 1,
                    'available_periods' => json_encode([1, 3, 6, 12]),
                    'default_period' => 1,
                    'price_per_month' => 50000,
                    'is_trial_available' => 1,
                    'trial_days' => 7,
                    'grace_period_days' => 3,
                    'sort_order' => 1,
                    'capabilities' => json_encode([
                        'features' => [
                            'can_add_staff' => [
                                'field' => 'can_add_staff',
                                'label' => 'Menambah Staff',
                                'value' => true,
                                'icon' => 'dashicons-groups',
                                'css_class' => 'feature-enabled'
                            ],
                            'can_export' => [
                                'field' => 'can_export',
                                'label' => 'Export Data',
                                'value' => false,
                                'icon' => 'dashicons-download',
                                'css_class' => 'feature-disabled'
                            ],
                            'can_bulk_import' => [
                                'field' => 'can_bulk_import',
                                'label' => 'Import Massal',
                                'value' => false,
                                'icon' => 'dashicons-upload',
                                'css_class' => 'feature-disabled'
                            ]
                        ],
                        'limits' => [
                            'max_staff' => 2,
                            'max_departments' => 1,
                            'max_active_projects' => 3
                        ],
                        'notifications' => [
                            'email' => true,
                            'dashboard' => true,
                            'push' => false
                        ]
                    ]),
                    'created_by' => $current_user_id,
                    'created_at' => current_time('mysql'),
                    'status' => 'active'
                ],
	            [
	                'name' => 'Prioritas',
	                'slug' => 'prioritas',
	                'description' => 'Paket menengah dengan maksimal 5 staff',
	                'max_staff' => 5,
	                'max_departments' => 3,
	                'available_periods' => json_encode([1, 3, 6, 12]),
	                'default_period' => 1,
	                'price_per_month' => 100000,
	                'is_trial_available' => 1,
	                'trial_days' => 7,
	                'grace_period_days' => 5,
	                'sort_order' => 2,
                    'capabilities' => json_encode([
                        'features' => [
                            'can_add_staff' => [
                                'field' => 'can_add_staff',
                                'label' => 'Menambah Staff',
                                'value' => true,
                                'icon' => 'dashicons-groups',
                                'css_class' => 'feature-enabled'
                            ],
                            'can_export' => [
                                'field' => 'can_export',
                                'label' => 'Export Data',
                                'value' => false,
                                'icon' => 'dashicons-download',
                                'css_class' => 'feature-disabled'
                            ],
                            'can_bulk_import' => [
                                'field' => 'can_bulk_import',
                                'label' => 'Import Massal',
                                'value' => false,
                                'icon' => 'dashicons-upload',
                                'css_class' => 'feature-disabled'
                            ]
                        ],
                        'limits' => [
                            'max_staff' => 2,
                            'max_departments' => 2,
                            'max_active_projects' => 5
                        ],
                        'notifications' => [
                            'email' => true,
                            'dashboard' => true,
                            'push' => false
                        ]
                    ]),
    	                'created_by' => $current_user_id,
    	                'created_at' => current_time('mysql'),
    	                'status' => 'active'
    	            ],
	            [
	                'name' => 'Utama',
	                'slug' => 'utama',
	                'description' => 'Paket premium tanpa batasan staff',
	                'max_staff' => -1,
	                'max_departments' => -1,
	                'available_periods' => json_encode([1, 3, 6, 12]),
	                'default_period' => 1,
	                'price_per_month' => 200000,
	                'is_trial_available' => 0,
	                'trial_days' => 0,
	                'grace_period_days' => 7,
	                'sort_order' => 3,
	                'capabilities' => json_encode([
                        'features' => [
                            'can_add_staff' => [
                                'field' => 'can_add_staff',
                                'label' => 'Menambah Staff',
                                'value' => true,
                                'icon' => 'dashicons-groups',
                                'css_class' => 'feature-enabled'
                            ],
                            'can_export' => [
                                'field' => 'can_export',
                                'label' => 'Export Data',
                                'value' => true,
                                'icon' => 'dashicons-download',
                                'css_class' => 'feature-enabled'
                            ],
                            'can_bulk_import' => [
                                'field' => 'can_bulk_import',
                                'label' => 'Import Massal',
                                'value' => false,
                                'icon' => 'dashicons-upload',
                                'css_class' => 'feature-disabled'
                            ]
                        ],
	                    'limits' => [
	                        'max_staff' => -1,
	                        'max_departments' => -1,
	                        'max_active_projects' => -1
	                    ],
	                    'notifications' => [
	                        'email' => true,
	                        'dashboard' => true,
	                        'push' => true
	                    ]
	                ]),
	                'created_by' => $current_user_id,
	                'created_at' => current_time('mysql'),
	                'status' => 'active'
	            ]
	        ];

	        // Start transaction
	        $wpdb->query('START TRANSACTION');

	        foreach ($defaults as $level) {
	            $result = $wpdb->insert($this->table, $level);
	            if ($result === false) {
	                throw new \Exception($wpdb->last_error);
	            }
	        }

	        // Clear any existing cache
	        $this->clearCache();

	        // Commit transaction
	        $wpdb->query('COMMIT');

	        return true;

	    } catch (\Exception $e) {
	        // Rollback on error
	        $wpdb->query('ROLLBACK');
	        error_log('Error inserting default membership levels: ' . $e->getMessage());
	        return false;
	    }
	}

    public function getFormattedLevelData($slug) {
        global $wpdb;
        $level = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE slug = %s AND status = 'active'",
            $slug
        ));

        if (!$level) return null;

        return [
            'id' => $level->id,
            'name' => $level->name,
            'max_staff' => $level->max_staff,
            'capabilities' => json_decode($level->capabilities, true),
            'price_per_month' => $level->price_per_month,
            'trial_info' => [
                'has_trial' => (bool)$level->is_trial_available,
                'trial_days' => $level->trial_days
            ]
        ];
    }
    */

}
