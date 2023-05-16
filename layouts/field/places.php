<?php
/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2023 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

\defined('_JEXEC') or die;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string     $autocomplete   Autocomplete attribute for the field.
 * @var   bool       $autofocus      Is autofocus enabled?
 * @var   string     $class          Classes for the input.
 * @var   string     $description    Description of the field.
 * @var   bool       $disabled       Is this field disabled?
 * @var   string     $group          Group the field belongs to. <fields> section in form XML.
 * @var   bool       $hidden         Is this field hidden in the form?
 * @var   string     $hint           Placeholder for the field.
 * @var   string     $id             DOM id of the field.
 * @var   string     $label          Label of the field.
 * @var   string     $labelclass     Classes to apply to the label.
 * @var   bool       $multiple       Does this field support multiple values?
 * @var   string     $name           Name of the input field.
 * @var   string     $onchange       Onchange attribute for the field.
 * @var   string     $onclick        Onclick attribute for the field.
 * @var   string     $pattern        Pattern (Reg Ex) of value of the form field.
 * @var   bool       $readonly       Is this field read only?
 * @var   bool       $repeat         Allows extensions to duplicate elements.
 * @var   bool       $required       Is this field required?
 * @var   int        $size           Size attribute of the input.
 * @var   bool       $spellcheck     Spellcheck state for the form field.
 * @var   string     $validate       Validation rules to apply.
 * @var   array      $value          Value attribute of the field.
 * @var   array      $checkedOptions Options that will be set as checked.
 * @var   bool       $hasValue       Has this field a value assigned?
 * @var   array      $options        Options available for this field.
 *
 * Field specific variables
 * @var  string|null $map_key        Yandex.Map key.
 * @var  string|null $map_error      Yandex.Map empty key message.
 */

// Load assets
if (!empty($map_key))
{
	/** @var \Joomla\CMS\Document\Document $document */
	$document = Factory::getApplication()->getDocument();

	/** @var \Joomla\CMS\WebAsset\WebAssetManager $assets */
	$assets         = $document->getWebAssetManager();
	$assetsRegistry = $assets->getRegistry();

	$assetsRegistry->addExtensionRegistryFile('plg_radicalmart_shipping_apiship');
	$assets->registerAndUseScript('plg_radicalmart_shipping_apiship.map',
		'//api-maps.yandex.ru/2.1/?lang=ru-RU&apikey=' . $map_key, [])
		->usePreset('plg_radicalmart_shipping_apiship.fields.places');

	$markerSize = 32;
	$marker     = HTMLHelper::image('plg_radicalmart_shipping_apiship/marker.php', '',
			'', true, true) . '?size=' . $markerSize;

	$jsOptions = [
		'iconImageHref'       => $marker . '&color=fa1f0c',
		'iconImageSize'       => [$markerSize, $markerSize],
		'iconImageOffset'     => [($markerSize / 2) * -1, $markerSize * -1],
		'iconActiveImageHref' => $marker . '&color=147247',
		'markers'             => false,
		'hiddenLabel'         => $hiddenLabel
	];

	if (!empty($value))
	{
		foreach ($value as $key => $item)
		{
			$jsOptions['markers'][$key] = [(float) $item['latitude'], (float) $item['longitude']];
			$jsOptions['center']        = false;
			$jsOptions['zoom']          = false;
		}
	}

	$document->addScriptOptions($id, $jsOptions);
}
?>
<div>
	<?php if (empty($map_key)): ?>
		<div class="alert alert-danger">
			<?php echo Text::_($map_error); ?>
		</div>
	<?php else: ?>
		<div id="<?php echo $id; ?>" radicalmart-shipping-apiship-field-places="container" data-mode="edit"
			 class="card">
			<div radicalmart-shipping-apiship-field-places="template" style="display: none">
				<?php echo LayoutHelper::render('plugins.radicalmart_shipping.apiship.field.places.row',
					['id' => $id, 'name' => $name]); ?>
			</div>

			<div class="row g-1">
				<div class="col-lg-8 col-xl-7">
					<div id="<?php echo $id . '_map'; ?>" radicalmart-shipping-apiship-field-places="map"
						 class="bg-light">
						<span class="btn btn-success" radicalmart-shipping-apiship-field-places="action-add">
							<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_ACTION_ADD'); ?>
						</span>
						<span class="btn btn-danger" radicalmart-shipping-apiship-field-places="action-cancel_add">
							<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_ACTION_CANCEL_ADD'); ?>
						</span>
						<span class="btn btn-primary" radicalmart-shipping-apiship-field-places="action-show_all">
							<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_ACTION_SHOW_ALL'); ?>
						</span>
					</div>
				</div>
				<div class="fields-overlay col-lg-4 col-xl-5">
					<div class="text-center p-2">
						<div radicalmart-shipping-apiship-field-places="message-no_select_items" class="text-warning"
							 style="display: none">
							<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_MESSAGE_NO_SELECT_ITEMS'); ?>
						</div>
						<div radicalmart-shipping-apiship-field-places="message-no_items" class="text-error"
							 style="display: none">
							<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_MESSAGE_NO_ITEMS'); ?>
						</div>
						<div radicalmart-shipping-apiship-field-places="message-add" class="text-success"
							 style="display: none">
							<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_MESSAGE_ADD'); ?>
						</div>
					</div>
					<div radicalmart-shipping-apiship-field-places="form">
						<?php if (!empty($value))
						{
							foreach ($value as $item)
							{
								echo LayoutHelper::render('plugins.radicalmart_shipping.apiship.field.places.row',
									['id' => $id, 'name' => $name, 'value' => $item]);
							}
						} ?>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>

