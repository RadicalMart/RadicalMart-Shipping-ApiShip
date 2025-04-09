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

class RadicalMartShippingApiShipCheckout extends JoomlaAjaxUtil {
	constructor() {
		super('RadicalMartShippingApiShipCheckout');
		this.options = Joomla.getOptions('com_radicalmart.checkout');
		this.controller = false;
		this.form = false;
	}

	initialization() {
		let form = document.querySelector('[radicalmart-checkout="form"], [data-radicalmart-checkout="form"]');
		if (!form) {
			return;
		}

		this.setVariable('form', form);
		this.setVariable('controller', form.getAttribute('action'));
		this.loadTariffs();
	}

	loadTariffs() {
		let errors = document.querySelectorAll('[radicalmart-checkout-display="shipping.error"],'
			+ '[data-radicalmart-checkout-display="shipping.error"]');
		errors.forEach((element) => {
			element.style.display = 'none';
		});

		this.sendAjax('loadTariffs', new FormData(this.form), true).then((response) => {
			console.log(response);
		}).catch((e) => {
			if (e.message) {
				errors.forEach((element) => {
					console.log(element);
					element.innerHTML = e.message;
					element.style.display = '';
				});
				console.error(e);
			}
		})
	}
}

export default RadicalMartShippingApiShipCheckout;

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
	console.log('onRadicalMartShippingApiShipCheckoutAfterUpdateDisplayData');
	let loading = document.querySelector('#checkout_shipping_loading');
	if (loading) {
		loading.style.display = 'none';
	}

	document.querySelectorAll('[radicalmart-checkout-display="shipping.message"],'
		+ '[data-radicalmart-checkout-display="shipping.message"]')
		.forEach((element) => {
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
});