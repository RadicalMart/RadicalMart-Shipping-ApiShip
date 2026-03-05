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

export const ElementsUtils = {
	getElementByAttribute(attribute, value = null, parent = document, matchType) {
		return parent.querySelector(this.getElementSelectorByAttribute(attribute, value, matchType));
	},

	getElementsByAttribute(attribute, value = null, parent = document, matchType) {
		return parent.querySelectorAll(this.getElementSelectorByAttribute(attribute, value, matchType));
	},

	getClosestByAttribute(attribute, value = null, element = null, matchType = '=') {
		if (!element) {
			return null;
		}
		let result = null,
			selectors = this.getElementSelectorsByAttribute(attribute, value, matchType);
		for (let i = 0; i < selectors.length; i++) {
			const selector = selectors[i];
			result = element.closest(selector);
			if (result) {
				break;
			}
		}

		return result;
	},

	getElementSelectorsByAttribute(attribute, value = null, matchType = '=') {
		let selector = attribute;
		if (value !== null) {
			selector += matchType + '"' + value + '"';
		}

		return [
			'[' + selector + ']',
			'[data-' + selector + ']'
		];
	},

	getElementSelectorByAttribute(attribute, value = null, matchType) {
		return this.getElementSelectorsByAttribute(attribute, value, matchType).join(',');
	},

	getAttributeValue(element, attribute, default_value = null) {
		let result = null;
		if (element) {
			result = element.getAttribute(attribute);
			if (!result) {
				result = element.getAttribute('data-' + attribute);
			}
		}

		return (result) ? result : default_value;
	}
}