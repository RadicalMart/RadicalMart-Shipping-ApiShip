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
	ymaps.ready(() => {
		document.querySelectorAll(
			'[radicalmart-shipping-apiship-input="points"], [data-radicalmart-shipping-apiship-input="points"]')
			.forEach((container) => {
				let selector = container.getAttribute('data-selector'),
					jsOptions = Joomla.getOptions(selector),
					id = jsOptions.id,
					valueCoordinates = jsOptions.valueCoordinates;

				// Initialize map
				let map = new ymaps.Map(id + '_map', {
						center: [0, 0],
						zoom: 0,
						controls: ['zoomControl']
					},
					{
						yandexMapDisablePoiInteractivity: true,
					});

				// Zoom
				map.behaviors.disable('scrollZoom');
				document.querySelector('body').onkeydown = (event) => {
					if (event.keyCode === 17) map.behaviors.enable('scrollZoom');
				};
				document.querySelector('body').onkeyup = (event) => {
					if (event.keyCode === 17) map.behaviors.disable('scrollZoom');
				};

				let formData = new FormData();
				formData.set('action', 'getPoints');
				formData.set('shipping', jsOptions.shipping);
				formData.set('operation', jsOptions.operation);
				formData.set('marker', JSON.stringify(jsOptions.marker));

				let mapFrame = container.querySelector('#' + id + '_map > ymaps');
				mapFrame.style.display = 'none';

				axios({
					method: 'post',
					url: jsOptions.controller,
					data: formData,
					headers: {'Content-Type': 'multipart/form-data'},
				}).then((response) => {
					if (response.data.success) {
						let data = response.data.data[0];
					} else displayError(response.data.message);
				}).catch((error) => {
					if (error.message !== 'Request aborted') {
						displayError(error.message);
					}
				});

				// if (!valueCoordinates) {
				// 	ymaps.geolocation.get({mapStateAutoApply: true}).then((result) => {
				// 		map.setCenter(result.geoObjects.get(0).geometry.getCoordinates(), 10);
				// 	});
				// } else {
				// 	map.setCenter(valueCoordinates, 15);
				// }

				function displayError(message) {
					console.error(message);
				}

				//mapFrame.style.display = 'none';
			});
	});
});
