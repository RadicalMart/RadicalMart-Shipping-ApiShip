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

import JoomlaAjaxUtil from "../util/ajax.es6";

class RadicalMartShippingApiShipFieldPoints extends JoomlaAjaxUtil {
	constructor(container) {

		let id = container.getAttribute('id'),
			options = Joomla.getOptions(id),
			coreOptions = Joomla.getOptions('plg_radicalmart_shipping_apiship.field.points'),
			controller = (coreOptions) ? coreOptions.controller : false;

		if (!options || !controller) {
			return;
		}

		super('RadicalMartShippingApiShipFieldPoints');
		this.id = id;
		this.container = container;
		this.options = options;
		this.controller = controller;

		this.map = false;
		this.objectManager = false;
		this.panel = false;

		this.rows = false;
		this.providers = false;
		this.activeProviders = false;

		let value = (options.value) ? options.value : false;
		this.current = (value && value.latitude && value.longitude) ? {
			id: parseInt(value.id),
			coordinates: [value.latitude, value.longitude],
		} : false;

		this.markers = options.markers;

		this.markerPreset = {
			default: this.markers['default'],
			active: this.markers['active'],
			size: (options.markerPreset && options.markerPreset.size) ? options.markerPreset.size : [32, 32],
			offset: (options.markerPreset && options.markerPreset.offset) ? options.markerPreset.offset : [-16, -32],
		}

		this.clusterPreset = {
			preset: (options.clusterPreset && options.clusterPreset.preset)
				? options.clusterPreset.preset : 'islands#invertedNightClusterIcons',
			default: [],
			active: [],
		}
		let clusterIcons = (options.clusterPreset && options.clusterPreset.icons)
			? options.clusterPreset.icons : [56, 72].map(size => {
				const offset = -size / 2;
				return {
					size: [size, size], offset: [offset, offset],
				};
			});

		clusterIcons.forEach((iconParams) => {
			this.clusterPreset['default'].push({
				href: this.markers['cluster-default'], size: iconParams.size, offset: iconParams.offset,
			});
			this.clusterPreset['active'].push({
				href: this.markers['cluster-active'], size: iconParams.size, offset: iconParams.offset,
			})
		})

		this.itemTemplate = (options.itemTemplate) ? options.itemTemplate : false;

		this.initialize().then();
	}

	async initialize() {
		let loading = this.container.querySelector('[radicalmart-shipping-apiship-field-points="loading"],'
				+ '[data-radicalmart-shipping-apiship-field-points="loading"]'),
			error = this.container.querySelector('[radicalmart-shipping-apiship-field-points="error"],'
				+ '[data-radicalmart-shipping-apiship-field-points="error"]');

		if (loading) {
			loading.style.display = '';
		}

		if (error) {
			error.innerHTML = '';
			error.style.display = 'none'
		}

		try {
			await this.loadMap();
			await this.getPointsData();
			await this.showPoints();
			this.loadFilters();
			if (loading) {
				loading.style.display = 'none'
			}
		} catch (er) {
			console.error(er);
			if (error) {
				error.innerHTML = er;
				error.style.display = ''
			}
		}
	}

