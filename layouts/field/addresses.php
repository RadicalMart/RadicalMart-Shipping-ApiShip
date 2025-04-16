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

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
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
 * @var  int                       $shipping       Shipping method id.
 * @var  \Joomla\Registry\Registry $shippingParams Available Operation Filter.
 * @var   array                    $addresses      Customer addresses array.
 */

$fields = [
	'id'        => '',
	'country'   => 'uk-width-1-1',
	'region'    => 'uk-width-1-3@s',
	'city'      => 'uk-width-2-3@s',
	'zip'       => 'uk-width-1-3@s',
	'street'    => 'uk-width-2-3@s',
	'house'     => 'uk-width-1-4@s',
	'building'  => 'uk-width-1-4@s',
	'entrance'  => 'uk-width-1-4@s',
	'floor'     => 'uk-width-1-4@s',
	'apartment' => 'uk-width-1-4@s',

];

if (empty($value))
{
	$value = ['id' => 'new'];
}

/** @var \Joomla\CMS\Document\Document $document */
$document       = Factory::getApplication()->getDocument();
$assets         = $document->getWebAssetManager();
$assetsRegistry = $assets->getRegistry();
$assetsRegistry->addExtensionRegistryFile('plg_radicalmart_shipping_apiship');
$assets->useScript('plg_radicalmart_shipping_apiship.fields.addresses');

$document->addScriptOptions($id, ['addresses' => $addresses]);
?>

<div id="<?php echo $id; ?>" radicalmart-shipping-apiship-field-addresses="container">
	<div class="uk-grid-small" uk-grid="">
		<?php foreach ($addresses as $address) : ?>
			<div class="uk-width-1-3@s">
				<a class="uk-display-block uk-button uk-button-default uk-padding-small"
				   radicalmart-shipping-apiship-field-addresses="address"
				   data-value="<?php echo $address['id']; ?>">
					<div>
						<?php echo $address['display']; ?>
					</div>
					<?php if ($address['id'] !== 'new') : ?>
						<div>
							<button type="button" class="uk-button uk-button-small uk-button-danger"
									radicalmart-shipping-apiship-field-addresses="change">
								<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_POINTS_ADDRESSES_FIELD_CHANGE'); ?>
							</button>
						</div>
					<?php endif; ?>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
	<div radicalmart-shipping-apiship-field-addresses="form" class="uk-grid-small" uk-grid="" style="display: none">
		<?php foreach ($fields as $key => $column):
			$fieldDisplay = $shippingParams->get('field_' . $key, 'required');
			if ($fieldDisplay === 'hidden')
			{
				$column = 'uk-hidden';
			}
			$fieldValue =

			$attributes = [
				'id'          => $id . '_' . $key,
				'name'        => $name . '[' . $key . ']',
				'type'        => ($fieldDisplay === 'hidden') ? 'hidden' : 'text',
				'class'       => 'form-control uk-input',
				'placeholder' => Text::_('PLG_RADICALMART_SHIPPING_APISHIP_FIELD_' . $key . '_HINT'),

				'radicalmart-shipping-apiship-field-addresses' => 'input_' . $key,
			];
			if ($fieldDisplay === 'required')
			{
				$attributes['required'] = '';
				$attributes['class']    .= ' required';
			}
			?>
			<div class="<?php echo $column; ?>">
				<input <?php echo ArrayHelper::toString($attributes); ?>>
			</div>
		<?php endforeach; ?>
	</div>
</div>
