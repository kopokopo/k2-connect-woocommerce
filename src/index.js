import './style.css';
import { createElement, render } from '@wordpress/element';
import K2PaymentContent from './K2PaymentContent';

const { getSetting } = window.wc.wcSettings;

const settings = getSetting('kkwoo_data', {});

const registerPaymentMethodWithRegistry = (registry = window.wc.wcBlocksRegistry) => {
	const { registerPaymentMethod } = registry;

	registerPaymentMethod({
		name: 'kkwoo',
		paymentMethodId: 'kkwoo',
		label: <span>{settings.title}</span>,
		ariaLabel: settings.title,
		content: createElement(K2PaymentContent),
		edit: createElement('p', {}, settings.description),
		canMakePayment: () => true,
		placeOrderButtonLabel: 'Lipa na M-Pesa',
		supports: {
			features: settings.supports ?? [],
		},
		payment: () => {
			return {
				then: (resolve) => {
					// We'll resolve success only after modal confirms
					window.kkwooResolvePayment = resolve;

					// Trigger modal open logic (can use custom event or global state)
					const event = new CustomEvent('kkwoo-open-modal');
					window.dispatchEvent(event);
					},
			};
		},
	});

	window.kkwooRegistered = true;
};

window.addEventListener('DOMContentLoaded', () => {
	registerPaymentMethodWithRegistry();
});