	loadMap() {
		return new Promise((result, reject) => {
			if (this.map) {
				result(this.map);
				return;
			}

			let mapBlock = this.container.querySelector('[radicalmart-shipping-apiship-field-points="map"],'
				+ '[data-radicalmart-shipping-apiship-field-points="map"]');
			if (!mapBlock) {
				reject(new Error('Map container not found.'));
			}

			let map = new ymaps.Map(mapBlock.getAttribute('id'), {
				center: (this.current) ? this.current.coordinates : [0, 0],
				zoom: (this.current) ? 15 : 0,
				controls: ['zoomControl']
			}, {
				yandexMapDisablePoiInteractivity: true,
			});

			if (!this.current) {
				ymaps.geolocation.get({mapStateAutoApply: true}).then((result) => {
					let coordinates = result.geoObjects.get(0).geometry.getCoordinates();
					map.setCenter(coordinates, 10);
				});
			}

			map.behaviors.disable('scrollZoom');
			document.querySelector('body').onkeydown = (event) => {
				if (event.key === 'Control') {
					map.behaviors.enable('scrollZoom');
				}
			};
			document.querySelector('body').onkeyup = (event) => {
				if (event.key === 'Control') {
					map.behaviors.disable('scrollZoom');
				}
			};

			let geolocationControl = new ymaps.control.GeolocationControl({
				options: {noPlacemark: true}
			});
			map.controls.add(geolocationControl);
			geolocationControl.events.add('locationchange', (event) => {
				map.setCenter(event.get('position'), 10);
			});

			// Add search
			let search = new ymaps.control.SearchControl({options: {'noPlacemark': true}});
			map.controls.add(search);
			search.events.add('resultselect', () => {
				let coordinates = search.getResultsArray()[0].geometry.getCoordinates();
				map.setCenter(coordinates, 15);
			});

			let objectManager = new ymaps.ObjectManager({
				clusterize: true,
				hasBalloon: false,
				clusterDisableClickZoom: true,
				preset: this.clusterPreset.preset,
				clusterIcons: this.clusterPreset.default,
			});
			objectManager.events.add('click', (event) => {
				let objectId = event.get('objectId'),
					currentZoom = map.getZoom();

				if (objectManager.clusters.getById(objectId)) {
					if (currentZoom >= 14) {
						let cluster = objectManager.clusters.getById(objectId),
							geoObjects = cluster.properties.geoObjects || [],
							items = geoObjects.map((feature) => {
								return this.rows.find(row => row.id === feature.id);
							});
						this.map.balloon.open(event.get('coords'), {
							contentBody: items.map(item => this.interpolateTemplate(item)).join('<hr>'),
						}, {
							panelMaxMapArea: Infinity
						}).then(() => {
							this.container.querySelectorAll(
								'[radicalmart-shipping-apiship-field-points="select"],'
								+ '[data-radicalmart-shipping-apiship-field-points="select"]').forEach(button => {
								button.addEventListener('click', (event) => {
									event.preventDefault();
									let point_id = parseInt(button.getAttribute('data-point_id')),
										point = this.rows.find(p => p.id === point_id);

									if (point) {
										this.setValue(point);
									}
								});
							});
						});
					} else {
						map.setCenter(event.get('coords'), currentZoom + 1);
					}
				} else {
					let point = this.rows.find(p => p.id === objectId);
					if (point) {
						map.setCenter([point.lat, point.lng], (currentZoom < 15) ? 15 : currentZoom);
						this.setValue(point);
					}
				}
			});
			map.geoObjects.add(objectManager);

			map.events.add('boundschange', () => {
				if (this.rows) {
					this.showPoints().then();
				}
			});
			map.events.add('click', () => {
				if (map.balloon && map.balloon.isOpen()) {
					map.balloon.close();
				}
			});

			map.events.add('balloonopen', () => {
				this.container.dispatchEvent(new Event('balloonopen'));
			});

			map.events.add('balloonclose', () => {
				this.container.dispatchEvent(new Event('balloonclose'));
			});

			this.objectManager = objectManager;
			this.map = map;
			result(map);
		});
	}

	getPointsData() {
		let options = this.options;
		return new Promise((success, error) => {
			if (this.rows) {
				success(this.rows);
				return;
			}
			this.sendAjax('getPoints', {
				'context': options.context, 'shipping': options.shipping, 'operation': options.operation,
			}, true).then((response) => {
				response.forEach(point => {
					point.id = parseInt(point.id);
					point.providerTitle = Joomla.Text._('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' + point.providerKey);
					point.title = point.providerTitle + ' - ' + point.name;
					point.display = point.providerTitle + ' - ' + point.address;
					point.marker = (this.markers[point.providerKey])
						? this.markers[point.providerKey] : this.markerPreset.default;
				});

				this.rows = response;
				let providers = new Set(this.rows.map(p => p.providerKey));
				this.providers = providers;
				this.activeProviders = providers;

				success(response);
			}).catch((e) => {
				if (e.message) {
					error(e.message);
				} else {
					error('Ajax Error')
				}
			})
		});
	}

