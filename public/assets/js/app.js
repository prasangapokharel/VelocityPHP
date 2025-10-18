/**
 * Core AJAX Router & SPA Handler
 * Provides zero-refresh navigation with browser history API
 * Handles all dynamic content loading and state management
 * 
 * @package VelocityPHP
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // SPA Core Application
    window.NativeApp = {
        config: {
            contentSelector: '#app-content',
            loadingClass: 'loading',
            transitionDuration: 300,
            cacheViews: true,
            enableHistory: true
        },

        cache: {},
        currentRoute: null,
        isLoading: false,

        /**
         * Initialize the application
         */
        init: function() {
            this.setupAjaxDefaults();
            this.bindNavigationEvents();
            this.bindFormEvents();
            this.setupHistoryAPI();
            this.setupCSRFToken();
            this.preloadCriticalAssets();
            
            // Mark initial route
            this.currentRoute = window.location.pathname;
            
            console.log('🚀 NativeApp initialized - Zero-refresh mode active');
        },

        /**
         * Configure jQuery AJAX defaults
         */
        setupAjaxDefaults: function() {
            $.ajaxSetup({
                cache: false,
                timeout: 30000,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // Global AJAX error handler
            $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
                if (jqxhr.status === 403) {
                    NativeApp.showError('Access denied. Please login again.');
                } else if (jqxhr.status === 404) {
                    NativeApp.loadRoute('/404', false);
                } else if (jqxhr.status === 500) {
                    NativeApp.showError('Server error. Please try again.');
                }
                console.error('AJAX Error:', thrownError, settings.url);
            });
        },

        /**
         * Setup CSRF token for all AJAX requests
         */
        setupCSRFToken: function() {
            const token = $('meta[name="csrf-token"]').attr('content');
            if (token) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': token
                    }
                });
            }
        },

        /**
         * Bind click events to all internal links for AJAX navigation
         */
        bindNavigationEvents: function() {
            const self = this;

            // Delegate click events for dynamic links
            $(document).on('click', 'a[href]:not([target="_blank"]):not([data-no-ajax])', function(e) {
                const $link = $(this);
                const href = $link.attr('href');

                // Skip external links, anchors, and javascript: links
                if (self.isExternalLink(href) || href.startsWith('#') || href.startsWith('javascript:')) {
                    return;
                }

                e.preventDefault();
                self.navigate(href, true);
            });

            // Handle back/forward browser buttons
            if (this.config.enableHistory) {
                window.addEventListener('popstate', function(e) {
                    if (e.state && e.state.route) {
                        self.loadRoute(e.state.route, false);
                    } else {
                        self.loadRoute(window.location.pathname, false);
                    }
                });
            }
        },

        /**
         * Bind AJAX form submissions
         */
        bindFormEvents: function() {
            const self = this;

            $(document).on('submit', 'form[data-ajax]', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const method = $form.attr('method') || 'POST';
                const action = $form.attr('action') || window.location.pathname;
                const formData = new FormData(this);

                self.submitForm(action, method, formData, $form);
            });
        },

        /**
         * Setup HTML5 History API
         */
        setupHistoryAPI: function() {
            if (this.config.enableHistory && window.history && window.history.pushState) {
                // Replace current state with initial route
                history.replaceState(
                    { route: window.location.pathname },
                    document.title,
                    window.location.pathname
                );
            }
        },

        /**
         * Navigate to a new route
         */
        navigate: function(url, updateHistory) {
            // Prevent duplicate navigation
            if (url === this.currentRoute && !url.includes('?')) {
                return;
            }

            this.loadRoute(url, updateHistory);
        },

        /**
         * Load route via AJAX
         */
        loadRoute: function(url, updateHistory) {
            const self = this;

            // Prevent concurrent requests
            if (this.isLoading) {
                return;
            }

            // Check cache first
            if (this.config.cacheViews && this.cache[url]) {
                this.renderContent(this.cache[url], url, updateHistory);
                return;
            }

            this.isLoading = true;
            this.showLoading();

            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    // Cache the response
                    if (self.config.cacheViews) {
                        self.cache[url] = response;
                    }

                    self.renderContent(response, url, updateHistory);
                },
                error: function(jqxhr) {
                    self.hideLoading();
                    
                    if (jqxhr.status === 404) {
                        self.renderContent({
                            html: '<div class="error-404"><h1>404</h1><p>Page not found</p></div>',
                            title: '404 - Not Found'
                        }, url, updateHistory);
                    } else {
                        self.showError('Failed to load page. Please try again.');
                    }
                },
                complete: function() {
                    self.isLoading = false;
                }
            });
        },

        /**
         * Render content into the page
         */
        renderContent: function(response, url, updateHistory) {
            const self = this;
            const $content = $(this.config.contentSelector);

            // Fade out current content
            $content.fadeOut(this.config.transitionDuration, function() {
                // Update content
                if (response.html) {
                    $content.html(response.html);
                }

                // Update title
                if (response.title) {
                    document.title = response.title;
                }

                // Update meta tags
                if (response.meta) {
                    self.updateMetaTags(response.meta);
                }

                // Execute inline scripts
                if (response.scripts) {
                    self.executeScripts(response.scripts);
                }

                // Update browser history
                if (updateHistory && self.config.enableHistory) {
                    history.pushState(
                        { route: url },
                        response.title || document.title,
                        url
                    );
                }

                // Update current route
                self.currentRoute = url;

                // Fade in new content
                $content.fadeIn(self.config.transitionDuration, function() {
                    self.hideLoading();
                    
                    // Trigger custom event for page loaded
                    $(document).trigger('page:loaded', [url, response]);
                    
                    // Scroll to top
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });

                // Re-bind events for new content
                self.bindDynamicEvents();
            });
        },

        /**
         * Submit form via AJAX
         */
        submitForm: function(url, method, formData, $form) {
            const self = this;
            const $submitBtn = $form.find('[type="submit"]');
            const originalBtnText = $submitBtn.html();

            // Disable submit button
            $submitBtn.prop('disabled', true).html('<span class="spinner"></span> Loading...');

            $.ajax({
                url: url,
                method: method.toUpperCase(),
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        self.showSuccess(response.message || 'Success!');

                        // Redirect if specified
                        if (response.redirect) {
                            setTimeout(function() {
                                self.navigate(response.redirect, true);
                            }, 1000);
                        }

                        // Reset form if specified
                        if (response.resetForm) {
                            $form[0].reset();
                        }

                        // Trigger custom event
                        $(document).trigger('form:success', [response, $form]);
                    } else {
                        self.showError(response.message || 'An error occurred');
                        
                        // Show validation errors
                        if (response.errors) {
                            self.showValidationErrors($form, response.errors);
                        }
                    }
                },
                error: function(jqxhr) {
                    const response = jqxhr.responseJSON;
                    self.showError(response?.message || 'Form submission failed');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html(originalBtnText);
                }
            });
        },

        /**
         * Show validation errors on form fields
         */
        showValidationErrors: function($form, errors) {
            // Clear previous errors
            $form.find('.error-message').remove();
            $form.find('.error').removeClass('error');

            // Display new errors
            $.each(errors, function(field, messages) {
                const $field = $form.find('[name="' + field + '"]');
                $field.addClass('error');
                
                const errorHtml = '<div class="error-message">' + messages.join('<br>') + '</div>';
                $field.after(errorHtml);
            });
        },

        /**
         * Update meta tags dynamically
         */
        updateMetaTags: function(meta) {
            $.each(meta, function(name, content) {
                let $meta = $('meta[name="' + name + '"]');
                if ($meta.length === 0) {
                    $meta = $('<meta name="' + name + '">');
                    $('head').append($meta);
                }
                $meta.attr('content', content);
            });
        },

        /**
         * Execute inline scripts from AJAX response
         */
        executeScripts: function(scripts) {
            if (Array.isArray(scripts)) {
                scripts.forEach(function(script) {
                    try {
                        eval(script);
                    } catch (e) {
                        console.error('Script execution error:', e);
                    }
                });
            }
        },

        /**
         * Bind events for dynamically loaded content
         */
        bindDynamicEvents: function() {
            // Trigger custom event for other scripts to hook into
            $(document).trigger('content:updated');
        },

        /**
         * Check if link is external
         */
        isExternalLink: function(url) {
            if (!url) return false;
            
            const link = document.createElement('a');
            link.href = url;
            
            return link.hostname !== window.location.hostname ||
                   url.startsWith('http://') ||
                   url.startsWith('https://');
        },

        /**
         * Show loading indicator
         */
        showLoading: function() {
            $('body').addClass(this.config.loadingClass);
            
            // Show loading bar if exists
            $('#loading-bar').addClass('active');
        },

        /**
         * Hide loading indicator
         */
        hideLoading: function() {
            $('body').removeClass(this.config.loadingClass);
            
            // Hide loading bar
            $('#loading-bar').removeClass('active');
        },

        /**
         * Show success notification
         */
        showSuccess: function(message) {
            this.showNotification(message, 'success');
        },

        /**
         * Show error notification
         */
        showError: function(message) {
            this.showNotification(message, 'error');
        },

        /**
         * Show notification toast
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            const $notification = $('<div class="notification notification-' + type + '">' + message + '</div>');
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.addClass('show');
            }, 10);
            
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        },

        /**
         * Preload critical assets
         */
        preloadCriticalAssets: function() {
            // Preload commonly used routes
            const criticalRoutes = ['/dashboard', '/profile'];
            
            criticalRoutes.forEach(function(route) {
                if (route !== window.location.pathname) {
                    // Silently preload in background
                    $.ajax({
                        url: route,
                        method: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            NativeApp.cache[route] = response;
                        }
                    });
                }
            });
        },

        /**
         * Clear view cache
         */
        clearCache: function() {
            this.cache = {};
            console.log('View cache cleared');
        },

        /**
         * API helper for making AJAX calls
         */
        api: {
            get: function(url, data) {
                return $.ajax({
                    url: url,
                    method: 'GET',
                    data: data,
                    dataType: 'json'
                });
            },

            post: function(url, data) {
                return $.ajax({
                    url: url,
                    method: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    dataType: 'json'
                });
            },

            put: function(url, data) {
                return $.ajax({
                    url: url,
                    method: 'PUT',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    dataType: 'json'
                });
            },

            delete: function(url) {
                return $.ajax({
                    url: url,
                    method: 'DELETE',
                    dataType: 'json'
                });
            }
        }
    };

    // Auto-initialize when DOM is ready
    $(document).ready(function() {
        NativeApp.init();
        
        // Enable detailed console logging
        console.log('%c🚀 NativeApp Initialized', 'color: #10b981; font-size: 16px; font-weight: bold');
        console.log('%cDebug Mode: ON', 'color: #3b82f6');
        console.log('%cZero-refresh navigation active', 'color: #10b981');
    });

})(jQuery);

// Global error handler for uncaught errors
window.addEventListener('error', function(event) {
    console.group('❌ JavaScript Error Caught');
    console.error('Message:', event.message);
    console.error('Source:', event.filename);
    console.error('Line:', event.lineno);
    console.error('Column:', event.colno);
    console.error('Error object:', event.error);
    console.groupEnd();
    
    // Show user-friendly error
    if (window.NativeApp) {
        NativeApp.showError('JavaScript Error: ' + event.message);
    }
});

// Global AJAX error handler
$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    console.group('❌ AJAX Error');
    console.log('URL:', settings.url);
    console.log('Method:', settings.type);
    console.log('Status:', jqxhr.status, jqxhr.statusText);
    console.log('Response:', jqxhr.responseText);
    console.log('Error:', thrownError);
    console.groupEnd();
});
