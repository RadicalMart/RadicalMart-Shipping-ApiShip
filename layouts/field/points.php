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

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Extension\ApiShip;
use Joomla\Utilities\ArrayHelper;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string                   $autocomplete   Autocomplete attribute for the field.
 * @var   bool                     $autofocus      Is autofocus enabled?
 * @var   string                   $class          Classes for the input.
 * @var   string                   $description    Description of the field.
 * @var   bool                     $disabled       Is this field disabled?
 * @var   string                   $group          Group the field belongs to. <fields> section in form XML.
 * @var   bool                     $hidden         Is this field hidden in the form?
 * @var   string                   $hint           Placeholder for the field.
 * @var   string                   $id             DOM id of the field.
 * @var   string                   $label          Label of the field.
 * @var   string                   $labelclass     Classes to apply to the label.
 * @var   bool                     $multiple       Does this field support multiple values?
 * @var   string                   $name           Name of the input field.
 * @var   string                   $onchange       Onchange attribute for the field.
 * @var   string                   $onclick        Onclick attribute for the field.
 * @var   string                   $pattern        Pattern (Reg Ex) of value of the form field.
 * @var   bool                     $readonly       Is this field read only?
 * @var   bool                     $repeat         Allows extensions to duplicate elements.
 * @var   bool                     $required       Is this field required?
 * @var   int                      $size           Size attribute of the input.
 * @var   bool                     $spellcheck     Spellcheck state for the form field.
 * @var   string                   $validate       Validation rules to apply.
 * @var   array                    $value          Value attribute of the field.
 * @var   array                    $checkedOptions Options that will be set as checked.
 * @var   bool                     $hasValue       Has this field a value assigned?
 * @var   array                    $options        Options available for this field.
 *
 * Field specific variables
 * @var  string|null               $context        Context selector string.
 * @var  int                       $shipping       Shipping method id.
 * @var  \Joomla\Registry\Registry $shippingParams Available Operation Filter.
 * @var  string|null               $map_key        Yandex.Map key.
 * @var  string|null               $map_error      Yandex.Map empty key message.
 */

$map_key = $shippingParams->get('points_map_key');

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
		'//api-maps.yandex.ru/2.1/?lang=ru-RU&apikey=' . $map_key)
		->useScript('plg_radicalmart_shipping_apiship.fields.points')
		->addInlineStyle('
			#' . $id . '_map {
			width: 100%;
			height: 400px;
			background: #e5e5e5;
		}
		#' . $id . ' [class*="gotoymaps"],
		#' . $id . ' [class*="gototech"] {
			display: none !important;
		}#' . $id . ' ymaps[class*="-image"] {
			background-repeat: no-repeat !important;
		}
	');

	$clusterIcons = [];
	foreach ([56, 72] as $size)
	{
		$offset         = ($size / 2) * -1;
		$clusterIcons[] = [
			'size'   => [$size, $size],
			'offset' => [$offset, $offset],
		];
	}

	$providers = array_keys($shippingParams->get('sender', []));

	$document->addScriptOptions($id, [
		'id'            => $id,
		'name'          => $name,
		'value'         => $value,
		'shipping'      => $shipping,
		'providers'     => $providers,
		'markers'       => ApiShip::getMapMarkers(),
		'markerPreset'  => [
			'size'   => [32, 42],
			'offset' => [-16, -42],
		],
		'clusterPreset' => ['preset' => 'islands#invertedNightClusterIcons', 'icons' => $clusterIcons],
		'itemTemplate'  => LayoutHelper::render('plugins.radicalmart_shipping.apiship.field.points.item')
	]);
}
?>
<div>
	<?php if (empty($map_key)): ?>
		<div class="alert alert-danger">
			<?php echo Text::_($map_error); ?>
		</div>
	<?php else: ?>
		<div id="<?php echo $id; ?>" radicalmart-shipping-apiship-field-points="container">
			<div radicalmart-shipping-apiship-field-points="error" class="uk-alert uk-alert-danger"
				 style="display: none">
			</div>
			<div class="uk-position-relative">
				<div radicalmart-shipping-apiship-field-points="filters"
					 class="uk-position-top-right uk-position-z-index uk-position-small"
					 data-class_default="uk-button-danger"
					 data-class_active="uk-button-primary"
					 style="display: none">
					<?php foreach ($providers as $provider): ?>
						<button type="button" data-provider-key="<?php echo $provider; ?>"
								class="uk-button uk-button-primary uk-button-small">
							<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' . $provider); ?>
						</button>
					<?php endforeach; ?>
				</div>
				<div radicalmart-shipping-apiship-field-points="loading"
					 class="uk-position-cover uk-flex uk-flex-center uk-flex-middle uk-overlay-default uk-position-z-index">
					<div uk-spinner="ratio: 3"></div>
				</div>
				<div radicalmart-shipping-apiship-field-points="panel"
					 class="uk-card uk-card-default uk-padding-small uk-width-medium uk-position-absolute uk-position-right uk-position-z-index uk-overflow-auto"
					 style="display: none;">
				</div>
				<div id="<?php echo $id . '_map'; ?>" radicalmart-shipping-apiship-field-points="map"></div>
			</div>

			<div class="uk-margin-small-bottom mb-3">
				<?php foreach (['id', 'title', 'address', 'latitude', 'longitude', 'countryCode', 'providerKey', 'display'] as $key)
				{
					$attributes = [
						'id'    => $id . '_' . $key,
						'name'  => $name . '[' . $key . ']',
						'type'  => 'hidden',
						'class' => (!empty($class)) ? 'form-control uk-input ' . $class : 'form-control uk-input',
						'value' => (!empty($value[$key])) ? $value[$key] : '',

						'radicalmart-shipping-apiship-field-points' => 'input_' . $key,
					];

					if ($key === 'address')
					{
						if (!empty($onchange))
						{
							$attributes['onchange'] = $onchange;
						}
						if (!empty($required))
						{
							$attributes['required'] = $required;
						}
					}
					elseif ($key === 'display' && $hint)
					{
						$attributes['placeholder'] = $hint;
					}

					echo '<input ' . ArrayHelper::toString($attributes) . '>';
				} ?>
			</div>
		</div>
	<?php endif; ?>
</div>

