/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2025 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

"use strict";

import JoomlaAjaxUtil from "../util/ajax.es6";

class RadicalMartShippingApiShipCheckout {
	initialization() {
		let form = document.querySelector('[radicalmart-checkout="form"], [data-radicalmart-checkout="form"]');
		if (!form) {
			return;
		}

		this.messages = document.querySelectorAll('[radicalmart-checkout-display="shipping.message"],'
			+ '[data-radicalmart-checkout-display="shipping.message"]');
		this.errors = document.querySelectorAll('[radicalmart-checkout-display="shipping.error"],'
			+ '[data-radicalmart-checkout-display="shipping.error"]');
		this.loading =  document.querySelector('[radicalmart-checkout-shipping-apiship="loading"],'
			+ '[data-radicalmart-checkout-shipping-apiship="loading"]');

		this.loadTariffs();
	}

	displayMessages() {
		if (this.loading) {
			this.loading.style.display = 'none';
		}

		this.messages.forEach((element) => {
			if (event.detail && event.detail.shipping.message) {
				element.innerHTML = event.detail.shipping.message.replace(/(\r\n|\n|\r)/gm, '<br>');
				element.style.display = '';
			} else {
				element.style.display = 'none';
			}
		});

		document.querySelectorAll('[radicalmart-checkout-display="shipping.error"],'
			+ '[data-radicalmart-checkout-display="shipping.error"]')
			.forEach((element) => {
				if (event.detail && event.detail.shipping.error) {
					element.innerHTML = event.detail.shipping.error.replace(/(\r\n|\n|\r)/gm, '<br>');
					element.style.display = '';
				} else {
					element.style.display = 'none';
				}
			});
	}

	async loadTariffs() {

		let tariffField = document.querySelector('[name="jform[shipping][tariff][id]"]'),
			canBeLoaded = false;
		if (!tariffField) {
			return;
		}
		let tariffFieldContainer = tariffField
			.closest('[radicalmart-shipping-apiship-field-tariffs="container"],'
				+ '[data-radicalmart-shipping-apiship-field-tariffs="container"]');
		if (!tariffFieldContainer) {
			return;
		}

		await new Promise((resolve) => {
			let attempts = 0;
			let interval = setInterval(() => {
				attempts++;
				if (tariffFieldContainer.FieldClass || attempts > 6) {
					clearInterval(interval);
					resolve();
				}
			}, 10);
		});

		if (!tariffFieldContainer.FieldClass) {
			return;
		}
		let tariffFieldClass = tariffFieldContainer.FieldClass;

		let pointField = document.querySelector('[name="jform[shipping][point][id]"]');
		if (pointField) {
			if (pointField.value && parseInt(pointField.value) > 0) {
				canBeLoaded = true;
			}
		}

		let tariffContainer = document.querySelector('[radicalmart-checkout-shipping-apiship="tariff"],'
			+ '[data-radicalmart-checkout-shipping-apiship="tariff"]');
		if (!canBeLoaded) {
			if (tariffContainer) {
				tariffContainer.style.display = 'none';
			}
			return;
		}
		this.errors.forEach((element) => {
			element.style.display = 'none';
		});

		this.messages.forEach((element) => {
			element.style.display = 'none';
		});
		if (this.loading) {
			this.loading.style.display = '';
		}
		await tariffFieldClass.loadList();
		if (this.loading) {
			this.loading.style.display = 'none';
		}
		if (tariffContainer) {
			tariffContainer.style.display = '';
		}
	}

	updateShippingPrice() {
		if (this.loading) {
			this.loading.style.display = '';
		}

		window.RadicalMartCheckout().updateDisplayData();
	}
}

window.RadicalMartShippingApiShipCheckoutClass = null;
window.RadicalMartShippingApiShipCheckout = () => {
	if (window.RadicalMartShippingApiShipCheckoutClass === null) {
		window.RadicalMartShippingApiShipCheckoutClass = new RadicalMartShippingApiShipCheckout();
	}
	return window.RadicalMartShippingApiShipCheckoutClass;
}

document.addEventListener('DOMContentLoaded', () => {
	window.RadicalMartShippingApiShipCheckout().initialization();
});

document.addEventListener('onRadicalMartCheckoutAfterUpdateDisplayData', (event) => {

	window.RadicalMartShippingApiShipCheckout().displayMessages();
});