<?php
/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2025 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

namespace Joomla\Plugin\RadicalMartShipping\ApiShip\Field\ApiShip;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\Component\RadicalMart\Administrator\Helper\ParamsHelper;

class PointsField extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $type = 'ApiShip_Points';

	/**
	 * Name of the layout being used to render the field.
	 *
	 * @var    string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $layout = 'plugins.radicalmart_shipping.apiship.field.points';

	/**
	 * Context selector string.
	 *
	 * @var  string|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected ?string $context = null;

	/**
	 * Shipping method id.
	 *
	 * @var  int|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected ?int $shipping = null;

	/**
	 * Yandex.Map key.
	 *
	 * @var  string|null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected ?string $map_key = null;

	/**
	 * Yandex.Map empty key message.
	 *
	 * @var  string|null
	 *
	 * @since  __DEPLOY_VERSION__
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
	 * @since  __DEPLOY_VERSION__
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null): bool
	{
		if ($return = parent::setup($element, $value, $group))
		{
			$this->shipping  = (!empty($this->element['shipping'])) ? (int) $this->element['shipping'] : $this->shipping;
			$this->context   = (!empty($this->element['context'])) ? (string) $this->element['context']
				: $this->context;
			$this->map_key   = (!empty($this->element['map_key'])) ? (trim((string) $this->element['map_key']))
				: $this->map_key;
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
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getLayoutData(): array
	{
		$data                   = parent::getLayoutData();
		$data['shipping']       = $this->shipping;
		$data['shippingParams'] = ParamsHelper::getShippingMethodsParams($this->shipping);
		$data['context']        = $this->context;
		$data['map_key']        = $this->map_key;
		$data['map_error']      = $this->map_error;

		return $data;
	}
}