/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

"use strict";

"use strict";

class JoomlaAjaxUtil {
	constructor(extension = 'Joomla') {
		this.extension_selector = extension.toLowerCase().replace(' ', '_');
		this.extension_name = extension;
		this.options = null;
		this.controller = null;
		this.csrf = null;
	}

	setVariable(key, value) {
		this[key] = value;
	}

	getVariable(key, defaultValue = null) {
		return (!this[key] || this[key] === null) ? defaultValue : this[key];
	}

	triggerEvent(name = null, data = null) {
		if (name) {
			console.debug(this.extension_name + ' Triggered:' + name);
			document.dispatchEvent(new CustomEvent(name, {detail: data}));
		}
	}

	sendAjax(task = null, data = {}, csrfCache = false) {
		return new Promise((success, error) => {
			if (task === null) {
				return error({message: 'Task is empty'});
			}

			let controller = this.controller;
			if (!controller) {
				return error({message: 'Controller not found'});
			}

			let isFormData = (data instanceof FormData),
				formData = (isFormData) ? data : new FormData();
			if (!isFormData) {
				formData = this.objectToFormData(data, formData);
			}
			formData.set('task', task);

			let csrf = this.getVariable('csrf', false);
			if (!csrfCache || !csrf) {
				this.getCSRF().then((csrf) => {
					formData.set(csrf, '1');
					this.sendRequest(controller, formData)
						.then((s) => success(s))
						.catch((e) => error(e))
				}).catch((e) => {
					if (e.message === 'Request aborted' || e.message === null || e.message === '') {
						console.error('aborted');
					} else {
						return error(e);
					}
				});
			} else {
				formData.set(csrf, '1');
				this.sendRequest(controller, formData)
					.then((s) => success(s))
					.catch((e) => error(e))
			}

		});
	}

	objectToFormData(data = {}, formData = null, path = null) {
		if (formData === null) {
			formData = new FormData();
		}

		if (path === null) {
			path = '';
		}

		Object.keys(data).forEach((key) => {
			let name = (path) ? path + '[' + key + ']' : key,
				value = data[key];
			if (Array.isArray(value)) {
				value.forEach((val) => {
					formData.append(name + '[]', val)
				})
			} else if (typeof value === 'object') {
				formData = this.objectToFormData(value, formData, name);
			} else {
				formData.set(name, value);
			}
		});

		return formData;
	}

	sendRequest(controller, formData) {
		return new Promise((success, error) => {
			Joomla.request({
				url: controller,
				data: formData,
				method: 'POST',
				onSuccess: (response) => {
					try {
						response = JSON.parse(response);
						if (response.success) {
							return success(response.data);
						} else {
							return error({message: response.message});
						}
					} catch (je) {
						return error(je);
					}
				},
				onError: (e) => {
					let errorObject = this.parseJoomlaRequestError(e);
					if (errorObject) {
						return error(errorObject);
					}
				}
			});
		});
	}

	getCSRF() {
		return new Promise((success, error) => {
			let controller = this.controller;
			if (!controller) {
				return error({message: 'Controller not found'});
			}

			let formData = new FormData();
			formData.set('task', 'getCSRF');
			formData.set('check_post', '1');
			Joomla.request({
				url: controller,
				data: formData,
				method: 'POST',
				onSuccess: (response) => {
					try {
						response = JSON.parse(response);
						if (response.success) {
							let token = response.data;
							if (token) {
								success(token);
								this.setVariable('csrf', token);
							} else {
								return error({message: 'Token not found'});
							}
						} else {
							return error({message: response.message});
						}
					} catch (je) {
						return error(je);
					}
				},
				onError: (e) => {
					let errorObject = this.parseJoomlaRequestError(e);
					if (errorObject) {
						return error(errorObject);
					}
				}
			});
		});
	}

	parseJoomlaRequestError(error) {
		if (error instanceof XMLHttpRequest) {
			if (error.status === 0) {

				console.error('aborted');
				return false;
			}

			let message;
			if (error.response) {
				let responseElement = document.createElement('div');
				responseElement.innerHTML = error.response;
				if (responseElement) {
					responseElement = responseElement.querySelector('title');
					if (responseElement) {
						message = responseElement.textContent;
					}
				}
			} else {
				message = error.status + ' ' + error.statusText;
			}

			return {'message': message};
		}

		if (typeof error === 'object') {
			if (error.message === 'Request aborted'
				|| error.message === null
				|| error.message === ''
				|| error.message === 0
				|| error.message === '0') {

				console.error('aborted');
				return false;
			}
		} else {
			return error;
		}
	}

	appendLayout(context, name, html) {
		let attribute = this.extension_selector + '-' + context + '-layout',
			selector = '[' + attribute + '="' + name + '"]',
			exists = document.querySelectorAll(selector);

		// Remove old
		if (exists.length > 0) {
			exists.forEach((exist) => exist.remove());
		}

		// Append new element
		let newElement = document.createElement('div');
		newElement.innerHTML = html;
		newElement.setAttribute(attribute, name);
		newElement.setAttribute('data-main', '1');
		document.body.appendChild(newElement);

		// Move scripts
		let layout = document.querySelector(selector + '[data-main="1"]');
		layout.querySelectorAll('script').forEach((element) => {
			let script = document.createElement('script');
			script.setAttribute(attribute, name);
			script.textContent = element.textContent;
			document.body.appendChild(script);
			element.remove();
		});

		return layout;
	}

	insertDisplayData(context, data) {
		[
			this.extension_selector + '-' + context + '-display',
			'data-' + this.extension_selector + '-' + context + '-display',
		].forEach((attribute) => {
			document.querySelectorAll('[' + attribute + ']').forEach((element) => {
				let path = element.getAttribute(attribute).split('.'),
					content = JSON.parse(JSON.stringify(data));
				path.forEach((key) => {
					if (content && content[key]) {
						content = content[key];
					} else {
						content = false;
					}
				});

				if (content && (typeof content === 'number' || typeof content === 'string')) {
					element.textContent = content;
				} else {
					element.textContent = '';
				}
			});
		});
	}

	createHash(object) {
		let string = JSON.stringify(object),
			hash = 0;
		for (let i = 0; i < string.length; i++) {
			const char = string.charCodeAt(i);
			hash = (hash << 5) - hash + char;
			hash = hash & hash;
		}
		return hash;
	}
}

export default JoomlaAjaxUtil;