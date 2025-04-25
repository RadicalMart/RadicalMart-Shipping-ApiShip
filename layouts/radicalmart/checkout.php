<?php
/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  Form   $form     Form object.
 * @var  object $item     Checkout object.
 * @var  object $shipping Checkout shipping method object.
 *
 */

if (empty($shipping))
{
	return;
}

$delivery_type = (int) $shipping->params->get('delivery_type', 2);

// Load assets
/** @var \Joomla\CMS\Document\Document $document */
$document = Factory::getApplication()->getDocument();

/** @var \Joomla\CMS\WebAsset\WebAssetManager $assets */
$assets = $document->getWebAssetManager();
$assets->getRegistry()
	->addExtensionRegistryFile('plg_radicalmart_shipping_apiship');

$assets->useScript('plg_radicalmart_shipping_apiship.site.checkout');
?>
<div class="position-relative">
	<div radicalmart-checkout-display="shipping.error" class="alert alert-danger" style="display: none"></div>
	<div radicalmart-checkout-display="shipping.message" class="alert alert-info" style="display: none"></div>
	<div radicalmart-shipping-apiship-checkout="loading">
		<div class="position-absolute top-0 bottom-0 start-0 end-0 bg-light bg-opacity-75 d-flex justify-content-center align-items-center"
			 style="z-index: 1">
			<div class="spinner-border text-info" role="status"
				 style="width: 4rem; height: 4rem;"></div>
		</div>
	</div>
	<?php if ($delivery_type === 2): ?>
		<div class="mb-3" radicalmart-shipping-apiship-checkout="point">
			<div class="h5">
				<?php echo Text::_($form->getFieldAttribute('point', 'label', '', 'shipping')); ?>
			</div>
			<div>
				<?php echo $form->getInput('point', 'shipping'); ?>
			</div>
		</div>
	<?php else: ?>
		<div class="mb-3" radicalmart-shipping-apiship-checkout="address">
			<div class="h5">
				<?php echo Text::_($form->getFieldAttribute('address', 'label', '', 'shipping')); ?>
			</div>
			<div>
				<?php echo $form->getInput('address', 'shipping'); ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="mb-3" radicalmart-shipping-apiship-checkout="tariff">
		<div class="h5">
			<?php echo Text::_($form->getFieldAttribute('tariff', 'label', '', 'shipping')); ?>
		</div>
		<div>
			<?php echo $form->getInput('tariff', 'shipping'); ?>
		</div>
	</div>

	<?php if ($shipping->params->get('field_comment', 'hidden') !== 'hidden'): ?>
		<div class="mb-3"><?php echo $form->getInput('comment', 'shipping'); ?></div>
	<?php endif; ?>

	<div class="d-flex">
		<div class="me-2 fw-bold">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_COST') . ': '; ?>
		</div>
		<div radicalmart-checkout-display="shipping.order.price.final_string">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_COST_CALCULATE'); ?>
		</div>
	</div>
</div>