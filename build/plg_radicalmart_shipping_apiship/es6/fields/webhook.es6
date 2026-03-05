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


class RadicalMartShippingApiShipFieldWebhook extends JoomlaAjaxUtil {
	constructor(container) {
		super('RadicalMartShippingApiShipFieldWebhook');

		let coreOptions = Joomla.getOptions('plg_radicalmart_shipping_apiship.field.webhook'),
			controller = (coreOptions) ? coreOptions.controller : false;


		if (!controller) {
			return;
		}

		this.attribute = 'radicalmart-shipping-apiship-field-webhook';
		this.container = container;
		this.controller = controller;

		this.button = ElementsUtils.getElementByAttribute(this.attribute, 'button', this.container);
		this.input = ElementsUtils.getElementByAttribute(this.attribute, 'input', this.container);
		this.shipping = container.getAttribute('data-shipping');
		if (!this.button || !this.input || !this.shipping) {
			return;
		}
		this.shipping = parseInt(this.shipping);

		this.buttonDisplay();

		this.button.addEventListener('click', (event) => {
			event.preventDefault();

			this.createWebhook();
		})
	}

	createWebhook() {
		this.button.setAttribute('disabled', '');
		this.sendAjax('createWebhook', {
			shipping: this.shipping
		}).then((response) => {
			this.input.value = response.url;
			this.buttonDisplay();
			this.button.removeAttribute('disabled');
		}).catch((error) => {
			console.error(error);
			this.button.removeAttribute('disabled');
			let message = (typeof error === 'object') ? error.message : error;

			if (!message?.length) {
				message = 'Unknown error'
			}

			if (message) {
				Joomla.renderMessages({error: ['ApiShip: ' + message]}, '#system-message-container', true);
			}
		})
	}

	buttonDisplay() {
		let value = this.input.value,
			needle = this.container.getAttribute('data-needle');

		this.button.style.display = (value === needle) ? 'none' : '';
	}
}

document.addEventListener('DOMContentLoaded', () => {
	ElementsUtils.getElementsByAttribute('radicalmart-shipping-apiship-field-webhook', 'container')
		.forEach((container) => {
			container.FieldClass = new RadicalMartShippingApiShipFieldWebhook(container);
		});
});