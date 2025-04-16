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

class RadicalMartShippingApiShipFieldAddresses extends JoomlaAjaxUtil {
	constructor(container) {
		let id = container.getAttribute('id'), options = Joomla.getOptions(id),
			coreOptions = Joomla.getOptions('plg_radicalmart_shipping_apiship.field.addresses'),
			controller = (coreOptions) ? coreOptions.controller : false;


		if (!options || !controller) {
			return;
		}

		super('RadicalMartShippingApiShipFieldAddresses');

		this.id = id;
		this.container = container;
		this.options = options;
		this.controller = controller;
		this.addresses = (options.addresses);
		this.selects = container.querySelectorAll('[radicalmart-shipping-apiship-field-addresses="address"],'
			+ '[data-radicalmart-shipping-apiship-field-addresses="address"]');
		this.fieldID = container.querySelector('[radicalmart-shipping-apiship-field-addresses="input_id"],'
			+ '[data-radicalmart-shipping-apiship-field-addresses="input_id"]');

		this.initialize();

	}

	initialize() {
		let addressesKeys = Object.keys(this.addresses);
		if (addressesKeys.length === 1) {
			this.select('new');
		} else {
			this.select(this.fieldID.value);
		}
		this.selects.forEach((button) => {
			button.addEventListener('click', () => {
				this.select(button.getAttribute('data-value'));
			});
		})
	}

	select(id) {
		this.selects.forEach((button) => {
			if (button.getAttribute('data-value') === id) {
				button.classList.add('active');
				button.classList.add('uk-active');
			} else {
				button.classList.remove('active');
				button.classList.remove('uk-active');
			}
		})

		let address = (this.addresses[id]) ? this.addresses[id] : false;
		if (!address) {
			address = this.addresses['new'];
		}
		Object.keys(address).forEach((key) => {
			let value = (key === 'id' && id === 'new') ? '' : address[key],
				selector = 'radicalmart-shipping-apiship-field-addresses="input_' + key + '"',
				field = this.container.querySelector('[' + selector + '],[data-' + selector + ']');
			if (field && field.value !== value) {
				field.value = value;
			}
		});

		if (id === 'new') {
			this.toggleForm();
		}
	}

	toggleForm(action = 'show') {
		let form = this.container.querySelector('[radicalmart-shipping-apiship-field-addresses="form"],'
			+ '[data-radicalmart-shipping-apiship-field-addresses="form"]');
		if (form) {
			form.style.display = (action === 'show') ? '' : 'none';
		}
	}
}

document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('[radicalmart-shipping-apiship-field-addresses="container"],'
		+ '[data-radicalmart-shipping-apiship-field-addresses="container"]')
		.forEach((container) => {
			container.FieldClass = new RadicalMartShippingApiShipFieldAddresses(container);
		});
});