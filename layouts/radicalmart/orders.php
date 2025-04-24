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

use Joomla\CMS\Language\Text;
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
?>
<div>
	<span><?php echo $item->shipping->get('title'); ?></span>
	<?php if (!empty($price['final'])): ?>
		<span class="text-nowrap"><?php echo ' (' . $price['final_string'] . ')'; ?></span>
	<?php endif; ?>
	<a data-bs-toggle="collapse" href="#<?php echo $collapseId; ?>" class="link-secondary">
		<span class="icon-info-circle"></span>
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
</div>