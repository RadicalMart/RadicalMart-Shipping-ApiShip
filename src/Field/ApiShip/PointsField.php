<?php
/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     1.0.0
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

namespace Joomla\Plugin\RadicalMartShipping\ApiShip\Field\ApiShip;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Extension\ApiShip;

class PointsField extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 *
	 * @since  1.0.0
	 */
	protected $type = 'ApiShip_Points';

	/**
	 * Name of the layout being used to render the field.
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
	protected $layout = 'plugins.radicalmart_shipping.apiship.field.points';

	/**
	 * Shipping method id.
	 *
	 * @var  int
	 *
	 * @since  1.0.0
	 */
	protected int $shipping = 0;

	/**
	 * Field context for get tariffs request.
	 *
	 * @var string|null
	 *
	 * @since 1.0.0
	 */
	protected ?string $context = null;

	/**
	 * Field context for get tariffs request.
	 *
	 * @var int
	 *
	 * @since 1.0.0
	 */
	protected int $order_id = 0;

	/**
	 * Yandex.Map empty key message.
	 *
	 * @var  string|null
	 *
	 * @since  1.0.0
	 */
	protected ?string $map_error = null;

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
			$this->shipping  = (!empty($this->element['shipping'])) ? (int) $this->element['shipping'] : $this->shipping;
			$this->context   = (!empty($this->element['context'])) ? (string) $this->element['context'] : $this->context;
			$this->order_id  = (!empty($this->element['order_id'])) ? (int) $this->element['order_id'] : $this->order_id;
			$this->map_error = (!empty($this->element['map_error'])) ? (trim((string) $this->element['map_error']))
				: 'PLG_RADICALMART_SHIPPING_APISHIP_ERROR_MAP_KEY';
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
		$data                   = parent::getLayoutData();
		$data['shipping']       = $this->shipping;
		$data['context']        = $this->context;
		$data['order_id']       = $this->order_id;
		$data['shippingParams'] = ApiShip::getShippingMethodParams($this->shipping);
		$data['map_error']      = $this->map_error;

		return $data;
	}
}