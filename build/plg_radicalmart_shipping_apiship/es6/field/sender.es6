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
				layout = jsOptions.layout,
				fieldTitle = container.querySelector('[name="' + name + '[title]"]'),
				fieldAddress = container.querySelector('[name="' + name + '[address]"]'),
				fieldLatitude = container.querySelector('[name="' + name + '[latitude]"]'),
				fieldLongitude = container.querySelector('[name="' + name + '[longitude]"]');

			console.log('wtf');
			if (layout === 'administrator') {
				container.querySelector('[name="' + name + '[key]"]').addEventListener('setValue', setValues);
			} else {
				container.querySelectorAll('[name="' + name + '[key]"]').forEach((input) => {
					input.addEventListener('change', (event) => {
						setValues();
						container.querySelectorAll('[name="' + name + '[key]"]').forEach((field) => {
							let toggleElement = field.closest('[data-class-toggle]');
							if (toggleElement) {
								let toggleClass = toggleElement.getAttribute('data-class-toggle');
								try {
									let toggleClasses = toggleClass.split(':')
									if (field.checked) {
										toggleElement.classList.add(toggleClasses[0]);
										toggleElement.classList.remove(toggleClasses[1]);
									} else {
										toggleElement.classList.add(toggleClasses[1]);
										toggleElement.classList.remove(toggleClasses[0]);
									}
								} catch (e) {

								}

							}
						});
					});
				})
			}
			setValues();

			function setValues() {
				container.querySelector('[name="' + name + '[key]"]')
				let value = (layout === 'administrator') ? container.querySelector('[name="' + name + '[key]"]').value
					: container.querySelector('[name="' + name + '[key]"]:checked').value

				console.log(value);

				let place = places[value];
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