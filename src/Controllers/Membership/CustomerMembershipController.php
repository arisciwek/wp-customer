<?php
/**
 * Customer Membership Controller
 *
 * @package     WP_Customer
 * @subpackage  Controllers/Membership
 * @version     1.0.11
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Controllers/Membership/CustomerMembershipController.php
 *
 * Description: Controller untuk mengelola customer membership.
 *              Handles CRUD operations for membership features.
 *              Includes caching integration and security checks.
 *              
 * Dependencies:
 * - MembershipFeatureModel for data operations
 * - CustomerCacheManager for caching
 * - WordPress AJAX API
 * - WordPress Capability System
 *
 * Changelog:
 * 1.0.0 - 2024-02-10
 * - Initial version
 * - Added CRUD operations
 * - Added cache integration
 * - Added security checks
 */

namespace WPCustomer\Controllers\Membership;

use WPCustomer\Models\Company\CompanyMembershipModel;

class CustomerMembershipController {

    private $membershipModel;

    public function __construct() {
        $this->membershipModel = new CompanyMembershipModel();
    }

    /**
     * Create a new membership
     *
     * @param array $data Membership data
     * @return int|false Membership ID or false on failure
     */
    public function createMembership(array $data) {
        return $this->membershipModel->create($data);
    }
}
