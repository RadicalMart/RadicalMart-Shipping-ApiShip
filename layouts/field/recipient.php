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
use Joomla\Utilities\ArrayHelper;

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
		->useScript('plg_radicalmart_shipping_apiship.fields.recipient')
		->addInlineStyle('
			#' . $id . '_map {
				width: 100%;
				height: 400px;
				background: #e5e5e5;
			}
			#' . $id . ' [class*="gotoymaps"],
			#' . $id . ' [class*="gototaxi"],
			#' . $id . ' [class*="gototech"] {
				display: none !important;
			}
		');

	$markerSize = 32;
	$marker     = HTMLHelper::image('plg_radicalmart_shipping_apiship/marker.php', '', '', true, true)
		. '?size=' . $markerSize;
	$selector   = 'radicalmart-shipping-apiship-field-recipient_' . $id;
	$jsOptions  = [
		'id'               => $id,
		'name'             => $name,
		'value'            => $value,
		'valueCoordinates' => (!empty($value) && !empty($value['latitude']) && !empty($value['longitude']))
			? [(float) $value['latitude'], (float) $value['longitude']] : false,
		'marker'           => [
			'point'              => true,
			'draggable'          => true,
			'iconLayout'         => 'default#image',
			'iconImageHref'      => $marker . '&color=147247',
			'iconImageSize'      => [$markerSize, $markerSize],
			'iconImageOffset'    => [($markerSize / 2) * -1, $markerSize * -1],
			'openBalloonOnClick' => false,
		]
	];
	$document->addScriptOptions($selector, $jsOptions);
	$suggest = [
		'id'          => $id . '_suggest',
		'class'       => (!empty($class)) ? 'form-control uk-input ' . $class : 'form-control uk-input',
		'value'       => (!empty($value['address'])) ? $value['address'] : '',
		'placeholder' => (!empty($hint)) ? $hint : '',
	];
}
?>
<div>
	<?php if (empty($map_key)): ?>
		<div class="alert alert-danger">
			<?php echo Text::_($map_error); ?>
		</div>
	<?php else: ?>
		<div id="<?php echo $id; ?>" radicalmart-shipping-apiship-field="recipient"
			 data-selector="<?php echo $selector; ?>">
			<div class="uk-margin-small-bottom mb-3">
				<input type="text" <?php echo ArrayHelper::toString($suggest); ?>>
			</div>
			<div id="<?php echo $id . '_map'; ?>"></div>
			<?php foreach (['address', 'countryCode', 'latitude', 'longitude'] as $key)
			{
				echo LayoutHelper::render('joomla.form.field.hidden', [
					'id'       => $id . '_' . $key,
					'name'     => $name . '[' . $key . ']',
					'value'    => (!empty($value[$key])) ? $value[$key] : '',
					'onchange' => ($key === 'address' && !empty($onchange)) ? $onchange : false,
					'required' => ($key === 'address' && !empty($required)) ? $required : false,
				]);
			} ?>
		</div>
	<?php endif; ?>
</div>

