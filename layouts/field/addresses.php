<?php
/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
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

$app       = Factory::getApplication();
$language  = $app->getLanguage();
$providers = $shippingParams->get('providers', []);

$fields = [
	'zip'       => 'col-md-3',
	'country'   => 'col-md-6',
	'region'    => 'col-md-4',
	'city'      => 'col-md-8',
	'street'    => 'col-md-8',
	'house'     => 'col-md-2',
	'building'  => 'col-md-2',
	'entrance'  => 'col-md-2',
	'floor'     => 'col-md-2',
	'apartment' => 'col-md-2',

	'uid'     => 'hidden',
	'string'  => 'hidden',
	'display' => 'hidden',
];

if (empty($value))
{
	$value    = ['uid' => 'new'];
	$selected = false;
}
else
{
	$selected = $value['uid'];
}

if (empty($value['provider']) && !empty($providers))
{
	$value['provider'] = $providers[0];
}

// Load assets
/** @var \Joomla\CMS\Document\Document $document */
$document = $app->getDocument();

/** @var \Joomla\CMS\WebAsset\WebAssetManager $assets */
$assets = $document->getWebAssetManager();
$assets->getRegistry()
	->addExtensionRegistryFile('plg_radicalmart_shipping_apiship');

$assets->useScript('plg_radicalmart_shipping_apiship.fields.addresses');
$document->addScriptOptions($id, [
	'shipping'  => $shipping,
	'addresses' => $addresses,
	'selected'  => $selected
]);
?>

<div id="<?php echo $id; ?>" radicalmart-shipping-apiship-field-addresses="container" class="position-relative">
	<div class="row">
		<?php foreach ($addresses as $address) : ?>
			<div class="col-md-4">
				<a radicalmart-shipping-apiship-field-addresses="address"
				   data-value="<?php echo $address['uid']; ?>"
				   class="d-block btn btn-outline-secondary">
					<?php if ($address['uid'] === 'new'): ?>
						<div>
							<span class="icon-plus"></span>
							<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_POINTS_ADDRESSES_FIELD_ADD'); ?>
						</div>
					<?php else: ?>
						<div class="fw-bold">
							<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' . $address['provider']); ?>
						</div>
						<div class="small">
							<?php echo $address['string']; ?>
						</div>
					<?php endif; ?>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
	<div radicalmart-shipping-apiship-field-addresses="error" class="alert alert-danger"
		 style="display: none">
	</div>
	<div radicalmart-shipping-apiship-field-addresses="loading" style="display: none">
		<div class="position-absolute top-0 bottom-0 start-0 end-0 bg-light bg-opacity-75 d-flex justify-content-center align-items-center"
			 style="z-index: 9999">
			<div class="spinner-border text-info" role="status"
				 style="width: 4rem; height: 4rem;"></div>
		</div>
	</div>
	<div radicalmart-shipping-apiship-field-addresses="form" class="row g-2 mt-3 mb-3">
		<div class="col-md-3">
			<?php
			$attributes = [
				'id'    => $id . '_provider',
				'name'  => $name . '[provider]',
				'class' => 'form-select',

				'radicalmart-shipping-apiship-field-addresses' => 'input_provider',
				'data-validate-key'                            => 'validate[provider]',
			];
			?>
			<div class="form-floating">
				<select <?php echo ArrayHelper::toString($attributes); ?>>
					<?php foreach ($providers as $provider) :
						$selected = (!empty($value['provider']) && $value['provider'] === $provider)
							? ' selected="selected"' : '';
						?>
						<option value="<?php echo $provider; ?>" <?php echo $selected; ?>>
							<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' . $provider); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<label for="<?php echo $attributes['id']; ?>">
					<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_FIELD_PROVIDER'); ?>
				</label>
			</div>
		</div>
		<?php foreach ($fields as $key => $column):
			$display = $shippingParams->get('field_' . $key, 'hidden');
			if ($display === 'hidden')
			{
				$column = 'hidden';
			}

			$hint = 'PLG_RADICALMART_SHIPPING_APISHIP_FIELD_' . $key . '_HINT';
			$hint = ($language->hasKey($hint)) ? Text::_($hint) : '';

			$label = 'PLG_RADICALMART_SHIPPING_APISHIP_FIELD_' . $key;
			$label = ($language->hasKey($label)) ? Text::_($label) : $key;

			$attributes = [
				'id'          => $id . '_' . $key,
				'name'        => $name . '[' . $key . ']',
				'type'        => ($display === 'hidden') ? 'hidden' : 'text',
				'class'       => 'form-control',
				'value'       => (!empty($value[$key])) ? $value[$key] : '',
				'placeholder' => $hint,

				'radicalmart-shipping-apiship-field-addresses' => 'input_' . $key,
				'data-validate-key'                            => 'validate[' . $key . ']',
			];

			if ($display === 'required')
			{
				$attributes['required'] = '';
				$attributes['class']    .= ' required';
				$label                  .= ' <span class="text-danger">*</span>';
			}

			if ($key === 'string')
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
			?>
			<div class="<?php echo $column; ?>">
				<div class="form-floating">
					<input <?php echo ArrayHelper::toString($attributes); ?>>
					<label for="<?php echo $attributes['id']; ?>"><?php echo $label; ?></label>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<div>
		<button type="button" class="btn btn-primary"
				radicalmart-shipping-apiship-field-addresses="validate_button" style="display:none">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_POINTS_ADDRESSES_FIELD_VALIDATE'); ?>
		</button>
	</div>
</div>