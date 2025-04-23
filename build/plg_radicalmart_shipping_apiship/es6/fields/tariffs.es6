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

class RadicalMartShippingApiShipFieldTariffs extends JoomlaAjaxUtil {
	constructor(container) {
		super('RadicalMartShippingApiShipFieldTariffs');

		let id = container.getAttribute('id'),
			options = Joomla.getOptions('plg_radicalmart_shipping_apiship.field.tariffs'),
			controller = (options) ? options.controller : false, form = container.closest('form'),
			fieldOptions = Joomla.getOptions(id);

		if (!options || !controller || !form || !fieldOptions) {
			return;
		}

		this.id = id;
		this.name = container.getAttribute('data-name');
		this.controller = controller;
		this.form = form;
		this.context = fieldOptions.context;
		this.error = container.querySelector('[radicalmart-shipping-apiship-field-tariffs="error"],'
			+ '[data-radicalmart-shipping-apiship-field-tariffs="error"]');
		this.loading = container.querySelector('[radicalmart-shipping-apiship-field-tariffs="loading"],'
			+ '[data-radicalmart-shipping-apiship-field-tariffs="loading"]');
		this.list = container.querySelector('[radicalmart-shipping-apiship-field-tariffs="list"],'
			+ '[data-radicalmart-shipping-apiship-field-tariffs="list"]');
		this.fieldId = container.querySelector('[radicalmart-shipping-apiship-field-tariffs="input_id"],'
			+ '[data-radicalmart-shipping-apiship-field-tariffs="input_id"]');
		this.fieldName = container.querySelector('[radicalmart-shipping-apiship-field-tariffs="input_name"],'
			+ '[data-radicalmart-shipping-apiship-field-tariffs="input_name"]');
		this.fieldHash = container.querySelector('[radicalmart-shipping-apiship-field-tariffs="input_hash"],'
			+ '[data-radicalmart-shipping-apiship-field-tariffs="input_hash"]');
	}


	loadList() {
		if (this.loading) {
			this.loading.style.display = '';
		}
		if (this.error) {
			this.error.style.display = 'none';
		}
		return new Promise((resolve, reject) => {
			let formData = new FormData(this.form);
			formData.set('field_id', this.id);
			formData.set('field_context', this.context);
			this.sendAjax('loadTariffs', formData, true).then((response) => {
				if (this.loading) {
					this.loading.style.display = 'none';
				}
				this.list.innerHTML = response.html;
				this.fieldHash.value = response.hash;

				this.list.querySelectorAll('[radicalmart-shipping-apiship-field-tariffs="input_tariff"],'
					+ '[data-radicalmart-shipping-apiship-field-tariffs="input_tariff"]')
					.forEach((input) => {
						input.addEventListener('change', () => {
							this.setFieldValue();
						})
					});
				if (response.value) {
					this.setFieldValue();
				}

				resolve(response);
			}).catch((e) => {
				let error = (e.message) ? e.message : 'RadicalMartShippingApiShipFieldTariffs - AJAX Error';
				if (this.loading) {
					this.loading.style.display = 'none';
				}
				if (this.error) {
					this.error.style.display = '';
					this.error.innerHTML = error;
				}
				this.list.innerHTML = '';
				this.setFieldValue();
				console.error(error);
				reject(error);
			})
		});
	}

	setFieldValue() {
		let find = false;
		this.list.querySelectorAll('[radicalmart-shipping-apiship-field-tariffs="input_tariff"],'
			+ '[data-radicalmart-shipping-apiship-field-tariffs="input_tariff"]')
			.forEach((input) => {
				if (input.checked || input.getAttribute('checked')) {
					find = input;
				}
			});

		this.fieldName.value = (find) ? find.getAttribute('data-tariff_name') : '';
		this.fieldId.value = (find) ? find.value : '';
		this.fieldId.dispatchEvent(new Event('change'));
	}
}

document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('[radicalmart-shipping-apiship-field-tariffs="container"],' + '[data-radicalmart-shipping-apiship-field-tariffs="container"]')
		.forEach((container) => {
			container.FieldClass = new RadicalMartShippingApiShipFieldTariffs(container);
		});
});