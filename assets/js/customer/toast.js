/**
 * File: toast.js
 * Path: /wp-customer/assets/js/customer/toast.js
 * Description: Komponen notifikasi untuk feedback UI
 * Version: 2.0.0
 * Last modified: 2024-11-28 09:45:00
 */

const wpCustomerToast = {
    container: null,
    
    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'wp-customer-toast-container';
            this.container.style.cssText = `
                position: fixed;
                top: 32px;
                right: 20px;
                z-index: 999999;
            `;
            document.body.appendChild(this.container);
        }
    },
    
    show(message, type = 'success', duration = 3000) {
        this.init();
        
        const toast = document.createElement('div');
        toast.className = `wp-customer-toast wp-customer-toast-${type}`;
        toast.style.cssText = `
            margin-bottom: 10px;
            padding: 12px 24px;
            border-radius: 4px;
            color: #fff;
            font-size: 14px;
            min-width: 250px;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;
        
        switch (type) {
            case 'success':
                toast.style.backgroundColor = '#4CAF50';
                break;
            case 'error':
                toast.style.backgroundColor = '#f44336';
                break;
            case 'warning':
                toast.style.backgroundColor = '#ff9800';
                break;
            case 'info':
                toast.style.backgroundColor = '#2196F3';
                break;
        }
        
        // Support untuk multiple line messages
        if (Array.isArray(message)) {
            message.forEach(msg => {
                const p = document.createElement('p');
                p.style.margin = '5px 0';
                p.textContent = msg;
                toast.appendChild(p);
            });
        } else {
            toast.textContent = message;
        }

        this.container.appendChild(toast);
        
        requestAnimationFrame(() => {
            toast.style.opacity = '1';
        });
        
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.parentElement.removeChild(toast);
                }
            }, 300);
        }, duration);
    },
    
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
    }
    
};

// Expose untuk global scope
window.wpCustomerToast = wpCustomerToast;

