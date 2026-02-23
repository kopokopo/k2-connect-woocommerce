import './kkwoo-block-style.css';
import { createElement } from '@wordpress/element';

const { getSetting } = window.wc.wcSettings;

const settings = getSetting( 'kkwoo_data', {} );
const title = settings.title ?? 'Lipa na M-PESA';

const { registerPaymentMethod } = window.wc.wcBlocksRegistry;

registerPaymentMethod( {
	name: 'kkwoo',
	paymentMethodId: 'kkwoo',
	label: createElement( 'span', {}, title ),
	ariaLabel: title,
	content: createElement(
		'p',
		{},
		'Click "Lipa na M-PESA" below to pay with M-PESA.'
	),
	edit: createElement(
		'p',
		{},
		'Click "Lipa na M-PESA" below to pay with M-PESA.'
	),
	canMakePayment: () => true,
	placeOrderButtonLabel: 'Lipa na M-PESA',
	supports: {
		features: settings.supports ?? [],
	},
} );
