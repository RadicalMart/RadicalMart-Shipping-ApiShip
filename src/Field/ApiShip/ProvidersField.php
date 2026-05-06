<?php
/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2026 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

namespace Joomla\Plugin\RadicalMartShipping\ApiShip\Field\ApiShip;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Helper\ApiShipHelper;

class ProvidersField extends ListField
{
	/**
	 * Available providers keys.
	 *
	 * @var  array
	 *
	 * @since  1.0.0
	 */
	protected array $available = [];

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
			$this->available = (!empty($this->element['available']))
				? explode(',', (string) $this->element['available']) : $this->available;

		}

		return $return;
	}

	/**
	 * Method to get the field options.
	 *
	 * @throws  \Exception
	 *
	 * @return  array  The field option objects.
	 *
	 * @since  1.0.0
	 */
	protected function getOptions(): array
	{
		// Prepare options
		$options = parent::getOptions();

		foreach (ApiShipHelper::$providers as $provider)
		{
			if (count($this->available) > 0 && !in_array($provider, $this->available))
			{
				continue;
			}

			$option        = new \stdClass();
			$option->value = $provider;
			$option->text  = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' . $provider);

			$options[] = $option;
		}

		return $options;
	}
}