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
		document.querySelectorAll('[radicalmart-shipping-apiship-input="recipient"], [data-radicalmart-shipping-apiship-input="recipient"]')
			.forEach((container) => {
				let selector = container.getAttribute('data-selector'),
					jsOptions = Joomla.getOptions(selector),
					id = jsOptions.id,
					name = jsOptions.name,
					fieldAddress = container.querySelector('[name="' + name + '[address]"]'),
					fieldId = container.querySelector('[name="' + name + '[id]"]'),
					fieldTitle = container.querySelector('[name="' + name + '[title]"]'),
					fieldLatitude = container.querySelector('[name="' + name + '[latitude]"]'),
					fieldLongitude = container.querySelector('[name="' + name + '[longitude]"]'),
					valueCoordinates = jsOptions.coordinates,
					map = new ymaps.Map(id + '_map', {
							center: (valueCoordinates) ? valueCoordinates : [0, 0],
							zoom: (valueCoordinates) ? 15 : 0,
							controls: ['zoomControl']
						},
						{
							yandexMapDisablePoiInteractivity: true,
						});

				// Add markers
				let form = container.closest('form'),
					formType = jsOptions.name,
					formData = new FormData(form);
				formData.set('formType', formType);
				formData.set('action', 'getPVZ');


				let objectManager = new ymaps.ObjectManager({
					clusterize: true,
					gridSize: 64,
					hasBalloon: false,
				});

				map.geoObjects.add(objectManager);

				objectManager.objects.events.add('click', function (event) {
					let object = objectManager.objects.getById(event.get('objectId'));
					fieldId.value = object.id;
					fieldTitle.value = object.title;
					fieldLatitude.value = object.latitude;
					fieldLongitude.value = object.longitude;
					if (fieldAddress.value !==  object.address) {
						fieldAddress.value =  object.address;
						fieldAddress.dispatchEvent(new Event('change', {'bubbles': true}));
					}
					console.log(object);
				});

				axios({
					method: 'post',
					url: jsOptions.controller,
					data: formData,
					headers: {'Content-Type': 'multipart/form-data'},
				}).then((response) => {
					if (response.data.success) {
						let data = response.data.data[0];

						objectManager.add(data);


						console.log(data);
					} else {
						if (formType === 'checkout') {
							window.RadicalMartCheckout().triggerEvent('onRadicalMartCheckoutError', response.data.message);
							form.querySelector('[name*="[shipping][price][base]"]').value = -1;
							form.querySelector('[name*="[shipping][price][final]"]').value = -1;
							form.querySelector('[name*="[shipping][price][hash]"]').value = '';
							window.RadicalMartCheckout().updateDisplayData();
						}
						console.error(response.data.message);
					}
				}).catch((error) => {
					if (error.message !== 'Request aborted') {
						if (formType === 'checkout') {
							window.RadicalMartCheckout().triggerEvent('onRadicalMartCheckoutError', error.message);
							form.querySelector('[name*="[shipping][price][base]"]').value = -1;
							form.querySelector('[name*="[shipping][price][final]"]').value = -1;
							form.querySelector('[name*="[shipping][price][hash]"]').value = '';
							window.RadicalMartCheckout().updateDisplayData();
						}
						console.error(error.message);
					}
				});


				// Scroll zoom
				map.behaviors.disable('scrollZoom');
				document.querySelector('body').onkeydown = (event) => {
					if (event.keyCode === 17) map.behaviors.enable('scrollZoom');
				};
				document.querySelector('body').onkeyup = (event) => {
					if (event.keyCode === 17) map.behaviors.disable('scrollZoom');
				};

				// map.geoObjects.add(marker);

				// Location
				let geolocationControl = new ymaps.control.GeolocationControl({
					options: {noPlacemark: true}
				});
				map.controls.add(geolocationControl);
				geolocationControl.events.add('locationchange', (event) => {
					map.setCenter(event.get('position'), 10);
				});

				if (!valueCoordinates) {
					ymaps.geolocation.get({mapStateAutoApply: true}).then((result) => {
						let coordinates = result.geoObjects.get(0).geometry.getCoordinates();
						map.setCenter(coordinates, 10);
					});
				}

				// Add search
				let search = new ymaps.control.SearchControl({options: {'noPlacemark': true}});
				map.controls.add(search);
				search.events.add('resultselect', () => {
					let coordinates = search.getResultsArray()[0].geometry.getCoordinates();
					map.setCenter(coordinates, 10);
				});


				// function setValues() {
				// 	let coordinates = marker.geometry.getCoordinates(),
				// 		latitude = (coordinates[0]).toFixed(6),
				// 		longitude = (coordinates[1]).toFixed(6);
				// 	ymaps.geocode(coordinates).then((result) => {
				// 		let address = result.geoObjects.get(0).properties.get('text');
				// 		fieldLatitude.value = latitude;
				// 		fieldLongitude.value = longitude;
				// 		if (fieldAddress.value !== address) {
				// 			fieldAddress.value = address;
				// 			fieldAddress.dispatchEvent(new Event('change', {'bubbles': true}));
				// 		}
				// 	});
				// }
			});
	});
});