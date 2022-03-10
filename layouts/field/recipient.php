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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

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
 */

if (!is_array($value)) $value = (new Registry($value))->toArray();

$document = Factory::getDocument();
$apikey   = ComponentHelper::getParams('com_radicalmart')->get('shipping_apiship_yandexmap_key');
HTMLHelper::script('//api-maps.yandex.ru/2.1/?lang=ru-RU&apikey=' . $apikey, array('version' => 'auto'));
HTMLHelper::script('plg_radicalmart_shipping_apiship/field-recipient.min.js', array('version' => 'auto', 'relative' => true));

$markerSize = 32;
$marker     = HTMLHelper::image('plg_radicalmart_shipping_apiship/marker.php', '', '', true, true)
	. '?size=' . $markerSize;
$selector   = 'radicalmart-shipping-apiship-input-recipient' . $id;
$jsOptions  = array(
	'id'               => $id,
	'name'             => $name,
	'value'            => $value,
	'valueCoordinates' => (!empty($value) && !empty($value['latitude']) && !empty($value['longitude']))
		? array((float) $value['latitude'], (float) $value['longitude']) : false,
	'marker'           => array(
		'point'              => true,
		'draggable'          => true,
		'iconLayout'         => 'default#image',
		'iconImageHref'      => $marker . '&color=147247',
		'iconImageSize'      => array($markerSize, $markerSize),
		'iconImageOffset'    => array(($markerSize / 2) * -1, $markerSize * -1),
		'openBalloonOnClick' => false,
	)
);
$document->addScriptOptions($selector, $jsOptions);

$document->addStyleDeclaration('
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
$suggest = array(
	'id'          => $id . '_suggest',
	'class'       => (!empty($class)) ? $class : '',
	'value'       => (!empty($value['address'])) ? $value['address'] : '',
	'placeholder' => (!empty($hint)) ? $hint : '',
);
?>
<div id="<?php echo $id; ?>" radicalmart-shipping-apiship-input="recipient"
	 data-selector="<?php echo $selector; ?>">
	<div style="margin-bottom: 5px">
		<input type="text" <?php echo ArrayHelper::toString($suggest); ?>>
	</div>
	<div id="<?php echo $id . '_map'; ?>"></div>
	<?php foreach (array('address', 'latitude', 'longitude') as $key)
	{
		echo LayoutHelper::render('joomla.form.field.hidden', array(
			'id'       => $id . '_' . $key,
			'name'     => $name . '[' . $key . ']',
			'value'    => (!empty($value[$key])) ? $value[$key] : '',
			'onchange' => ($key === 'address' && !empty($onchange)) ? $onchange : false,
			'disabled' => false,
		));
	}; ?>
</div>