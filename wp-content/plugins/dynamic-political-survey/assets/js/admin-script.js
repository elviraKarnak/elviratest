/**
 * General Admin Scripts
 * Common functionality for admin pages
 */

(function($) {
    'use strict';
    
    const DPSAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.handleNotices();
        },
        
        bindEvents: function() {
            // Confirm deletion
            $(document).on('click', '.dps-confirm-delete', this.confirmDelete);
            
            // Toggle sections
            $(document).on('click', '.dps-section-toggle', this.toggleSection);
            
            // Copy to clipboard
            $(document).on('click', '.dps-copy-text', this.copyToClipboard);
            
            // Auto-save indicator
            this.initAutoSave();
        },
        
        confirmDelete: function(e) {
            const message = $(this).data('confirm') || 'Are you sure you want to delete this? This action cannot be undone.';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        },
        
        toggleSection: function(e) {
            e.preventDefault();
            const $section = $(this).closest('.dps-section');
            const $content = $section.find('.dps-section-content');
            const $icon = $(this).find('.dashicons');
            
            $content.slideToggle(300);
            $icon.toggleClass('dashicons-arrow-down dashicons-arrow-up');
        },
        
        copyToClipboard: function(e) {
            e.preventDefault();
            const text = $(this).data('text');
            const $button = $(this);
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    DPSAdmin.showCopySuccess($button);
                }).catch(function() {
                    DPSAdmin.fallbackCopy(text, $button);
                });
            } else {
                DPSAdmin.fallbackCopy(text, $button);
            }
        },
        
        fallbackCopy: function(text, $button) {
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            
            try {
                document.execCommand('copy');
                this.showCopySuccess($button);
            } catch (err) {
                alert('Failed to copy. Please copy manually: ' + text);
            }
            
            $temp.remove();
        },
        
        showCopySuccess: function($button) {
            const originalHtml = $button.html();
            $button.html('<span class="dashicons dashicons-yes"></span> Copied!').addClass('copied');
            
            setTimeout(function() {
                $button.html(originalHtml).removeClass('copied');
            }, 2000);
        },
        
        initTooltips: function() {
            // Simple tooltip implementation
            $('[data-tooltip]').each(function() {
                const $el = $(this);
                const text = $el.data('tooltip');
                
                $el.on('mouseenter', function() {
                    const $tooltip = $('<div class="dps-tooltip">' + text + '</div>');
                    $('body').append($tooltip);
                    
                    const pos = $el.offset();
                    $tooltip.css({
                        top: pos.top - $tooltip.outerHeight() - 10,
                        left: pos.left + ($el.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                    }).fadeIn(200);
                });
                
                $el.on('mouseleave', function() {
                    $('.dps-tooltip').fadeOut(200, function() {
                        $(this).remove();
                    });
                });
            });
        },
        
        handleNotices: function() {
            // Auto-dismiss success notices after 5 seconds
            setTimeout(function() {
                $('.notice.is-dismissible').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        initAutoSave: function() {
            let saveTimer;
            
            // Watch for form changes
            $('.dps-auto-save-form').on('change input', 'input, select, textarea', function() {
                clearTimeout(saveTimer);
                
                $('.auto-save-indicator').remove();
                
                saveTimer = setTimeout(function() {
                    DPSAdmin.showAutoSaveIndicator('Saving...');
                    
                    // Trigger save event
                    $(document).trigger('dps-auto-save');
                    
                    setTimeout(function() {
                        DPSAdmin.showAutoSaveIndicator('Saved', 'success');
                    }, 1000);
                }, 2000);
            });
        },
        
        showAutoSaveIndicator: function(text, type) {
            $('.auto-save-indicator').remove();
            
            const className = type === 'success' ? 'success' : 'saving';
            const icon = type === 'success' ? 'yes' : 'update';
            
            const $indicator = $('<div class="auto-save-indicator ' + className + '">' +
                '<span class="dashicons dashicons-' + icon + '"></span> ' + text +
            '</div>');
            
            $('body').append($indicator);
            
            if (type === 'success') {
                setTimeout(function() {
                    $indicator.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 2000);
            }
        },
        
        showLoader: function($element) {
            $element.addClass('loading').append('<span class="dps-loader"></span>');
        },
        
        hideLoader: function($element) {
            $element.removeClass('loading').find('.dps-loader').remove();
        },
        
        showNotice: function(message, type) {
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        DPSAdmin.init();
    });
    
    // Make DPSAdmin globally accessible
    window.DPSAdmin = DPSAdmin;
    
})(jQuery);

// Add CSS for tooltips and indicators
jQuery(document).ready(function($) {
    $('<style>')
        .text(`
            .dps-tooltip {
                position: absolute;
                background: #2c3e50;
                color: #fff;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 13px;
                z-index: 10000;
                display: none;
                max-width: 250px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            }
            
            .dps-tooltip:before {
                content: '';
                position: absolute;
                bottom: -5px;
                left: 50%;
                transform: translateX(-50%);
                width: 0;
                height: 0;
                border-left: 5px solid transparent;
                border-right: 5px solid transparent;
                border-top: 5px solid #2c3e50;
            }
            
            .auto-save-indicator {
                position: fixed;
                bottom: 30px;
                right: 30px;
                background: #fff;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.15);
                z-index: 10000;
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 14px;
            }
            
            .auto-save-indicator.saving .dashicons {
                animation: spin 1s linear infinite;
            }
            
            .auto-save-indicator.success {
                background: #27ae60;
                color: #fff;
            }
            
            .dps-loader {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid #f3f3f3;
                border-top: 2px solid #667eea;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin-left: 10px;
            }
            
            .loading {
                position: relative;
                opacity: 0.6;
                pointer-events: none;
            }
            
            .button.copied {
                background: #27ae60 !important;
                border-color: #27ae60 !important;
                color: #fff !important;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `)
        .appendTo('head');
});