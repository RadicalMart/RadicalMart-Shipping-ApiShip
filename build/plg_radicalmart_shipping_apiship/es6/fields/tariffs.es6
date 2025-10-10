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

import JoomlaAjaxUtil from "../util/ajax.es6";
import {ElementsUtils} from "../util/elements.es6";

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
		this.attribute = 'radicalmart-shipping-apiship-field-tariffs';
		this.name = container.getAttribute('data-name');
		this.controller = controller;
		this.context = fieldOptions.context;

		this.form = form;
		this.error = ElementsUtils.getElementByAttribute(this.attribute, 'error', container);
		this.loading = ElementsUtils.getElementByAttribute(this.attribute, 'loading', container);
		this.list = ElementsUtils.getElementByAttribute(this.attribute, 'list', container);
		this.fieldId = ElementsUtils.getElementByAttribute(this.attribute, 'input_id', container);
		this.fieldName = ElementsUtils.getElementByAttribute(this.attribute, 'input_name', container);
		this.fieldHash = ElementsUtils.getElementByAttribute(this.attribute, 'input_hash', container);
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

				ElementsUtils.getElementsByAttribute(this.attribute, 'input_tariff', this.list)
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
		ElementsUtils.getElementsByAttribute(this.attribute, 'input_tariff', this.list)
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
	ElementsUtils.getElementsByAttribute('radicalmart-shipping-apiship-field-tariffs', 'container')
		.forEach((container) => {
			container.FieldClass = new RadicalMartShippingApiShipFieldTariffs(container);
		});
});