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
 * @var  string $class  Classes for the input.
 * @var  string $id     DOM id of the field.
 * @var  string $name   Name of the input field.
 * @var  array  $value  Value attribute of the field.
 * @var  array  $places Places fields.
 *
 */

$selector = 'input-apiship-from_' . $id;
HTMLHelper::script('plg_radicalmart_shipping_apiship/field-from.min.js', array('version' => 'auto', 'relative' => true));
Factory::getDocument()->addScriptOptions($selector, array(
	'places' => $places
))

?>
<div id="<?php echo $id; ?>" input-apiship-from="<?php echo $selector; ?>">
	<select>
		<?php foreach ($places as $key => $place): ?>
			<option value="<?php echo $key; ?>"<?php if ($value['key'] === $key) echo ' selected'; ?>>
				<?php echo $place['title'] . '( ' . $place['address'] . ')'; ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php foreach (array('addressString', 'lat', 'lng', 'title', 'key') as $field): ?>
		<input type="text" name="<?php echo $name . '[' . $field . ']'; ?>"
			   input-apiship-from-field="<?php echo $field; ?>">
	<?php endforeach; ?>
</div>