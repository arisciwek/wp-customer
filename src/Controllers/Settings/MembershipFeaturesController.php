<?php
/**
 * Membership Features Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Settings/MembershipFeaturesController.php
 *
 * Description: CRUD controller untuk Membership Features entity.
 *              Extends AbstractCrudController dari wp-app-core.
 *              Handles membership features management in settings.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (Task-2204)
 * - Initial implementation
 * - Extends AbstractCrudController
 * - Implements 9 abstract methods
 * - Custom: getAllFeatures(), getFeatureGroups()
 * - AJAX hooks for CRUD operations
 */

namespace WPCustomer\Controllers\Settings;

use WPAppCore\Controllers\Abstract\AbstractCrudController;
use WPCustomer\Models\Settings\MembershipFeaturesModel;
use WPCustomer\Validators\Settings\MembershipFeaturesValidator;
use WPCustomer\Cache\MembershipFeaturesCacheManager;

defined('ABSPATH') || exit;

class MembershipFeaturesController extends AbstractCrudController {

    /**
     * @var MembershipFeaturesModel
     */
    private $model;

    /**
     * @var MembershipFeaturesValidator
     */
    private $validator;

    /**
     * @var MembershipFeaturesCacheManager
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = new MembershipFeaturesModel();
        $this->validator = new MembershipFeaturesValidator();
        $this->cache = MembershipFeaturesCacheManager::getInstance();

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
        add_action('wp_ajax_create_membership_feature', [$this, 'store']);
        add_action('wp_ajax_get_membership_feature', [$this, 'show']);
        add_action('wp_ajax_update_membership_feature', [$this, 'update']);
        add_action('wp_ajax_delete_membership_feature', [$this, 'delete']);

        // Custom hooks
        add_action('wp_ajax_get_all_membership_features', [$this, 'getAllFeaturesAjax']);
        add_action('wp_ajax_get_membership_feature_groups', [$this, 'getFeatureGroupsAjax']);
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
        return 'membership_feature';
    }

    /**
     * Get entity name (plural)
     *
     * @return string
     */
    protected function getEntityNamePlural(): string {
        return 'membership_features';
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
     * @return MembershipFeaturesValidator
     */
    protected function getValidator() {
        return $this->validator;
    }

    /**
     * Get model instance
     *
     * @return MembershipFeaturesModel
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
            'field_name' => sanitize_text_field($_POST['field_name'] ?? ''),
            'group_id' => isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0,
            'metadata' => $this->sanitizeJson($_POST['metadata'] ?? '{}'),
            'settings' => $this->sanitizeJson($_POST['settings'] ?? '{}'),
            'sort_order' => isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0,
            'status' => sanitize_text_field($_POST['status'] ?? 'active')
        ];
    }

    /**
     * Prepare data for update operation
     *
     * @param int $id Feature ID
     * @return array Sanitized data
     */
    protected function prepareUpdateData(int $id): array {
        $data = [];

        if (isset($_POST['field_name'])) {
            $data['field_name'] = sanitize_text_field($_POST['field_name']);
        }

        if (isset($_POST['group_id'])) {
            $data['group_id'] = (int) $_POST['group_id'];
        }

        if (isset($_POST['metadata'])) {
            $data['metadata'] = $this->sanitizeJson($_POST['metadata']);
        }

        if (isset($_POST['settings'])) {
            $data['settings'] = $this->sanitizeJson($_POST['settings']);
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
     * Override delete() to invalidate cache
     *
     * @return void
     */
    public function delete(): void {
        try {
            $this->verifyNonce();

            $id = $this->getId();

            // Check permission
            $this->checkPermission('delete');

            // Delete
            if (!$this->model->delete($id)) {
                throw new \Exception('Failed to delete membership feature');
            }

            // Clear cache
            $this->cache->invalidateMembershipFeatureCache($id);

            $this->sendSuccess(__('Membership feature deleted successfully', 'wp-customer'));

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

            // Prepare and validate
            $data = $this->prepareUpdateData($id);
            $this->validate($data, $id);

            // Update
            $updated = $this->model->update($id, $data);
            if (!$updated) {
                throw new \Exception('Failed to update membership feature');
            }

            // Clear cache
            $this->cache->invalidateMembershipFeatureCache($id);

            // Get updated data
            $feature = $this->model->find($id);

            wp_send_json_success([
                'message' => __('Membership feature updated successfully', 'wp-customer'),
                'data' => $feature
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
            $this->cache->invalidateMembershipFeatureCache($result);

            $this->sendSuccess(
                __('Membership feature created successfully', 'wp-customer'),
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
     * Get all features grouped by groups
     * Used by settings page view
     *
     * @return array Grouped features
     */
    public function getAllFeatures(): array {
        return $this->model->getActiveGroupsAndFeatures();
    }

    /**
     * Get feature groups
     * Used by settings page view
     *
     * @return array Groups
     */
    public function getFeatureGroups(): array {
        return $this->model->getFeatureGroups();
    }

    /**
     * AJAX endpoint to get all features
     *
     * @return void
     */
    public function getAllFeaturesAjax(): void {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new \Exception('Permission denied');
            }

            $features = $this->getAllFeatures();

            wp_send_json_success([
                'message' => 'Features retrieved successfully',
                'data' => $features
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX endpoint to get feature groups
     *
     * @return void
     */
    public function getFeatureGroupsAjax(): void {
        try {
            check_ajax_referer('wp_customer_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new \Exception('Permission denied');
            }

            $groups = $this->getFeatureGroups();

            wp_send_json_success([
                'message' => 'Groups retrieved successfully',
                'data' => $groups
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Sanitize JSON data
     *
     * @param mixed $data Data to sanitize
     * @return string JSON string
     */
    private function sanitizeJson($data): string {
        if (is_string($data)) {
            // WordPress automatically adds slashes to POST data, strip them
            $data = stripslashes($data);

            // Validate JSON
            $decoded = json_decode($data, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return wp_json_encode($decoded);
            }
            return '{}';
        }

        if (is_array($data)) {
            return wp_json_encode($data);
        }

        return '{}';
    }
}
