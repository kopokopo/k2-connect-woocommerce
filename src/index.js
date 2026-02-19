import './kkwoo-block-style.css';
import { createElement } from '@wordpress/element';

const { getSetting } = window.wc.wcSettings;

const settings = getSetting( 'kkwoo_data', {} );

const registerPaymentMethodWithRegistry = (
	registry = window.wc.wcBlocksRegistry
) => {
	const { registerPaymentMethod } = registry;

	registerPaymentMethod( {
		name: 'kkwoo',
		paymentMethodId: 'kkwoo',
		label: <span>{ settings.title }</span>,
		ariaLabel: settings.title,
		content: createElement(
			'p',
			{},
			'Click &quot;Lipa na M-PESA&quot; below to pay with M-PESA.'
		),
		edit: createElement( 'p', {}, settings.description ),
		canMakePayment: () => true,
		placeOrderButtonLabel: 'Lipa na M-PESA',
		supports: {
			features: settings.supports ?? [],
		},
	} );

	window.kkwooRegistered = true;
};

window.addEventListener( 'DOMContentLoaded', () => {
	registerPaymentMethodWithRegistry();
} );