	showPoints() {
		return new Promise((resolve) => {
			let bounds = this.map.getBounds(),
				[sw, ne] = bounds,
				limit = 100,
				currentAdd = (!this.current);

			for (let i = 0; i < this.rows.length; i++) {
				let row = this.rows[i],
					count = this.objectManager.objects.getLength(),
					inBounds = (row.lat >= sw[0] && row.lat <= ne[0]
						&& row.lng >= sw[1] && row.lng <= ne[1]),
					inProvider = (this.activeProviders
						&& this.activeProviders.size > 0
						&& this.activeProviders.has(row.providerKey)),
					isCurrent = (this.current && this.current.id === row.id),
					mapObject = this.objectManager.objects.getById(row.id),
					isNotLimit = count < limit;

				if (mapObject) {
					if (!isCurrent && (!inProvider || !inBounds)) {
						this.objectManager.objects.remove(mapObject);
						continue;
					}
					if (isCurrent) {
						currentAdd = true;
						this.objectManager.objects.setObjectOptions(row.id, {
							iconImageHref: this.markerPreset.active
						});
					} else {
						this.objectManager.objects.setObjectOptions(row.id, {
							iconImageHref: row.marker
						});
					}
					continue;
				}

				let needAdd = false;
				if (isCurrent) {
					currentAdd = true;
					needAdd = true;
				} else {
					needAdd = (inProvider && inBounds && isNotLimit);
				}

				if (!needAdd) {
					continue;
				}

				this.objectManager.add({
					type: 'Feature',
					id: row.id,
					geometry: {
						type: 'Point',
						coordinates: [row.lat, row.lng],
					}, properties: {
						hintContent: row.title,
					}, options: {
						iconLayout: 'default#image',
						iconImageHref: (isCurrent) ? this.markerPreset.active : row.marker,
						iconImageSize: this.markerPreset.size,
						iconImageOffset: this.markerPreset.offset,
					}
				});

				if (!currentAdd) {
					continue;
				}

				if (currentAdd && count >= limit) {
					break;
				}
			}

			this.updateClusters();
			resolve(true);
		});
	}

	loadFilters() {
		this.providers.forEach((providerKey) => {
			let providerTitle = Joomla.Text._('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' + providerKey);
			let button = new ymaps.control.Button({
				data: {
					content: providerTitle,
					title: providerTitle,
				},
				options: {
					selectOnClick: false,
				}
			});
			button.select();
			this.map.controls.add(button, {float: 'right'});
			button.events.add('click', () => {

				if (this.activeProviders.has(providerKey)) {
					this.activeProviders.delete(providerKey);
					button.deselect();

				} else {
					this.activeProviders.add(providerKey);
					button.select();
				}
				this.showPoints().then();
			});

		});
	}

	interpolateTemplate(data) {
		let template = (this.itemTemplate) ? this.itemTemplate : false;
		if (!template) {
			let link = document.createElement('a');
			link.setAttribute('radicalmart-shipping-apiship-field-points', 'select');
			link.setAttribute('data-point_id', '{point.id}');
			link.style.display = 'block';
			link.innerHTML = '{point.providerTitle} - {point.address}';
			template = link.outerHTML;
		}
		return template.replace(/\{point\.([\w]+)}/g, (match, key) => {
			return data[key] !== undefined ? data[key] : '';
		});
	}

	setValue(point) {
		let mapping = {
			id: 'id',
			title: 'title',
			lat: 'latitude',
			lng: 'longitude',
			address: 'address',
			countryCode: 'countryCode',
			providerKey: 'providerKey',
			display: 'display',

		}, addressField = false;

		Object.keys(mapping).forEach((from) => {
			let to = mapping[from], value = (point[from]) ? point[from] : false,
				selector = 'radicalmart-shipping-apiship-field-points="input_' + to + '"',
				input = this.container.querySelector('[' + selector + '],[data-' + selector + ']');
			if (value && input) {
				input.value = value;
				if (to === 'address') {
					addressField = input;
				}
			}
		});
		if (addressField) {
			addressField.dispatchEvent(new Event('change'));
		}

		this.current = {
			id: point.id, coordinates: [point.lat, point.lng],
		}

		if (this.map.balloon) {
			this.map.balloon.close();
		}

		this.showPoints();
	}

	updateClusters() {
		let clusters = this.objectManager.clusters;
		if (!clusters) {
			return;
		}

		clusters.getAll().forEach((cluster) => {
			let hasActive = false;
			if (this.current) {
				hasActive = cluster.properties.geoObjects.some(obj => parseInt(obj.id) === this.current.id);
			}
			this.objectManager.clusters.setClusterOptions(cluster.id, {
				clusterIcons: (hasActive) ? this.clusterPreset.active : this.clusterPreset.default
			});
		});
	}
}

document.addEventListener('DOMContentLoaded', () => {
	ymaps.ready(() => {
		document.querySelectorAll('[radicalmart-shipping-apiship-field-points="container"],'
			+ '[data-radicalmart-shipping-apiship-field-points="container"]')
			.forEach((container) => {
				container.FieldClass = new RadicalMartShippingApiShipFieldPoints(container);
			});
	});
});