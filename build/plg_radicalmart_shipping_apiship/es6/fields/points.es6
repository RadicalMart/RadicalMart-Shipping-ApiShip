/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2023 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

"use strict";

import RadicalMartShippingApiShipAjax from "../util/ajax.es6";

document.addEventListener('DOMContentLoaded', () => {
	ymaps.ready(() => {
		document.querySelectorAll(
			'[radicalmart-shipping-apiship-field="points"], [data-radicalmart-shipping-apiship-field="points"]')
			.forEach((container) => {
				let selector = container.getAttribute('data-selector'),
					jsOptions = Joomla.getOptions(selector),
					context = jsOptions.context,
					id = jsOptions.id,
					name = jsOptions.name,
					form = container.closest('form'),
					selected = jsOptions.value;

				let point = false;
				if (selected && selected.latitude && selected.longitude) {
					point = {
						id: selected.id,
						coordinates: [selected.latitude, selected.longitude],
					}
				}

				// Initialize map
				let map = new ymaps.Map(id + '_map', {
						center: (point) ? point.coordinates : [0, 0],
						zoom: (point) ? 15 : 0,
						controls: ['zoomControl']
					},
					{
						yandexMapDisablePoiInteractivity: true,
					});

				// Zoom
				map.behaviors.disable('scrollZoom');
				document.querySelector('body').onkeydown = (event) => {
					if (event.keyCode === 17) {
						map.behaviors.enable('scrollZoom');
					}
				};
				document.querySelector('body').onkeyup = (event) => {
					if (event.keyCode === 17) {
						map.behaviors.disable('scrollZoom');
					}
				};

				// Location
				let geolocationControl = new ymaps.control.GeolocationControl({
					options: {noPlacemark: true}
				});
				map.controls.add(geolocationControl);
				geolocationControl.events.add('locationchange', (event) => {
					map.setCenter(event.get('position'), 10);
				});
				if (!point) {
					ymaps.geolocation.get({mapStateAutoApply: true}).then((result) => {
						let coordinates = result.geoObjects.get(0).geometry.getCoordinates();
						map.setCenter(coordinates, 10);
					});
				}

				// Object manager
				let cluster = (jsOptions.cluster) ? jsOptions.cluster : {
						clusterize: true,
						gridSize: 64,
						hasBalloon: false,
					},
					objectManager = new ymaps.ObjectManager(cluster);
				map.geoObjects.add(objectManager);
				console.log(cluster);

				// Add click
				objectManager.objects.events.add('click', function (event) {
					let object = objectManager.objects.getById(event.get('objectId'));
					['id', 'title', 'latitude', 'longitude', 'address'].forEach((key) => {
						let field = container.querySelector('[name="' + name + '[' + key + ']"]'),
							value = object[key];
						if (field) {
							if (key === 'address' && field.value !== value) {
								field.value = value;
								field.dispatchEvent(new Event('change', {'bubbles': true}));
							} else {
								field.value = value;
							}
						}
					});
				});

				// Get points
				let mapFrame = container.querySelector('#' + id + '_map > ymaps');
				mapFrame.style.display = 'none';

				let ajax = new RadicalMartShippingApiShipAjax();
				ajax.setVariable('controller', jsOptions.controller);
				ajax.sendAjax('getPoints', {
					'context': context,
					'shipping': jsOptions.shipping,
					'operation': jsOptions.operation,
					'marker': JSON.stringify(jsOptions.marker)
				}).then((response) => {
					objectManager.add(response);
					mapFrame.style.display = '';
				}).catch((error) => {
					if (context === 'com_radicalmart.checkout') {
						window.RadicalMartCheckout().triggerEvent('onRadicalMartCheckoutError', error.message);
						form.querySelector('[name*="[shipping][price][base]"]').value = -1;
						form.querySelector('[name*="[shipping][price][final]"]').value = -1;
						form.querySelector('[name*="[shipping][price][hash]"]').value = '';
						window.RadicalMartCheckout().updateDisplayData();
					}
					console.error(error.message);
				})
			});
	});
});