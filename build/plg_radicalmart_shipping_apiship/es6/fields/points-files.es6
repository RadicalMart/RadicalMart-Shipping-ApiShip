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

class RadicalMartShippingApiShipFieldPointsFiles extends JoomlaAjaxUtil {
	constructor(container) {
		super('RadicalMartShippingApiShipFieldPointsFiles');
		let id = container.getAttribute('id'),
			shipping = parseInt(container.getAttribute('data-shipping')),
			options = Joomla.getOptions('plg_radicalmart_shipping_apiship.field.points.files');
		if (!shipping || !options || !options.controller) {
			return;
		}

		this.id = id;
		this.shipping = shipping;
		this.attribute = 'radicalmart-shipping-apiship-field-points-files';
		this.container = container;
		this.options = options;
		this.controller = options.controller;
		this.offset = 0;
		this.total = 0;

		this.loading = ElementsUtils.getElementByAttribute(this.attribute, 'loading', this.container);
		this.result = ElementsUtils.getElementByAttribute(this.attribute, 'result', this.container);
		this.error = ElementsUtils.getElementByAttribute(this.attribute, 'error', this.container);
		this.button = ElementsUtils.getElementByAttribute(this.attribute, 'reset', this.container);
		if (this.button) {
			this.button.addEventListener('click', (event) => {
				event.preventDefault();
				this.resetCache().then();
			});
		}
	}

	async resetCache() {
		if (this.loading) {
			this.loading.style.display = '';
		}
		if (this.error) {
			this.error.style.display = 'none';
		}
		if (this.result) {
			this.result.innerHTML = '';
		}
		try {
			await this.removeOldData();
			await this.createCache();
		} catch (e) {
		}
		if (this.loading) {
			this.loading.style.display = 'none';
		}
	}

	removeOldData() {
		return new Promise((resolve, reject) => {
			this.sendAjax('PointsFilesRemoveData', {
				'shipping': this.shipping,
			}, true).then((response) => {
				resolve(response);
			}).catch((e) => {
				this.displayError(e.message);
				reject();
			})
		});
	}

	createCache() {
		return new Promise((resolve, reject) => {
			let ajaxData = {
				shipping: this.shipping,
				offset: 0,
				limit: 3500,
				action: 'start'
			};

			this.sendAjax('PointsFilesCreateCache', ajaxData, true).then((totals) => {
				if (!totals.offsets || totals.offsets.length === 0) {
					resolve();
					return;
				}

				let promises = totals.offsets.map((offset) => {
					let adviseData = {...ajaxData, action: 'advise', offset};
					return this.sendAjax('PointsFilesCreateCache', adviseData, true);
				});

				Promise.allSettled(promises).then((results) => {
					let successful = results.filter(r => r.status === 'fulfilled'),
						failed = results.filter(r => r.status === 'rejected');
					if (failed.length > 0) {
						let errors = [];
						failed.forEach((r, i) => {
							errors.push(r.reason);
						});
						this.displayError(errors.join('<br>'))
					}
					if (successful.length > 0) {
						let totalData = {...ajaxData, action: 'final'};
						this.sendAjax('PointsFilesCreateCache', totalData, true).then((message) => {
							if (this.result) {
								this.result.innerHTML = message;
							}
							resolve();
						}).catch((err) => {
							this.displayError(err.message);
							reject(err);
						});
					} else {
						reject();
					}
				});
			}).catch((e) => {
				this.displayError(e.message);
				reject(e);
			});
		});
	}

	displayError(message = null) {
		if (this.loading) {
			this.loading.style.display = 'none';
		}
		if (this.error) {
			if (!message) {
				message = 'Unknown Ajax Error'
			}
			this.error.innerHTML = message;
			this.error.style.display = '';
		}
	}

}


document.addEventListener('DOMContentLoaded', () => {
	ElementsUtils.getElementsByAttribute('radicalmart-shipping-apiship-field-points-files', 'container')
		.forEach((container) => {
			container.FieldClass = new RadicalMartShippingApiShipFieldPointsFiles(container);
		});
});