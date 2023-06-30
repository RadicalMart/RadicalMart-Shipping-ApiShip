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
				let id = container.getAttribute('id'),
					options = Joomla.getOptions(id),
					value = options.value,
					current = false;
				if (value && value.latitude && value.longitude) {
					current = {
						id: value.id,
						coordinates: [value.latitude, value.longitude],
					}
				}

				// Initialize map
				let map = new ymaps.Map(id + '_map', {
						center: (current) ? current.coordinates : [0, 0],
						zoom: (current) ? 15 : 0,
						controls: ['zoomControl']
					},
					{
						yandexMapDisablePoiInteractivity: true,
					});
				if (!current) {
					ymaps.geolocation.get({mapStateAutoApply: true}).then((result) => {
						let coordinates = result.geoObjects.get(0).geometry.getCoordinates();
						map.setCenter(coordinates, 10);
					});
				}

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

				// Object manager
				let objectManager = new ymaps.ObjectManager((options.cluster) ? options.cluster : {
					clusterize: true,
					gridSize: 64,
					hasBalloon: false,
				});
				map.geoObjects.add(objectManager);

				// Get points
				let mapFrame = container.querySelector('#' + id + '_map > ymaps');
				mapFrame.style.display = 'none';

				let form = container.closest('form'),
					ajax = new RadicalMartShippingApiShipAjax();
				ajax.setVariable('controller', options.controller);
				ajax.sendAjax('getPoints', {
					'context': options.context,
					'shipping': options.shipping,
					'operation': options.operation,
					'marker': JSON.stringify(options.marker)
				}).then((response) => {
					objectManager.add(response);
					mapFrame.style.display = '';
					if (current) {
						setValue(current.id);
					}
				}).catch((error) => {
					if (options.context === 'com_radicalmart.checkout') {
						window.RadicalMartCheckout().triggerEvent('onRadicalMartCheckoutError', error.message);
						form.querySelector('[name*="[shipping][price][base]"]').value = -1;
						form.querySelector('[name*="[shipping][price][final]"]').value = -1;
						form.querySelector('[name*="[shipping][price][hash]"]').value = '';
						window.RadicalMartCheckout().updateDisplayData();
					} else {
						Joomla.renderMessages({
							error: [error.message]
						});
					}
					console.error(error.message);
				});

				// Add click
				objectManager.objects.events.add('click', function (event) {
					let object = objectManager.objects.getById(event.get('objectId'));
					setValue(object.id);
				});

				// Set value
				function setValue(object_id) {
					let marker = options.marker;
					objectManager.objects.each((object) => {
						marker.iconImageHref = marker.iconImage;
						if (object.id === object_id) {
							['id', 'title', 'suggest', 'latitude', 'longitude', 'address'].forEach((key) => {
								let field = container.querySelector('[name="' + options.name + '[' + key + ']"]'),
									value = object[key];
								if (field) {
									if (key === 'address'
										&& (field.value !== value || field.getAttribute('data-set') !== '1')) {
										ymaps.geocode([object.latitude, object.longitude],).then((result) => {
											let geoObject = result.geoObjects.get(0);
											field.value = geoObject.getCountry() + ', '
												+ geoObject.getAddressLine();
											field.setAttribute('data-set', '1');
											field.dispatchEvent(new Event('change', {'bubbles': true}));
										});
									} else {
										field.value = value;
									}
								}
							});
							marker.iconImageHref = marker.iconImageActive;
							objectManager.objects.setObjectOptions(object.id, marker);

							if (object_id === current.id) {
								if (objectManager.getObjectState(object.id).isClustered) {
									map.setCenter([object.latitude, object.longitude], 20);
								}
							}
						} else {
							objectManager.objects.setObjectOptions(object.id, marker);
						}
					});

				}
			});
	});
});