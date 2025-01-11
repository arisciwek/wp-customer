<?php
/**
 * Customer Registration Form Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Auth
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/auth/register.php
 *
 * Description: Template untuk form registrasi customer baru.
 *              Menangani pendaftaran user WordPress sekaligus data customer.
 *              Form mencakup field username, email, password dan data customer
 *              seperti nama perusahaan, NIB, dan NPWP.
 *
 * Dependencies:
 * - jQuery
 * - wp-customer-toast
 * - WordPress AJAX
 * 
 * Changelog:
 * 1.0.0 - 2024-01-11
 * - Initial version
 * - Added registration form with validation
 * - Added AJAX submission handling
 * - Added NPWP formatter
 */

defined('ABSPATH') || exit;
?>

<h2><?php _e('Daftar Customer Baru', 'wp-customer'); ?></h2>

<form id="customer-register-form" class="wp-customer-form" method="post">
    <?php wp_nonce_field('wp_customer_register', 'register_nonce'); ?>

    <!-- Card untuk Informasi Login -->
    <div class="wp-customer-card">
        <div class="wp-customer-card-header">
            <h3><?php _e('Informasi Login', 'wp-customer'); ?></h3>
        </div>
        <div class="wp-customer-card-body">
            <!-- Username -->
            <div class="form-group">
                <label for="username">Username <span class="required">*</span></label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       class="regular-text" 
                       required>
                <p class="description"><?php _e('Username untuk login', 'wp-customer'); ?></p>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       class="regular-text" 
                       required>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="regular-text" 
                       required>
            </div>
        </div>
    </div>

    <!-- Card untuk Informasi Perusahaan -->
    <div class="wp-customer-card">
        <div class="wp-customer-card-header">
            <h3><?php _e('Informasi Perusahaan', 'wp-customer'); ?></h3>
        </div>
        <div class="wp-customer-card-body">
            <!-- Nama Lengkap/Perusahaan -->
            <div class="form-group">
                <label for="name">Nama Lengkap/Perusahaan <span class="required">*</span></label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       class="regular-text" 
                       required>
                <p class="description"><?php _e('Nama ini akan digunakan sebagai identitas customer', 'wp-customer'); ?></p>
            </div>

            <!-- NIB -->
            <div class="form-group">
                <label for="nib">Nomor Induk Berusaha (NIB) <span class="required">*</span></label>
                <input type="text" 
                       id="nib" 
                       name="nib" 
                       class="regular-text" 
                       required>
            </div>

            <!-- NPWP -->
            <div class="form-group">
                <label for="npwp">NPWP <span class="required">*</span></label>
                <input type="text" 
                       id="npwp" 
                       name="npwp" 
                       class="regular-text" 
                       required>
            </div>
        </div>
    </div>
	<div class="wp-customer-submit clearfix">
	    <div class="form-submit">
	        <button type="submit" class="button button-primary">
	            <?php _e('Daftar', 'wp-customer'); ?>
	        </button>
	    </div>
	</div>
</form>

