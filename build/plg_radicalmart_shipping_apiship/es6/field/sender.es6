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
	document.querySelectorAll('[radicalmart-shipping-apiship-input="sender"], [data-radicalmart-shipping-apiship-input="sender"]')
		.forEach((container) => {
			let selector = container.getAttribute('data-selector'),
				jsOptions = Joomla.getOptions(selector),
				places = jsOptions.places,
				name = jsOptions.name,
				fieldKey = container.querySelector('[name="' + name + '[key]"]'),
				fieldTitle = container.querySelector('[name="' + name + '[title]"]'),
				fieldAddress = container.querySelector('[name="' + name + '[address]"]'),
				fieldLatitude = container.querySelector('[name="' + name + '[latitude]"]'),
				fieldLongitude = container.querySelector('[name="' + name + '[longitude]"]');

			fieldKey.addEventListener('change', setValues);
			setValues();

			function setValues() {
				let place = places[fieldKey.value];
				fieldTitle.value = place.title;
				fieldLatitude.value = place.latitude;
				fieldLongitude.value = place.longitude;
				if (fieldAddress.value !== place.address) {
					fieldAddress.value = place.address;
					fieldAddress.dispatchEvent(new Event('change', {'bubbles': true}));
				}
			}
		});
});