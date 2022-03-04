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

use Joomla\CMS\Language\Text;

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
	$value            = array('title' => '', 'address' => '', 'key' => 'REPLACE_TO_KEY', 'latitude' => '', 'longitude' => '');
	$replaceAttribute = 'data-replace_';
}
$key  = $value['key'];
$id   .= '_' . $key;
$name .= '[' . $key . ']';
?>

<div input-apiship-places="item" data-key="<?php echo $key; ?>" data-active="0" class="form-vertical"
     style="display: none">
	<a class="" input-apiship-places="action-close" uk-icon="close"
	   title="<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_ACTION_CLOSE'); ?>"></a>
	<div class="control-group">
		<label for="<?php echo $id . '_title'; ?>" class="control-label">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_ROW_TITLE'); ?>
		</label>
		<div class="controls">
			<input type="text" input-apiship-places="title" class="span12"
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
			<input type="text" input-apiship-places="address" class="span12"
			       <?php echo $replaceAttribute; ?>id="<?php echo $id . '_address'; ?>"
			       <?php echo $replaceAttribute; ?>name="<?php echo $name . '[address]'; ?>"
			       value="<?php echo $value['address']; ?>">
		</div>
	</div>
	<div>
		<span class="btn btn-danger" input-apiship-places="action-delete">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_ACTION_DELETE'); ?>
		</span>
		<span class="btn btn-inverse" input-apiship-places="action-set_address">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PLACES_ACTION_SET_ADDRESS'); ?>
		</span>
	</div>
	<input type="hidden" input-apiship-places="key"
	       <?php echo $replaceAttribute; ?>id="<?php echo $id . '_key'; ?>"
	       <?php echo $replaceAttribute; ?>name="<?php echo $name . '[key]'; ?>"
	       value="<?php echo $value['key']; ?>">
	<input type="hidden" input-apiship-places="latitude"
	       <?php echo $replaceAttribute; ?>id="<?php echo $id . '_latitude'; ?>"
	       <?php echo $replaceAttribute; ?>name="<?php echo $name . '[latitude]'; ?>"
	       value="<?php echo $value['latitude']; ?>">
	<input type="hidden" input-apiship-places="longitude"
	       <?php echo $replaceAttribute; ?>id="<?php echo $id . '_longitude '; ?>"
	       <?php echo $replaceAttribute; ?>name="<?php echo $name . '[longitude]'; ?>"
	       value="<?php echo $value['longitude']; ?>">
</div>