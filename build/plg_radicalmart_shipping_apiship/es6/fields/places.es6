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
		let attr = 'radicalmart-shipping-apiship-field-places';
		document.querySelectorAll('[' + attr + '="container"]').forEach((container) => {
			let id = container.getAttribute('id'),
				jsOptions = Joomla.getOptions(id),
				map = new ymaps.Map(id + '_map', {
						center: [0, 0],
						zoom: 3,
						controls: ['zoomControl']
					},
					{
						yandexMapDisablePoiInteractivity: true,
					});

			// Scroll zoom
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

			// Add search
			let search = new ymaps.control.SearchControl({options: {'noPlacemark': true}});
			map.controls.add(search);
			search.events.add('resultselect', () => {
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
			geolocationControl.events.add('locationchange', (event) => {
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
			Object.keys(jsOptions.markers).forEach((k) => {
				addMarker(k, jsOptions.markers[k]);
				initRowActions(k);
				hasValues = true;
			});
			if (hasValues) {
				let valuesInterval = setInterval(() => {
					map.setBounds(map.geoObjects.getBounds(), {
						checkZoomRange: true,
						zoomMargin: 9
					}).then(() => {
						if (map.getZoom() > 14) {
							map.setZoom(14);
						}
						if (map.getZoom() > 5) {
							clearInterval(valuesInterval);
						}
					}, this);
				}, 10);
			} else {
				ymaps.geolocation.get({mapStateAutoApply: true}).then((result) => {
					map.setCenter(result.geoObjects.get(0).geometry.getCoordinates(), 15);
				});
			}
			displayMessages();

			map.events.add('click', (event) => {
				if (container.getAttribute('data-mode') === 'add') {
					createItem(event.get('coords'));
				} else {
					setActiveRow();
					setActiveMarker();
				}
			});

			container.querySelectorAll('[' + attr + '="action-add"]')
				.forEach((button) => {
					button.addEventListener('click', (event) => {
						event.preventDefault();
						container.setAttribute('data-mode', 'add');
						setActiveRow();
						setActiveMarker();
						displayMessages();
					});
				});

			container.querySelectorAll('[' + attr + '="action-cancel_add"]')
				.forEach((button) => {
					button.addEventListener('click', (event) => {
						event.preventDefault();
						container.setAttribute('data-mode', 'edit');
						displayMessages();
					});
				});

			container.querySelectorAll('[' + attr + '="action-show_all"]')
				.forEach((button) => {
					button.addEventListener('click', (event) => {
						event.preventDefault();
						map.setBounds(map.geoObjects.getBounds(), {
							checkZoomRange: true,
							zoomMargin: 9
						}).then(() => {
							if (map.getZoom() > 14) {
								map.setZoom(14);
							}
						}, this);
					});
				});

			function createItem(coordinates = null) {
				if (coordinates === null) {
					coordinates = map.getCenter();
				}
				let key = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15),
					row = addRow(key),
					marker = addMarker(key, coordinates);

				setRowCoordinates(key);
				setRowAddress(key);
				setActiveRow(key);
				setActiveMarker(key);
				setMarkerBalloon(key);

				row.querySelector('[' + attr + '="title"]').focus();
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
				map.geoObjects.each((geoObject) => {
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

					marker.events.add('contextmenu', () => {
						removeItem(key);
					});

					marker.events.add('dragstart', () => {
						setActiveMarker(key);
						setActiveRow(key);
					}, marker);

					marker.events.add('dragend', () => {
						setRowCoordinates(key);
						displayRowUpdateAddress(key);
					}, marker);

					marker.events.add('click', () => {
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
				if (marker) {
					map.geoObjects.remove(marker);
				}
			}

			function setActiveMarker(key) {
				map.geoObjects.each((geoObject) => {
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
						'<strong>' + row.querySelector('[' + attr + '="title"]').value + '</strong><br>'
						+ row.querySelector('[' + attr + '="address"]').value
					);
					if (open === 1) {
						marker.balloon.open();
					} else if (open === -1) {
						marker.balloon.close();
					}
				}
			}

			function getRow(key) {
				return container.querySelector('[' + attr + '="item"][data-key="' + key + '"]');
			}

			function addRow(key) {
				if (!getRow(key)) {
					let regexp_key = new RegExp('REPLACE_TO_KEY', 'g'),
						regexp_attributes_name = new RegExp('data-replace_', 'g'),
						newElement = container.querySelector('[' + attr + '="template"] [' + attr + '="item"]')
							.cloneNode(true);
					newElement.innerHTML = newElement.innerHTML.replace(regexp_key, key);
					newElement.innerHTML = newElement.innerHTML.replace(regexp_attributes_name, '');

					newElement.setAttribute('data-key', key);
					container.querySelector('[' + attr + '="form"]')
						.append(newElement);
					initRowActions(key);
				}

				return getRow(key);
			}

			function initRowActions(key) {
				let row = getRow(key);
				if (row) {
					row.querySelectorAll('[' + attr + '="action-delete"]')
						.forEach((button) => {
							button.addEventListener('click', (event) => {
								event.preventDefault();
								removeItem(key);
							});
						});

					row.querySelectorAll('[' + attr + '="action-close"]')
						.forEach((button) => {
							button.addEventListener('click', (event) => {
								event.preventDefault();
								setActiveRow();
								setActiveMarker();
							});
						});

					row.querySelectorAll('[' + attr + '="action-set_address"]')
						.forEach((button) => {
							button.addEventListener('click', (event) => {
								event.preventDefault();
								setRowAddress(key);
								row.querySelector('[' + attr + '="address"]').focus();
							});
						});

					['[' + attr + '="title"]', '[' + attr + '="address"]']
						.forEach((selector) => {
							let element = row.querySelector(selector);
							element.addEventListener('focus', () => {
								setMarkerBalloon(key, 1);
							});
							element.addEventListener('focusout', () => {
								setMarkerBalloon(key, -1);
							});
							element.addEventListener('change', () => {
								setMarkerBalloon(key, 1);
							});
							element.addEventListener('cut', () => {
								setMarkerBalloon(key, 1);
							});
							element.addEventListener('paste', () => {
								setMarkerBalloon(key, 1);
							});
							element.addEventListener('drop', () => {
								setMarkerBalloon(key, 1);
							});
							element.addEventListener('keyup', () => {
								setMarkerBalloon(key, 1);
							});
						});
				}
			}

			function displayRowUpdateAddress(key) {
				let row = getRow(key);
				if (row) {
					row.querySelectorAll('[' + attr + '="update_address"]')
						.forEach((element) => {
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
					row.querySelector('[' + attr + '="latitude"]').value = (marker.geometry.getCoordinates()[0])
						.toFixed(6);
					row.querySelector('[' + attr + '="longitude"]').value = (marker.geometry.getCoordinates()[1])
						.toFixed(6);
				}
			}

			function setRowAddress(key) {
				let row = getRow(key),
					marker = getMarker(key);
				if (row && marker) {
					ymaps.geocode(marker.geometry.getCoordinates()).then((result) => {
						let geoObject = result.geoObjects.get(0);
						row.querySelector('[' + attr + '="address"]').value = geoObject.getCountry() + ', '
							+ geoObject.getAddressLine();
						row.querySelector('[' + attr + '="address"]')
							.dispatchEvent(new Event('change', {'bubbles': true}));
					});
				}
			}

			function setActiveRow(key) {
				container.querySelectorAll('[' + attr + '="form"] [' + attr + '="item"]')
					.forEach((element) => {
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
				let no_select_items = container.querySelector('[' + attr + '="message-no_select_items"]'),
					no_items = container.querySelector('[' + attr + '="message-no_items"]'),
					add = container.querySelector('[' + attr + '="message-add"]');

				if (container.getAttribute('data-mode') === 'add') {
					add.style.display = '';
					no_select_items.style.display = 'none';
					no_items.style.display = 'none';
				} else {
					add.style.display = 'none';
					let rows = container.querySelectorAll('[' + attr + '="form"] [' + attr + '="item"]');
					if (rows.length === 0) {
						no_items.style.display = '';
						no_select_items.style.display = 'none';
					} else {
						no_items.style.display = 'none';
						if (container.querySelector('[' + attr + '="form"] [' + attr + '="item"][data-active="1"]')) {
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