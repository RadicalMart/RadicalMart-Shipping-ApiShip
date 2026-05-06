/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     1.0.2
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2026 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

"use strict";

import JoomlaAjaxUtil from "../util/ajax.es6";
import {ElementsUtils} from "../util/elements.es6";

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
		this.attribute = 'radicalmart-shipping-apiship-field-address';
		this.container = container;

		this.options = options;
		this.controller = controller;
		this.shipping = (options.shipping) ? options.shipping : 0;

		this.error = ElementsUtils.getElementByAttribute(this.attribute, 'error', container);
		this.loading = ElementsUtils.getElementByAttribute(this.attribute, 'loading', container);
		this.fieldSting = ElementsUtils.getElementByAttribute(this.attribute, 'input_string', container);
		this.fields = ElementsUtils.getElementsByAttribute(this.attribute, 'input_', container, '*=');
		this.validateButtons = ElementsUtils.getElementsByAttribute(this.attribute, 'validate_button', container);

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


		return new Promise(() => {
			let formData = new FormData();
			formData.set('shipping', this.shipping);

			this.fields.forEach((field) => {
				formData.set(field.getAttribute('data-validate-key'), field.value);
				field.classList.remove('invalid');
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
	ElementsUtils.getElementsByAttribute('radicalmart-shipping-apiship-field-address', 'container')
		.forEach((container) => {
			container.FieldClass = new RadicalMartShippingApiShipFieldAddress(container);
		});
});