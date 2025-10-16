<?php
/**
 * Test Script for Review-06: BranchModel::getUserRelation() Debug Logging
 *
 * This script tests that the BranchModel::getUserRelation() method is
 * properly logging to debug.log with the correct format matching CustomerModel
 */

// Load WordPress
require_once '/home/mkt01/Public/wppm/public_html/wp-load.php';

// Ensure WP_DEBUG is enabled
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    echo "ERROR: WP_DEBUG is not enabled. Cannot test logging.\n";
    exit(1);
}

// Load the model
use WPCustomer\Models\Branch\BranchModel;

$branchModel = new BranchModel();

echo "=== Testing BranchModel::getUserRelation() Debug Logging ===\n\n";

// Test 1: Test with branch_id = 0 (general access check)
echo "Test 1: General access check (branch_id = 0)\n";
echo "----------------------------------------\n";
$result1 = $branchModel->getUserRelation(0);
echo "Access type: " . $result1['access_type'] . "\n";
echo "Is admin: " . ($result1['is_admin'] ? 'Yes' : 'No') . "\n";
echo "Is customer admin: " . ($result1['is_customer_admin'] ? 'Yes' : 'No') . "\n";
echo "Is branch admin: " . ($result1['is_branch_admin'] ? 'Yes' : 'No') . "\n";
echo "Is employee: " . ($result1['is_customer_employee'] ? 'Yes' : 'No') . "\n\n";

// Test 2: Test with specific branch_id
echo "Test 2: Specific branch check (branch_id = 32)\n";
echo "----------------------------------------\n";
$result2 = $branchModel->getUserRelation(32);
echo "Access type: " . $result2['access_type'] . "\n";
echo "Branch ID: " . $result2['branch_id'] . "\n";
echo "Customer ID: " . ($result2['customer_id'] ?? 'N/A') . "\n";
echo "Customer Name: " . ($result2['customer_name'] ?? 'N/A') . "\n";
echo "Branch Name: " . ($result2['branch_name'] ?? 'N/A') . "\n\n";

// Test 3: Test cache hit by calling again with same parameters
echo "Test 3: Testing cache hit (calling again with branch_id = 32)\n";
echo "----------------------------------------\n";
$result3 = $branchModel->getUserRelation(32);
echo "Access type: " . $result3['access_type'] . "\n";
echo "(Should see 'Cache hit' in debug.log)\n\n";

// Test 4: Test with non-existent branch
echo "Test 4: Non-existent branch (branch_id = 99999)\n";
echo "----------------------------------------\n";
$result4 = $branchModel->getUserRelation(99999);
echo "Access type: " . $result4['access_type'] . "\n";
echo "Has access: " . (($result4['access_type'] !== 'none') ? 'Yes' : 'No') . "\n\n";

echo "=== Test Complete ===\n";
echo "\nIMPORTANT: Check /home/mkt01/Public/wppm/public_html/wp-content/debug.log for:\n";
echo "1. 'BranchModel::getUserRelation - Cache miss for access_type X and branch Y' messages\n";
echo "2. 'BranchModel::getUserRelation - Cache hit for access_type X and branch Y' messages\n";
echo "3. 'Access Result: Array(...)' with full relation details\n";
echo "\nExpected log format should match CustomerModel pattern exactly.\n";