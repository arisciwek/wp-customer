jQuery(document).ready(function($) {
    'use strict';

    // Global namespace for admin helpers
    window.adminHelpers = {
        // URL Hash Management
        handleUrlHash: function() {
            const hash = window.location.hash;
            return hash && hash.match(/^#\d+$/) ? parseInt(hash.substring(1)) : null;
        },
        
        /*
        updateUrlHash: function(id) {
            if (id) {
                window.history.pushState({ id: id }, '', `#${id}`);
            } else {
                window.history.pushState({ id: null }, '', 
                    window.location.pathname + window.location.search);
            }
        },
        */        

        // Panel Management
        /*
        showRightPanel: function($container) {
            console.log('Show panel kanan admin');
            const $rightPanel = $container.find('.right-panel');
            const $leftPanel = $container.find('.left-panel');

            // Adjust layout to 45-55 split
            $leftPanel.css('flex', '0 0 45%');
            $rightPanel.css({
                'display': 'block',
                'flex': '0 0 55%'
            });
            $container.addClass('show-right-panel');

            // Trigger event for other components
            $('body').trigger('rightPanelOpened', [$rightPanel]);
        },
        */

        /*
        hideRightPanel: function($container) {
            const $rightPanel = $container.find('.right-panel');
            const $leftPanel = $container.find('.left-panel');

            // Reset layout
            $leftPanel.css('flex', '1');
            $rightPanel.css('display', 'none');
            $container.removeClass('show-right-panel');

            // Clear URL hash
            this.updateUrlHash(null);

            // Trigger event for other components
            $('body').trigger('rightPanelClosed');
        },
        */
        /*
        initTabs: function($container) {
            const self = this;
            
            $container.find('.nav-tab').on('click', function(e) {
                e.preventDefault();
                const $tab = $(this);
                const tabId = $tab.attr('href');
                const $tabContent = $(tabId);
                
                // Update active states
                $tab.siblings().removeClass('nav-tab-active');
                $tab.addClass('nav-tab-active');
                
                $tabContent.siblings('.tab-pane').removeClass('active');
                $tabContent.addClass('active');
                
                // Lazy load content if not already loaded
                if (!$tabContent.data('loaded')) {
                    self.loadTabContent($tabContent);
                }
            });
        },
        */
        
        /*
        loadTabContent: function($tabContent) {
            const loadUrl = $tabContent.data('load-url');
            const loadParams = $tabContent.data('load-params');

            if (loadUrl) {
                this.setLoading($tabContent);
                
                $.ajax({
                    url: loadUrl,
                    data: loadParams,
                    success: function(response) {
                        $tabContent.html(response);
                        $tabContent.data('loaded', true);
                    },
                    complete: function() {
                        adminHelpers.removeLoading($tabContent);
                    }
                });
            }
        },
        */


        // Modal Management
        openModal: function($modal) {
            $modal.addClass('open');
            $('body').addClass('modal-open');
        },

        closeModal: function($modal) {
            $modal.removeClass('open');
            $('body').removeClass('modal-open');
            $('body').trigger('modalClosed');
        },

        // AJAX Helpers
        ajaxRequest: function(options) {
            const self = this;
            const defaults = {
                url: window.ajaxurl || '',
                type: 'POST',
                data: {},
                beforeSend: function() {
                    if (options.loadingTarget) {
                        self.setLoading($(options.loadingTarget));
                    }
                },
                complete: function() {
                    if (options.loadingTarget) {
                        self.removeLoading($(options.loadingTarget));
                    }
                }
            };

            return $.ajax({ ...defaults, ...options });
        },

        // Loading State
        setLoading: function($element) {
            $element.addClass('loading-state');
        },

        removeLoading: function($element) {
            $element.removeClass('loading-state');
        },

        // Debug Logging
        debugLog: function(message, data) {
            if (window.customerManagement && customerManagement.debug) {
                console.log(message, data);
            }
        }
    };

    // Global Event Handlers
    $('.modal-close, [data-dismiss="modal"]').on('click', function() {
        window.adminHelpers.closeModal($(this).closest('.modal'));
    });

    // Handle back/forward browser buttons
    window.onpopstate = function(event) {
        const id = event.state ? event.state.id : window.adminHelpers.handleUrlHash();
        $('body').trigger('hashChange', [id]);
    };

    // Initialize tooltips if available
    if ($.fn.tooltip) {
        $('[data-tooltip]').tooltip();
    }

    // Check for initial hash on page load
    const initialId = window.adminHelpers.handleUrlHash();
    if (initialId) {
        $('body').trigger('hashChange', [initialId]);
    }


    // Global utility untuk manipulasi DataTable columns
    window.tableHelper = {
        hideColumns: function(table, columns = []) {
            if (!table || !table.columns) {
                console.warn('Parameter harus berupa instance DataTable yang valid');
                return;
            }
            
            if (columns[0] === -1) {
                // Jika -1, tampilkan semua kolom
                table.columns().visible(true);
            } else {
                // Reset semua kolom jadi visible dulu
                table.columns().visible(true);
                
                // Sembunyikan kolom yang diminta
                if (columns.length > 0) {
                    columns.forEach(index => {
                        table.column(index).visible(false);
                    });
                }
            }
            
            // Redraw untuk menyesuaikan layout
            table.columns.adjust().draw();
        }
    };
    
});
