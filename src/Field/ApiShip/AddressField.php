<?php
/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

namespace Joomla\Plugin\RadicalMartShipping\ApiShip\Field\ApiShip;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Extension\ApiShip;

class AddressField extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 *
	 * @since  1.0.0
	 */
	protected $type = 'ApiShip_Addresses';

	/**
	 * Name of the layout being used to render the field.
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
	protected $layout = 'plugins.radicalmart_shipping.apiship.field.address';

	/**
	 * Shipping method id.
	 *
	 * @var  int
	 *
	 * @since  1.0.0
	 */
	protected int $shipping = 0;

	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value.
	 *
	 * @throws  \Exception
	 *
	 * @return  bool  True on success.
	 *
	 * @since  1.0.0
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null): bool
	{
		if ($return = parent::setup($element, $value, $group))
		{
			$this->shipping = (!empty($this->element['shipping'])) ? (int) $this->element['shipping'] : $this->shipping;
		}

		return $return;
	}

	/**
	 * Method to get the data to be passed to the layout for rendering.
	 *
	 * @throws  \Exception
	 *
	 * @return  array Layout data array.
	 *
	 * @since  1.0.0
	 */
	protected function getLayoutData(): array
	{
		$shippingParams         = ApiShip::getShippingMethodParams($this->shipping);
		$data                   = parent::getLayoutData();
		$data['shipping']       = $this->shipping;
		$data['shippingParams'] = $shippingParams;

		$value          = (!empty($data['value'])) ? $data['value'] : [];
		$fields_default = $shippingParams->get('fields_default', []);
		foreach (array_keys(ApiShip::$defaultAddressFieldsParams) as $key)
		{
			if (!empty($value[$key]))
			{
				continue;
			}
			$value[$key] = (!empty($fields_default[$key])) ? $fields_default[$key] : '';
		}
		$data['value'] = $value;

		return $data;
	}
}