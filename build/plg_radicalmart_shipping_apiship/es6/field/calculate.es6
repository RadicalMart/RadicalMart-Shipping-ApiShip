/*
 * @package     RadicalMart Package
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2022 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

"use strict";

import axios from 'axios';

document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('[input-apiship-calculate="container"]').forEach(function (container) {
		let jsOptions = Joomla.getOptions(container.getAttribute('id')),
			error = container.querySelector('[input-apiship-calculate="error"]'),
			form = container.closest('form'),
			checkoutDisplay = document.querySelector('[radicalmart-checkout-display="shipping.order.price.final_string"]'),
			fromField = false,
			toField = false;
		if (form) {
			fromField = form.querySelector('[input-apiship-from-field="addressString"]');
			if (fromField) fromField.addEventListener('input', calculate);
			toField = form.querySelector('[input-apiship-to-field="addressString"]');
			if (toField) toField.addEventListener('input', calculate);
		}
		if (checkoutDisplay) checkoutDisplay.textContent = 'Calculate';
		error.style.display = 'none';

		function calculate() {
			error.style.display = 'none';
			if (form) {
				let formData = new FormData(form);
				axios({
					method: 'post',
					url: jsOptions.controller,
					data: formData,
					headers: {'Content-Type': 'multipart/form-data'},
				}).then((response) => {
					if (response.data.success) {
						let data = response.data.data[0];
						container.querySelector('[input-apiship-calculate="cost"]').value = data.cost;
						container.querySelector('[input-apiship-calculate="daysMin"]').value = data.daysMin;
						container.querySelector('[input-apiship-calculate="daysMax"]').value = data.daysMax;
						container.querySelector('[input-apiship-calculate="hash"]').value = data.hash;
						window.RadicalMartCheckout().updateDisplayData();
					} else {
						if (checkoutDisplay) checkoutDisplay.textContent = response.data.message;
						error.style.display = '';
						error.textContent = response.data.message;
						console.error(response.data.message);
					}
				}).catch((error) => {
					if (error.message !== 'Request aborted') {
						if (checkoutDisplay) checkoutDisplay.textContent = error.message;
						error.textContent = error.message;
						error.style.display = '';
						console.error(error.message);
					}
				});
			}
		}
	});
});
