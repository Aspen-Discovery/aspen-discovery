/**
 * Example Plugin JavaScript
 * This file demonstrates how plugins can include external JS files
 */

(function () {
    'use strict';

    // Example plugin initialization
    window.ExamplePluginExternal = {

        /**
         * Initialize the plugin
         */
        init: function () {
            console.log('Example Plugin external JS loaded');
            this.addEventListeners();
            this.addBanner();
        },

        /**
         * Add event listeners
         */
        addEventListeners: function () {
            // Example: Add click handler to logo
            var logo = document.querySelector('.header-logo');
            if (logo) {
                logo.addEventListener('click', function (e) {
                    console.log('Logo clicked - Example Plugin detected this!');
                });
            }
        },

        /**
         * Add a sample banner to demonstrate CSS classes
         */
        addBanner: function () {
            // Only add banner on homepage
            if (window.location.pathname === '/' || window.location.pathname.indexOf('/Search/Home') !== -1) {
                var banner = document.createElement('div');
                banner.className = 'example-plugin-banner';
                banner.innerHTML = 'Example Plugin is active!';
                banner.style.marginBottom = '10px';

                var mainContent = document.querySelector('#main-content') || document.querySelector('body');
                if (mainContent) {
                    mainContent.insertBefore(banner, mainContent.firstChild);
                }
            }
        },

        /**
         * Utility function to highlight elements
         */
        highlightElement: function (selector) {
            var elements = document.querySelectorAll(selector);
            elements.forEach(function (element) {
                element.classList.add('example-plugin-highlight');
            });
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            window.ExamplePluginExternal.init();
        });
    } else {
        window.ExamplePluginExternal.init();
    }

})(); 