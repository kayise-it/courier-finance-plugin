/**
 * Component-specific JavaScript utilities
 * This file provides common functionality for plugin components
 */

// Component utilities
const ComponentUtils = {
    // Check if required dependencies are available
    checkDependencies: function() {
        const dependencies = {
            jQuery: typeof jQuery !== 'undefined',
            SpinnerManager: typeof SpinnerManager !== 'undefined',
            myPluginAjax: typeof myPluginAjax !== 'undefined'
        };
        
        const missing = Object.keys(dependencies).filter(key => !dependencies[key]);
        if (missing.length > 0) {
            console.warn('Missing dependencies:', missing);
            return false;
        }
        return true;
    },

    // Safe spinner management
    showSpinner: function(element) {
        if (typeof SpinnerManager !== 'undefined' && SpinnerManager.show) {
            SpinnerManager.show(element);
        } else {
            // Fallback: add a simple loading class
            element.classList.add('loading');
        }
    },

    hideSpinner: function(element) {
        if (typeof SpinnerManager !== 'undefined' && SpinnerManager.hide) {
            SpinnerManager.hide(element);
        } else {
            // Fallback: remove loading class
            element.classList.remove('loading');
        }
    },

    // Safe AJAX calls
    ajaxCall: function(action, data, callback) {
        if (typeof myPluginAjax === 'undefined') {
            console.error('myPluginAjax not available');
            if (callback) callback({success: false, message: 'AJAX not available'});
            return;
        }

        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', myPluginAjax.nonces.get_waybills_nonce);
        
        // Add additional data
        for (const [key, value] of Object.entries(data)) {
            formData.append(key, value);
        }

        fetch(myPluginAjax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(callback)
        .catch(error => {
            console.error('AJAX error:', error);
            if (callback) callback({success: false, message: error.message});
        });
    }
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check dependencies and log status
    ComponentUtils.checkDependencies();
    
    // Add loading styles if SpinnerManager is not available
    if (typeof SpinnerManager === 'undefined') {
        const style = document.createElement('style');
        style.textContent = `
            .loading {
                position: relative;
                opacity: 0.6;
            }
            .loading::after {
                content: '';
                position: absolute;
                right: 8px;
                top: 50%;
                transform: translateY(-50%);
                width: 16px;
                height: 16px;
                border: 2px solid rgba(0,0,0,0.1);
                border-radius: 50%;
                border-top-color: #3498db;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                to { transform: translateY(-50%) rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }
});

// Export for use in other scripts
window.ComponentUtils = ComponentUtils;
