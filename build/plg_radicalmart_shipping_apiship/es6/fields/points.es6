/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2026 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

"use strict";

import JoomlaAjaxUtil from "../util/ajax.es6";
import {ElementsUtils} from "../util/elements.es6";

class RadicalMartShippingApiShipFieldPoints extends JoomlaAjaxUtil {
	constructor(container) {
		super('RadicalMartShippingApiShipFieldPoints');

		let id = container.getAttribute('id'),
			options = Joomla.getOptions(id),
			coreOptions = Joomla.getOptions('plg_radicalmart_shipping_apiship.field.points'),
			controller = (coreOptions) ? coreOptions.controller : false;


		if (!options || !controller) {
			return;
		}

		this.id = id;
		this.attribute = 'radicalmart-shipping-apiship-field-points';
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
		let loading = ElementsUtils.getElementByAttribute(this.attribute, 'loading', this.container),
			error = ElementsUtils.getElementByAttribute(this.attribute, 'error', this.container);

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
		} catch (caught) {
			if (loading) {
				loading.style.display = 'none'
			}
			console.error(caught);
			if (error) {
				error.innerHTML = (caught instanceof Object) ? caught.message : caught;
				error.style.display = ''
			}
		}
	}

	loadMap() {
		return new Promise((resolve, reject) => {
			if (this.map) {
				resolve(this.map);
				return;
			}

			let mapBlock = ElementsUtils.getElementByAttribute(this.attribute, 'map', this.container);
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
							ElementsUtils.getElementsByAttribute(this.attribute, 'select', this.container)
								.forEach(button => {
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
			resolve(map);
		});
	}

	getPointsData() {
		let options = this.options;

		return new Promise((resolve, reject) => {
			this.rows = [];
			this.providers = [];
			let ajaxData = {
				context: options.context,
				shipping: options.shipping,
				order_id: options.order_id,
				operation: options.operation,
				file: '',
			};

			this.sendAjax('getPointsData', ajaxData, true).then((request) => {
				let filters = (request.filters) ? request.filters : {
						cod: false,
						package: false,
					},
					files = (request.files) ? request.files : {},
					keys = Object.keys(request.files);
				if (keys.length === 0) {
					reject('Points to found');
					return;
				}

				console.log(filters);

				let promises = keys.map((key) => {
					let data = files[key];
					let innerData = {...ajaxData, file: data.file};
					return this.sendAjax('getPoints', innerData, true).then((rows) => {
						rows.forEach((point) => {
							point.id = parseInt(point.id);
							point.providerTitle = Joomla.Text._('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' + point.providerKey);
							point.title = point.providerTitle + ' - ' + point.name;
							point.display = point.providerTitle + ' - ' + point.address;
							point.marker = (this.markers[point.providerKey])
								? this.markers[point.providerKey] : this.markerPreset.default;

							if (this.pointDataFilter(filters, point)) {
								this.rows.push(point);
							}
						});
					});
				});

				Promise.allSettled(promises).then((results) => {
					let successful = results
							.filter(r => r.status === 'fulfilled'),
						failed = results
							.filter(r => r.status === 'rejected');
					if (failed.length > 0) {
						failed.forEach((r, i) => {
							console.warn(`Error ${i + 1}:`, r.reason);
						});
					}
					if (successful.length > 0 && this.rows.length > 0) {
						let providers = new Set(this.rows.map(p => p.providerKey));
						this.providers = providers;
						this.activeProviders = providers;
						resolve();
					} else {
						if (successful.length === 0) {
							reject('All getPoints requests failed');
						} else {
							reject(Joomla.Text._('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_NO_PICKUP_POINTS'));
						}
					}
				});
			}).catch((e) => {
				reject(e?.message || 'Ajax Error');
			});
		});
	}

	pointDataFilter(filters, point) {
		// Check CoD
		if (!point.hasOwnProperty('cod')) {
			point.cod = 1;
		}
		point.cod = (parseInt(point.cod, 10) === 1);
		if (filters.cod && !point.cod) {
			return false;
		}

		// Check limits
		if (!point.hasOwnProperty('limits') || !point.limits) {
			point.limits = {};
		}
		if (!filters.package || !point.limits) {
			return true;
		}

		let filter_package = {
				sizeA: Number(filters.package.sizeA) || 0,
				sizeB: Number(filters.package.sizeB) || 0,
				sizeC: Number(filters.package.sizeC) || 0,
				weight: Number(filters.package.weight) || 0
			},
			point_limit = {
				maxSizeA: point.limits.maxSizeA != null ? Number(point.limits.maxSizeA) : null,
				maxSizeB: point.limits.maxSizeB != null ? Number(point.limits.maxSizeB) : null,
				maxSizeC: point.limits.maxSizeC != null ? Number(point.limits.maxSizeC) : null,
				maxSizeSum: point.limits.maxSizeSum != null ? Number(point.limits.maxSizeSum) : null,
				minWeight: point.limits.minWeight != null ? Number(point.limits.minWeight) : null,
				maxWeight: point.limits.maxWeight != null ? Number(point.limits.maxWeight) : null,
				maxVolume: point.limits.maxVolume != null ? Number(point.limits.maxVolume) : null
			};

		// Check weight
		if (point_limit.minWeight != null && filter_package.weight < point_limit.minWeight) {
			return false;
		}
		if (point_limit.maxWeight != null && filter_package.weight > point_limit.maxWeight) {
			return false;
		}

		// Size sum
		if (point_limit.maxSizeSum != null) {
			let sum = filter_package.sizeA + filter_package.sizeB + filter_package.sizeC;
			if (sum > point_limit.maxSizeSum) {
				return false;
			}
		}

		// Check volume
		if (point_limit.maxVolume != null) {
			let volume = filter_package.sizeA * filter_package.sizeB * filter_package.sizeC;
			if (volume > point_limit.maxVolume) {
				return false;
			}
		}

		// Check sizes
		let filter_package_dimensions = [filter_package.sizeA, filter_package.sizeB, filter_package.sizeC]
				.sort((a, b) => a - b),
			infinity = Number.POSITIVE_INFINITY,
			point_limit_dimensions = [
				point_limit.maxSizeA == null ? infinity : point_limit.maxSizeA,
				point_limit.maxSizeB == null ? infinity : point_limit.maxSizeB,
				point_limit.maxSizeC == null ? infinity : point_limit.maxSizeC
			].sort((a, b) => a - b);
		for (let i = 0; i < 3; i++) {
			if (filter_package_dimensions[i] > point_limit_dimensions[i]) {
				return false;
			}
		}

		return true;
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
				input = ElementsUtils.getElementByAttribute(this.attribute, 'input_' + to, this.container);
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

		this.showPoints().then();
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
		ElementsUtils.getElementsByAttribute('radicalmart-shipping-apiship-field-points', 'container')
			.forEach((container) => {
				container.FieldClass = new RadicalMartShippingApiShipFieldPoints(container);
			});
	});
});