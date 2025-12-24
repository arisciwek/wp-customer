/**
 * Membership Groups Modal Script
 *
 * @package     WP_Customer
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-customer/assets/js/settings/customer-membership-groups-modal-script.js
 *
 * Description: Handles membership groups management in modal
 *              Using WP-Modal for display
 *              CRUD operations via AJAX
 *
 * Dependencies:
 * - jQuery
 * - WPModal (from wp-modal plugin)
 * - CustomerToast
 * - wpCustomerGroupsModal (localized data)
 *
 * Changelog:
 * 1.0.0 - 2025-11-14 (Task-2205)
 * - Initial creation
 * - Modal management for groups
 * - CRUD operations
 */

(function($) {
    'use strict';

    const MembershipGroupsModal = {
        currentGroupId: null,
        modalInstance: null,

        init() {
            this.bindEvents();
        },

        bindEvents() {
            // Open modal button
            $(document).on('click', '#manage-membership-groups', () => {
                this.openModal();
            });

            // Show form (from list view)
            $(document).on('click', '#show-group-form, #show-group-form-empty', () => {
                this.showForm();
            });

            // Cancel form (back to list)
            $(document).on('click', '#cancel-group-form, #cancel-group-form-btn', () => {
                this.showList();
            });

            // Auto-generate slug from name
            $(document).on('input', '#group-name', (e) => {
                this.autoGenerateSlug(e.target.value);
            });

            // Edit group
            $(document).on('click', '.edit-group-btn', (e) => {
                const groupId = $(e.currentTarget).data('id');
                this.editGroup(groupId);
            });

            // Delete group
            $(document).on('click', '.delete-group-btn', (e) => {
                const groupId = $(e.currentTarget).data('id');
                this.deleteGroup(groupId);
            });

            // Form submit
            $(document).on('submit', '#membership-group-form', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });
        },

        openModal() {
            console.log('Opening groups modal...');
            console.log('AJAX URL:', wpCustomerGroupsModal.ajaxUrl);
            console.log('Nonce:', wpCustomerGroupsModal.nonce);

            const bodyUrl = wpCustomerGroupsModal.ajaxUrl + '?action=get_groups_modal_content&nonce=' + wpCustomerGroupsModal.nonce;
            console.log('Full bodyUrl:', bodyUrl);

            WPModal.show({
                type: 'form',
                title: wpCustomerGroupsModal.i18n.modalTitle,
                bodyUrl: bodyUrl,
                size: 'large',
                buttons: {
                    close: {
                        label: wpCustomerGroupsModal.i18n.closeModal,
                        classes: 'button',
                        callback: () => {
                            WPModal.hide();
                            // Reload parent page if groups were modified
                            if (this.hasChanges) {
                                window.location.reload();
                            }
                        }
                    }
                },
                onClose: () => {
                    if (this.hasChanges) {
                        window.location.reload();
                    }
                },
                onLoadSuccess: () => {
                    console.log('Modal content loaded successfully');
                },
                onLoadError: (error) => {
                    console.error('Modal content load error:', error);
                }
            });
        },

        showForm(groupData = null) {
            $('#groups-list-view').hide();
            $('#group-form-view').show();

            if (groupData) {
                // Edit mode
                $('#form-title').text(wpCustomerGroupsModal.i18n.editGroup);
                this.populateForm(groupData);
                // Disable slug in edit mode (slug should not be changed)
                $('#group-slug').prop('disabled', true);
            } else {
                // Add mode
                $('#form-title').text(wpCustomerGroupsModal.i18n.addGroup);
                $('#membership-group-form')[0].reset();
                $('#group-id').val('');
                this.currentGroupId = null;
                // Disable slug in add mode (auto-generated from name)
                $('#group-slug').prop('disabled', true);
            }
        },

        showList() {
            $('#group-form-view').hide();
            $('#groups-list-view').show();
            $('#membership-group-form')[0].reset();
            this.currentGroupId = null;
        },

        autoGenerateSlug(name) {
            // Always auto-generate slug from name
            const slug = name
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            $('#group-slug').val(slug);
        },

        editGroup(groupId) {
            this.currentGroupId = groupId;

            // Show loading
            $('#groups-list-view').hide();
            $('#groups-loading').show();

            $.ajax({
                url: wpCustomerGroupsModal.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_membership_group',
                    id: groupId,
                    nonce: wpCustomerGroupsModal.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const groupData = response.data.data || response.data;
                        this.showForm(groupData);
                    } else {
                        CustomerToast.error(response.data.message);
                        this.showList();
                    }
                },
                error: () => {
                    CustomerToast.error(wpCustomerGroupsModal.i18n.loadError);
                    this.showList();
                },
                complete: () => {
                    $('#groups-loading').hide();
                }
            });
        },

        populateForm(data) {
            $('#group-id').val(data.id);
            $('#group-name').val(data.name);
            $('#group-slug').val(data.slug);
            $('#capability-group').val(data.capability_group);
            $('#group-description').val(data.description);
            $('#sort-order').val(data.sort_order || 0);
        },

        handleSubmit() {
            const form = $('#membership-group-form')[0];
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Temporarily enable slug field to get its value
            const $slugField = $('#group-slug');
            const wasDisabled = $slugField.prop('disabled');
            if (wasDisabled) {
                $slugField.prop('disabled', false);
            }

            const groupId = $('#group-id').val();
            const isEdit = groupId !== '';
            const action = isEdit ? 'update_membership_group' : 'create_membership_group';

            const formData = {
                action: action,
                nonce: wpCustomerGroupsModal.nonce,
                name: $('#group-name').val(),
                slug: $slugField.val(),
                capability_group: $('#capability-group').val(),
                description: $('#group-description').val(),
                sort_order: $('#sort-order').val() || 0,
                status: 'active'
            };

            if (isEdit) {
                formData.id = groupId;
            }

            // Re-disable slug field
            if (wasDisabled) {
                $slugField.prop('disabled', true);
            }

            const $spinner = $('#membership-group-form .spinner');
            const $submitBtn = $('#membership-group-form button[type="submit"]');

            $.ajax({
                url: wpCustomerGroupsModal.ajaxUrl,
                type: 'POST',
                data: formData,
                beforeSend: () => {
                    $spinner.addClass('is-active');
                    $submitBtn.prop('disabled', true);
                },
                success: (response) => {
                    if (response.success) {
                        CustomerToast.success(response.data.message);
                        this.hasChanges = true;
                        // Reload modal content
                        this.reloadModalContent();
                    } else {
                        CustomerToast.error(response.data.message);
                    }
                },
                error: () => {
                    CustomerToast.error(wpCustomerGroupsModal.i18n.saveError);
                },
                complete: () => {
                    $spinner.removeClass('is-active');
                    $submitBtn.prop('disabled', false);
                }
            });
        },

        deleteGroup(groupId) {
            if (!confirm(wpCustomerGroupsModal.i18n.deleteConfirm)) {
                return;
            }

            $.ajax({
                url: wpCustomerGroupsModal.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delete_membership_group',
                    id: groupId,
                    nonce: wpCustomerGroupsModal.nonce
                },
                success: (response) => {
                    if (response.success) {
                        CustomerToast.success(response.data.message);
                        this.hasChanges = true;
                        // Reload modal content
                        this.reloadModalContent();
                    } else {
                        CustomerToast.error(response.data.message);
                    }
                },
                error: () => {
                    CustomerToast.error(wpCustomerGroupsModal.i18n.deleteError);
                }
            });
        },

        reloadModalContent() {
            $('#groups-list-view').hide();
            $('#group-form-view').hide();
            $('#groups-loading').show();

            $.ajax({
                url: wpCustomerGroupsModal.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'get_groups_modal_content',
                    nonce: wpCustomerGroupsModal.nonce
                },
                success: (html) => {
                    // Response is HTML string, not JSON
                    $('.wpmodal-body').html(html);
                    // Re-initialize after content reload
                    $('#groups-loading').hide();
                    $('#groups-list-view').show();
                },
                error: () => {
                    CustomerToast.error(wpCustomerGroupsModal.i18n.reloadError);
                },
                complete: () => {
                    $('#groups-loading').hide();
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        MembershipGroupsModal.init();
    });

})(jQuery);
