<?php
/**
 * Customer Employee List Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/CustomerEmployee/Partials
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/customer-employee/partials/_customer_employee_profile_field.php
 *
 * Description: Template untuk menampilkan profile karyawan.
 *
 * Changelog:
 * 1.0.0 - 2024-01-12
 * - Initial release
 * - Added loading states
 * - Added empty state messages
 * - Added proper DataTable structure
 */

defined('ABSPATH') || exit;

?>


<h2>Informasi Tambahan Karyawan</h2>
<table class="form-table">
    <tr>
        <th><label>Customer</label></th>
        <td><?php echo esc_html($employeeData['customer_name'] ?? '-'); ?></td>
    </tr>
    <tr>
        <th><label>Cabang</label></th>
        <td><?php echo esc_html($employeeData['branch_name'] ?? '-'); ?></td>
    </tr>
    <tr>
        <th><label>Jabatan</label></th>
        <td><?php echo esc_html($employeeData['position'] ?? '-'); ?></td>
    </tr>
</table>

<h2>Role & Capabilities</h2>
<table class="form-table">
    <tr>
        <th><label>Roles</label></th>
        <td>
            <?php echo implode(', ', array_map('esc_html', $user_roles)); ?>
        </td>
    </tr>
    <tr>
        <th><label>Capabilities</label></th>
        <td>
            <ul style="columns: 2;">
                <?php foreach ($user_capabilities as $cap) : ?>
                    <li><?php echo esc_html($cap); ?></li>
                <?php endforeach; ?>
            </ul>
        </td>
    </tr>
</table>
