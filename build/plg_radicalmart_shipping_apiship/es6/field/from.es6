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

document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('[input-apiship-from]').forEach((container) => {
		let selector = container.getAttribute('input-apiship-from'),
			params = Joomla.getOptions(selector),
			select = container.querySelector('select');

		select.addEventListener('change', setValues)
		setValues();


		function setValues() {
			let values = params.places[select.value];
			container.querySelector('[input-apiship-from-field="addressString"]').value = values.address;
			container.querySelector('[input-apiship-from-field="lat"]').value = values.latitude;
			container.querySelector('[input-apiship-from-field="lng"]').value = values.longitude;
			container.querySelector('[input-apiship-from-field="title"]').value = values.title;
			container.querySelector('[input-apiship-from-field="key"]').value = values.key;

			container.querySelector('[input-apiship-from-field="addressString"]')
				.dispatchEvent(new Event('input', {'bubbles': true}));
		}
	});
});
