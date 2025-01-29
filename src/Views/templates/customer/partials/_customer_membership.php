<?php
/**
 * Customer Membership Tab Template
 *
 * @package     WP_Customer
 * @subpackage  Views/Templates/Customer/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/src/Views/templates/customer/partials/_customer_membership.php
 *
 * Description: Template untuk menampilkan informasi membership customer
 *              Menampilkan status membership aktif, penggunaan staff,
 *              fitur yang tersedia, dan opsi upgrade ke level yang
 *              lebih tinggi. Template ini bersifat read-only dengan
 *              opsi aksi upgrade membership.
 *
 * Components:
 * - Membership status card
 * - Staff usage progress bar
 * - Active capabilities list
 * - Upgrade plan cards (Regular/Priority/Utama)
 *
 * Dependencies:
 * - wp-customer-membership.css
 * - wp-customer-membership.js
 * - WP_Customer_Settings class
 * - membership-settings.php
 *
 * Changelog:
 * v1.0.0 - 2024-01-10
 * - Initial version
 * - Added membership status display
 * - Added staff usage visualization
 * - Added capabilities list
 * - Added upgrade plan options
 * - Integrated with membership settings
 */

defined('ABSPATH') || exit;

// Pastikan data membership tersedia
$membership = $membership ?? [];
$staff_count = $membership['staff_count'] ?? 0;
$max_staff = $membership['max_staff'] ?? 2;
$level = $membership['level'] ?? 'regular';
$capabilities = $membership['capabilities'] ?? [];

?>

<div id="membership-info" class="tab-content">
    <!-- Membership Status Card -->
  <div class="membership-status-wrapper clearfix">
      <div class="membership-status-header">
          <h3>Status Membership Saat Ini</h3>
          <span class="membership-badge regular">Level Regular</span>
      </div>

      <div class="membership-info-grid">
          <!-- Staff Usage Section -->
          <div class="info-card">
              <div class="info-card-header">
                  <i class="dashicons dashicons-groups"></i>
                  <h4>Penggunaan Staff</h4>
              </div>
              <div class="staff-usage">
                  <div class="usage-bar">
                      <div class="usage-fill" style="width: 0%"></div>
                  </div>
                  <div class="usage-stats">
                      <span class="usage-current">0</span>
                      <span class="usage-separator">/</span>
                      <span class="usage-limit">2</span>
                      <span class="usage-label">staff</span>
                  </div>
              </div>
          </div>

          <!-- Active Features Section -->
          <div class="info-card">
              <div class="info-card-header">
                  <i class="dashicons dashicons-star-filled"></i>
                  <h4>Fitur Aktif</h4>
              </div>
              <ul class="feature-list">
                  <li><i class="dashicons dashicons-yes"></i>Dapat menambah staff</li>
                  <li><i class="dashicons dashicons-yes"></i>1 departemen</li>
              </ul>
          </div>

          <!-- Membership Details Section -->
          <div class="info-card">
              <div class="info-card-header">
                  <i class="dashicons dashicons-info"></i>
                  <h4>Detail Membership</h4>
              </div>
              <div class="membership-details">
                  <div class="detail-item">
                      <span class="detail-label">Status:</span>
                      <span class="detail-value status-active">Aktif</span>
                  </div>
                  <div class="detail-item">
                      <span class="detail-label">Masa Berlaku:</span>
                      <span class="detail-value">31 Des 2024</span>
                  </div>
              </div>
          </div>
      </div>
  </div>

  <!-- Available Upgrades Section -->
  <div class="membership-upgrades clearfix">
      <h3>Pilihan Upgrade Membership</h3>
      <div class="upgrade-cards">
          <!-- Priority Card -->
          <div class="upgrade-card priority">
              <div class="card-header">
                  <h4>Priority</h4>
                  <span class="level-badge">Level 2</span>
              </div>
              <div class="card-features">
                  <ul>
                      <li>Maksimal 5 staff</li>
                      <li>Dapat menambah staff</li>
                      <li>Dapat export data</li>
                      <li>3 departemen</li>
                  </ul>
              </div>
              <button class="upgrade-button">Upgrade ke Priority</button>
          </div>

          <!-- Utama Card -->
          <div class="upgrade-card utama">
              <div class="card-header">
                  <h4>Utama</h4>
                  <span class="level-badge">Level 3</span>
              </div>
              <div class="card-features">
                  <ul>
                      <li>Unlimited staff</li>
                      <li>Semua fitur Priority</li>
                      <li>Dapat bulk import</li>
                      <li>Unlimited departemen</li>
                  </ul>
              </div>
              <button class="upgrade-button">Upgrade ke Utama</button>
          </div>
      </div>
  </div>
</div>
