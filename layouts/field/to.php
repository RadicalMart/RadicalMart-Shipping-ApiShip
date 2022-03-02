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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  string $class Classes for the input.
 * @var  string $id    DOM id of the field.
 * @var  string $name  Name of the input field.
 * @var  string $key   Yandex map api key.
 * @var  array  $value Value attribute of the field.
 * @var  string $save  Save data url.
 *
 */

if (empty($save)) $save = false;

HTMLHelper::script('//api-maps.yandex.ru/2.1/?lang=ru-RU&apikey=' . $key, array('version' => 'auto'));
HTMLHelper::script('plg_radicalmart_shipping_apiship/field-to.min.js', array('version' => 'auto', 'relative' => true));
HTMLHelper::stylesheet('plg_radicalmart_shipping_apiship/field-to.min.css', array('version' => 'auto', 'relative' => true));


$markerSize = 32;
$marker     = HTMLHelper::image('plg_radicalmart_shipping_apiship/marker.php', '', '', true, true)
	. '?size=' . $markerSize;

$jsOptions = array(
	'iconImageHref'       => $marker . '&color=fa1f0c',
	'iconImageSize'       => array($markerSize, $markerSize),
	'iconImageOffset'     => array(($markerSize / 2) * -1, $markerSize * -1),
	'iconActiveImageHref' => $marker . '&color=147247',
	'markers'             => false,
	'center'              => array(55.766072, 37.619894),
	'zoom'                => 10,
	'coordinates'         => false,
);

if (!empty($value))
{
	$jsOptions['center']      = array((float) $value['lat'], (float) $value['lng']);
	$jsOptions['coordinates'] = array((float) $value['lat'], (float) $value['lng']);
	$jsOptions['zoom']        = 15;
}
Factory::getDocument()->addScriptOptions($id, $jsOptions);
?>
<div id="<?php echo $id; ?>" input-apiship-to="container">
	<div id="<?php echo $id . '_map'; ?>" input-apiship-to="map" style="width: 100%; height: 250px; background: red">
	</div>
	<input type="text" name="<?php echo $name . '[addressString]'; ?>" input-apiship-to-field="addressString"
		<?php if (!empty($value['addressString'])) echo $value['addressString']; ?> readonly>

	<input type="text" name="<?php echo $name . '[lat]'; ?>" input-apiship-to-field="lat"
		<?php if (!empty($value['lat'])) echo $value['lat']; ?> readonly>

	<input type="text" name="<?php echo $name . '[lng]'; ?>" input-apiship-to-field="lng"
		<?php if (!empty($value['lat'])) echo $value['lng']; ?> readonly>
</div>