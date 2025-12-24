<?php
/**
 * Membership Groups Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Settings/MembershipGroupsController.php
 *
 * Description: CRUD controller untuk Membership Groups entity.
 *              Extends AbstractCrudController dari wp-app-core.
 *              Handles membership groups management in settings.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (Task-2204)
 * - Initial implementation
 * - Extends AbstractCrudController
 * - Implements 9 abstract methods
 * - Custom: getAllGroups(), getGroupsByCapabilityGroup()
 * - AJAX hooks for CRUD operations
 */

namespace WPCustomer\Controllers\Settings;

use WPAppCore\Controllers\Abstract\AbstractCrudController;
use WPCustomer\Models\Settings\MembershipGroupsModel;
use WPCustomer\Validators\Settings\MembershipGroupsValidator;
use WPCustomer\Cache\MembershipGroupsCacheManager;

defined('ABSPATH') || exit;

class MembershipGroupsController extends AbstractCrudController {

    /**
     * @var MembershipGroupsModel
     */
    private $model;

    /**
     * @var MembershipGroupsValidator
     */
    private $validator;

    /**
     * @var MembershipGroupsCacheManager
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = new MembershipGroupsModel();
        $this->validator = new MembershipGroupsValidator();
        $this->cache = MembershipGroupsCacheManager::getInstance();

        // Register AJAX hooks
        $this->registerAjaxHooks();
    }

    /**
     * Register AJAX hooks
     *
     * @return void
     */
    private function registerAjaxHooks(): void {
        // CRUD hooks
        add_action('wp_ajax_create_membership_group', [$this, 'store']);
        add_action('wp_ajax_get_membership_group', [$this, 'show']);
        add_action('wp_ajax_update_membership_group', [$this, 'update']);
        add_action('wp_ajax_delete_membership_group', [$this, 'delete']);

        // Custom hooks
        add_action('wp_ajax_get_all_membership_groups', [$this, 'getAllGroupsAjax']);
        add_action('wp_ajax_get_membership_groups_by_capability', [$this, 'getGroupsByCapabilityAjax']);

        // Modal content hook
        add_action('wp_ajax_get_groups_modal_content', [$this, 'getModalContent']);
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (9 required)
    // ========================================

    /**
     * Get entity name (singular)
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'membership_group';
    }

    /**
     * Get entity name (plural)
     *
     * @return string
     */
    protected function getEntityNamePlural(): string {
        return 'membership_groups';
    }

    /**
     * Get nonce action
     *
     * @return string
     */
    protected function getNonceAction(): string {
        return 'wp_customer_nonce';
    }

    /**
     * Get text domain
     *
     * @return string
     */
    protected function getTextDomain(): string {
        return 'wp-customer';
    }

    /**
     * Get validator instance
     *
     * @return MembershipGroupsValidator
     */
    protected function getValidator() {
        return $this->validator;
    }

    /**
     * Get model instance
     *
     * @return MembershipGroupsModel
     */
    protected function getModel() {
        return $this->model;
    }

    /**
     * Get cache group
     *
     * @return string
     */
    protected function getCacheGroup(): string {
        return 'wp_customer';
    }

    /**
     * Prepare data for create operation
     *
     * @return array Sanitized data
     */
    protected function prepareCreateData(): array {
        return [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'slug' => sanitize_title($_POST['slug'] ?? ''),
            'capability_group' => sanitize_text_field($_POST['capability_group'] ?? 'features'),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'sort_order' => isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0,
            'status' => sanitize_text_field($_POST['status'] ?? 'active')
        ];
    }

    /**
     * Prepare data for update operation
     *
     * @param int $id Group ID
     * @return array Sanitized data
     */
    protected function prepareUpdateData(int $id): array {
        $data = [];

        if (isset($_POST['name'])) {
            $data['name'] = sanitize_text_field($_POST['name']);
        }

        if (isset($_POST['slug'])) {
            $data['slug'] = sanitize_title($_POST['slug']);
        }

        if (isset($_POST['capability_group'])) {
            $data['capability_group'] = sanitize_text_field($_POST['capability_group']);

            // Validate capability_group
            if (!in_array($data['capability_group'], ['features', 'limits', 'notifications'])) {
                $data['capability_group'] = 'features';
            }
        }

        if (isset($_POST['description'])) {
            $data['description'] = sanitize_textarea_field($_POST['description']);
        }

        if (isset($_POST['sort_order'])) {
            $data['sort_order'] = (int) $_POST['sort_order'];
        }

        if (isset($_POST['status'])) {
            $data['status'] = sanitize_text_field($_POST['status']);

            // Validate status
            if (!in_array($data['status'], ['active', 'inactive'])) {
                $data['status'] = 'active';
            }
        }

        return $data;
    }

    // ========================================
    // OVERRIDE CRUD METHODS FOR CUSTOM LOGIC
    // ========================================

    /**
     * Override show() to fix action name
     * AbstractCrudController uses 'read' but AbstractValidator expects 'view'
     *
     * @return void
     */
    public function show(): void {
        try {
            // Verify nonce
            $this->verifyNonce();

            // Check permission (use 'view' instead of 'read')
            $this->checkPermission('view');

            // Get and validate ID
            $id = $this->getId();

            // Get entity via model
            $entity = $this->getModel()->find($id);

            if (!$entity) {
                throw new \Exception(
                    sprintf(
                        __('%s not found', $this->getTextDomain()),
                        ucfirst($this->getEntityName())
                    )
                );
            }

            // Send success response
            wp_send_json_success([
                'message' => __('Data retrieved successfully', $this->getTextDomain()),
                'data' => $entity
            ]);

        } catch (\Exception $e) {
            $this->handleError($e, 'read');
        }
    }

    /**
     * Override delete() to validate and invalidate cache
     *
     * @return void
     */
    public function delete(): void {
        try {
            $this->verifyNonce();

            $id = $this->getId();

            // Check permission
            $this->checkPermission('delete');

            // IMPORTANT: Validate delete operation (check if group has features)
            $deleteErrors = $this->validator->validateDelete($id);
            if (!empty($deleteErrors)) {
                throw new \Exception(implode(' ', $deleteErrors));
            }

            // Get group info before deletion (for cache invalidation)
            $group = $this->model->find($id);
            $slug = $group->slug ?? null;

            // Delete
            if (!$this->model->delete($id)) {
                throw new \Exception('Failed to delete membership group');
            }

            // Clear cache
            $this->cache->invalidateMembershipGroupCache($id, $slug);

            $this->sendSuccess(__('Membership group deleted successfully', 'wp-customer'));

        } catch (\Exception $e) {
            $this->handleError($e, 'delete');
        }
    }

    /**
     * Override update() to invalidate cache
     *
     * @return void
     */
    public function update(): void {
        try {
            $this->verifyNonce();

            $id = $this->getId();
            $this->checkPermission('update');

            // Get old group info for cache invalidation
            $old_group = $this->model->find($id);
            $old_slug = $old_group->slug ?? null;

            // Prepare and validate
            $data = $this->prepareUpdateData($id);
            $this->validate($data, $id);

            // Update
            $updated = $this->model->update($id, $data);
            if (!$updated) {
                throw new \Exception('Failed to update membership group');
            }

            // Clear cache
            $new_slug = $data['slug'] ?? $old_slug;
            $this->cache->invalidateMembershipGroupCache($id, $old_slug);

            // If slug changed, also clear new slug cache
            if ($new_slug && $new_slug !== $old_slug) {
                $this->cache->delete('membership_group_by_slug', $new_slug);
            }

            // Get updated data
            $group = $this->model->find($id);

            wp_send_json_success([
                'message' => __('Membership group updated successfully', 'wp-customer'),
                'data' => $group
            ]);

        } catch (\Exception $e) {
            $this->handleError($e, 'update');
        }
    }

    /**
     * Override store() to invalidate cache
     *
     * @return void
     */
    public function store(): void {
        try {
            $this->verifyNonce();
            $this->checkPermission('create');

            // Prepare data
            $data = $this->prepareCreateData();

            // Validate
            $this->validate($data);

            // Create
            $result = $this->model->create($data);

            // Clear cache
            $this->cache->invalidateMembershipGroupCache($result, $data['slug']);

            $this->sendSuccess(
                __('Membership group created successfully', 'wp-customer'),
                $result
            );

        } catch (\Exception $e) {
            $this->handleError($e, 'create');
        }
    }

    // ========================================
    // CUSTOM METHODS
    // ========================================

    /**
     * Get all active groups
     * Used by settings page view
     *
     * @return array Active groups
     */
    public function getAllGroups(): array {
        return $this->model->getAllActiveGroups();
    }

    /**
     * Get groups by capability group
     * Used by settings page view
     *
     * @param string $capability_group Capability group
     * @return array Groups
     */
    public function getGroupsByCapabilityGroup(string $capability_group): array {
        return $this->model->getGroupsByCapabilityGroup($capability_group);
    }

    /**
     * AJAX endpoint to get all groups
     *
     * @return void
     */
    public function getAllGroupsAjax(): void {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new \Exception('Permission denied');
            }

            $groups = $this->getAllGroups();

            wp_send_json_success([
                'message' => 'Groups retrieved successfully',
                'data' => $groups
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX endpoint to get groups by capability group
     *
     * @return void
     */
    public function getGroupsByCapabilityAjax(): void {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new \Exception('Permission denied');
            }

            $capability_group = sanitize_text_field($_POST['capability_group'] ?? '');

            if (empty($capability_group)) {
                throw new \Exception('Capability group is required');
            }

            $groups = $this->getGroupsByCapabilityGroup($capability_group);

            wp_send_json_success([
                'message' => 'Groups retrieved successfully',
                'data' => $groups
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Get modal content HTML
     * Returns rendered modal template with groups data
     *
     * @return void
     */
    public function getModalContent(): void {
        // Verify nonce - check both GET and POST
        $nonce = $_REQUEST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'wp_customer_nonce')) {
            wp_die('<div class="notice notice-error"><p>Invalid nonce</p></div>');
        }

        if (!current_user_can('manage_options')) {
            wp_die('<div class="notice notice-error"><p>Permission denied</p></div>');
        }

        // Get all active groups
        $groups = $this->getAllGroups();

        // Build template path - ensure trailing slash
        $plugin_path = defined('WP_CUSTOMER_PATH') ? WP_CUSTOMER_PATH : plugin_dir_path(dirname(dirname(dirname(__FILE__)))) . '/';
        $template_path = $plugin_path . 'src/Views/modals/membership-groups-modal.php';

        // Debug: Check if path is correct
        if (!file_exists($template_path)) {
            $error_msg = 'Modal template not found.<br>';
            $error_msg .= 'Plugin Path: ' . esc_html($plugin_path) . '<br>';
            $error_msg .= 'Template Path: ' . esc_html($template_path) . '<br>';
            $error_msg .= 'File exists: ' . (file_exists($template_path) ? 'YES' : 'NO');
            wp_die('<div class="notice notice-error"><p>' . $error_msg . '</p></div>');
        }

        // Set header for HTML response
        header('Content-Type: text/html; charset=utf-8');

        // Include template directly (no output buffering to avoid conflicts)
        include $template_path;

        wp_die(); // Clean exit
    }
}
