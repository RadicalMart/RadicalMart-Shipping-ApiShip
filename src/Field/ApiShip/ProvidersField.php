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

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Helper\ApiShipHelper;

class ProvidersField extends ListField
{
	/**
	 * Method to get the field options.
	 *
	 * @throws  \Exception
	 *
	 * @return  array  The field option objects.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function getOptions(): array
	{
		// Prepare options
		$options = parent::getOptions();

		foreach (ApiShipHelper::$providers as $provider)
		{
			$option        = new \stdClass();
			$option->value = $provider;
			$option->text  = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' . $provider);

			$options[] = $option;
		}

		return $options;
	}
}