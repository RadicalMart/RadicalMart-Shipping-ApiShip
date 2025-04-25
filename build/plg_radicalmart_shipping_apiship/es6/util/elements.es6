/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2025 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
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
	}
}