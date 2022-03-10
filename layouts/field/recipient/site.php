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
<div class="uk-grid-small uk-child-width-1-3@s uk-grid-match" uk-grid >
	<?php foreach ($places as $key => $place):
		$checked = ($key === $value) ? 'checked' : ''; ?>
		<div>
			<label for="<?php echo $id . '_' . $key; ?>"
				   class="uk-tile uk-text-center uk-padding uk-display-block uk-tile-<?php echo ($checked) ? 'primary' : 'muted'; ?>"
				   data-class-toggle="uk-tile-primary:uk-tile-muted">
				<input id="<?php echo $id . '_' . $key; ?>" class="uk-radio uk-hidden" type="radio"
					   name="<?php echo $name; ?>" value="<?php echo $key; ?>"<?php echo $checked; ?>>
				<div class="uk-margin-remove uk-h4">
					<?php echo $place['title']; ?>
				</div>
				<div class="uk-text-small uk-text-muted">
					<?php echo $place['address']; ?>
				</div>
			</label>
		</div>
	<?php endforeach; ?>
</div>