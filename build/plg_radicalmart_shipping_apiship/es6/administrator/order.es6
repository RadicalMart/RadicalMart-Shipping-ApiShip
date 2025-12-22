/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     1.0.0
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

"use strict";

import JoomlaAjaxUtil from "../util/ajax.es6";
import {ElementsUtils} from "../util/elements.es6";

class RadicalMartShippingApiShipAdministratorOrder extends JoomlaAjaxUtil {
	constructor() {
		super('RadicalMartShippingApiShipAdministratorOrder');
	}

	initialization() {
		let attribute = 'radicalmart-shipping-apiship-order';
		this.container = ElementsUtils.getElementByAttribute(attribute, 'container');
		this.form = this.container.closest('form');
		this.options = Joomla.getOptions('plg_radicalmart_shipping_apiship.administrator.order');
		if (!this.form || !this.options || !this.options.controller) {
			return;
		}
		this.controller = this.options.controller;

		this.attribute = attribute;
		this.message = ElementsUtils.getElementByAttribute(attribute, 'message');
		this.error = ElementsUtils.getElementByAttribute(attribute, 'error');
		this.loading = ElementsUtils.getElementByAttribute(attribute, 'loading');
		this.tariffContainer = ElementsUtils.getElementByAttribute(attribute, 'tariff');
		this.priceContainer = ElementsUtils.getElementByAttribute(attribute, 'price');
		this.actionsContainer = ElementsUtils.getElementByAttribute(attribute, 'actions');

		this.pointIdField = document.querySelector('[name="jform[shipping][edit][point][id]"]');
		this.addressStringField = document.querySelector('[name="jform[shipping][edit][address][string]"]');
		if (this.pointIdField) {
			let pointsContainer = ElementsUtils.getClosestByAttribute(
				'radicalmart-shipping-apiship-field-points', 'container', this.pointIdField);
			if (pointsContainer) {
				pointsContainer.addEventListener('balloonopen', () => {
					if (this.tariffContainer) {
						this.tariffContainer.style.display = 'none';
					}
					if (this.priceContainer) {
						this.priceContainer.style.display = 'none';
					}
					if (this.actionsContainer) {
						this.actionsContainer.style.display = 'none';
					}
				})
				pointsContainer.addEventListener('balloonclose', () => {
					if (this.tariffContainer) {
						this.tariffContainer.style.display = '';
					}
					if (this.priceContainer) {
						this.priceContainer.style.display = '';
					}
					if (this.actionsContainer) {
						this.actionsContainer.style.display = '';
					}
				})
			}
		} else if (this.addressStringField) {
			let addressContainer = ElementsUtils.getClosestByAttribute(
				'radicalmart-shipping-apiship-field-address', 'container', this.addressStringField);
			if (addressContainer) {
				addressContainer.addEventListener('address_not_valid', () => {
					if (this.tariffContainer) {
						this.tariffContainer.style.display = 'none';
					}
					if (this.priceContainer) {
						this.priceContainer.style.display = 'none';
					}
					if (this.actionsContainer) {
						this.actionsContainer.style.display = 'none';
					}
				})
				addressContainer.addEventListener('address_valid', () => {
					if (this.tariffContainer) {
						this.tariffContainer.style.display = '';
					}
					if (this.priceContainer) {
						this.priceContainer.style.display = '';
					}
					if (this.actionsContainer) {
						this.actionsContainer.style.display = '';
					}
				})
			}
		}

		this.getPriceRequest = false;
		let modal_id = this.container.getAttribute('data-modal_id');
		if (!modal_id) {
			return;
		}
		let modal = document.getElementById(modal_id);
		if (!modal) {
			return;
		}

		modal.addEventListener('show.bs.modal', () => {
			this.loadTariffs().then();
		})
	}

	async loadTariffs() {
		let tariffField = document.querySelector('[name="jform[shipping][edit][tariff][id]"]'),
			canBeLoaded = false;
		if (!tariffField) {
			return;
		}

		let tariffFieldContainer = ElementsUtils.getClosestByAttribute(
			'radicalmart-shipping-apiship-field-tariffs', 'container', tariffField);
		if (!tariffFieldContainer) {
			return;
		}

		await new Promise((resolve) => {
			let attempts = 0;
			let interval = setInterval(() => {
				attempts++;
				if (tariffFieldContainer.FieldClass || attempts > 6) {
					clearInterval(interval);
					resolve();
				}
			}, 10);
		});

		if (!tariffFieldContainer.FieldClass) {
			return;
		}
		let tariffFieldClass = tariffFieldContainer.FieldClass;

		if (this.pointIdField) {
			if (this.pointIdField.value && parseInt(this.pointIdField.value) > 0) {
				canBeLoaded = true;
			}
		} else if (this.addressStringField) {
			if (this.addressStringField.value && this.addressStringField !== '') {
				canBeLoaded = true;
			}
		}

		if (!canBeLoaded) {
			if (this.tariffContainer) {
				this.tariffContainer.style.display = 'none';
			}
			if (this.priceContainer) {
				this.priceContainer.style.display = 'none';
			}
			if (this.actionsContainer) {
				this.actionsContainer.style.display = 'none';
			}
			if (parseInt(tariffField.value) > 0) {
				tariffField.value = 0;
			}
			return;
		}
		if (this.error) {
			this.error.style.display = 'none';
		}
		if (this.message) {
			this.message.style.display = 'none';
		}
		if (this.loading) {
			this.loading.style.display = '';
		}

		await tariffFieldClass.loadList();
		if (this.getPriceRequest === false && this.loading) {
			this.loading.style.display = 'none';
		}
		if (this.tariffContainer) {
			this.tariffContainer.style.display = '';
		}
	}

