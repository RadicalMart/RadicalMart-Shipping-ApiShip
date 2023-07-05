<?php
/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2023 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  string $id    DOM id of the field.
 * @var  string $name  Name of the input field.
 * @var  array  $value Field value.
 *
 */

$replaceAttribute = '';

if (empty($value))
{
	$value            = ['title' => '', 'address' => '', 'key' => 'REPLACE_TO_KEY', 'latitude' => '', 'longitude' => ''];
	$replaceAttribute = 'data-replace_';
}

$key  = $value['key'];
$id   .= '_' . $key;
$name .= '[' . $key . ']';
?>
<div radicalmart-shipping-apiship-field-places="item" data-key="<?php echo $key; ?>" data-active="0"
	 class="options-form form-vertical" style="display: none">
	<div class="text-end">
		<a href="javascript:void(0);" radicalmart-shipping-apiship-field-places="action-close"
		   class="icon-cancel text-danger"
		   title="<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_ACTION_CLOSE'); ?>"></a>
	</div>

	<div class="control-group">
		<label for="<?php echo $id . '_title'; ?>" class="control-label">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_ROW_TITLE'); ?>
		</label>
		<div class="controls">
			<input type="text" radicalmart-shipping-apiship-field-places="title" class="form-control"
			       <?php echo $replaceAttribute; ?>id="<?php echo $id . '_title'; ?>"
			       <?php echo $replaceAttribute; ?>name="<?php echo $name . '[title]'; ?>"
				   value="<?php echo $value['title']; ?>">
		</div>
	</div>
	<div class="control-group">
		<label for="<?php echo $id . '_address'; ?>" class="control-label">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_ROW_ADDRESS'); ?>
		</label>
		<div class="controls">
			<input type="text" radicalmart-shipping-apiship-field-places="address" class="form-control"
			       <?php echo $replaceAttribute; ?>id="<?php echo $id . '_address'; ?>"
			       <?php echo $replaceAttribute; ?>name="<?php echo $name . '[address]'; ?>"
				   value="<?php echo $value['address']; ?>">
		</div>
	</div>
	<div>
		<span class="btn btn-sm btn-outline-danger" radicalmart-shipping-apiship-field-places="action-delete">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_ACTION_DELETE'); ?>
		</span>
		<span class="btn btn-sm btn-outline-secondary" radicalmart-shipping-apiship-field-places="action-set_address">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_ACTION_SET_ADDRESS'); ?>
		</span>
	</div>
	<?php foreach (['key', 'countryCode', 'latitude', 'longitude'] as $key): ?>
		<input type="hidden" radicalmart-shipping-apiship-field-places="<?php echo $key; ?>"
		       <?php echo $replaceAttribute; ?>id="<?php echo $id . '_' . $key; ?>"
		       <?php echo $replaceAttribute; ?>name="<?php echo $name . '[' . $key . ']'; ?>"
			   value="<?php echo (!empty($value[$key])) ? $value[$key] : ''; ?>">
	<?php endforeach; ?>
</div>
