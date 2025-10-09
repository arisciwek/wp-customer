/**
 * Employee Toast Component
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Employee
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/employee/employee-toast.js
 *
 * Description: Komponen toast notification khusus untuk employee.
 *              Support queue system untuk multiple notifications.
 *              Includes custom styling dan animations.
 *              Terintegrasi dengan operasi CRUD employee.
 *
 * Changelog:
 * 1.0.0 - 2024-01-12
 * - Initial implementation
 * - Added queue system
 * - Added animation handling
 * - Added pre-defined messages
 */
const EmployeeToast = {
    container: null,
    queue: [],
    isProcessing: false,
    defaultDuration: 3000,

    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'employee-toast-container';
            document.body.appendChild(this.container);
        }
    },

    show(message, type = 'info', duration = this.defaultDuration) {
        this.init();

        // Allow array of messages
        const messages = Array.isArray(message) ? message : [message];

        // Add to queue
        this.queue.push({ messages, type, duration });

        if (!this.isProcessing) {
            this.processQueue();
        }
    },

    async processQueue() {
        if (this.queue.length === 0) {
            this.isProcessing = false;
            return;
        }

        this.isProcessing = true;
        const { messages, type, duration } = this.queue.shift();

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `employee-toast employee-toast-${type}`;

        // Add messages
        messages.forEach(msg => {
            const p = document.createElement('p');
            p.textContent = msg;
            toast.appendChild(p);
        });

        // Add close button
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.className = 'close-toast';
        closeBtn.onclick = () => this.removeToast(toast);
        toast.appendChild(closeBtn);

        // Add to container with animation
        this.container.appendChild(toast);
        await new Promise(resolve => setTimeout(resolve, 50));
        toast.classList.add('show');

        // Auto remove after duration
        const timeoutId = setTimeout(() => this.removeToast(toast), duration);
        toast.dataset.timeoutId = timeoutId;
    },

    async removeToast(toast) {
        if (!toast.isRemoving) {
            toast.isRemoving = true;

            // Clear timeout if exists
            if (toast.dataset.timeoutId) {
                clearTimeout(parseInt(toast.dataset.timeoutId));
            }

            // Animate out
            toast.classList.add('hide');
            toast.classList.remove('show');

            await new Promise(resolve => setTimeout(resolve, 300));
            if (toast.parentElement) {
                toast.parentElement.removeChild(toast);
            }

            this.processQueue();
        }
    },

    // Main notification methods
    success(message, duration) {
        this.show(message, 'success', duration);
    },

    error(message, duration) {
        this.show(message, 'error', duration);
    },

    warning(message, duration) {
        this.show(message, 'warning', duration);
    },

    info(message, duration) {
        this.show(message, 'info', duration);
    },

    // Employee-specific message methods
    showValidationErrors(errors) {
        if (typeof errors === 'string') {
            this.error(errors);
        } else if (Array.isArray(errors)) {
            this.error(errors);
        } else if (typeof errors === 'object') {
            this.error(Object.values(errors));
        }
    },

    showSuccessWithWarnings(message, warnings) {
        // Show success first
        this.success(message);

        // Show warnings after a short delay if they exist
        if (warnings && warnings.length) {
            setTimeout(() => {
                this.warning(warnings);
            }, 500);
        }
    },

    // Pre-defined messages untuk employee
    showCreated() {
        this.success('Karyawan berhasil ditambahkan');
    },

    showUpdated() {
        this.success('Data karyawan berhasil diperbarui');
    },

    showDeleted() {
        this.success('Karyawan berhasil dihapus');
    },

    showStatusChanged(status) {
        const message = status === 'active' 
            ? 'Karyawan berhasil diaktifkan'
            : 'Karyawan berhasil dinonaktifkan';
        this.success(message);
    },

    showEmailValidationError() {
        this.error('Email karyawan sudah digunakan');
    },

    showDepartmentLimitReached() {
        this.warning('Batas maksimal karyawan untuk departemen ini telah tercapai');
    },

    showPermissionError() {
        this.error('Anda tidak memiliki izin untuk melakukan operasi ini');
    },

    showServerError() {
        this.error('Terjadi kesalahan saat menghubungi server. Silakan coba lagi.');
    }
};

// Expose for global use
window.EmployeeToast = EmployeeToast;
