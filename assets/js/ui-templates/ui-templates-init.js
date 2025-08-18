/**
 * Initialize Templates Namespace
 * This file must be loaded before any individual template files
 */
window.KKWooTemplates = window.KKWooTemplates || {};

// Add helper functions that templates can use
(function(templates) {
    'use strict';
    
    templates.getImageUrl = function(key) {
        return KKWooData[key] || '';
    };
    
})(window.KKWooTemplates);
