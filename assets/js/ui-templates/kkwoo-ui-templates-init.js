/* global KKWooData */

/**
 * Initialize Templates Namespace.
 *
 * This file must be loaded before any individual template files.
 */
window.KKWooTemplates = window.KKWooTemplates || {};

/**
 * Add helper functions that templates can use.
 *
 * @param {Object} templates Template registry object.
 */
( function ( templates ) {
	'use strict';

	/**
	 * Get an image URL from localized data.
	 *
	 * @param {string} key Image key.
	 * @return {string} Image URL or empty string.
	 */
	templates.getImageUrl = function ( key ) {
		return KKWooData[ key ] || '';
	};
} )( window.KKWooTemplates );
