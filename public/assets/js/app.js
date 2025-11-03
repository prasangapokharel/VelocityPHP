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
            cacheViews: false, // Disabled for now to prevent wrong content caching
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
            
            // Silent initialization - no console logs in production
            if (typeof DEBUG_MODE !== 'undefined' && DEBUG_MODE) {
                console.log('🚀 NativeApp initialized - Zero-refresh mode active');
            }
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
                // Completely ignore prefetch requests with query strings (?_=timestamp)
                if (settings.url.includes('?_=') && settings.type === 'GET') {
                    return; // Silent ignore - no errors shown
                }
                
                // Skip error handling for routes that don't exist
                if (settings.url.includes('/profile') || settings.url.includes('/404')) {
                    return;
                }
                
                // Only handle actual user-triggered errors
                if (jqxhr.status === 403) {
                    NativeApp.showError('Access denied. Please login again.');
                } else if (jqxhr.status === 404) {
                    // Only load 404 page for actual 404s, not prefetch errors
                    if (!settings.url.includes('?_=')) {
                        NativeApp.loadRoute('/404', false);
                    }
                } else if (jqxhr.status === 500) {
                    // Only show error for POST/PUT/DELETE or GET without query string
                    if (settings.type !== 'GET' || !settings.url.includes('?_=')) {
                        NativeApp.showError('Server error. Please try again.');
                    }
                }
                
                // NO console logging - completely silent for production
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
            const submittingForms = new WeakSet();

            $(document).on('submit', 'form[data-ajax]', function(e) {
                e.preventDefault();
                
                if (submittingForms.has(this)) {
                    return;
                }
                
                submittingForms.add(this);
                
                const $form = $(this);
                const method = $form.attr('method') || 'POST';
                const action = $form.attr('action') || window.location.pathname;
                const formData = new FormData(this);

                self.submitForm(action, method, formData, $form).always(function() {
                    submittingForms.delete($form[0]);
                });
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

            // Clear cache for this specific route to ensure fresh content
            delete this.cache[url];
            
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
                error: function(jqxhr, status, error) {
                    self.hideLoading();
                    
                    // Try to parse response if available
                    let response = null;
                    try {
                        if (jqxhr.responseText) {
                            response = JSON.parse(jqxhr.responseText);
                        }
                    } catch(e) {
                        // Not JSON - ignore
                    }
                    
                    if (jqxhr.status === 404) {
                        // Use the clean 404 page from server response
                        if (response && response.html) {
                            self.renderContent(response, url, updateHistory);
                        } else {
                            // Fallback clean 404
                            self.renderContent({
                                html: '<div class="container text-center py-2xl"><h1 class="text-4xl font-bold mb-md">404</h1><p class="text-lg mb-xl text-neutral-600">Page not found</p><a href="/" class="btn btn-primary btn-md">Go Home</a></div>',
                                title: '404 - Page Not Found'
                            }, url, updateHistory);
                        }
                    } else {
                        // Show simple error message
                        self.showError('Something went wrong. Please try again.');
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

            if ($submitBtn.prop('disabled')) {
                return $.Deferred().reject({status: 400, responseJSON: {message: 'Form is already submitting'}});
            }

            $submitBtn.prop('disabled', true).html('<span class="spinner"></span> Loading...');

            return $.ajax({
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
                        // Silent error handling - no console logs
                    }
                });
            }
        },

        /**
         * Bind events for dynamically loaded content
         */
        bindDynamicEvents: function() {
            // Execute any inline scripts in the newly loaded content
            const $content = $(this.config.contentSelector);
            $content.find('script').each(function() {
                try {
                    // Create a new script element and execute it
                    const script = document.createElement('script');
                    if (this.src) {
                        script.src = this.src;
                    } else {
                        script.textContent = this.textContent;
                    }
                    document.body.appendChild(script);
                    // Clean up
                    setTimeout(() => script.remove(), 100);
                } catch (e) {
                    console.error('Script execution error:', e);
                }
            });
            
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
         * Clear all navigation cache
         */
        clearCache: function() {
            this.cache = {};
            if (typeof DEBUG_MODE !== 'undefined' && DEBUG_MODE) {
                console.log('🧹 Navigation cache cleared');
            }
        },

        /**
         * Clear cache for specific route
         */
        clearRouteCache: function(url) {
            delete this.cache[url];
            if (typeof DEBUG_MODE !== 'undefined' && DEBUG_MODE) {
                console.log('🧹 Cache cleared for:', url);
            }
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
            if (typeof DEBUG_MODE !== 'undefined' && DEBUG_MODE) {
                console.log('View cache cleared');
            }
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
                const xhr = $.ajax({
                    url: url,
                    method: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    dataType: 'json'
                });
                
                xhr.always(function() {
                    if (typeof DEBUG_MODE !== 'undefined' && DEBUG_MODE) {
                    }
                });
                
                return xhr;
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
                const token = $('meta[name="csrf-token"]').attr('content');
                return $.ajax({
                    url: url,
                    method: 'DELETE',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': token || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
            }
        }
    };

    // Auto-initialize when DOM is ready
    $(document).ready(function() {
        NativeApp.init();
        
        // Enable detailed console logging
        // Silent initialization - only log in debug mode
        if (typeof DEBUG_MODE !== 'undefined' && DEBUG_MODE) {
            console.log('%c🚀 NativeApp Initialized', 'color: #10b981; font-size: 16px; font-weight: bold');
            console.log('%cDebug Mode: ON', 'color: #3b82f6');
            console.log('%cZero-refresh navigation active', 'color: #10b981');
        }
    });

})(jQuery);

// Global error handler for uncaught errors
window.addEventListener('error', function(event) {
    // Completely silent error handling - no console logs
    // Ignore errors from external scripts (browser extensions, etc.)
    if (event.filename && (
        event.filename.includes('spoofer') || 
        event.filename.includes('extension') ||
        event.filename.includes('chrome-extension') ||
        event.filename.includes('moz-extension')
    )) {
        return; // Ignore external script errors
    }
    
    // Only show user-friendly error for real application errors
    // Don't show errors on page refresh/load
    if (window.NativeApp && event.filename && 
        (event.filename.includes('app.js') || event.filename.includes('/assets/')) &&
        !window.location.href.includes('?_=')) {
        NativeApp.showError('An error occurred. Please try again.');
    }
});

// Global AJAX error handler
$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    // Only log in debug mode
    // Silent error handling - no console logs in production
    // Errors are handled gracefully by the application
});
