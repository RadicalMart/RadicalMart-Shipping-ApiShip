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
		this.loading = document.querySelector('[radicalmart-shipping-apiship-checkout="loading"],'
			+ '[data-radicalmart-shipping-apiship-checkout="loading"]');

		this.tariffContainer = document.querySelector('[radicalmart-shipping-apiship-checkout="tariff"],'
			+ '[data-radicalmart-shipping-apiship-checkout="tariff"]');
		this.pointIdField = document.querySelector('[name="jform[shipping][point][id]"]');
		this.addressStringField = document.querySelector('[name="jform[shipping][address][string]"]');

		if (this.tariffContainer) {
			if (this.pointIdField) {
				let pointsContainer = this.pointIdField.closest(
					'[radicalmart-shipping-apiship-field-points="container"],'
					+ '[data-radicalmart-shipping-apiship-field-points="container"]');
				if (pointsContainer) {
					pointsContainer.addEventListener('balloonopen', () => {
						this.tariffContainer.style.display = 'none';
					})
					pointsContainer.addEventListener('balloonclose', () => {
						this.tariffContainer.style.display = '';
					})
				}
			} else if (this.addressStringField) {
				let addressesContainer = this.addressStringField.closest(
					'[radicalmart-shipping-apiship-field-addresses="container"],'
					+ '[data-radicalmart-shipping-apiship-field-addresses="container"]');

				if (addressesContainer) {
					addressesContainer.addEventListener('address_not_valid', () => {
						this.tariffContainer.style.display = 'none';
					})
					addressesContainer.addEventListener('address_valid', () => {
						this.tariffContainer.style.display = '';
					})
				}
			}
		}

		this.loadTariffs().then();
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

		if (this.pointIdField) {
			if (this.pointIdField.value && parseInt(this.pointIdField.value) > 0) {
				canBeLoaded = true;
			}
		} else if (this.addressStringField) {
			if (this.addressStringField.value && this.addressStringField !== '') {
				canBeLoaded = true;
			}
		}

		if (!canBeLoaded) {
			if (this.tariffContainer) {
				this.tariffContainer.style.display = 'none';
			}
			if (parseInt(tariffField.value) > 0) {
				tariffField.value = 0;
				window.RadicalMartCheckout().updateDisplayData();
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
		if (this.tariffContainer) {
			this.tariffContainer.style.display = '';
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

document.addEventListener('onRadicalMartCheckoutAfterUpdateDisplayData', () => {
	window.RadicalMartShippingApiShipCheckout().displayMessages();
});