<?php
/**
 * Enhanced Autoloader for WP Customer Plugin
 *
 * @package     WP_Customer
 * @subpackage  Includes
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/includes/class-autoloader.php
 *
 * Description: Advanced autoloader untuk WP Customer plugin.
 *              Menangani autoloading dengan:
 *              - Validasi class name
 *              - Namespace mapping
 *              - Cache management
 *              - Error handling dan debug
 *              - File existence checking
 *              
 * Dependencies:
 * - WordPress core
 * - WP_DEBUG untuk logging
 * 
 * Usage:
 * require_once WP_CUSTOMER_PATH . 'includes/class-autoloader.php';
 * $autoloader = new WPCustomerAutoloader('WPCustomer\\', plugin_dir_path(__FILE__));
 * $autoloader->register();
 *
 * Changelog:
 * 1.0.0 - 2024-02-11
 * - Initial release
 * - Added namespace mapping
 * - Added cache management
 * - Added debug logging
 * - Added class validation
 */

class WPCustomerAutoloader {
    private $prefix;
    private $baseDir;
    private $mappings = [];
    private $loadedClasses = [];
    private $debugMode;
    
    public function __construct($prefix, $baseDir) {
        $this->prefix = $prefix;
        $this->baseDir = rtrim($baseDir, '/\\') . '/';
        $this->debugMode = defined('WP_DEBUG') && WP_DEBUG;
        
        // Add default mapping
        $this->addMapping('', 'src/');
    }
    
    /**
     * Add custom namespace to directory mapping
     */
    public function addMapping($namespace, $directory) {
        $namespace = trim($namespace, '\\');
        $this->mappings[$namespace] = rtrim($directory, '/\\') . '/';
    }
    
    /**
     * Register the autoloader
     */
    public function register() {
        spl_autoload_register([$this, 'loadClass']);
    }
    
    /**
     * Unregister the autoloader
     */
    public function unregister() {
        spl_autoload_unregister([$this, 'loadClass']);
    }
    
    /**
     * Main class loading method
     */
    public function loadClass($class) {
        try {
            // Check if class already loaded
            if (isset($this->loadedClasses[$class])) {
                return true;
            }
            
            // Validate class name format
            if (!$this->isValidClassName($class)) {
                $this->log("Invalid class name format: $class");
                return false;
            }
            
            // Check if class uses our namespace
            if (strpos($class, $this->prefix) !== 0) {
                return false;
            }
            
            // Get the relative class name
            $relativeClass = substr($class, strlen($this->prefix));
            
            // Find matching namespace mapping
            $mappedPath = $this->findMappedPath($relativeClass);
            if (!$mappedPath) {
                $this->log("No mapping found for class: $class");
                return false;
            }
            
            // Build the full file path
            $file = $this->baseDir . $mappedPath;
            
            // Check if file exists
            if (!$this->validateFile($file)) {
                $this->log("File not found or not readable: $file");
                return false;
            }
            
            // Load the file
            require_once $file;
            
            // Verify class was actually loaded
            if (!$this->verifyClassLoaded($class)) {
                $this->log("Class $class not found in file $file");
                return false;
            }
            
            // Mark class as loaded
            $this->loadedClasses[$class] = true;
            $this->log("Successfully loaded class: $class");
            
            return true;
            
        } catch (\Exception $e) {
            $this->log("Error loading class $class: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate class name format
     */
    private function isValidClassName($class) {
        return preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*$/', $class);
    }
    
    /**
     * Find mapped path for class
     */
    private function findMappedPath($relativeClass) {
        // Try each mapping from most specific to least
        foreach ($this->mappings as $namespace => $directory) {
            if (empty($namespace) || strpos($relativeClass, $namespace) === 0) {
                $classPath = empty($namespace) ? $relativeClass : substr($relativeClass, strlen($namespace));
                return $directory . str_replace('\\', '/', $classPath) . '.php';
            }
        }
        return false;
    }
    
    /**
     * Validate file exists and is readable
     */
    private function validateFile($file) {
        if (!file_exists($file)) {
            $this->log("File does not exist: $file");
            return false;
        }
        
        if (!is_readable($file)) {
            $this->log("File not readable: $file");
            return false;
        }
        
        return true;
    }
    
    /**
     * Verify class was actually loaded
     */
    private function verifyClassLoaded($class) {
        return class_exists($class, false) || 
               interface_exists($class, false) || 
               trait_exists($class, false);
    }
    
    /**
     * Debug logging
     */
    private function log($message) {
        if ($this->debugMode) {
            // error_log("[WPCustomerAutoloader] $message");
        }
    }
    
    /**
     * Get list of loaded classes
     */
    public function getLoadedClasses() {
        return array_keys($this->loadedClasses);
    }
    
    /**
     * Clear loaded classes cache
     */
    public function clearCache() {
        $this->loadedClasses = [];
    }
}
