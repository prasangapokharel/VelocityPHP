/**
 * Core AJAX Router & SPA Handler
 * Provides zero-refresh navigation with browser history API
 * Handles all dynamic content loading and state management
 *
 * @package VelocityPHP
 * @version 1.1.0
 */

(function ($) {
    'use strict';

    window.NativeApp = {
        config: {
            contentSelector: '#app-content',
            loadingClass: 'loading',
            // 150ms fade is barely perceptible but still smooth — keeps it snappy
            transitionDuration: 150,
            cacheViews: true,
            enableHistory: true,
            // Abort any in-flight navigation after this many ms
            requestTimeout: 15000
        },

        cache: {},
        currentRoute: null,

        // Active XHR so we can abort it on rapid navigation
        _activeXhr: null,

        // Loading-bar completion timer
        _loadingTimer: null,

        init: function () {
            this.setupAjaxDefaults();
            this.setupCSRFToken();
            this.bindNavigationEvents();
            this.bindFormEvents();
            this.setupHistoryAPI();

            this.currentRoute = window.location.pathname;

            if (typeof DEBUG_MODE !== 'undefined' && DEBUG_MODE) {
                console.log('🚀 NativeApp initialized — zero-refresh SPA active');
            }
        },

        // ── AJAX defaults ────────────────────────────────────────────────────────

        setupAjaxDefaults: function () {
            $.ajaxSetup({
                cache: false,
                timeout: this.config.requestTimeout,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
        },

        setupCSRFToken: function () {
            const token = $('meta[name="csrf-token"]').attr('content');
            if (token) {
                $.ajaxSetup({
                    headers: $.extend({}, $.ajaxSettings.headers || {}, {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token
                    })
                });
            }
        },

        // ── Navigation ───────────────────────────────────────────────────────────

        bindNavigationEvents: function () {
            const self = this;

            $(document).on('click', 'a[href]:not([target="_blank"]):not([data-no-ajax])', function (e) {
                const href = $(this).attr('href');
                if (!href) return;
                if (
                    self.isExternalLink(href) ||
                    href.startsWith('#') ||
                    href.startsWith('javascript:') ||
                    href.startsWith('mailto:') ||
                    href.startsWith('tel:')
                ) return;

                e.preventDefault();
                self.navigate(href, true);
            });

            if (this.config.enableHistory) {
                window.addEventListener('popstate', function (e) {
                    const route = (e.state && e.state.route) ? e.state.route : window.location.pathname;
                    self.loadRoute(route, false);
                });
            }
        },

        setupHistoryAPI: function () {
            if (this.config.enableHistory && window.history && window.history.pushState) {
                history.replaceState({ route: window.location.pathname }, document.title, window.location.pathname);
            }
        },

        navigate: function (url, updateHistory) {
            // Normalise to pathname only (strip same-origin host if present)
            try {
                const parsed = new URL(url, window.location.origin);
                if (parsed.hostname === window.location.hostname) {
                    url = parsed.pathname + parsed.search + parsed.hash;
                }
            } catch (e) { /* keep url as-is */ }

            // Strip jQuery cache-buster
            url = url.replace(/[?&]_=\d+/, '');

            if (url === this.currentRoute) return;

            // Abort any in-flight request for a different route
            if (this._activeXhr) {
                this._activeXhr.abort();
                this._activeXhr = null;
            }

            // Invalidate cache for this URL so it gets a fresh copy
            delete this.cache[url];

            this.loadRoute(url, updateHistory);
        },

        loadRoute: function (url, updateHistory) {
            const self = this;

            // Serve from cache instantly (no fade, no loading bar)
            if (this.config.cacheViews && this.cache[url]) {
                this._swapContent(this.cache[url], url, updateHistory);
                return;
            }

            this.showLoading();

            this._activeXhr = $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                timeout: this.config.requestTimeout,
                success: function (response) {
                    // Cache all GET responses except auth/user-specific pages
                    if (self.config.cacheViews && !self._isPrivateRoute(url)) {
                        self.cache[url] = response;
                    }
                    self._swapContent(response, url, updateHistory);
                },
                error: function (jqxhr, status) {
                    if (status === 'abort') return; // We aborted — ignore

                    self.hideLoading();

                    let response = null;
                    try { response = JSON.parse(jqxhr.responseText); } catch (e) {}

                    if (jqxhr.status === 302 || jqxhr.status === 301) {
                        // Follow redirects returned as JSON
                        const loc = jqxhr.getResponseHeader('Location');
                        if (loc) { self.navigate(loc, true); return; }
                    }

                    if (jqxhr.status === 401 || jqxhr.status === 403) {
                        self.navigate('/login', true);
                        return;
                    }

                    if (jqxhr.status === 404) {
                        const html = (response && response.html)
                            ? response.html
                            : '<div class="container text-center py-2xl"><h1 class="text-4xl font-bold mb-md">404</h1><p class="text-lg mb-xl text-neutral-600">Page not found</p><a href="/" class="btn btn-primary btn-md">Go Home</a></div>';
                        self._swapContent({ html: html, title: '404 – Page Not Found' }, url, updateHistory);
                        return;
                    }

                    self.showError('Something went wrong. Please try again.');
                },
                complete: function () {
                    self._activeXhr = null;
                }
            });
        },

        // ── Content swap ─────────────────────────────────────────────────────────

        _swapContent: function (response, url, updateHistory) {
            const self = this;
            const $content = $(this.config.contentSelector);
            const dur = this.config.transitionDuration;

            $content.fadeTo(dur, 0, function () {
                if (response.html) $content.html(response.html);

                if (response.title) document.title = response.title;
                if (response.meta)  self.updateMetaTags(response.meta);

                if (updateHistory && self.config.enableHistory) {
                    history.pushState({ route: url }, response.title || document.title, url);
                }

                self.currentRoute = url;

                // Re-run inline scripts from newly loaded HTML
                self.bindDynamicEvents();

                $content.fadeTo(dur, 1, function () {
                    self.hideLoading();
                    window.scrollTo({ top: 0, behavior: 'instant' });
                    $(document).trigger('page:loaded', [url, response]);
                });
            });
        },

        // ── Form submissions ─────────────────────────────────────────────────────

        bindFormEvents: function () {
            const self = this;
            const submitting = new WeakSet();

            $(document).on('submit', 'form[data-ajax]', function (e) {
                e.preventDefault();
                if (submitting.has(this)) return;
                submitting.add(this);

                const $form = $(this);
                const method = ($form.attr('method') || 'POST').toUpperCase();
                const action = $form.attr('action') || window.location.pathname;

                self.submitForm(action, method, new FormData(this), $form)
                    .always(function () { submitting.delete($form[0]); });
            });
        },

        submitForm: function (url, method, formData, $form) {
            const self = this;
            const $btn = $form.find('[type="submit"]');
            const origHtml = $btn.html();

            if ($btn.prop('disabled')) {
                return $.Deferred().reject();
            }

            $btn.prop('disabled', true).html('<span class="spinner"></span> Loading…');

            return $.ajax({
                url: url,
                method: method,
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        self.showSuccess(response.message || 'Success!');
                        if (response.redirect) {
                            // Short delay so the user sees the success toast
                            setTimeout(function () {
                                self.navigate(response.redirect, true);
                            }, 500);
                        }
                        if (response.resetForm) $form[0].reset();
                        $(document).trigger('form:success', [response, $form]);
                    } else {
                        self.showError(response.message || 'An error occurred');
                        if (response.errors) self.showValidationErrors($form, response.errors);
                    }
                },
                error: function (jqxhr) {
                    const res = jqxhr.responseJSON;
                    self.showError((res && res.message) ? res.message : 'Form submission failed');
                    if (res && res.errors) self.showValidationErrors($form, res.errors);
                },
                complete: function () {
                    $btn.prop('disabled', false).html(origHtml);
                }
            });
        },

        showValidationErrors: function ($form, errors) {
            $form.find('.error-message').remove();
            $form.find('.input-error').removeClass('input-error');

            $.each(errors, function (field, messages) {
                const $field = $form.find('[name="' + field + '"]');
                $field.addClass('input-error');
                $field.after('<div class="error-message">' + messages.join('<br>') + '</div>');
            });
        },

        // ── Dynamic events after content swap ────────────────────────────────────

        bindDynamicEvents: function () {
            const $content = $(this.config.contentSelector);

            $content.find('script').each(function () {
                const s = document.createElement('script');
                if (this.src) {
                    try {
                        const u = new URL(this.src, window.location.origin);
                        if (u.hostname !== window.location.hostname) return;
                        s.src = this.src;
                        s.async = false;
                        document.head.appendChild(s);
                        setTimeout(function () { s.remove(); }, 1000);
                    } catch (e) { /* ignore malformed src */ }
                } else {
                    s.textContent = this.textContent;
                    document.head.appendChild(s);
                    document.head.removeChild(s);
                }
            });

            // Re-read CSRF token in case a new session was started (e.g. after login)
            NativeApp.setupCSRFToken();

            $(document).trigger('content:updated');
        },

        // ── Loading indicator ─────────────────────────────────────────────────────

        showLoading: function () {
            const bar = document.getElementById('loading-bar');
            if (!bar) return;
            clearTimeout(this._loadingTimer);
            bar.style.transition = 'none';
            bar.style.width = '0%';
            bar.style.opacity = '1';
            // Force reflow so the width reset takes effect before the animation
            bar.offsetWidth; // eslint-disable-line no-unused-expressions
            bar.style.transition = 'width 15s cubic-bezier(0.1, 0.05, 0, 1)';
            bar.style.width = '85%';
        },

        hideLoading: function () {
            const bar = document.getElementById('loading-bar');
            if (!bar) return;
            clearTimeout(this._loadingTimer);
            bar.style.transition = 'width 0.1s ease';
            bar.style.width = '100%';
            this._loadingTimer = setTimeout(function () {
                bar.style.transition = 'opacity 0.2s ease';
                bar.style.opacity = '0';
                setTimeout(function () {
                    bar.style.width = '0%';
                    bar.style.opacity = '1';
                }, 250);
            }, 150);
        },

        // ── Notifications ─────────────────────────────────────────────────────────

        showSuccess: function (msg) { this.showNotification(msg, 'success'); },
        showError:   function (msg) { this.showNotification(msg, 'error'); },

        showNotification: function (message, type) {
            const $n = $('<div class="notification notification-' + (type || 'info') + '">' + message + '</div>');
            $('body').append($n);
            requestAnimationFrame(function () { $n.addClass('show'); });
            setTimeout(function () {
                $n.removeClass('show');
                setTimeout(function () { $n.remove(); }, 350);
            }, 3500);
        },

        // ── Utilities ─────────────────────────────────────────────────────────────

        updateMetaTags: function (meta) {
            $.each(meta, function (name, content) {
                let $m = $('meta[name="' + name + '"]');
                if (!$m.length) { $m = $('<meta name="' + name + '">'); $('head').append($m); }
                $m.attr('content', content);
            });
        },

        isExternalLink: function (url) {
            if (!url) return false;
            const a = document.createElement('a');
            a.href = url;
            return a.hostname !== '' && a.hostname !== window.location.hostname;
        },

        _isPrivateRoute: function (url) {
            const priv = ['/dashboard', '/users', '/login', '/register', '/logout',
                          '/forgot-password', '/profile', '/account', '/settings'];
            return priv.some(function (p) { return url === p || url.startsWith(p + '/'); });
        },

        clearCache: function () { this.cache = {}; },

        // ── Public API ────────────────────────────────────────────────────────────

        api: {
            get: function (url, data) {
                return $.ajax({ url: url, method: 'GET', data: data, dataType: 'json' });
            },
            post: function (url, data) {
                return $.ajax({ url: url, method: 'POST', data: JSON.stringify(data),
                    contentType: 'application/json', dataType: 'json' });
            },
            put: function (url, data) {
                return $.ajax({ url: url, method: 'PUT', data: JSON.stringify(data),
                    contentType: 'application/json', dataType: 'json' });
            },
            delete: function (url) {
                const token = $('meta[name="csrf-token"]').attr('content');
                return $.ajax({ url: url, method: 'DELETE', dataType: 'json',
                    headers: { 'X-CSRF-TOKEN': token || '', 'X-Requested-With': 'XMLHttpRequest' } });
            }
        }
    };

    // ── Boot ─────────────────────────────────────────────────────────────────────

    $(document).ready(function () {
        NativeApp.init();

        if (typeof DEBUG_MODE !== 'undefined' && DEBUG_MODE) {
            console.log('%c⚡ VelocityPHP SPA ready', 'color:#6366f1;font-weight:bold;font-size:14px');
        }
    });

})(jQuery);

// Silence noisy extension/cross-origin errors
window.addEventListener('error', function (e) {
    if (!e.filename) return;
    if (e.filename.includes('extension') || e.filename.includes('chrome-extension') ||
        e.filename.includes('moz-extension')) return;
    if (window.NativeApp && e.filename.includes('/assets/js/') &&
        !window.location.search.includes('_=')) {
        NativeApp.showError('A script error occurred. Please refresh the page.');
    }
});
