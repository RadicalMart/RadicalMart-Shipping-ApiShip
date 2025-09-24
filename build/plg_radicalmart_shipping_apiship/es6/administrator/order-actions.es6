/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

import JoomlaAjaxUtil from "../util/ajax.es6";
import {ElementsUtils} from "../util/elements.es6";

class RadicalMartShippingApiShipAdministratorOrderActionButton extends JoomlaAjaxUtil {
	constructor(button) {
		super('RadicalMartShippingApiShipAdministratorOrderActions');

		this.options = Joomla.getOptions('plg_radicalmart_shipping_apiship.administrator.order.actions');
		if (!this.options?.controller) {
			return;
		}
		this.controller = this.options.controller;

		this.element = button;
		this.attribute = 'radicalmart-shipping-apiship-order-actions';
		this.action = ElementsUtils.getAttributeValue(button, this.attribute);
		this.order_id = parseInt(button.getAttribute('data-order_id'));
		this.context = button.getAttribute('data-context');
		if (!this.order_id) {
			return;
		}

		if (!button.getAttribute('data-order_action_int')) {
			button.addEventListener('click', (event) => {
				event.preventDefault();
				this.execute();
			});

			button.setAttribute('data-order_action_int', 1);
		}
	}

	execute() {
		this.sendAjax('executeOrderAction', {
			order_id: this.order_id,
			action: this.action,
			context: this.context,
		}).then((response) => {
			if (response.messages) {
				let messages = [];
				response.messages.forEach((message) => {
					messages.push('ApiShip: ' + message)
				})
				Joomla.renderMessages({success: messages}, '#system-message-container');

			}
			console.log(this.context)
			if (this.context === 'com_radicalmart.order') {

				if (response.set_data) {
					let prefix = 'jform[shipping]';
					Object.keys(response.set_data).forEach((field) => {
						let name = prefix + '[' + field + ']',
							value = response.set_data[field];

						if (typeof value === 'object') {
							Object.keys(value).forEach((sub_field) => {
								let sub_name = prefix + '[' + field + '][' + sub_field + ']',
									sub_value = response.set_data[field][sub_field];
								this.setFieldValue(sub_name, sub_value);
							});
						} else {
							this.setFieldValue(name, value);
						}
					})
				}
			}

			console.log(response);
		}).catch((error) => {
			this.error(error);
		})
	}

	setFieldValue(name, value) {
		let element = document.querySelector('[name="' + name + '"]');
		console.log(element);
		if (element) {
			element.value = value;
		}
	}

	error(error) {
		console.error(error);
		let message = (typeof error === 'object') ? error.message : error;

		if (!message?.length) {
			message = 'Unknown error'
		}

		if (message) {
			Joomla.renderMessages({error: ['ApiShip: ' + message]}, '#system-message-container', true);
		}
	}
}

window.ApishipOrderActionButton = (button) => {
	if (!button.ApishipOrderActionButtonClass) {
		button.ApishipOrderActionButtonClass = new RadicalMartShippingApiShipAdministratorOrderActionButton(button);
	}
}
document.addEventListener('DOMContentLoaded', () => {
	ElementsUtils.getElementsByAttribute('radicalmart-shipping-apiship-order-actions')
		.forEach((button) => {
			window.ApishipOrderActionButton(button);
		})
});