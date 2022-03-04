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
	document.querySelectorAll('[input-apiship-places="container"]').forEach(function (container) {
		let jsOptions = Joomla.getOptions(container.getAttribute('id'));
		if (jsOptions.hiddenLabel
			&& container.closest('.control-group')
			&& container.closest('.controls')) {
			container.closest('.controls').style.marginLeft = '0';
		}
	});
	ymaps.ready(function () {
		document.querySelectorAll('[input-apiship-places="container"]').forEach(function (container) {
			let jsOptions = Joomla.getOptions(container.getAttribute('id')),
				map = new ymaps.Map(container.querySelector('[input-apiship-places="map"]').getAttribute('id'), {
						center: [0, 0],
						zoom: 3,
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
				if (container.getAttribute('data-mode') === 'add') {
					createItem(coordinates);
				} else {
					setActiveRow();
					setActiveMarker();
				}
			});

			// Add GeoLocation
			let geolocationControl = new ymaps.control.GeolocationControl({options: {noPlacemark: true}});
			map.controls.add(geolocationControl);
			geolocationControl.events.add('locationchange', function (event) {
				let coordinates = event.get('position');
				map.setCenter(coordinates, 15);
				if (container.getAttribute('data-mode') === 'add') {
					createItem(coordinates);
				} else {
					event.get('position')
					setActiveRow();
					setActiveMarker();
				}
			});

			// Insert values
			let hasValues = false;
			Object.keys(jsOptions.markers).forEach(function (k) {
				addMarker(k, jsOptions.markers[k]);
				initRowActions(k);
				hasValues = true;
			});
			if (hasValues) {
				let valuesInterval = setInterval(function () {
					map.setBounds(map.geoObjects.getBounds(), {
						checkZoomRange: true,
						zoomMargin: 9
					}).then(function () {
						if (map.getZoom() > 14) map.setZoom(14);
						if (map.getZoom() > 5) clearInterval(valuesInterval);
					}, this);
				}, 10);
			} else {
				ymaps.geolocation.get({mapStateAutoApply: true}).then(function (result) {
					map.setCenter(result.geoObjects.get(0).geometry.getCoordinates(), 15);
				});
			}
			displayMessages();

			map.events.add('click', function (event) {
				if (container.getAttribute('data-mode') === 'add') {
					createItem(event.get('coords'));
				} else {
					setActiveRow();
					setActiveMarker();
				}
			});

			container.querySelectorAll('[input-apiship-places="action-add"]').forEach(function (button) {
				button.addEventListener('click', function (event) {
					event.preventDefault();
					container.setAttribute('data-mode', 'add');
					setActiveRow();
					setActiveMarker();
					displayMessages();
				});
			});

			container.querySelectorAll('[input-apiship-places="action-cancel_add"]').forEach(function (button) {
				button.addEventListener('click', function (event) {
					event.preventDefault();
					container.setAttribute('data-mode', 'edit');
					displayMessages();
				});
			});

			container.querySelectorAll('[input-apiship-places="action-show_all"]').forEach(function (button) {
				button.addEventListener('click', function (event) {
					event.preventDefault();
					map.setBounds(map.geoObjects.getBounds(), {
						checkZoomRange: true,
						zoomMargin: 9
					}).then(function () {
						if (map.getZoom() > 14) map.setZoom(14);
					}, this);
				});
			});

			function createItem(coordinates = null) {
				if (coordinates === null) coordinates = map.getCenter();
				let key = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15),
					row = addRow(key),
					marker = addMarker(key, coordinates);

				setRowCoordinates(key);
				setRowAddress(key);
				setActiveRow(key);
				setActiveMarker(key);
				setMarkerBalloon(key);

				row.querySelector('[input-apiship-places="title"]').focus();
				marker.balloon.open();

				container.setAttribute('data-mode', 'edit');
				displayMessages();
			}

			function removeItem(key) {
				deleteMarker(key);
				deleteRow(key);
			}

			function getMarker(key) {
				let result = false;
				map.geoObjects.each(function (geoObject) {
					if (geoObject.options.get('key') === key) {
						result = geoObject;
					}
				});
				return result;
			}

			function addMarker(key, coordinates = null) {
				if (!getMarker(key)) {
					let marker = new ymaps.Placemark(coordinates, {
						balloonContent: '',
					}, {
						key: key,
						point: true,
						draggable: true,
						iconLayout: 'default#image',
						iconImageHref: jsOptions.iconImageHref,
						iconImageSize: jsOptions.iconImageSize,
						iconImageOffset: jsOptions.iconImageOffset,
						openBalloonOnClick: false
					});
					map.geoObjects.add(marker);

					marker.events.add('contextmenu', function () {
						removeItem(key);
					});

					marker.events.add('dragstart', function () {
						setActiveMarker(key);
						setActiveRow(key);
					}, marker);

					marker.events.add('dragend', function () {
						setRowCoordinates(key);
						displayRowUpdateAddress(key);
					}, marker);

					marker.events.add('click', function () {
						if (container.getAttribute('data-mode') === 'edit') {
							setActiveRow(key);
							setActiveMarker(key);
							displayMessages();
						}
					}, marker);
				}

				return getMarker(key);
			}

			function deleteMarker(key) {
				let marker = getMarker(key);
				if (marker) map.geoObjects.remove(marker);
			}

			function setActiveMarker(key) {
				map.geoObjects.each(function (geoObject) {
					if (geoObject.options.get('point')) {
						if (geoObject.options.get('key') === key) {
							geoObject.options.set('iconImageHref', jsOptions.iconActiveImageHref);
							setMarkerBalloon(key);
						} else {
							geoObject.options.set('iconImageHref', jsOptions.iconImageHref);
							geoObject.balloon.close();
						}
					}
				});
			}

			function setMarkerBalloon(key, open) {
				let row = getRow(key),
					marker = getMarker(key);
				if (row && marker) {
					marker.properties.set('balloonContent',
						'<strong>' + row.querySelector('[input-apiship-places="title"]').value + '</strong><br>'
						+ row.querySelector('[input-apiship-places="address"]').value
					);
					if (open === 1) marker.balloon.open();
					else if (open === -1) marker.balloon.close();
				}
			}

			function getRow(key) {
				return container.querySelector('[input-apiship-places="item"][data-key="' + key + '"]');
			}

			function addRow(key) {
				if (!getRow(key)) {
					let regexp_key = new RegExp('REPLACE_TO_KEY', 'g'),
						regexp_attributes_name = new RegExp('data-replace_', 'g'),
						newElement = container.querySelector('[input-apiship-places="template"] [input-apiship-places="item"]')
							.cloneNode(true);
					newElement.innerHTML = newElement.innerHTML.replace(regexp_key, key);
					newElement.innerHTML = newElement.innerHTML.replace(regexp_attributes_name, '');

					newElement.setAttribute('data-key', key);
					container.querySelector('[input-apiship-places="form"]').append(newElement);
					initRowActions(key);
				}
				return getRow(key);
			}

			function initRowActions(key) {
				let row = getRow(key);
				if (row) {
					row.querySelectorAll('[input-apiship-places="action-delete"]').forEach(function (button) {
						button.addEventListener('click', function (event) {
							event.preventDefault();
							removeItem(key);
						});
					});

					row.querySelectorAll('[input-apiship-places="action-close"]').forEach(function (button) {
						button.addEventListener('click', function (event) {
							event.preventDefault();
							setActiveRow();
							setActiveMarker();
						});
					});

					row.querySelectorAll('[input-apiship-places="action-set_address"]').forEach(function (button) {
						button.addEventListener('click', function (event) {
							event.preventDefault();
							setRowAddress(key);
							row.querySelector('[input-apiship-places="address"]').focus();
						});
					});

					['[input-apiship-places="title"]', '[input-apiship-places="address"]'].forEach(function (selector) {
						let element = row.querySelector(selector);

						element.addEventListener('focus', function () {
							setMarkerBalloon(key, 1);
						});
						element.addEventListener('focusout', function () {
							setMarkerBalloon(key, -1);
						});
						element.addEventListener('change', function () {
							setMarkerBalloon(key, 1);
						});
						element.addEventListener('cut', function () {
							setMarkerBalloon(key, 1);
						});
						element.addEventListener('paste', function () {
							setMarkerBalloon(key, 1);
						});
						element.addEventListener('drop', function () {
							setMarkerBalloon(key, 1);
						});
						element.addEventListener('keyup', function () {
							setMarkerBalloon(key, 1);
						});
					});
				}
			}

			function displayRowUpdateAddress(key) {
				let row = getRow(key);
				if (row) {
					row.querySelectorAll('[input-apiship-places="update_address"]').forEach(function (element) {
						element.style.display = '';
					});
				}
			}

			function deleteRow(key) {
				let row = getRow(key);
				if (row) {
					row.remove();
				}
				displayMessages();
			}

			function setRowCoordinates(key) {
				let row = getRow(key),
					marker = getMarker(key);
				if (row && marker) {
					row.querySelector('[input-apiship-places="latitude"]').value = (marker.geometry.getCoordinates()[0])
						.toFixed(6);
					row.querySelector('[input-apiship-places="longitude"]').value = (marker.geometry.getCoordinates()[1])
						.toFixed(6);
				}
			}

			function setRowAddress(key) {
				let row = getRow(key),
					marker = getMarker(key);
				if (row && marker) {
					ymaps.geocode(marker.geometry.getCoordinates()).then(function (result) {
						row.querySelector('[input-apiship-places="address"]').value = result.geoObjects.get(0)
							.properties.get('text');
						row.querySelector('[input-apiship-places="address"]')
							.dispatchEvent(new Event('change', {'bubbles': true}));
					});
				}
			}

			function setActiveRow(key) {
				container.querySelectorAll('[input-apiship-places="form"] [input-apiship-places="item"]').forEach(function (element) {
					if (element.getAttribute('data-key') === key) {
						element.setAttribute('data-active', '1');
						element.style.display = '';
						container.setAttribute('data-mode', 'edit');
					} else {
						element.style.display = 'none';
						element.setAttribute('data-active', '0');
					}
				});
				displayMessages();
			}

			function displayMessages() {
				let no_select_items = container.querySelector('[input-apiship-places="message-no_select_items"]'),
					no_items = container.querySelector('[input-apiship-places="message-no_items"]'),
					add = container.querySelector('[input-apiship-places="message-add"]');

				container.querySelector('[input-apiship-places="message-save_success"]').style.display = 'none';
				container.querySelector('[input-apiship-places="message-save_error"]').style.display = 'none';
				container.querySelector('[input-apiship-places="message-save_loading"]').style.display = 'none';

				if (container.getAttribute('data-mode') === 'add') {
					add.style.display = '';
					no_select_items.style.display = 'none';
					no_items.style.display = 'none';
				} else {
					add.style.display = 'none';
					let rows = container.querySelectorAll('[input-apiship-places="form"] [input-apiship-places="item"]');
					if (rows.length === 0) {
						no_items.style.display = '';
						no_select_items.style.display = 'none';
					} else {
						no_items.style.display = 'none';
						if (container.querySelector('[input-apiship-places="form"] [input-apiship-places="item"][data-active="1"]')) {
							no_select_items.style.display = 'none';
						} else {
							no_select_items.style.display = '';
						}
					}
				}
			}
		});
	});
});