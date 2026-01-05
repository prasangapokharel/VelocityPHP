/**
 * VelocityPhp Core AJAX Router & SPA Handler
 * Zero-refresh navigation with browser history API
 * 
 * @package VelocityPhp
 * @version 1.1.0
 */

(function($) {
    'use strict';

    // SPA Core Application
    window.NativeApp = {
        config: {
            contentSelector: '#app-content',
            loadingClass: 'loading',
            transitionDuration: 200,
            cacheViews: false,
            enableHistory: true,
            debug: false
        },

        cache: {},
        currentRoute: null,
        isLoading: false,
        pendingRequests: new Map(),

        /**
         * Initialize the application
         */
        init: function() {
            this.config.debug = typeof DEBUG_MODE !== 'undefined' && DEBUG_MODE;
            this.setupAjaxDefaults();
            this.bindNavigationEvents();
            this.bindFormEvents();
            this.setupHistoryAPI();
            this.setupCSRFToken();
            this.currentRoute = window.location.pathname;
            
            if (this.config.debug) {
                console.log('%c⚡ VelocityPhp Initialized', 'color: #10b981; font-size: 14px; font-weight: bold');
            }
        },

        /**
         * Configure jQuery AJAX defaults
         */
        setupAjaxDefaults: function() {
            const self = this;
            
            $.ajaxSetup({
                cache: false,
                timeout: 30000,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // Global AJAX error handler
            $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
                // Ignore prefetch/preload requests
                if (settings.url.includes('?_=') || settings.isPrefetch) {
                    return;
                }
                
                // Handle specific HTTP errors
                if (jqxhr.status === 403) {
                    self.showError('Access denied.');
                } else if (jqxhr.status === 0 && thrownError === 'abort') {
                    // Request was aborted - ignore
                    return;
                }
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

            $(document).on('click', 'a[href]:not([target="_blank"]):not([data-no-ajax]):not([download])', function(e) {
                const $link = $(this);
                const href = $link.attr('href');

                // Skip external links, anchors, javascript, mailto, tel
                if (!href || 
                    self.isExternalLink(href) || 
                    href.startsWith('#') || 
                    href.startsWith('javascript:') ||
                    href.startsWith('mailto:') ||
                    href.startsWith('tel:')) {
                    return;
                }

                e.preventDefault();
                self.navigate(href, true);
            });

            // Handle browser back/forward
            if (this.config.enableHistory) {
                window.addEventListener('popstate', function(e) {
                    const route = e.state?.route || window.location.pathname;
                    self.loadRoute(route, false);
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
                const $submitBtn = $form.find('[type="submit"]');
                
                // Prevent double submission
                if ($submitBtn.prop('disabled')) {
                    return;
                }
                
                const method = ($form.attr('method') || 'POST').toUpperCase();
                const action = $form.attr('action') || window.location.pathname;
                const formData = new FormData(this);

                self.submitForm(action, method, formData, $form);
            });
        },

        /**
         * Setup HTML5 History API
         */
        setupHistoryAPI: function() {
            if (this.config.enableHistory && window.history?.pushState) {
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
            // Normalize URL
            url = url.split('?')[0]; // Remove query string for comparison
            
            // Prevent duplicate navigation
            if (url === this.currentRoute) {
                return;
            }

            // Clear cache for this route to ensure fresh content
            delete this.cache[url];
            
            this.loadRoute(url, updateHistory);
        },

        /**
         * Load route via AJAX
         */
        loadRoute: function(url, updateHistory) {
            const self = this;

            // Abort any pending request for same URL
            if (this.pendingRequests.has(url)) {
                this.pendingRequests.get(url).abort();
            }

            // Check cache first
            if (this.config.cacheViews && this.cache[url]) {
                this.renderContent(this.cache[url], url, updateHistory);
                return;
            }

            this.isLoading = true;
            this.showLoading();

            const xhr = $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (self.config.cacheViews) {
                        self.cache[url] = response;
                    }
                    self.renderContent(response, url, updateHistory);
                },
                error: function(jqxhr, status, error) {
                    self.hideLoading();
                    
                    if (status === 'abort') return;
                    
                    // Try to parse error response
                    let response = null;
                    try {
                        response = jqxhr.responseJSON || JSON.parse(jqxhr.responseText);
                    } catch(e) {}
                    
                    if (jqxhr.status === 404) {
                        if (response?.html) {
                            self.renderContent(response, url, updateHistory);
                        } else {
                            self.renderContent({
                                html: '<div class="container text-center py-20"><h1 class="text-4xl font-bold mb-4">404</h1><p class="text-lg mb-8 text-gray-600">Page not found</p><a href="/" class="inline-block px-6 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-700">Go Home</a></div>',
                                title: '404 - Page Not Found'
                            }, url, updateHistory);
                        }
                    } else {
                        self.showError(response?.message || 'Failed to load page. Please try again.');
                    }
                },
                complete: function() {
                    self.isLoading = false;
                    self.pendingRequests.delete(url);
                }
            });

            this.pendingRequests.set(url, xhr);
        },

        /**
         * Render content into the page
         */
        renderContent: function(response, url, updateHistory) {
            const self = this;
            const $content = $(this.config.contentSelector);

            $content.fadeOut(this.config.transitionDuration, function() {
                if (response.html) {
                    $content.html(response.html);
                }

                if (response.title) {
                    document.title = response.title;
                }

                if (response.meta) {
                    self.updateMetaTags(response.meta);
                }

                if (updateHistory && self.config.enableHistory) {
                    history.pushState(
                        { route: url },
                        response.title || document.title,
                        url
                    );
                }

                self.currentRoute = url;

                $content.fadeIn(self.config.transitionDuration, function() {
                    self.hideLoading();
                    $(document).trigger('page:loaded', [url, response]);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });

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

            $submitBtn.prop('disabled', true).html('<span class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span> Loading...');

            return $.ajax({
                url: url,
                method: method,
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        self.showSuccess(response.message || 'Success!');

                        if (response.redirect) {
                            setTimeout(function() {
                                self.navigate(response.redirect, true);
                            }, 1000);
                        }

                        if (response.resetForm) {
                            $form[0].reset();
                        }

                        $(document).trigger('form:success', [response, $form]);
                    } else {
                        self.showError(response.message || 'An error occurred');
                        
                        if (response.errors) {
                            self.showValidationErrors($form, response.errors);
                        }
                    }
                },
                error: function(jqxhr) {
                    const response = jqxhr.responseJSON;
                    self.showError(response?.message || response?.error || 'Form submission failed');
                    
                    if (response?.errors) {
                        self.showValidationErrors($form, response.errors);
                    }
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
            $form.find('.error-message').remove();
            $form.find('.error, .border-red-500').removeClass('error border-red-500');

            $.each(errors, function(field, messages) {
                const $field = $form.find('[name="' + field + '"]');
                $field.addClass('error border-red-500');
                
                const errorHtml = '<div class="error-message text-red-500 text-sm mt-1">' + 
                    (Array.isArray(messages) ? messages.join('<br>') : messages) + '</div>';
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
         * Bind events for dynamically loaded content
         */
        bindDynamicEvents: function() {
            const $content = $(this.config.contentSelector);
            
            // Execute inline scripts safely
            $content.find('script').each(function() {
                try {
                    if (this.src) {
                        const script = document.createElement('script');
                        script.src = this.src;
                        document.body.appendChild(script);
                    } else if (this.textContent.trim()) {
                        // Create and execute script safely
                        const script = document.createElement('script');
                        script.textContent = this.textContent;
                        document.body.appendChild(script);
                        document.body.removeChild(script);
                    }
                } catch (e) {
                    if (window.NativeApp.config.debug) {
                        console.error('Script error:', e);
                    }
                }
            });
            
            $(document).trigger('content:updated');
        },

        /**
         * Check if link is external
         */
        isExternalLink: function(url) {
            if (!url) return false;
            
            try {
                const link = new URL(url, window.location.origin);
                return link.hostname !== window.location.hostname;
            } catch(e) {
                return url.startsWith('http://') || url.startsWith('https://');
            }
        },

        /**
         * Clear all navigation cache
         */
        clearCache: function() {
            this.cache = {};
        },

        /**
         * Show loading indicator
         */
        showLoading: function() {
            $('body').addClass(this.config.loadingClass);
            $('#loading-bar').addClass('active');
        },

        /**
         * Hide loading indicator
         */
        hideLoading: function() {
            $('body').removeClass(this.config.loadingClass);
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
            
            // Remove existing notifications
            $('.notification').remove();
            
            const bgColor = type === 'success' ? 'bg-green-500' : 
                           type === 'error' ? 'bg-red-500' : 'bg-blue-500';
            
            const $notification = $('<div class="notification fixed top-4 right-4 px-6 py-3 rounded-lg text-white shadow-lg transform translate-x-full transition-transform duration-300 z-50 ' + bgColor + '">' + message + '</div>');
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.removeClass('translate-x-full');
            }, 10);
            
            setTimeout(function() {
                $notification.addClass('translate-x-full');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 4000);
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
    });

})(jQuery);

// Global error handler
window.addEventListener('error', function(event) {
    // Ignore errors from browser extensions
    if (event.filename && (
        event.filename.includes('extension') ||
        event.filename.includes('chrome-extension') ||
        event.filename.includes('moz-extension')
    )) {
        return;
    }
});
