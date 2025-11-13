<?php
/**
 * Membership Features Validator
 *
 * @package     WP_Customer
 * @subpackage  Validators/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Validators/Settings/MembershipFeaturesValidator.php
 *
 * Description: Validator untuk Membership Features CRUD operations.
 *              Extends AbstractValidator dari wp-app-core.
 *              Handles form validation dan permission checks.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (Task-2204)
 * - Initial implementation
 * - Extends AbstractValidator
 * - Implements 13 abstract methods
 * - Custom validation: field_name uniqueness, JSON format
 */

namespace WPCustomer\Validators\Settings;

use WPAppCore\Validators\Abstract\AbstractValidator;
use WPCustomer\Models\Settings\MembershipFeaturesModel;

defined('ABSPATH') || exit;

class MembershipFeaturesValidator extends AbstractValidator {

    /**
     * @var MembershipFeaturesModel
     */
    private $model;

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = new MembershipFeaturesModel();
    }

    // ========================================
    // IMPLEMENT ABSTRACT METHODS (13 required)
    // ========================================

    /**
     * Get entity name
     *
     * @return string
     */
    protected function getEntityName(): string {
        return 'membership_feature';
    }

    /**
     * Get entity display name
     *
     * @return string
     */
    protected function getEntityDisplayName(): string {
        return 'Membership Feature';
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
     * Get model instance
     *
     * @return MembershipFeaturesModel
     */
    protected function getModel() {
        return $this->model;
    }

    /**
     * Get create capability
     *
     * @return string
     */
    protected function getCreateCapability(): string {
        return 'manage_options';
    }

    /**
     * Get view capabilities
     *
     * @return array
     */
    protected function getViewCapabilities(): array {
        return ['manage_options'];
    }

    /**
     * Get update capabilities
     *
     * @return array
     */
    protected function getUpdateCapabilities(): array {
        return ['manage_options'];
    }

    /**
     * Get delete capability
     *
     * @return string
     */
    protected function getDeleteCapability(): string {
        return 'manage_options';
    }

    /**
     * Get list capability
     *
     * @return string
     */
    protected function getListCapability(): string {
        return 'manage_options';
    }

    /**
     * Validate create operation
     *
     * @param array $data Data to validate
     * @return array Errors (empty if valid)
     */
    protected function validateCreate(array $data): array {
        return $this->validateFormFields($data);
    }

    /**
     * Validate update operation
     *
     * @param int $id Entity ID
     * @param array $data Data to validate
     * @return array Errors (empty if valid)
     */
    protected function validateUpdate(int $id, array $data): array {
        return $this->validateFormFields($data, $id);
    }

    /**
     * Validate view operation
     *
     * @param int $id Entity ID
     * @return array Errors (empty if valid)
     */
    protected function validateView(int $id): array {
        if (!current_user_can('manage_options')) {
            return ['permission' => __('You do not have permission to view membership features.', 'wp-customer')];
        }
        return [];
    }

    /**
     * Validate delete operation
     *
     * @param int $id Entity ID
     * @return array Errors (empty if valid)
     */
    protected function validateDeleteOperation(int $id): array {
        $errors = [];

        // Check permission
        if (!current_user_can('manage_options')) {
            $errors[] = __('You do not have permission to delete membership features', 'wp-customer');
            return $errors;
        }

        // Check if feature exists
        $feature = $this->model->find($id);
        if (!$feature) {
            $errors[] = __('Membership feature not found', 'wp-customer');
        }

        return $errors;
    }

    /**
     * Check if user can create
     *
     * @return bool
     */
    protected function canCreate(): bool {
        return current_user_can('manage_options');
    }

    /**
     * Check if user can update
     *
     * @param int $id Entity ID
     * @return bool
     */
    protected function canUpdateEntity(int $id): bool {
        return current_user_can('manage_options');
    }

    /**
     * Check if user can view
     *
     * @param int $id Entity ID
     * @return bool
     */
    protected function canViewEntity(int $id): bool {
        return current_user_can('manage_options');
    }

    /**
     * Check if user can delete
     *
     * @param int $id Entity ID
     * @return bool
     */
    protected function canDeleteEntity(int $id): bool {
        return current_user_can('manage_options');
    }

    /**
     * Check if user can list
     *
     * @return bool
     */
    protected function canList(): bool {
        return current_user_can('manage_options');
    }

    /**
     * Check if user can view based on relation
     *
     * @param array $relation Relation data (not used for membership features - admin only)
     * @return bool
     */
    protected function checkViewPermission(array $relation): bool {
        return current_user_can('manage_options');
    }

    /**
     * Check if user can update based on relation
     *
     * @param array $relation Relation data (not used for membership features - admin only)
     * @return bool
     */
    protected function checkUpdatePermission(array $relation): bool {
        return current_user_can('manage_options');
    }

    /**
     * Check if user can delete based on relation
     *
     * @param array $relation Relation data (not used for membership features - admin only)
     * @return bool
     */
    protected function checkDeletePermission(array $relation): bool {
        return current_user_can('manage_options');
    }

    // ========================================
    // CUSTOM VALIDATION METHODS
    // ========================================

    /**
     * Validate form fields
     *
     * @param array $data Data to validate
     * @param int|null $id Entity ID (for update)
     * @return array Errors (empty if valid)
     */
    protected function validateFormFields(array $data, ?int $id = null): array {
        $errors = [];

        // Field name validation
        $field_name = trim($data['field_name'] ?? '');
        if (empty($field_name)) {
            $errors['field_name'] = __('Field name is required.', 'wp-customer');
        } elseif (strlen($field_name) > 50) {
            $errors['field_name'] = __('Field name maximum 50 characters.', 'wp-customer');
        } elseif ($this->model->existsByFieldName($field_name, $id)) {
            $errors['field_name'] = __('Field name already exists.', 'wp-customer');
        }

        // Group ID validation
        if (empty($data['group_id'])) {
            $errors['group_id'] = __('Group is required', 'wp-customer');
        } elseif (!is_numeric($data['group_id'])) {
            $errors['group_id'] = __('Invalid group ID', 'wp-customer');
        }

        // Metadata validation (should be valid JSON)
        if (isset($data['metadata'])) {
            if (is_string($data['metadata'])) {
                json_decode($data['metadata']);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors['metadata'] = __('Invalid metadata JSON format', 'wp-customer');
                }
            } elseif (!is_array($data['metadata'])) {
                $errors['metadata'] = __('Metadata must be array or valid JSON string', 'wp-customer');
            }
        } else {
            $errors['metadata'] = __('Metadata is required', 'wp-customer');
        }

        // Settings validation (should be valid JSON)
        if (isset($data['settings'])) {
            if (is_string($data['settings'])) {
                json_decode($data['settings']);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors['settings'] = __('Invalid settings JSON format', 'wp-customer');
                }
            } elseif (!is_array($data['settings'])) {
                $errors['settings'] = __('Settings must be array or valid JSON string', 'wp-customer');
            }
        } else {
            $errors['settings'] = __('Settings is required', 'wp-customer');
        }

        // Sort order validation
        if (isset($data['sort_order']) && !is_numeric($data['sort_order'])) {
            $errors['sort_order'] = __('Sort order must be a number', 'wp-customer');
        }

        // Status validation
        if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive'])) {
            $errors['status'] = __('Invalid status value', 'wp-customer');
        }

        return $errors;
    }
}