	getShippingPrice() {
		return new Promise((resolve, reject) => {
			this.getPriceRequest = true;
			if (this.loading) {
				this.loading.style.display = '';
			}
			if (this.priceContainer) {
				this.priceContainer.style.display = 'none';
			}
			if (this.actionsContainer) {
				this.actionsContainer.style.display = 'none';
			}
			let formData = new FormData(this.form);
			formData.set('jform[shipping][recalculate_price]', '1');
			document.querySelectorAll('[name*="jform[shipping][edit]"]').forEach((input) => {
				let name = input.getAttribute('name').replace('[edit]', '');
				formData.set(name, input.value);
			});

			this.sendAjax('getAdministratorShippingPrice', formData, true).then((response) => {
				this.getPriceRequest = false;
				if (this.loading) {
					this.loading.style.display = 'none';
				}

				let price_base = parseFloat(response.price.base),
					price_recipient = parseFloat(response.price.recipient),
					is_recipient_payment = (response.price.is_recipient_payment);

				document.querySelector('[name="jform[shipping][edit][price][base]"]').value = price_base;
				document.querySelector('[name="jform[shipping][edit][price][recipient]"]').value = price_recipient;
				if (is_recipient_payment) {
					document.querySelector('[name="jform[shipping][edit][price][update]"]').checked = true;
					document.querySelector('[name="jform[shipping][edit][price][update]"]')
						.setAttribute('checked', '');
				}

				let hasPrice = false;
				if (is_recipient_payment && price_recipient > 0) {
					hasPrice = true;
				} else if (!is_recipient_payment && price_base > 0) {
					hasPrice = true;
				}

				if (!hasPrice) {
					if (this.priceContainer) {
						this.priceContainer.style.display = 'none';
					}
					if (this.actionsContainer) {
						this.actionsContainer.style.display = 'none';
					}
					if (this.message && response.message && response.message !== '') {
						this.message.style.display = '';
						this.message.innerHTML = response.message;
					}
					if (this.error && response.error && response.error !== '') {
						this.error.style.display = '';
						this.error.innerHTML = response.error;
					}
				} else {
					if (this.priceContainer) {
						let priceBaseContainer = ElementsUtils.getElementByAttribute(this.attribute,
							'price_base', this.priceContainer);
						if (priceBaseContainer) {
							priceBaseContainer.style.display = (is_recipient_payment) ? 'none' : '';
						}

						let priceRecipientContainer = ElementsUtils.getElementByAttribute(this.attribute,
							'price_recipient', this.priceContainer);
						if (priceRecipientContainer) {
							priceRecipientContainer.style.display = (!is_recipient_payment) ? 'none' : '';
						}

						this.priceContainer.style.display = '';
					}
					if (this.actionsContainer) {
						this.actionsContainer.style.display = '';
					}
					if (this.error) {
						this.error.style.display = 'none';
					}
					if (this.message) {
						this.message.style.display = 'none';
					}
				}
				resolve(response);
			}).catch((e) => {
				this.getPriceRequest = false;
				if (this.loading) {
					this.loading.style.display = 'none';
				}

				let error = (e.message) ? e.message : 'AJAX Error';
				if (this.error) {
					this.error.style.display = '';
					this.error.innerHTML = error;
				}
				if (this.priceContainer) {
					this.priceContainer.style.display = 'none';
				}
				if (this.actionsContainer) {
					this.actionsContainer.style.display = 'none';
				}
				console.error(error);
				reject(error);
			})
		});
	}

	setValues(save = false) {
		let updatePrice = document.querySelector('[name="jform[shipping][edit][price][update]"]').checked;
		document.querySelectorAll('[name*="jform[shipping][edit]"]').forEach((edit) => {
			let editName = edit.getAttribute('name');

			if (editName === 'jform[shipping][edit][price][base]' && !updatePrice) {
				return;
			}

			let sourceName = editName.replace('[edit]', ''),
				source = document.querySelector('[name="' + sourceName + '"]');

			if (source) {
				source.value = edit.value;
			}
		});

		if (save) {
			Joomla.sendForm('order.apply');
		} else {
			Joomla.sendForm('order.reload');
		}
	}
}

window.RadicalMartShippingApiShipAdministratorOrderClass = null;
window.RadicalMartShippingApiShipAdministratorOrder = () => {
	if (window.RadicalMartShippingApiShipAdministratorOrderClass === null) {
		window.RadicalMartShippingApiShipAdministratorOrderClass = new RadicalMartShippingApiShipAdministratorOrder();
	}
	return window.RadicalMartShippingApiShipAdministratorOrderClass;
}

document.addEventListener('DOMContentLoaded', () => {
	window.RadicalMartShippingApiShipAdministratorOrder().initialization();
});