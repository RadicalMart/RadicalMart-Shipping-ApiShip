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

document.addEventListener('DOMContentLoaded', function () {
	ymaps.ready(function () {
		document.querySelectorAll('[input-apiship-to="container"]').forEach(function (container) {
			let jsOptions = Joomla.getOptions(container.getAttribute('id')),
				centerCoordinates = [0, 0],
				coordinates = (jsOptions.coordinates) ? jsOptions.coordinates : [0, 0],
				map = new ymaps.Map(container.querySelector('[input-apiship-to="map"]').getAttribute('id'), {
						center: (jsOptions.center) ? jsOptions.center : centerCoordinates,
						zoom: (jsOptions.zoom) ? jsOptions.zoom : 3,
						controls: ['zoomControl']
					},
					{
						yandexMapDisablePoiInteractivity: true,
					});

			// Scroll zoom
			map.behaviors.disable('scrollZoom');
			document.querySelector('body').onkeydown = function (event) {
				if (event.keyCode === 17) {
					map.behaviors.enable('scrollZoom');
				}
			};
			document.querySelector('body').onkeyup = function (event) {
				if (event.keyCode === 17) {
					map.behaviors.disable('scrollZoom');
				}
			};

			// Add search
			let search = new ymaps.control.SearchControl({options: {'noPlacemark': true}});
			map.controls.add(search);
			search.events.add('resultselect', function () {
				let coordinates = search.getResultsArray()[0].geometry.getCoordinates();
				map.setCenter(coordinates, 15);
				placeMarker(coordinates);
			});

			// Marker
			let marker = new ymaps.Placemark(coordinates, {
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
			setValues();

			function placeMarker(coordinates) {
				marker.geometry.setCoordinates(coordinates);
				setValues();
			}

			// Location
			let geolocationControl = new ymaps.control.GeolocationControl({
				options: {noPlacemark: true}
			});

			map.controls.add(geolocationControl);
			geolocationControl.events.add('locationchange', function (event) {
				placeMarker(event.get('position'));
			});
			if (coordinates === [0, 0]) {
				ymaps.geolocation.get({mapStateAutoApply: true}).then(function (result) {
					let coordinates = result.geoObjects.get(0).geometry.getCoordinates();
					map.setCenter(coordinates, 15);
					placeMarker(coordinates);
				});
			}

			// Events
			map.events.add('click', function (event) {
				placeMarker(event.get('coords'));
			});
			marker.events.add('dragend', function () {
				setValues();
			}, marker);

			function setValues() {
				let coordinates = marker.geometry.getCoordinates(),
					latitude = (coordinates[0]).toFixed(6),
					longitude = (coordinates[1]).toFixed(6);
				ymaps.geocode(coordinates).then(function (result) {
					container.querySelector('[input-apiship-to-field="addressString"]').value = result.geoObjects.get(0).properties.get('text');
				});

				container.querySelector('[input-apiship-to-field="lat"]').value = latitude;
				container.querySelector('[input-apiship-to-field="lng"]').value = longitude;
			}
		});
	});
});