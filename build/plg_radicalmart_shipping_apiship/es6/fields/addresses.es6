/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     1.0.1
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

"use strict";

import JoomlaAjaxUtil from "../util/ajax.es6";
import {ElementsUtils} from "../util/elements.es6";

class RadicalMartShippingApiShipFieldAddresses extends JoomlaAjaxUtil {
	constructor(container) {
		super('RadicalMartShippingApiShipFieldAddresses');

		let id = container.getAttribute('id'), options = Joomla.getOptions(id),
			coreOptions = Joomla.getOptions('plg_radicalmart_shipping_apiship.field.addresses'),
			controller = (coreOptions) ? coreOptions.controller : false;

		if (!options || !controller) {
			return;
		}

		this.id = id;
		this.attribute = 'radicalmart-shipping-apiship-field-addresses';
		this.container = container;
		this.options = options;
		this.controller = controller;

		this.shipping = (options.shipping) ? options.shipping : 0;
		this.addresses = (options.addresses) ? options.addresses : {};

		this.addressesKeys = Object.keys(this.addresses);
		this.selected = (options.selected) ? options.selected : false;

		this.error = ElementsUtils.getElementByAttribute(this.attribute, 'error', container);
		this.loading = ElementsUtils.getElementByAttribute(this.attribute, 'loading', container);
		this.selects = ElementsUtils.getElementsByAttribute(this.attribute, 'address', container);
		this.fieldSting = ElementsUtils.getElementByAttribute(this.attribute, 'input_string', container);
		this.fields = ElementsUtils.getElementsByAttribute(this.attribute, 'input_', container, '*=');
		this.validateButtons = ElementsUtils.getElementsByAttribute(this.attribute, 'validate_button', container);

		this.initialize();
	}

	initialize() {
		this.select((this.selected) ? this.selected : this.addressesKeys[0]);
		this.selects.forEach((button) => {
			button.addEventListener('click', (event) => {
				event.preventDefault();
				this.select(button.getAttribute('data-value'));
			});
		})
		this.validateButtons.forEach((button) => {
			button.addEventListener('click', (event) => {
				event.preventDefault();
				this.validate().then();
			})
		});
		if (this.fieldSting.value && this.fieldSting.value !== '') {
			this.validate().then()
		}
		this.fields.forEach((field) => {
			field.addEventListener('focus', () => {
				this.userChange(field);
			})
		});
	}

	userChange(field) {
		if (field) {
			field.classList.remove('invalid');
			field.classList.remove('uk-form-danger');
		}
		this.fieldSting.value = '';
		this.toggleValidateButtons();
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

		address['string'] = '';
		Object.keys(address).forEach((key) => {
			let value = address[key],
				field = ElementsUtils.getElementByAttribute(this.attribute, 'input_' + key, this.container);
			if (field && field.value !== value) {
				field.value = value;
			}
		});
		this.fieldSting.value = '';
		this.fieldSting.dispatchEvent(new Event('change'));

		this.fields.forEach((field) => {
			field.classList.remove('invalid');
			field.classList.remove('uk-form-danger');
		});

		if (this.error) {
			this.error.style.display = 'none';
		}

		this.userChange();
	}

	validate() {

		return new Promise(() => {
			let formData = new FormData();
			formData.set('shipping', this.shipping);

			this.fields.forEach((field) => {
				formData.set(field.getAttribute('data-validate-key'), field.value);
				field.classList.remove('invalid');
				field.classList.remove('uk-form-danger');
			});
			if (this.loading) {
				this.loading.style.display = '';
			}
			if (this.error) {
				this.error.style.display = 'none';
			}
			this.sendAjax('validateAddress', formData, true).then((response) => {
				if (this.loading) {
					this.loading.style.display = 'none';
				}
				if (!response.valid) {
					if (this.error) {
						this.error.innerHTML = response.message;
						this.error.style.display = '';
					}
					console.error(response.message);

					response.empty_fields_keys.forEach((field_name) => {
						let field = ElementsUtils.getElementByAttribute(
							this.attribute, 'input_' + field_name, this.container);
						if (field) {
							field.classList.add('invalid');
							field.classList.add('uk-form-danger');
						}
					});
				}

				Object.keys(response.values).forEach((field_name) => {
					let field_value = response.values[field_name],
						field = ElementsUtils.getElementByAttribute(
							this.attribute, 'input_' + field_name, this.container);
					if (field && field.value !== field_value) {
						field.value = field_value;
						field.dispatchEvent(new Event('change'));
					}
				})
				this.toggleValidateButtons();
			}).catch((e) => {
				if (this.loading) {
					this.loading.style.display = 'none';
				}
				if (e.message && this.error) {
					this.error.innerHTML = e.message;
					this.error.style.display = '';
				}
				console.error((e.message) ? e.message : 'AJAX ERROR')

				this.fieldSting.value = '';
				this.fieldSting.dispatchEvent(new Event('change'));
				this.toggleValidateButtons();
			})
		});
	}

	toggleValidateButtons() {
		let show = !(this.fieldSting.value && this.fieldSting.value !== '');
		this.validateButtons.forEach((button) => {
			button.style.display = (show) ? '' : 'none';
		})
		if (show) {
			this.container.dispatchEvent(new Event('address_not_valid'));
		} else {
			this.container.dispatchEvent(new Event('address_valid'));
		}
	}
}

document.addEventListener('DOMContentLoaded', () => {
	ElementsUtils.getElementsByAttribute('radicalmart-shipping-apiship-field-addresses', 'container')
		.forEach((container) => {
			container.FieldClass = new RadicalMartShippingApiShipFieldAddresses(container);
		});
});