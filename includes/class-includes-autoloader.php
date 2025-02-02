<?php
/**
 * File: class-includes-autoloader.php
 * Path: /wp-customer/includes/class-includes-autoloader.php
 * Description: Menangani autoloading untuk file-file di direktori includes.
 *              Mendukung autoloading untuk subdirektori seperti docgen dan pdf.
 *              Mengikuti konvensi penamaan WordPress (class-*.php).
 * 
 * @package     WP_Customer
 * @subpackage  Includes
 * @version     1.0.1
 * @author      arisciwek
 * 
 * Description: Autoloader untuk file di direktori includes:
 *              - Menangani loading file-file class di direktori includes
 *              - Mengikuti konvensi penamaan WordPress (class-*.php)
 *              - Mendukung subdirektori seperti docgen dan pdf
 *              - Memiliki debug logging untuk troubleshooting
 * 
 * Dependencies:
 * - WP_DEBUG untuk logging
 * - Konvensi penamaan class WP_Customer_*
 * - Struktur direktori dengan /includes dan subdirektorinya
 * 
 * Changelog:
 * 1.0.1 - 2024-02-02
 * - Added support for docgen and pdf subdirectories
 * - Enhanced debug logging
 * - Added file existence checks
 * 
 * 1.0.0 - 2024-01-23  
 * - Initial creation
 * - Added basic autoloading functionality
 * - Added prefix handling
 */

class WP_Customer_Includes_Autoloader {
    private $baseDir;
    private $prefix;
    
    public function __construct($baseDir) {
        $this->baseDir = rtrim($baseDir, '/') . '/';
        $this->prefix = 'WP_Customer_';
    }
    
    public function register() {
        spl_autoload_register([$this, 'autoload']);
    }
    
    public function autoload($class) {
        // Only handle classes with our prefix
        if (strpos($class, $this->prefix) !== 0) {
            return;
        }

        // Debug logging if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Includes Autoloader attempting to load: " . $class);
        }

        // Remove prefix from class name
        $class_name = substr($class, strlen($this->prefix));
        
        // Convert class name format to file name format
        // Example: DocGen_Customer_Detail_Module -> class-docgen-customer-detail-module.php
        $file_name = 'class-' . strtolower(
            str_replace('_', '-', $class_name)
        ) . '.php';
        
        $file = $this->baseDir . $file_name;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Looking for file: " . $file);
            error_log("File exists: " . (file_exists($file) ? 'yes' : 'no'));
        }

        if (file_exists($file)) {
            require_once $file;
            return true;
        }
        
        // Also check in subdirectories
        $subdirs = ['docgen', 'pdf'];
        foreach ($subdirs as $subdir) {
            $file = $this->baseDir . $subdir . '/' . $file_name;
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
    }
}
