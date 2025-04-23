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

class RadicalMartShippingApiShipFieldAddress extends JoomlaAjaxUtil {
	constructor(container) {
		super('RadicalMartShippingApiShipFieldAddress');

		let id = container.getAttribute('id'),
			options = Joomla.getOptions(id),
			coreOptions = Joomla.getOptions('plg_radicalmart_shipping_apiship.field.address'),
			controller = (coreOptions) ? coreOptions.controller : false;

		if (!options || !controller) {
			return;
		}


		this.id = id;
		this.container = container;
		this.options = options;
		this.controller = controller;
		this.shipping = (options.shipping) ? options.shipping : 0;
		this.fieldSting = this.getContainerElement('input_string');
		this.fields = this.container.querySelectorAll('[radicalmart-shipping-apiship-field-address*="input_"],' +
			'[data-radicalmart-shipping-apiship-field-address*="input_"]');
		this.validateButtons = this.getContainerElements('validate_button');
		this.initialize();
	}

	initialize() {
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
		}
		this.fieldSting.value = '';
		this.toggleValidateButtons();
	}

	validate() {
		let error = this.getContainerElement('error'),
			loading = this.getContainerElement('loading');

		return new Promise(() => {
			let formData = new FormData();
			formData.set('shipping', this.shipping);

			this.fields.forEach((field) => {
				formData.set(field.getAttribute('data-validate-key'), field.value);
				field.classList.remove('invalid');
			});
			if (loading) {
				loading.style.display = '';
			}
			if (error) {
				error.style.display = 'none';
			}
			this.sendAjax('validateAddress', formData, true).then((response) => {
				if (loading) {
					loading.style.display = 'none';
				}
				if (!response.valid) {
					if (error) {
						error.innerHTML = response.message;
						error.style.display = '';
					}
					console.error(response.message);

					response.empty_fields_keys.forEach((field_name) => {
						let field = this.getContainerElement('input_' + field_name);
						if (field) {
							field.classList.add('invalid');
							field.classList.add('uk-form-danger');
						}
					});
				}

				Object.keys(response.values).forEach((field_name) => {
					let field_value = response.values[field_name],
						field = this.getContainerElement('input_' + field_name);
					if (field && field.value !== field_value) {
						field.value = field_value;
						field.dispatchEvent(new Event('change'));
					}
				})
				this.toggleValidateButtons();
			}).catch((e) => {
				if (loading) {
					loading.style.display = 'none';
				}
				if (e.message && error) {
					error.innerHTML = e.message;
					error.style.display = '';
				}
				console.error((e.message) ? e.message : 'AJAX ERROR')

				this.fieldSting.value = '';
				this.fieldSting.dispatchEvent(new Event('change'));
				this.toggleValidateButtons();
			})
		});
	}

	getContainerElement(key) {
		return this.container.querySelector('[radicalmart-shipping-apiship-field-address="' + key + '"],'
			+ '[data-radicalmart-shipping-apiship-field-address="' + key + '"]');
	}

	getContainerElements(key) {
		return this.container.querySelectorAll('[radicalmart-shipping-apiship-field-address="' + key + '"],'
			+ '[data-radicalmart-shipping-apiship-field-address="' + key + '"]');
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
	document.querySelectorAll('[radicalmart-shipping-apiship-field-address="container"],'
		+ '[data-radicalmart-shipping-apiship-field-address="container"]')
		.forEach((container) => {
			container.FieldClass = new RadicalMartShippingApiShipFieldAddress(container);
		});
});