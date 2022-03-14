<?php
/*
 * @package     RadicalMart Package
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2022 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;

class JFormFieldApiShip_Points extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $type = 'apiship_points';

	/**
	 * Name of the layout being used to render the field.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $layout = 'plugins.radicalmart_shipping.apiship.field.points';

	/**
	 * Shipping method id.
	 *
	 * @var  int
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $shipping = null;

	/**
	 * Available Operation Filter.
	 *
	 * @var  string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $operation = null;

	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		if ($return = parent::setup($element, $value, $group))
		{
			$this->shipping  = (!empty($this->element['shipping'])) ? (int) $this->element['shipping'] : $this->shipping;
			$this->operation = (!empty($this->element['operation'])) ? (string) $this->element['operation'] : $this->operation;
		}

		$this->multiple = true;

		return $return;
	}

	/**
	 * Method to get the data to be passed to the layout for rendering.
	 *
	 * @return  array Layout data array.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getLayoutData()
	{
		$data              = parent::getLayoutData();
		$data['shipping']  = $this->shipping;
		$data['operation'] = $this->operation;

		return $data;
	}
}
