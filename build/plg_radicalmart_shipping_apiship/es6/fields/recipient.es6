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

document.addEventListener('DOMContentLoaded', () => {
	ymaps.ready(() => {
		document.querySelectorAll(
			'[radicalmart-shipping-apiship-field="recipient"], [data-radicalmart-shipping-apiship-field="recipient"]')
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
				map.events.add('click', function (event) {
					let coordinates = event.get('coords');
					setMarker(coordinates);
					setValues(coordinates, 'map');
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

				// Center or add marker
				let mapFrame = container.querySelector('#' + id + '_map > ymaps');
				if (!valueCoordinates) {
					mapFrame.style.display = 'none';
					ymaps.geolocation.get({mapStateAutoApply: true}).then((result) => {
						let coordinates = result.geoObjects.get(0).geometry.getCoordinates();
						map.setCenter(coordinates, 15);
						mapFrame.style.display = '';
					});
				} else {
					map.setCenter(valueCoordinates, 15);
					setMarker(valueCoordinates);
					mapFrame.style.display = '';
				}

				// Geolocation
				let geolocationControl = new ymaps.control.GeolocationControl({
					options: {noPlacemark: true}
				});
				map.controls.add(geolocationControl);
				geolocationControl.events.add('locationchange', (event) => {
					let coordinates = event.get('position');
					setMarker(coordinates);
					setValues(coordinates, 'map');
					map.setCenter(coordinates, 15);
				});

				// Suggest
				let suggest = new ymaps.SuggestView(id + '_suggest', '');
				suggest.events.add('select', (event) => {
					setValues(event.get('item').value, 'suggest');
				});

				// Functions
				function setValues(value, from) {
					ymaps.geocode(value).then((result) => {
						let fieldSuggest = container.querySelector('#' + id + '_suggest'),
							fieldAddress = container.querySelector('#' + id + '_address'),
							fieldLatitude = container.querySelector('#' + id + '_latitude'),
							fieldLongitude = container.querySelector('#' + id + '_longitude');

						let geoObject = result.geoObjects.get(0),
							address = geoObject.properties.get('text'),
							coordinates = geoObject.geometry.getCoordinates(),
							latitude = (coordinates[0]).toFixed(6),
							longitude = (coordinates[1]).toFixed(6);

						if (fieldLatitude.value !== latitude) fieldLatitude.value = latitude;
						if (fieldLongitude.value !== latitude) fieldLongitude.value = longitude;

						if (fieldAddress.value !== address) {
							fieldAddress.value = address;
							fieldAddress.dispatchEvent(new Event('change'));
						}

						if (from === 'map' && fieldSuggest.value !== address) fieldSuggest.value = address;
						else if (from === 'suggest') {
							setMarker(coordinates);
							map.setCenter(coordinates, 15);
						}
					});
				}

				function setMarker(coordinates) {
					map.geoObjects.removeAll();
					let marker = new ymaps.Placemark(coordinates, {}, jsOptions.marker);
					map.geoObjects.add(marker);
					marker.events.add('dragend', function () {
						setValues(marker.geometry.getCoordinates(), 'map');
					}, marker);
				}
			});
	});
});