# External Integration - Filter Hooks

Filters for integrating with external plugins and services.

## Available Integration Filters

### wilayah_indonesia_get_province_options

**Purpose**: Get province dropdown options

**Location**: `src/Models/Customer/CustomerModel.php:861`

**Parameters**:
- `$options` (array) - Default options array

**Returns**: `array`

**Option Structure**:
```php
[
    'province_id' => 'Province Name',
    // ...
]
```

**Example**:
```php
add_filter('wilayah_indonesia_get_province_options', 'get_provinces_from_api', 10, 1);

function get_provinces_from_api($options) {
    // Fetch from external API
    $response = wp_remote_get('https://api.wilayah.id/provinces');

    if (is_wp_error($response)) {
        return $options;  // Fallback to default
    }

    $provinces = json_decode(wp_remote_retrieve_body($response), true);

    $new_options = [];
    foreach ($provinces as $province) {
        $new_options[$province['id']] = $province['name'];
    }

    return $new_options;
}
```

---

### wilayah_indonesia_get_regency_options

**Purpose**: Get regency/city dropdown options

**Location**: `src/Models/Customer/CustomerModel.php:867`

**Parameters**:
- `$options` (array) - Default options array
- `$province_id` (int) - Province ID filter

**Returns**: `array`

**Example**:
```php
add_filter('wilayah_indonesia_get_regency_options', 'get_regencies_from_api', 10, 2);

function get_regencies_from_api($options, $province_id) {
    if (!$province_id) {
        return $options;
    }

    $response = wp_remote_get("https://api.wilayah.id/regencies/{$province_id}");

    if (is_wp_error($response)) {
        return $options;
    }

    $regencies = json_decode(wp_remote_retrieve_body($response), true);

    $new_options = [];
    foreach ($regencies as $regency) {
        $new_options[$regency['id']] = $regency['name'];
    }

    return $new_options;
}
```

---

## Integration with wp-wilayah-indonesia Plugin

The wp-wilayah-indonesia plugin should implement these filters:

```php
// In wp-wilayah-indonesia plugin
add_filter('wilayah_indonesia_get_province_options', 'wilayah_get_provinces', 10, 1);

function wilayah_get_provinces($options) {
    global $wpdb;

    $provinces = $wpdb->get_results(
        "SELECT id, name FROM {$wpdb->prefix}wilayah_provinces ORDER BY name",
        ARRAY_A
    );

    $options = [];
    foreach ($provinces as $province) {
        $options[$province['id']] = $province['name'];
    }

    return $options;
}
```

---

**Back to**: [README.md](../README.md)
