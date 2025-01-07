# Penggunaan Select List WP Customer

## Setup Awal

### 1. Dependensi
Sebelum menggunakan select list, pastikan semua dependensi telah terpenuhi:

- jQuery
- WordPress Core
- CustomerToast untuk notifikasi (opsional)

### 2. Enqueue Scripts dan Styles

```php
// Di file plugin Anda
add_action('admin_enqueue_scripts', function($hook) {
    // Cek apakah sedang di halaman yang membutuhkan select
    if ($hook === 'your-page.php') {
        // Enqueue script
        wp_enqueue_script(
            'wp-customer-select-handler',
            WP_CUSTOMER_URL . 'assets/js/components/select-handler.js',
            ['jquery'],
            WP_CUSTOMER_VERSION,
            true
        );

        // Setup data untuk JavaScript
        wp_localize_script('wp-customer-select-handler', 'wpCustomerData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_customer_select_nonce'),
            'texts' => [
                'select_branch' => __('Pilih Kabupaten/Kota', 'wp-customer'),
                'loading' => __('Memuat...', 'wp-customer'),
                'error' => __('Gagal memuat data', 'wp-customer')
            ]
        ]);

        // Enqueue CustomerToast jika digunakan
        wp_enqueue_script('customer-toast');
        wp_enqueue_style('customer-toast-style');
    }
});
```

### 3. Integrasi Cache System

```php
// Mengaktifkan cache
add_filter('wp_customer_enable_cache', '__return_true');

// Konfigurasi durasi cache (dalam detik)
add_filter('wp_customer_cache_duration', function() {
    return 3600; // 1 jam
});
```

## Penggunaan Hook

### 1. Filter untuk Data Options

```php
// Mendapatkan options customer dengan cache
$customer_options = apply_filters('wp_customer_get_customer_options', [
    '' => __('Pilih Customer', 'your-textdomain')
], true); // Parameter kedua untuk include_empty

// Mendapatkan options kabupaten/kota dengan cache
$branch_options = apply_filters(
    'wp_customer_get_branch_options',
    [],
    $customer_id,
    true // Parameter ketiga untuk include_empty
);
```

### 2. Action untuk Render Select

```php
// Render customer select dengan atribut lengkap
do_action('wp_customer_customer_select', [
    'name' => 'my_customer',
    'id' => 'my_customer_field',
    'class' => 'my-select-class wp-customer-customer-select',
    'data-placeholder' => __('Pilih Customer', 'your-textdomain'),
    'required' => 'required',
    'aria-label' => __('Pilih Customer', 'your-textdomain')
], $selected_customer_id);

// Render branch select dengan loading state
do_action('wp_customer_branch_select', [
    'name' => 'my_branch',
    'id' => 'my_branch_field',
    'class' => 'my-select-class wp-customer-branch-select',
    'data-loading-text' => __('Memuat...', 'your-textdomain'),
    'required' => 'required',
    'aria-label' => __('Pilih Kabupaten/Kota', 'your-textdomain')
], $customer_id, $selected_branch_id);
```

## Implementasi JavaScript

### 1. Event Handling

```javascript
(function($) {
    'use strict';

    const WPSelect = {
        init() {
            this.bindEvents();
            this.setupLoadingState();
        },

        bindEvents() {
            $(document).on('change', '.wp-customer-customer-select', this.handleCustomerChange.bind(this));
            $(document).on('wilayah:loaded', '.wp-customer-branch-select', this.handleBranchLoaded.bind(this));
        },

        setupLoadingState() {
            this.$loadingIndicator = $('<span>', {
                class: 'wp-customer-loading',
                text: wpCustomerData.texts.loading
            }).hide();
        },

        handleCustomerChange(e) {
            const $customer = $(e.target);
            const $branch = $('.wp-customer-branch-select');
            const customerId = $customer.val();

            // Reset dan disable branch select
            this.resetBranchSelect($branch);

            if (!customerId) return;

            // Show loading state
            this.showLoading($branch);

            // Make AJAX call
            $.ajax({
                url: wpCustomerData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_branch_options',
                    customer_id: customerId,
                    nonce: wpCustomerData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $branch.html(response.data.html);
                        $branch.trigger('wilayah:loaded');
                    } else {
                        this.handleError(response.data.message);
                    }
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    this.handleError(errorThrown);
                },
                complete: () => {
                    this.hideLoading($branch);
                }
            });
        },

        resetBranchSelect($branch) {
            $branch.prop('disabled', true)
                   .html(`<option value="">${wpCustomerData.texts.select_branch}</option>`);
        },

        showLoading($element) {
            $element.prop('disabled', true);
            this.$loadingIndicator.insertAfter($element).show();
        },

        hideLoading($element) {
            $element.prop('disabled', false);
            this.$loadingIndicator.hide();
        },

        handleError(message) {
            console.error('WP Select Error:', message);
            if (typeof CustomerToast !== 'undefined') {
                CustomerToast.error(message || wpCustomerData.texts.error);
            }
        },

        handleBranchLoaded(e) {
            const $branch = $(e.target);
            // Custom handling setelah data loaded
        }
    };

    $(document).ready(() => WPSelect.init());

})(jQuery);
```

