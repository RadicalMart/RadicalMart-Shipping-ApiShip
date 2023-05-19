/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2023 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

"use strict";

import RadicalMartShippingApiShipAjax from "./util/ajax.es6";

class RadicalMartShippingApiShipOrder extends RadicalMartShippingApiShipAjax {
	constructor() {
		super();

		this.options = Joomla.getOptions('radicalmart_shipping_apiship_radicalmart');
		this.controller = (this.options && this.options.controller) ? this.options.controller : false;

		this.form = false;
		this.context = (this.options) ? this.options.context : false;
	}

	initialization() {
		if (this.context) {
			if (this.context === 'com_radicalmart.checkout') {
				let form = document.querySelector('[radicalmart-checkout="form"], [data-radicalmart-checkout="form"]');
				if (form) {
					this.setVariable('form', form);
					form.querySelector('[name*="[shipping][price][base]"]').value = 0;
					form.querySelector('[name*="[shipping][price][final]"]').value = 0;
					form.querySelector('[name*="[shipping][price][hash]"]').value = '';
					window.RadicalMartCheckout().updateDisplayData();
				}
			}
			if (this.context === 'com_radicalmart.order') {
				let form = document.querySelector('#adminForm');
				this.setVariable('form', form);
			}
		}
	}

	calculate() {
		if (this.form && this.controller && this.context) {
			let context = this.context,
				action = (context === 'com_radicalmart.checkout') ? 'calculateCheckout' : 'calculateOrder',
				form = this.form,
				formData = new FormData(form);
			formData.set('context', this.options.context);
			formData.set('shipping', this.options.shipping);

			this.sendAjax(action, formData).then((response) => {
				if (context === 'com_radicalmart.checkout') {
					form.querySelector('[name*="[shipping][price][base]"]').value = response.base;
					form.querySelector('[name*="[shipping][price][final]"]').value = response.final;
					form.querySelector('[name*="[shipping][price][hash]"]').value = response.hash;
					window.RadicalMartCheckout().updateDisplayData();
				}
				console.log(response);
			}).catch((error) => {
				if (context === 'com_radicalmart.checkout') {
					window.RadicalMartCheckout().triggerEvent('onRadicalMartCheckoutError', error.message);
					form.querySelector('[name*="[shipping][price][base]"]').value = -1;
					form.querySelector('[name*="[shipping][price][final]"]').value = -1;
					form.querySelector('[name*="[shipping][price][hash]"]').value = '';
					window.RadicalMartCheckout().updateDisplayData();
				} else {
					Joomla.renderMessages({
						error: [error.message]
					});
				}
				console.error(error.message);
			});
		}
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