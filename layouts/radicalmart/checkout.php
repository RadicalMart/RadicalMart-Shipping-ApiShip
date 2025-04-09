<?php
/*
 * @package    RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2025 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\Plugin\RadicalMartShipping\ApiShip\Extension\ApiShip;
use Joomla\Registry\Registry;

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

$defaultFieldsParams = ApiShip::$defaultAddressFieldsParams;

if (empty($shipping))
{
	return;
}

$delivery_type = (int) $shipping->params->get('delivery_type', 2);


// Load assets
$app      = Factory::getApplication();
$document = $app->getDocument();

/** @var \Joomla\CMS\WebAsset\WebAssetManager $assets */
$assets = $document->getWebAssetManager();
$assets->getRegistry()->addExtensionRegistryFile('plg_radicalmart_shipping_apiship');
$assets->useScript('plg_radicalmart_shipping_apiship.site.checkout');

?>
<div radicalmart-shipping-apiship="checkout">
	<div radicalmart-checkout-display="shipping.error" class="uk-alert uk-alert-danger" style="display: none"></div>
	<div radicalmart-checkout-display="shipping.message" class="uk-alert uk-alert-primary" style="display: none"></div>
	<?php if ($delivery_type === 2): ?>
		<div class="uk-margin">
			<div class="uk-margin-small-bottom uk-h4">
				<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_CHECKOUT_POINT'); ?>
			</div>
			<div>
				<?php echo $form->getInput('point', 'shipping'); ?>
			</div>
		</div>
	<?php endif; ?>

	<div radicalmart-checkout-shipping-apiship="tariff" style="">
		<?php echo $form->getInput('tariff', 'shipping'); ?>
	</div>

	<?php if ($shipping->params->get('field_comment', $defaultFieldsParams['comment']) !== 'hidden'): ?>
		<div class="uk-margin"><?php echo $form->getInput('comment', 'shipping'); ?></div>
	<?php endif; ?>

	<div class="uk-flex uk-flex-middle uk-margin-small">
		<div class="uk-margin-small-right uk-text-bold">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_COST') . ': '; ?>
		</div>
		<div radicalmart-checkout-display="shipping.order.price.final_string">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_COST_CALCULATE'); ?>
		</div>
	</div>
</div>