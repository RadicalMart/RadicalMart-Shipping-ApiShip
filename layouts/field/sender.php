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
 * @var  string $class  Classes for the input.
 * @var  string $id     DOM id of the field.
 * @var  string $name   Name of the input field.
 * @var  array  $value  Value attribute of the field.
 * @var  array  $places Places fields.
 *
 */


HTMLHelper::script('plg_radicalmart_shipping_apiship/field-sender.min.js', array('version' => 'auto', 'relative' => true));

$selector = 'radicalmart-shipping-apiship-input-sender' . $id;
Factory::getDocument()->addScriptOptions($selector, array(
	'id'     => $id,
	'name'   => $name,
	'places' => $places,
));
if (!is_array($value)) $value = (new Registry($value))->toArray();


$class = (!isset($class)) ? '' : $class;
?>
<div id="<?php echo $id; ?>" radicalmart-shipping-apiship-input="sender"
	 data-selector="<?php echo $selector; ?>">
	<select id="<?php echo $id . '_key'; ?>" name="<?php echo $name . '[key]'; ?>" class="<?php echo $class; ?>">
		<?php foreach ($places as $key => $place):
			$selected = (!empty($value['key']) && $value['key'] === $key) ? 'selected' : '';
			?>
			<option value="<?php echo $key; ?>"<?php echo $selected; ?>>
				<?php echo $place['title']; ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php foreach (array('title', 'address', 'latitude', 'longitude') as $fieldKey):
		$fieldId = $id . '_' . $fieldKey;
		$fieldName = $name . '[' . $fieldKey . ']';
		$fieldValue = (!empty($value[$fieldName])) ? $value[$fieldName] : '';
		$fieldOnChange = (!empty($onchange) && $fieldKey === 'address') ? ' onchange="' . $onchange . '"' : '';
		$fieldRequired = ($fieldKey === 'address' && $required) ? 'required' : '';
		?>
		<input id="<?php echo $id; ?>" name="<?php echo $fieldName; ?>" type="hidden"
			<?php echo $fieldOnChange . $fieldRequired; ?> value="<?php echo $fieldValue; ?>">
	<?php endforeach; ?>
</div>