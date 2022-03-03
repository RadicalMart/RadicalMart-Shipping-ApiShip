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
use Joomla\Registry\Registry;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  string $class Classes for the input.
 * @var  string $id    DOM id of the field.
 * @var  string $name  Name of the input field.
 * @var  array  $value Value attribute of the field.
 * @var  array  $mode  Field mode.
 *
 */

$selector = 'input-apiship-calculate_' . $id;
HTMLHelper::script('plg_radicalmart_shipping_apiship/field-calculate.min.js', array('version' => 'auto', 'relative' => true));
Factory::getDocument()->addScriptOptions($id, array(
	'mode'       => $mode,
	'controller' => Route::_('index.php?option=com_ajax&plugin=apiship&group=radicalmart_shipping&action=calculateCheckout&format=json', false)
));

if (!is_array($value)) $value = (new Registry($value))->toArray();
?>
<div id="<?php echo $id; ?>" input-apiship-calculate="container">
	<div class="uk-alert uk-alert-danger" input-apiship-calculate="error" style="display: none"></div>
	<input type="text" name="<?php echo $name . '[cost]'; ?>"
		   value="<?php if (!empty($value['cost'])) echo $value['cost']; ?>"
		   input-apiship-calculate="cost">
	<input type="text" name="<?php echo $name . '[daysMin]'; ?>"
		   value="<?php if (!empty($value['daysMin'])) echo $value['daysMin']; ?>"
		   input-apiship-calculate="daysMin">
	<input type="text" name="<?php echo $name . '[daysMax]'; ?>"
		   value="<?php if (!empty($value['daysMax'])) echo $value['daysMax']; ?>"
		   input-apiship-calculate="daysMax">
	<input type="text" name="<?php echo $name . '[hash]'; ?>"
		   value="<?php if (!empty($value['hash'])) echo $value['hash']; ?>"
		   input-apiship-calculate="hash">
</div>