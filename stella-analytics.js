/**
 * Stella Analytics - Lightweight Tracking Snippet
 * Size: ~2KB minified
 * 
 * Usage:
 * <script src="https://your-domain.com/stella-analytics.js"></script>
 * <script>
 *   StellaAnalytics.init('sk_your_api_key_here', 'https://your-domain.com');
 * </script>
 */

(function(window) {
    'use strict';
    
    var StellaAnalytics = {
        apiKey: null,
        baseUrl: null,
        sessionId: null,
        queue: [],
        isTracking: false,
        
        /**
         * Initialize analytics tracking
         * @param {string} apiKey - Your Stella API key
         * @param {string} baseUrl - Your Stella instance URL
         */
        init: function(apiKey, baseUrl) {
            if (!apiKey || !baseUrl) {
                console.error('[Stella Analytics] API key and base URL required');
                return;
            }
            
            this.apiKey = apiKey;
            this.baseUrl = baseUrl.replace(/\/$/, ''); // Remove trailing slash
            this.sessionId = this.getOrCreateSessionId();
            this.isTracking = true;
            
            // Track initial pageview
            this.trackPageview();
            
            // Track page unload
            window.addEventListener('beforeunload', this.sendBeacon.bind(this));
            
            console.log('[Stella Analytics] Initialized');
        },
        
        /**
         * Get or create session ID
         */
        getOrCreateSessionId: function() {
            var sid = sessionStorage.getItem('stella_session_id');
            if (!sid) {
                sid = 'ss_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                sessionStorage.setItem('stella_session_id', sid);
            }
            return sid;
        },
        
        /**
         * Track a pageview
         */
        trackPageview: function() {
            this.track('pageview', null, {
                page_url: window.location.href,
                page_title: document.title,
                referrer: document.referrer
            });
        },
        
        /**
         * Track a custom event
         * @param {string} eventType - Type of event (e.g., 'click', 'form_submit', 'download')
         * @param {string} eventName - Name of the event (e.g., 'Download PDF', 'Sign Up Button')
         * @param {object} metadata - Additional event data
         */
        track: function(eventType, eventName, metadata) {
            if (!this.isTracking) return;
            
            var event = {
                event_type: eventType,
                event_name: eventName,
                page_url: metadata && metadata.page_url || window.location.href,
                page_title: metadata && metadata.page_title || document.title,
                referrer: metadata && metadata.referrer || document.referrer,
                session_id: this.sessionId,
                metadata: metadata || {}
            };
            
            this.queue.push(event);
            this.flush();
        },
        
        /**
         * Send queued events to server
         */
        flush: function() {
            if (this.queue.length === 0) return;
            
            var events = this.queue.slice();
            this.queue = [];
            
            // Send each event (could batch in production)
            events.forEach(function(event) {
                this.send(event);
            }.bind(this));
        },
        
        /**
         * Send event to server
         */
        send: function(event) {
            var url = this.baseUrl + '/api/analytics.php?action=track';
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': this.apiKey
                },
                body: JSON.stringify(event),
                keepalive: true
            }).catch(function(err) {
                console.error('[Stella Analytics] Failed to send event:', err);
            });
        },
        
        /**
         * Send events via sendBeacon for page unload
         */
        sendBeacon: function() {
            if (this.queue.length === 0) return;
            
            var url = this.baseUrl + '/api/analytics.php?action=track';
            this.queue.forEach(function(event) {
                var blob = new Blob([JSON.stringify(event)], { type: 'application/json' });
                navigator.sendBeacon(url, blob);
            });
            this.queue = [];
        },
        
        /**
         * Track a click event
         */
        trackClick: function(elementName, metadata) {
            this.track('click', elementName, metadata);
        },
        
        /**
         * Track a form submission
         */
        trackFormSubmit: function(formName, metadata) {
            this.track('form_submit', formName, metadata);
        },
        
        /**
         * Track a download
         */
        trackDownload: function(fileName, metadata) {
            this.track('download', fileName, metadata);
        },
        
        /**
         * Track a custom conversion
         */
        trackConversion: function(conversionName, metadata) {
            this.track('conversion', conversionName, metadata);
        }
    };
    
    // Expose to window
    window.StellaAnalytics = StellaAnalytics;
    
})(window);


