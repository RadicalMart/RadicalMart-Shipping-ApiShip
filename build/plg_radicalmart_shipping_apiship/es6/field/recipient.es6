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
	ymaps.ready(() => {
		document.querySelectorAll('[radicalmart-shipping-apiship-input="recipient"], [data-radicalmart-shipping-apiship-input="recipient"]')
			.forEach((container) => {
				let selector = container.getAttribute('data-selector'),
					jsOptions = Joomla.getOptions(selector),
					id = jsOptions.id,
					name = jsOptions.name,
					fieldAddress = container.querySelector('[name="' + name + '[address]"]'),
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

				// Scroll zoom
				map.behaviors.disable('scrollZoom');
				document.querySelector('body').onkeydown = (event) => {
					if (event.keyCode === 17) map.behaviors.enable('scrollZoom');
				};
				document.querySelector('body').onkeyup = (event) => {
					if (event.keyCode === 17) map.behaviors.disable('scrollZoom');
				};

				// Marker
				let marker = new ymaps.Placemark((valueCoordinates) ? valueCoordinates : [0, 0], {
					balloonContent: '',
				}, {
					point: true,
					draggable: true,
					iconLayout: 'default#image',
					iconImageHref: jsOptions.iconImageHref,
					iconImageSize: jsOptions.iconImageSize,
					iconImageOffset: jsOptions.iconImageOffset,
					openBalloonOnClick: false
				});
				map.geoObjects.add(marker);

				// Location
				let geolocationControl = new ymaps.control.GeolocationControl({
					options: {noPlacemark: true}
				});
				map.controls.add(geolocationControl);
				geolocationControl.events.add('locationchange', (event) => {
					placeMarker(event.get('position'));
					map.setCenter(event.get('position'), 15);
				});

				if (!valueCoordinates) {
					ymaps.geolocation.get({mapStateAutoApply: true}).then((result) => {
						let coordinates = result.geoObjects.get(0).geometry.getCoordinates();
						map.setCenter(coordinates, 15);
						placeMarker(coordinates);
					});
				}
				else setValues();

				// Add search
				let search = new ymaps.control.SearchControl({options: {'noPlacemark': true}});
				map.controls.add(search);
				search.events.add('resultselect', () => {
					let coordinates = search.getResultsArray()[0].geometry.getCoordinates();
					map.setCenter(coordinates, 15);
					placeMarker(coordinates);
				});

				// Events
				map.events.add('click', function (event) {
					placeMarker(event.get('coords'));
				});
				marker.events.add('dragend', function () {
					setValues();
				}, marker);

				function placeMarker(coordinates) {
					marker.geometry.setCoordinates(coordinates);
					setValues();
				}

				function setValues() {
					let coordinates = marker.geometry.getCoordinates(),
						latitude = (coordinates[0]).toFixed(6),
						longitude = (coordinates[1]).toFixed(6);
					ymaps.geocode(coordinates).then((result) => {
						let address = result.geoObjects.get(0).properties.get('text');
						fieldLatitude.value = latitude;
						fieldLongitude.value = longitude;
						if (fieldAddress.value !== address) {
							fieldAddress.value = address;
							fieldAddress.dispatchEvent(new Event('change', {'bubbles': true}));
						}
					});
				}
			});
	});
});