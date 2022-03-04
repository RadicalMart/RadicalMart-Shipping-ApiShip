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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  string $class       Classes for the input.
 * @var  string $id          DOM id of the field.
 * @var  string $name        Name of the input field.
 * @var  array  $value       Value attribute of the field.
 * @var  bool   $hiddenLabel Hide field label.
 *
 */

$apikey = ComponentHelper::getParams('com_radicalmart')->get('shipping_apiship_yandexmap_key');
HTMLHelper::script('//api-maps.yandex.ru/2.1/?lang=ru-RU&apikey=' . $apikey, array('version' => 'auto'));

HTMLHelper::script('plg_radicalmart_shipping_apiship/field-places.min.js', array('version' => 'auto', 'relative' => true));
HTMLHelper::stylesheet('plg_radicalmart_shipping_apiship/field-places.min.css', array('version' => 'auto', 'relative' => true));

$markerSize = 32;
$marker     = HTMLHelper::image('plg_radicalmart_shipping_apiship/marker.php', '', '', true, true)
	. '?size=' . $markerSize;

$jsOptions = array(
	'iconImageHref'       => $marker . '&color=fa1f0c',
	'iconImageSize'       => array($markerSize, $markerSize),
	'iconImageOffset'     => array(($markerSize / 2) * -1, $markerSize * -1),
	'iconActiveImageHref' => $marker . '&color=147247',
	'markers'             => false,
	'hiddenLabel'         => $hiddenLabel
);

if (!empty($value))
{
	foreach ($value as $key => $row)
	{
		$jsOptions['markers'][$key] = array((float) $row['latitude'], (float) $row['longitude']);
		$jsOptions['center']        = false;
		$jsOptions['zoom']          = false;
	}
}
Factory::getDocument()->addScriptOptions($id, $jsOptions);
?>
<div id="<?php echo $id; ?>" input-apiship-places="container" data-mode="edit"
	 class="well well-small">
	<div input-apiship-places="template" style="display: none">
		<?php echo LayoutHelper::render('plugins.radicalmart_shipping.apiship.field.places.row',
			array('id' => $id, 'name' => $name)); ?>
	</div>
	<div class="row-fluid">
		<div class="span7">
			<div id="<?php echo $id . '_map'; ?>" input-apiship-places="map">
				<span class="btn btn-success" input-apiship-places="action-add">
					<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_ACTION_ADD'); ?>
				</span>
				<span class="btn btn-danger" input-apiship-places="action-cancel_add">
					<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_ACTION_CANCEL_ADD'); ?>
				</span>
				<span class="btn btn-primary" input-apiship-places="action-show_all">
					<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_ACTION_SHOW_ALL'); ?>
				</span>
			</div>
		</div>
		<div class="fields-overlay span5">
			<div class="uk-text-center uk-visible@s">
				<div input-apiship-places="message-no_select_items" class="text-warning" style="display: none">
					<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_MESSAGE_NO_SELECT_ITEMS'); ?>
				</div>
				<div input-apiship-places="message-no_items" class="text-error" style="display: none">
					<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_MESSAGE_NO_ITEMS'); ?>
				</div>
				<div input-apiship-places="message-add" class="text-success" style="display: none">
					<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_MESSAGE_ADD'); ?>
				</div>
				<div input-apiship-places="message-save_loading" class="muted" style="display: none">
					<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_MESSAGE_SAVE_LOADING'); ?>
				</div>
				<div input-apiship-places="message-save_success" class="alert alert-success" style="display: none">
					<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_MESSAGE_SAVE_SUCCESS'); ?>
				</div>
				<div input-apiship-places="message-save_error" class="alert alert-error" style="display: none">
				</div>
			</div>
			<div input-apiship-places="form">
				<?php if (!empty($value))
				{
					foreach ($value as $row)
					{
						echo LayoutHelper::render('plugins.radicalmart_shipping.apiship.field.places.row',
							array('id' => $id, 'name' => $name, 'value' => $row));
					}
				} ?>
			</div>
		</div>
	</div>
</div>