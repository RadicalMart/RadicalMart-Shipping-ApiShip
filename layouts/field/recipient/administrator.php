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

?>

<select id="<?php echo $id; ?>" name="<?php echo $name; ?>" class="<?php echo $class; ?>" onchange="this.dispatchEvent(new Event('setValue', {'bubbles': true}))">
	<?php foreach ($places as $key => $place):
		$selected = ($key === $value) ? 'selected' : ''; ?>
		<option value="<?php echo $key; ?>"<?php echo $selected; ?>>
			<?php echo $place['title']; ?>
		</option>
	<?php endforeach; ?>
</select>