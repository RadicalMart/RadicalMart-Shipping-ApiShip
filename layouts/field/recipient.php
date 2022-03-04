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

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  string $class Classes for the input.
 * @var  string $id    DOM id of the field.
 * @var  string $name  Name of the input field.
 * @var  array  $value Value attribute of the field.
 *
 */

$apikey = ComponentHelper::getParams('com_radicalmart')->get('shipping_apiship_yandexmap_key');
HTMLHelper::script('//api-maps.yandex.ru/2.1/?lang=ru-RU&apikey=' . $apikey, array('version' => 'auto'));

HTMLHelper::script('plg_radicalmart_shipping_apiship/field-recipient.min.js', array('version' => 'auto', 'relative' => true));

$markerSize = 32;
$marker     = HTMLHelper::image('plg_radicalmart_shipping_apiship/marker.php', '', '', true, true)
	. '?size=' . $markerSize;

$selector  = 'radicalmart-shipping-apiship-input-recipient' . $id;
$jsOptions = array(
	'id'                  => $id,
	'name'                => $name,
	'iconImageHref'       => $marker . '&color=fa1f0c',
	'iconImageSize'       => array($markerSize, $markerSize),
	'iconImageOffset'     => array(($markerSize / 2) * -1, $markerSize * -1),
	'iconActiveImageHref' => $marker . '&color=147247',
	'coordinates'         => (!empty($value) && !empty($value['latitude']) && !empty($value['longitude']))
		? array((float) $value['latitude'], (float) $value['longitude']) : false,
);

Factory::getDocument()->addScriptOptions($selector, $jsOptions);
$class = (!isset($class)) ? '' : $class;

$fieldOnChange = (!empty($onchange)) ? ' onchange="' . $onchange . '"' : '';
$fieldRequired = ($required) ? 'required' : '';
$fieldClass    = (!empty($class)) ? ' class="' . $class . '"' : '';
$fieldValue    = (!empty($value['address'])) ? $value['address'] : '';
?>
<div id="<?php echo $id; ?>" radicalmart-shipping-apiship-input="recipient"
	 data-selector="<?php echo $selector; ?>">
	<div id="<?php echo $id . '_map'; ?>" style="width: 100%; height: 300px; background: #e5e5e5;">
	</div>
	<div style="margin-top:5px">
		<input id="<?php echo $id . '_address'; ?>" name="<?php echo $name . '[address]'; ?>" type="text"
			   readonly <?php echo $fieldOnChange . $fieldClass . $fieldRequired ?> value="<?php echo $fieldValue; ?>">
	</div>
	<?php foreach (array('latitude', 'longitude') as $fieldKey):
		$fieldId = $id . '_' . $fieldKey;
		$fieldName = $name . '[' . $fieldKey . ']';
		$fieldValue = (!empty($value[$fieldName])) ? $value[$fieldName] : '';
		?>
		<input id="<?php echo $id; ?>" name="<?php echo $fieldName; ?>" type="hidden"
			   value="<?php echo $fieldValue; ?>">
	<?php endforeach; ?>
</div>