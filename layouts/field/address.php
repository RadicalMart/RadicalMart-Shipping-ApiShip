<?php
/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     1.0.0
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
 * @var  \Joomla\Registry\Registry $shippingParams Shipping method  params.
 * @var   array                    $address        Customer address array.
 */

$app       = Factory::getApplication();
$language  = $app->getLanguage();
$providers = $shippingParams->get('providers', []);

if (empty($value))
{
	$value = ['uid' => 'new'];
}
if (empty($value['provider']) && !empty($providers))
{
	$value['provider'] = $providers[0];
}

$fields = [
	'country'   => 'control-group',
	'region'    => 'control-group',
	'city'      => 'control-group',
	'zip'       => 'control-group',
	'street'    => 'control-group',
	'house'     => 'control-group',
	'building'  => 'control-group',
	'entrance'  => 'control-group',
	'floor'     => 'control-group',
	'apartment' => 'control-group',
	'uid'       => 'd-none',
	'string'    => 'd-none',
	'display'   => 'd-none',
];

// Load assets
/** @var \Joomla\CMS\Document\Document $document */
$document = $app->getDocument();
$assets = $document->getWebAssetManager();
$assets->getRegistry()
	->addExtensionRegistryFile('plg_radicalmart_shipping_apiship');

$assets->useScript('plg_radicalmart_shipping_apiship.fields.address');
$document->addScriptOptions($id, [
	'shipping' => $shipping
]);
?>

<div id="<?php echo $id; ?>" radicalmart-shipping-apiship-field-address="container" class="position-relative">
	<div radicalmart-shipping-apiship-field-address="error" class="alert alert-danger"
		 style="display: none">
	</div>
	<div radicalmart-shipping-apiship-field-address="loading"
		 style="display: none">
		<div class="position-absolute top-0 bottom-0 start-0 end-0 bg-light bg-opacity-75 d-flex justify-content-center align-items-center"
			 style="z-index: 99999">
			<div class="spinner-border text-info" role="status"
				 style="width: 4rem; height: 4rem;"></div>
		</div>
	</div>
	<div radicalmart-shipping-apiship-field-address="form">
		<?php
		if (empty($providerValue) && !empty($providers))
		{
			$providerValue = $providers[0];
		}
		$attributes = [
			'id'                                         => $id . '_provider',
			'name'                                       => $name . '[provider]',
			'class'                                      => 'form-select',
			'radicalmart-shipping-apiship-field-address' => 'input_provider',
			'data-validate-key'                          => 'validate[provider]',
		];
		?>
		<div class="control-group">
			<div class="control-label">
				<label for="<?php echo $attributes['id']; ?>">
					<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_FIELD_PROVIDER'); ?>
				</label>
			</div>
			<div class="controls">
				<select <?php echo ArrayHelper::toString($attributes); ?>>
					<?php foreach ($providers as $provider) :
						$selected = ($providerValue === $provider) ? ' selected="selected"' : ''; ?>
						<option value="<?php echo $provider; ?>" <?php echo $selected; ?>>
							<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' . $provider); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<?php foreach ($fields as $key => $group):
			$display = $shippingParams->get('field_' . $key, 'hidden');
			if ($display === 'hidden')
			{
				$group = 'd-none';
			}

			$hint = 'PLG_RADICALMART_SHIPPING_APISHIP_FIELD_' . $key . '_HINT';
			$hint = ($language->hasKey($hint)) ? Text::_($hint) : '';

			$label      = 'PLG_RADICALMART_SHIPPING_APISHIP_FIELD_' . $key;
			$label      = ($language->hasKey($label)) ? Text::_($label) : $key;
			$attributes = [
				'id'          => $id . '_' . $key,
				'name'        => $name . '[' . $key . ']',
				'type'        => ($display === 'hidden') ? 'hidden' : 'text',
				'class'       => 'form-control',
				'value'       => (!empty($value[$key])) ? $value[$key] : '',
				'placeholder' => $hint,

				'radicalmart-shipping-apiship-field-address' => 'input_' . $key,
				'data-validate-key'                          => 'validate[' . $key . ']',
			];

			if ($display === 'required')
			{
				$attributes['required'] = '';
				$attributes['class']    .= ' required';
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
			<div class="<?php echo $group; ?>">
				<div class="control-label">
					<label for="<?php echo $attributes['id']; ?>">
						<?php echo Text::_($label); ?>
					</label>
				</div>
				<div class="controls">
					<input <?php echo ArrayHelper::toString($attributes); ?>>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<div>
		<button type="button" class="btn btn-primary"
				radicalmart-shipping-apiship-field-address="validate_button" style="display:none">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_POINTS_ADDRESS_FIELD_VALIDATE'); ?>
		</button>
	</div>
</div>