<?php
/**
 * Template for customer registration page
 */
defined('ABSPATH') || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('Daftar Customer Baru', 'wp-customer'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('wp-customer-registration-page'); ?>>
    <?php wp_body_open(); ?>
    
    <div class="wp-customer-page-wrapper">
        <div class="wp-customer-register-container">
            <?php include WP_CUSTOMER_PATH . 'src/Views/templates/auth/register.php'; ?>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>

