<?php

/*
 * @package     RadicalMart Shipping Addresses Plugin
 * @subpackage  plg_radicalmart_shipping_addresses
 * @version     1.0.0
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   Form   $form      The form instance for render the section
 * @var   string $basegroup The base group name
 * @var   string $group     Current group name
 * @var   array  $buttons   Array of the buttons that will be rendered
 */

$fields = [
	'provider'  => 'col-md-3',
	'zip'       => 'col-md-3',
	'country'   => 'col-md-6',
	'region'    => 'col-md-4',
	'city'      => 'col-md-8',
	'street'    => 'col-md-8',
	'house'     => 'col-md-2',
	'building'  => 'col-md-2',
	'entrance'  => 'col-md-2',
	'floor'     => 'col-md-2',
	'apartment' => 'col-md-2',

	'uid'     => 'hidden',
	'string'  => 'hidden',
	'display' => 'hidden',
]
?>
<div class="subform-repeatable-group ms-0" data-base-name="<?php echo $basegroup; ?>"
	 data-group="<?php echo $group; ?>">
	<?php if (!empty($buttons)) : ?>
		<div class="btn-toolbar text-end">
			<div class="btn-group">
				<?php if (!empty($buttons['add'])) : ?>
					<button type="button" class="group-add btn btn-sm btn-success"
							aria-label="<?php echo Text::_('JGLOBAL_FIELD_ADD'); ?>">
						<span class="icon-plus icon-white" aria-hidden="true"></span>
					</button>
				<?php endif; ?>
				<?php if (!empty($buttons['remove'])) : ?>
					<button type="button" class="group-remove btn btn-sm btn-danger"
							aria-label="<?php echo Text::_('JGLOBAL_FIELD_REMOVE'); ?>">
						<span class="icon-minus icon-white" aria-hidden="true"></span>
					</button>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
	<div class="row g-2">
		<?php foreach ($fields as $key => $column):
			if (empty($form->getField($key)))
			{
				continue;
			} ?>
			<div class="<?php echo $column; ?>">
				<div class="form-floating">
					<?php echo $form->getInput($key); ?>
					<?php echo $form->getLabel($key); ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>