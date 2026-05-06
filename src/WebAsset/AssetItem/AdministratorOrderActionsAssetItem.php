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
use Joomla\CMS\Router\Route;
use Joomla\CMS\WebAsset\WebAssetAttachBehaviorInterface;
use Joomla\CMS\WebAsset\WebAssetItem;

class AdministratorOrderActionsAssetItem extends WebAssetItem implements WebAssetAttachBehaviorInterface
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
		$optionsKey = 'plg_radicalmart_shipping_apiship.administrator.order.actions';
		if (!empty($doc->getScriptOptions($optionsKey)))
		{
			return;
		}

		Factory::getApplication()->getLanguage()->load('plg_radicalmart_shipping_apiship');
		$doc->addScriptOptions($optionsKey, [
			'controller' => Route::_('index.php?option=com_ajax&plugin=apiship&group=radicalmart_shipping&format=json', false)
		]);
	}
}