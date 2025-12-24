<?php
/**
 * Membership Groups Validator
 *
 * @package     WP_Customer
 * @subpackage  Validators/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Validators/Settings/MembershipGroupsValidator.php
 *
 * Description: Validator untuk Membership Groups CRUD operations.
 *              Extends AbstractValidator dari wp-app-core.
 *              Handles form validation dan permission checks.
 *
 * Changelog:
 * 1.0.0 - 2025-01-13 (Task-2204)
 * - Initial implementation
 * - Extends AbstractValidator
 * - Implements 13 abstract methods
 * - Custom validation: slug uniqueness, name format
 */

namespace WPCustomer\Validators\Settings;

use WPAppCore\Validators\Abstract\AbstractValidator;
use WPCustomer\Models\Settings\MembershipGroupsModel;

defined('ABSPATH') || exit;

class MembershipGroupsValidator extends AbstractValidator {

    /**
     * @var MembershipGroupsModel
     */
    private $model;

    /**
     * Constructor
     */
    public function __construct() {
        $this->model = new MembershipGroupsModel();
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
        return 'membership_group';
    }

    /**
     * Get entity display name
     *
     * @return string
     */
    protected function getEntityDisplayName(): string {
        return 'Membership Group';
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
     * @return MembershipGroupsModel
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
            return ['permission' => __('You do not have permission to view membership groups.', 'wp-customer')];
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
            $errors[] = __('You do not have permission to delete membership groups', 'wp-customer');
            return $errors;
        }

        // Check if group exists
        $group = $this->model->find($id);
        if (!$group) {
            $errors[] = __('Membership group not found', 'wp-customer');
            return $errors;
        }

        // Check if group has features
        $feature_count = $this->model->countFeatures($id);
        if ($feature_count > 0) {
            $errors[] = sprintf(
                __('Cannot delete group. It has %d active features. Please delete or move the features first.', 'wp-customer'),
                $feature_count
            );
        }

        return $errors;
    }

    /**
     * Public wrapper for validateDeleteOperation
     * Allows controller to validate delete before executing
     *
     * @param int $id Entity ID
     * @return array Errors (empty if valid)
     */
    public function validateDelete(int $id): array {
        return $this->validateDeleteOperation($id);
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
     * @param array $relation Relation data (not used for membership groups - admin only)
     * @return bool
     */
    protected function checkViewPermission(array $relation): bool {
        return current_user_can('manage_options');
    }

    /**
     * Check if user can update based on relation
     *
     * @param array $relation Relation data (not used for membership groups - admin only)
     * @return bool
     */
    protected function checkUpdatePermission(array $relation): bool {
        return current_user_can('manage_options');
    }

    /**
     * Check if user can delete based on relation
     *
     * @param array $relation Relation data (not used for membership groups - admin only)
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

        // Name validation
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors['name'] = __('Group name is required.', 'wp-customer');
        } elseif (strlen($name) > 50) {
            $errors['name'] = __('Group name maximum 50 characters.', 'wp-customer');
        }

        // Slug validation
        $slug = trim($data['slug'] ?? '');
        if (empty($slug)) {
            $errors['slug'] = __('Slug is required.', 'wp-customer');
        } elseif (strlen($slug) > 50) {
            $errors['slug'] = __('Slug maximum 50 characters.', 'wp-customer');
        } elseif (!preg_match('/^[a-z0-9-_]+$/', $slug)) {
            $errors['slug'] = __('Slug can only contain lowercase letters, numbers, hyphens, and underscores.', 'wp-customer');
        } elseif ($this->model->existsBySlug($slug, $id)) {
            $errors['slug'] = __('Slug already exists.', 'wp-customer');
        }

        // Capability group validation
        $capability_group = trim($data['capability_group'] ?? '');
        if (empty($capability_group)) {
            $errors['capability_group'] = __('Capability group is required.', 'wp-customer');
        } elseif (strlen($capability_group) > 50) {
            $errors['capability_group'] = __('Capability group maximum 50 characters.', 'wp-customer');
        } elseif (!in_array($capability_group, ['features', 'limits', 'notifications'])) {
            $errors['capability_group'] = __('Invalid capability group. Must be: features, limits, or notifications.', 'wp-customer');
        }

        // Description validation (optional)
        if (isset($data['description']) && !empty($data['description'])) {
            $description = trim($data['description']);
            if (strlen($description) > 500) {
                $errors['description'] = __('Description maximum 500 characters.', 'wp-customer');
            }
        }

        // Sort order validation
        if (isset($data['sort_order'])) {
            if (!is_numeric($data['sort_order'])) {
                $errors['sort_order'] = __('Sort order must be a number', 'wp-customer');
            } elseif ($data['sort_order'] < 0) {
                $errors['sort_order'] = __('Sort order must be a positive number', 'wp-customer');
            }
        }

        // Status validation
        if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive'])) {
            $errors['status'] = __('Invalid status value', 'wp-customer');
        }

        return $errors;
    }
}
