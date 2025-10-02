/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

"use strict";

import {ElementsUtils} from "../util/elements.es6";

class RadicalMartShippingApiShipCheckout {
	initialization() {
		let form = document.querySelector('[radicalmart-checkout="form"], [data-radicalmart-checkout="form"]');
		if (!form) {
			return;
		}

		this.messages = ElementsUtils.getElementsByAttribute('radicalmart-checkout-display', 'shipping.message');
		this.errors = ElementsUtils.getElementsByAttribute('radicalmart-checkout-display', 'shipping.error');
		this.loading = ElementsUtils.getElementByAttribute('radicalmart-shipping-apiship-checkout', 'loading');
		this.tariffContainer = ElementsUtils.getElementByAttribute('radicalmart-shipping-apiship-checkout', 'tariff');

		this.pointIdField = document.querySelector('[name="jform[shipping][point][id]"]');
		this.addressStringField = document.querySelector('[name="jform[shipping][address][string]"]');

		if (this.tariffContainer) {
			if (this.pointIdField) {
				let pointsContainer = ElementsUtils.getClosestByAttribute(
					'radicalmart-shipping-apiship-field-points', 'container', this.pointIdField);
				if (pointsContainer) {
					pointsContainer.addEventListener('balloonopen', () => {
						this.tariffContainer.style.display = 'none';
					})
					pointsContainer.addEventListener('balloonclose', () => {
						this.tariffContainer.style.display = '';
					})
				}
			} else if (this.addressStringField) {
				let addressesContainer = ElementsUtils.getClosestByAttribute(
					'radicalmart-shipping-apiship-field-addresses', 'container', this.addressStringField);
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

	displayMessages(event) {
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

		this.errors.forEach((element) => {
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

		let tariffFieldContainer = ElementsUtils.getClosestByAttribute(
			'radicalmart-shipping-apiship-field-tariffs', 'container', tariffField);
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

document.addEventListener('onRadicalMartCheckoutAfterUpdateDisplayData', (event) => {
	window.RadicalMartShippingApiShipCheckout().displayMessages(event);
});