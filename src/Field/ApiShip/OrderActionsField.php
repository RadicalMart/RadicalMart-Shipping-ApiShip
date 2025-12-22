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

class OrderActionsField extends FormField
{
	/**
	 * Name of the layout being used to render the field.
	 *
	 * @var  string
	 *
	 * @since  1.0.0
	 */
	protected $layout = 'plugins.radicalmart_shipping.apiship.administrator.order.actions';

	/**
	 * The Order id.
	 *
	 * @var int
	 *
	 * @since 1.0.0
	 */
	protected int $order_id = 0;

	/**
	 * The action context for callback.
	 *
	 * @var string
	 *
	 * @since 1.0.0
	 */
	protected string $context = 'plg_radicalmart_shipping.apiship.actions';

	/**
	 * The buttons array.
	 *
	 * @var array
	 *
	 * @since 1.0.0
	 */
	protected array $buttons = ['create', 'update_status', 'cancel'];

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
			$this->order_id = (!empty($this->element['order_id'])) ? (int) $this->element['order_id'] : $this->order_id;
			$this->context  = (!empty($this->element['context'])) ? (string) $this->element['context'] : $this->context;
			$this->buttons  = (!empty($this->element['buttons'])) ? explode(',', (string) $this->element['buttons'])
				: $this->buttons;
		}

		return $return;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since  1.0.0
	 */
	public function getInput(): string
	{
		if (empty($this->order_id))
		{
			return '';
		}

		return parent::getInput();
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
		$data             = parent::getLayoutData();
		$data['order_id'] = $this->order_id;
		$data['context']  = $this->context;
		$data['buttons']  = $this->buttons;

		return $data;
	}
}