<?php
/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   Form   $tmpl            The Empty form for template
 * @var   array  $forms           Array of JForm instances for render the rows
 * @var   bool   $multiple        The multiple state for the form field
 * @var   int    $min             Count of minimum repeating in multiple mode
 * @var   int    $max             Count of maximum repeating in multiple mode
 * @var   string $name            Name of the input field.
 * @var   string $fieldname       The field name
 * @var   string $fieldId         The field ID
 * @var   string $control         The forms control
 * @var   string $label           The field label
 * @var   string $description     The field description
 * @var   array  $buttons         Array of the buttons that will be rendered
 * @var   bool   $groupByFieldset Whether group the subform fields by it`s fieldset
 */

/** @var Form $form */
$form = $forms[0];
?>
<div radicalmart-shipping-apiship-order="body">
	<div class="p-3 position-relative">
		<div class="alert alert-danger" radicalmart-shipping-apiship-order="error" style="display: none"></div>
		<div class="alert alert-info" radicalmart-shipping-apiship-order="message" style="display: none"></div>
		<div radicalmart-shipping-apiship-order="loading" style="display: none">
			<div class="position-absolute top-0 bottom-0 start-0 end-0 bg-light bg-opacity-75 d-flex justify-content-center align-items-center"
				 style="z-index: 99999">
				<div class="spinner-border text-info" role="status"
					 style="width: 4rem; height: 4rem;"></div>
			</div>
		</div>
		<?php if ($form->getField('point')) : ?>
			<div radicalmart-shipping-apiship-order="point" class="mb-3">
				<div class="h4 mt-3"><?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ORDER_POINT'); ?></div>
				<?php echo $form->getInput('point'); ?>
			</div>
		<?php else: ?>
			<div radicalmart-shipping-apiship-order="address" class="mb-3">
				<div class="h4 mt-3"><?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ORDER_ADDRESS'); ?></div>
				<?php echo $form->getInput('address'); ?>
			</div>
		<?php endif; ?>
		<div radicalmart-shipping-apiship-order="tariff" class="mb-3">
			<div class="h4 "><?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ORDER_TARIFF'); ?></div>
			<?php echo $form->getInput('tariff'); ?>
		</div>
		<div radicalmart-shipping-apiship-order="price" class="mb-3">
			<div radicalmart-shipping-apiship-order="price_base">
				<div class="h4"><?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ORDER_COST'); ?></div>
				<?php echo $form->getInput('base', 'price'); ?>
				<?php echo $form->renderField('update', 'price'); ?>
			</div>
			<div radicalmart-shipping-apiship-order="price_recipient">
				<div class="h4"><?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_COST_RECIPIENT_PAYMENT'); ?></div>
				<?php echo $form->getInput('recipient', 'price'); ?>
				<div>
					<div class="text-warning">
						<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_COST_RECIPIENT_PAYMENT_MESSAGE'); ?>
					</div>
					<div class="small text-muted">
						<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_COST_RECIPIENT_PAYMENT_NOTE'); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>