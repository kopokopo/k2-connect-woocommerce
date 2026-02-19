/**
 * Polling Section Template.
 *
 * Adds a template function to render the polling section used to poll for payment status.
 *
 * @param {Object} templates Template registry object.
 */
( function ( templates ) {
	'use strict';

	templates.Polling = function () {
		return `
        <div id='polling'>
            <img src='${ templates.getImageUrl(
				'spinner_icon'
			) }' alt='Spinner icon' class='k2 spinner'/>
            <div>
                <p class='main-info'>Processing payment</p>
                <p class='side-note'>Please do not refresh or close the tab until the process is complete</p>
            </div>
        </div> 
      `;
	};
} )( window.KKWooTemplates );
