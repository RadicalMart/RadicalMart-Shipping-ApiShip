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

class StatusesField extends ListField
{
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

		foreach (ApiShipHelper::$statuses as $status)
		{

			$option        = new \stdClass();
			$option->value = $status;
			$option->text  = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_STATUS_' . $status);

			$options[] = $option;
		}

		return $options;
	}
}