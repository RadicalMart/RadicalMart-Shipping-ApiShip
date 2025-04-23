<?php

/*
 * @package     RadicalMart Shipping Addresses Plugin
 * @subpackage  plg_radicalmart_shipping_addresses
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2024 RadicalMart. All rights reserved.
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
				<?php if (!empty($buttons['move'])) : ?>
					<button type="button" class="group-move btn btn-sm btn-primary"
							aria-label="<?php echo Text::_('JGLOBAL_FIELD_MOVE'); ?>">
						<span class="icon-arrows-alt icon-white" aria-hidden="true"></span>
					</button>
					<button type="button" class="group-move-up btn btn-sm"
							aria-label="<?php echo Text::_('JGLOBAL_FIELD_MOVE_UP'); ?>">
						<span class="icon-chevron-up" aria-hidden="true"></span>
					</button>
					<button type="button" class="group-move-down btn btn-sm"
							aria-label="<?php echo Text::_('JGLOBAL_FIELD_MOVE_DOWN'); ?>">
						<span class="icon-chevron-down" aria-hidden="true"></span>
					</button>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
	<div class="row">
		<div class="col-md-12">
			<?php echo $form->renderField('uid'); ?>
			<?php echo $form->renderField('provider'); ?>
		</div>
		<?php if ($form->getField('country')): ?>
			<div class="col-md-12">
				<?php echo $form->renderField('country'); ?>
			</div>
		<?php endif; ?>
		<?php if ($form->getField('region')): ?>
			<div class="col-md-6">
				<?php echo $form->renderField('region'); ?>
			</div>
		<?php endif; ?>
		<?php if ($form->getField('city')): ?>
			<div class="col-md-6">
				<?php echo $form->renderField('city'); ?>
			</div>
		<?php endif; ?>
		<?php if ($form->getField('zip')): ?>
			<div class="col-md-2">
				<?php echo $form->renderField('zip'); ?>
			</div>
		<?php endif; ?>
		<?php if ($form->getField('street')): ?>
			<div class="col-md-8">
				<?php echo $form->renderField('street'); ?>
			</div>
		<?php endif; ?>
		<?php if ($form->getField('house')): ?>
			<div class="col-md-2">
				<?php echo $form->renderField('house'); ?>
			</div>
		<?php endif; ?>
		<?php if ($form->getField('building')): ?>
			<div class="col-md-3">
				<?php echo $form->renderField('building'); ?>
			</div>
		<?php endif; ?>
		<?php if ($form->getField('entrance')): ?>
			<div class="col-md-3">
				<?php echo $form->renderField('entrance'); ?>
			</div>
		<?php endif; ?>
		<?php if ($form->getField('floor')): ?>
			<div class="col-md-3">
				<?php echo $form->renderField('floor'); ?>
			</div>
		<?php endif; ?>
		<?php if ($form->getField('apartment')): ?>
			<div class="col-md-3">
				<?php echo $form->renderField('apartment'); ?>
			</div>
		<?php endif; ?>
	</div>
</div>