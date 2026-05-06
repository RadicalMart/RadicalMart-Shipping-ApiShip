<?php
/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     1.0.2
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2026 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

namespace Joomla\Plugin\RadicalMartShipping\ApiShip\WebAsset\AssetItem;

\defined('_JEXEC') or die;

use Joomla\CMS\Document\Document;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\WebAsset\WebAssetAttachBehaviorInterface;
use Joomla\CMS\WebAsset\WebAssetItem;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Helper\ApiShipHelper;

class PointsFieldAssetItem extends WebAssetItem implements WebAssetAttachBehaviorInterface
{
	/**
	 * Method called when asset attached to the Document.
	 *
	 * @param   Document  $doc  Active document.
	 *
	 * @throws \Exception
	 *
	 * @since   1.0.0
	 */
	public function onAttachCallback(Document $doc): void
	{
		$optionsKey = 'plg_radicalmart_shipping_apiship.field.points';
		if (!empty($doc->getScriptOptions($optionsKey)))
		{
			return;
		}

		Factory::getApplication()->getLanguage()->load('plg_radicalmart_shipping_apiship');
		foreach (ApiShipHelper::$providers as $provider)
		{
			Text::script('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' . $provider);
		}
		Text::script('PLG_RADICALMART_SHIPPING_APISHIP_ERROR_NO_PICKUP_POINTS');
		$doc->addScriptOptions($optionsKey, [
			'controller' => Route::_('index.php?option=com_ajax&plugin=apiship&group=radicalmart_shipping&format=json', false)
		]);
	}
}