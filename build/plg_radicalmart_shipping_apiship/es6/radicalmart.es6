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

class RadicalMartShippingApiShipRadicalMart extends RadicalMartShippingApiShipAjax {
	constructor() {
		super();

		this.options = Joomla.getOptions('radicalmart_shipping_apiship_radicalmart');
		this.controller = (this.options && this.options.controller) ? this.options.controller : false;

		this.form = false;
		this.formType = (this.options) ? this.options.formType : false;
	}

	initialization() {
		let form = document.querySelector('[radicalmart-checkout="form"], [data-radicalmart-checkout="form"]');
		if (form) {
			this.setVariable('form', form);
			this.calculate();
		}
	}

	calculate() {
		if (this.form && this.controller && this.formType) {
			let form = this.form,
				formData = new FormData(this.form),
				formType = this.getVariable('formType'),
				action = (formType === 'checkout') ? 'calculateCheckout' : 'calculateOrder'
			formData.set('action', action);

			console.log('calculate');
		}
	}
}

export default RadicalMartShippingApiShipRadicalMart;

window.RadicalMartShippingApiShipRadicalMartClass = null;
window.RadicalMartShippingApiShipRadicalMart = () => {
	if (window.RadicalMartShippingApiShipRadicalMartClass === null) {
		window.RadicalMartShippingApiShipRadicalMartClass = new RadicalMartShippingApiShipRadicalMart();
	}
	return window.RadicalMartShippingApiShipRadicalMartClass;
}

document.addEventListener('DOMContentLoaded', () => {
	window.RadicalMartShippingApiShipRadicalMart().initialization();
});