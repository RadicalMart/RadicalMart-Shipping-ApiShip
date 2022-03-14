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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Registry\Registry;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string  $autocomplete   Autocomplete attribute for the field.
 * @var   boolean $autofocus      Is autofocus enabled?
 * @var   string  $class          Classes for the input.
 * @var   string  $description    Description of the field.
 * @var   boolean $disabled       Is this field disabled?
 * @var   string  $group          Group the field belongs to. <fields> section in form XML.
 * @var   boolean $hidden         Is this field hidden in the form?
 * @var   string  $hint           Placeholder for the field.
 * @var   string  $id             DOM id of the field.
 * @var   string  $label          Label of the field.
 * @var   string  $labelclass     Classes to apply to the label.
 * @var   boolean $multiple       Does this field support multiple values?
 * @var   string  $name           Name of the input field.
 * @var   string  $onchange       Onchange attribute for the field.
 * @var   string  $onclick        Onclick attribute for the field.
 * @var   string  $pattern        Pattern (Reg Ex) of value of the form field.
 * @var   boolean $readonly       Is this field read only?
 * @var   boolean $repeat         Allows extensions to duplicate elements.
 * @var   boolean $required       Is this field required?
 * @var   integer $size           Size attribute of the input.
 * @var   boolean $spellcheck     Spellcheck state for the form field.
 * @var   string  $validate       Validation rules to apply.
 * @var   array   $value          Value attribute of the field.
 * @var   array   $checkedOptions Options that will be set as checked.
 * @var   boolean $hasValue       Has this field a value assigned?
 * @var   array   $options        Options available for this field.
 *
 * Field specific variables
 * @var  array    $places         Field places array.
 */

HTMLHelper::script('plg_radicalmart_shipping_apiship/field-sender.min.js', array('version' => 'auto', 'relative' => true));

$layout   = (Factory::getApplication()->isClient('site')) ? 'site' : 'administrator';
$selector = 'radicalmart-shipping-apiship-input-sender' . $id;
Factory::getDocument()->addScriptOptions($selector, array(
	'id'     => $id,
	'name'   => $name,
	'places' => $places,
	'layout' => $layout,
));
if (!is_array($value)) $value = (new Registry($value))->toArray();

$class = (!isset($class)) ? '' : $class;
?>
<div id="<?php echo $id; ?>" radicalmart-shipping-apiship-input="sender"
	 data-selector="<?php echo $selector; ?>">
	<?php

	echo LayoutHelper::render('plugins.radicalmart_shipping.apiship.field.sender.' . $layout, array(
		'id'     => $id . '_key',
		'name'   => $name . '[key]',
		'class'  => $class,
		'value'  => (!empty($value['key'])) ? $value['key'] : '',
		'places' => $places,
	)); ?>

	<?php foreach (array('title', 'address', 'latitude', 'longitude') as $fieldKey):
		$fieldId = $id . '_' . $fieldKey;
		$fieldName = $name . '[' . $fieldKey . ']';
		$fieldValue = (!empty($value[$fieldKey])) ? $value[$fieldKey] : '';
		$fieldOnChange = (!empty($onchange) && $fieldKey === 'address') ? ' onchange="' . $onchange . '"' : '';
		$fieldRequired = ($fieldKey === 'address' && $required) ? 'required' : '';
		?>
		<input id="<?php echo $id; ?>" name="<?php echo $fieldName; ?>" type="hidden"
			<?php echo $fieldOnChange . $fieldRequired; ?> value="<?php echo $fieldValue; ?>">
	<?php endforeach; ?>
</div>