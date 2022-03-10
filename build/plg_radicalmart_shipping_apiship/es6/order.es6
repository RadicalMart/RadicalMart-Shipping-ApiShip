/*
 * @package     RadicalMart Package
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2022 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

"use strict";

import axios from 'axios';

class RadicalMartShippingApiShipOrder {
	constructor() {
		this.options = Joomla.getOptions('radicalmart_shipping_apiship_order');
		this.form = false;
		this.controller = (this.options) ? this.options.controller : false;
		this.formType = (this.options) ? this.options.formType : false;
	}

	initialization() {
		let form = document.querySelector('[radicalmart-checkout="form"]');
		if (!form) form = document.querySelector('[data-radicalmart-checkout="form"]');
		if (form) {
			this.setVariable('form', form);
			// TODO Start calc
			//this.calculate();
		}
	}

	calculate() {
		if (this.form && this.controller && this.formType) {
			let form = this.form,
				formData = new FormData(this.form),
				formType = this.getVariable('formType'),
				action = (formType === 'checkout') ? 'calculateCheckout' : 'calculateOrder'
			formData.set('action', action);
			axios({
				method: 'post',
				url: this.getVariable('controller'),
				data: formData,
				headers: {'Content-Type': 'multipart/form-data'},
			}).then((response) => {
				if (response.data.success) {
					let data = response.data.data[0];
					if (formType === 'checkout') {
						form.querySelector('[name*="[shipping][price][base]"]').value = data.base;
						form.querySelector('[name*="[shipping][price][final]"]').value = data.final;
						form.querySelector('[name*="[shipping][price][hash]"]').value = data.hash;
						window.RadicalMartCheckout().updateDisplayData();
					}
				} else {
					if (formType === 'checkout') {
						window.RadicalMartCheckout().triggerEvent('onRadicalMartCheckoutError', response.data.message);
						form.querySelector('[name*="[shipping][price][base]"]').value = -1;
						form.querySelector('[name*="[shipping][price][final]"]').value = -1;
						form.querySelector('[name*="[shipping][price][hash]"]').value = '';
						window.RadicalMartCheckout().updateDisplayData();
					}
					console.error(response.data.message);
				}
			}).catch((error) => {
				if (error.message !== 'Request aborted') {
					if (formType === 'checkout') {
						window.RadicalMartCheckout().triggerEvent('onRadicalMartCheckoutError', error.message);
						form.querySelector('[name*="[shipping][price][base]"]').value = -1;
						form.querySelector('[name*="[shipping][price][final]"]').value = -1;
						form.querySelector('[name*="[shipping][price][hash]"]').value = '';
						window.RadicalMartCheckout().updateDisplayData();
					}
					console.error(error.message);
				}
			});
		}
	}

	setVariable(key, value) {
		this[key] = value;
	}

	getVariable(key, defaultValue = null) {
		if (!this[key] || this[key] === null) return defaultValue;
		return this[key];
	}
}

export default RadicalMartShippingApiShipOrder;

window.RadicalMartShippingApiShipOrderClass = null;
window.RadicalMartShippingApiShipOrder = () => {
	if (window.RadicalMartShippingApiShipOrderClass === null) {
		window.RadicalMartShippingApiShipOrderClass = new RadicalMartShippingApiShipOrder();
	}
	return window.RadicalMartShippingApiShipOrderClass;
}

document.addEventListener('DOMContentLoaded', () => {
	window.RadicalMartShippingApiShipOrder().initialization();
});