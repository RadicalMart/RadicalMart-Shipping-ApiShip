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
 * @var  string $class    Classes for the input.
 * @var  string $id       DOM id of the field.
 * @var  string $name     Name of the input field.
 * @var  string $value    Value attribute of the field.
 * @var  array  $options  Value attribute of the field.
 * @var  string $onChange Field on Change attribute.
 *
 */

$onChange = (!empty($onchange)) ? ' onchange="' . $onchange . '"' : '';
?>

<div class="uk-grid-small uk-child-width-1-3@s uk-grid-match" uk-grid>
	<?php foreach ($options as $o => $option):
		$checked = ((string) $option->value === (string) $value) ? 'checked' : ''; ?>
		<div>
			<label for="<?php echo $id . '_' . $o; ?>"
				   class="uk-tile uk-padding-small uk-text-center uk-padding uk-display-block uk-tile-<?php echo ($checked) ? 'primary' : 'muted'; ?>">
				<input id="<?php echo $id . '_' . $o; ?>" class="uk-radio uk-hidden" type="radio"
					   name="<?php echo $name; ?>" value="<?php echo $option->value; ?>"
					<?php echo $checked . $onChange; ?>>
				<div class="uk-margin-remove uk-text-nowrap">
					<?php echo $option->text; ?>
				</div>
			</label>
		</div>
	<?php endforeach; ?>
</div>