## Integrasi Cache System

Plugin ini menggunakan sistem cache WordPress untuk optimasi performa:

### 1. Cache Implementation

```php
class WPCache {
    private $cache_enabled;
    private $cache_duration;
    
    public function __construct() {
        $this->cache_enabled = apply_filters('wp_customer_enable_cache', true);
        $this->cache_duration = apply_filters('wp_customer_cache_duration', 3600);
    }
    
    public function get($key) {
        if (!$this->cache_enabled) return false;
        return wp_cache_get($key, 'wp_customer');
    }
    
    public function set($key, $data) {
        if (!$this->cache_enabled) return false;
        return wp_cache_set($key, $data, 'wp_customer', $this->cache_duration);
    }
    
    public function delete($key) {
        return wp_cache_delete($key, 'wp_customer');
    }
}
```

### 2. Penggunaan Cache

```php
// Di SelectListHooks.php
public function getCustomerOptions(array $default_options = [], bool $include_empty = true): array {
    $cache = new WPCache();
    $cache_key = 'customer_options_' . md5(serialize($default_options) . $include_empty);
    
    $options = $cache->get($cache_key);
    if (false !== $options) {
        return $options;
    }
    
    $options = $this->buildCustomerOptions($default_options, $include_empty);
    $cache->set($cache_key, $options);
    
    return $options;
}
```

## Error Handling & Debugging

### 1. PHP Error Handling

```php
try {
    // Operasi database atau file
} catch (\Exception $e) {
    error_log('WP Customer Plugin Error: ' . $e->getMessage());
    wp_send_json_error([
        'message' => __('Terjadi kesalahan saat memproses data', 'wp-customer')
    ]);
}
```

### 2. JavaScript Debugging

```javascript
// Aktifkan mode debug
add_filter('wp_customer_debug_mode', '__return_true');

// Di JavaScript
if (wpCustomerData.debug) {
    console.log('Customer changed:', customerId);
    console.log('AJAX response:', response);
}
```

## Testing & Troubleshooting

### 1. Unit Testing

```php
class WPSelectTest extends WP_UnitTestCase {
    public function test_customer_options() {
        $hooks = new SelectListHooks();
        $options = $hooks->getCustomerOptions();
        
        $this->assertIsArray($options);
        $this->assertArrayHasKey('', $options);
    }
}
```

### 2. Common Issues & Solutions

1. **Select Kabupaten Tidak Update**
   - Periksa Console Browser
   - Validasi nonce
   - Pastikan hook AJAX terdaftar

2. **Cache Tidak Bekerja**
   - Periksa Object Cache aktif
   - Validasi cache key
   - Cek durasi cache

3. **Loading State Tidak Muncul**
   - Periksa CSS terload
   - Validasi selector JavaScript
   - Cek konflik jQuery

## Support & Maintenance

### 1. Reporting Issues
- Gunakan GitHub Issues
- Sertakan error log
- Berikan langkah reproduksi

### 2. Development Workflow
1. Fork repository
2. Buat branch fitur
3. Submit pull request
4. Tunggu review

### 3. Kontribusi
- Ikuti coding standards
- Dokumentasikan perubahan
- Sertakan unit test

## Changelog

### Version 1.1.0 (2024-01-07)
- Implementasi loading state
- Perbaikan error handling
- Optimasi cache system
- Update dokumentasi

### Version 1.0.0 (2024-01-06)
- Initial release
- Basic select functionality
- Customer-branch relation
