<?php
/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     1.0.2
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2026 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\ParamsHelper;
use Joomla\Component\RadicalMart\Administrator\Helper\PriceHelper;
use Joomla\Registry\Registry;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  string $context Context selector string.
 * @var  object $item    Order object.
 *
 */

/** @var Registry $shipping */

$shipping = $item->shipping;
$price    = PriceHelper::prepareProductPrice($context, (new Registry($shipping->get('price')))->toArray(),
	$item->currency['code']);

$collapseId = 'order_' . $item->id . '_shipping';

$data = new Registry($shipping->get('data', []));

$delivery_type = 0;
$provider      = null;
$address       = null;
if (!empty($data->get('address')))
{
	$recipient     = $data->get('address');
	$delivery_type = 1;
	$providerKey   = (!empty($recipient->provider)) ? $recipient->provider : null;
	$address       = (!empty($recipient->string)) ? $recipient->string : null;

}
if (!empty($data->get('point')))
{
	$recipient     = $data->get('point');
	$delivery_type = 2;
	$providerKey   = (!empty($recipient->providerKey)) ? $recipient->providerKey : null;
	$address       = (!empty($recipient->address)) ? $recipient->address : null;
}

$params         = ParamsHelper::getShippingMethodsParams($shipping->get('id'));
$orders_enabled = ((int) $params->get('api_orders_enabled', 0) === 1);
?>
<?php if (!empty($data->get('api_order.status'))): ?>
	<div class="small text-nowrap">
		<?php echo $data->get('api_order.status'); ?>
	</div>
<?php endif; ?>
<div>
	<span><?php echo $item->shipping->get('title'); ?></span>
	<?php if (!empty($price['final'])): ?>
		<span class="text-nowrap"><?php echo ' (' . $price['final_string'] . ')'; ?></span>
	<?php endif; ?>
	<a data-bs-toggle="collapse" href="#<?php echo $collapseId; ?>" class="link-secondary">
		<span class="icon-fa fa-caret-square-down"></span>
	</a>
</div>
<div id="<?php echo $collapseId; ?>" class="collapse small">
	<div>
		<?php if (!empty($providerKey)): ?>
			<span class="fw-bold">
				<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' . $providerKey); ?>
			</span>
		<?php endif; ?>
		<?php if (!empty($delivery_type)): ?>
			<span>
				<?php echo ' - ' . Text::_('PLG_RADICALMART_SHIPPING_APISHIP_DELIVERY_TYPE_' . $delivery_type); ?>
			</span>
		<?php endif; ?>
	</div>
	<?php if (!empty($address)): ?>
		<div class="text-muted">
			<?php echo $address; ?>
		</div>
	<?php endif; ?>
	<?php if ($orders_enabled): ?>
		<div class="text-nowrap">
			<?php echo LayoutHelper::render('plugins.radicalmart_shipping.apiship.administrator.order.actions', [
				'order_id' => $item->id,
				'context'  => 'com_radicalmart.orders'
			]); ?>
		</div>
	<?php endif; ?>
</div>