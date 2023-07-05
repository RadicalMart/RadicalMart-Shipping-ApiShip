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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Registry\Registry;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string    $autocomplete   Autocomplete attribute for the field.
 * @var   bool      $autofocus      Is autofocus enabled?
 * @var   string    $class          Classes for the input.
 * @var   string    $description    Description of the field.
 * @var   bool      $disabled       Is this field disabled?
 * @var   string    $group          Group the field belongs to. <fields> section in form XML.
 * @var   bool      $hidden         Is this field hidden in the form?
 * @var   string    $hint           Placeholder for the field.
 * @var   string    $id             DOM id of the field.
 * @var   string    $label          Label of the field.
 * @var   string    $labelclass     Classes to apply to the label.
 * @var   bool      $multiple       Does this field support multiple values?
 * @var   string    $name           Name of the input field.
 * @var   string    $onchange       Onchange attribute for the field.
 * @var   string    $onclick        Onclick attribute for the field.
 * @var   string    $pattern        Pattern (Reg Ex) of value of the form field.
 * @var   bool      $readonly       Is this field read only?
 * @var   bool      $repeat         Allows extensions to duplicate elements.
 * @var   bool      $required       Is this field required?
 * @var   int       $size           Size attribute of the input.
 * @var   bool      $spellcheck     Spellcheck state for the form field.
 * @var   string    $validate       Validation rules to apply.
 * @var   array     $value          Value attribute of the field.
 * @var   array     $checkedOptions Options that will be set as checked.
 * @var   bool      $hasValue       Has this field a value assigned?
 * @var   array     $options        Options available for this field.
 *
 * Field specific variables
 * @var  array|null $places         Sender places data.
 */

// Prepare variables
if (!is_array($value))
{
	$value = (new Registry($value))->toArray();
}
$selector = 'radicalmart-shipping-apiship-field-sender_' . $id;
$class    = (!isset($class)) ? '' : $class;
$layout   = (Factory::getApplication()->isClient('site')) ? 'site' : 'administrator';

// Load assets
/** @var \Joomla\CMS\Document\Document $document */
$document = Factory::getApplication()->getDocument();

/** @var \Joomla\CMS\WebAsset\WebAssetManager $assets */
$assets         = $document->getWebAssetManager();
$assetsRegistry = $assets->getRegistry();
$assetsRegistry->addExtensionRegistryFile('plg_radicalmart_shipping_apiship');
$assets->useScript('plg_radicalmart_shipping_apiship.fields.sender');

$document->addScriptOptions($selector, [
	'id'     => $id,
	'name'   => $name,
	'places' => $places,
	'layout' => $layout,
]);
?>
<div id="<?php echo $id; ?>" radicalmart-shipping-apiship-field="sender" data-selector="<?php echo $selector; ?>">
	<?php
	echo LayoutHelper::render('plugins.radicalmart_shipping.apiship.field.sender.' . $layout, [
		'id'     => $id . '_key',
		'name'   => $name . '[key]',
		'class'  => $class,
		'value'  => (!empty($value['key'])) ? $value['key'] : '',
		'places' => $places,
	]); ?>
	<?php foreach (['title', 'address', 'countryCode', 'latitude', 'longitude'] as $fieldKey)
	{
		echo LayoutHelper::render('joomla.form.field.hidden', [
			'id'       => $id . '_' . $fieldKey,
			'name'     => $name . '[' . $fieldKey . ']',
			'value'    => (!empty($value[$fieldKey])) ? $value[$fieldKey] : '',
			'onchange' => (!empty($onchange) && $fieldKey === 'address') ? $onchange : '',
			'required' => ($fieldKey === 'address' && $required),
		]);
	} ?>
</div>
