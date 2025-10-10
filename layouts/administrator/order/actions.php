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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   int    $order_id Order id.
 * @var   array  $buttons  Order id.
 * @var   string $context  Context selector string.
 */

if (empty($order_id))
{
	return;
}
if (!isset($buttons))
{
	$buttons = ['create', 'update_status', 'cancel'];
}

if (count($buttons) === 0)
{
	return;
}

$classes = [
	'default'       => 'btn btn-sm btn-outline-secondary',
	'create'        => 'btn btn-sm btn-outline-success',
	'update_status' => 'btn btn-sm btn-outline-primary',
	'cancel'        => 'btn btn-sm btn-outline-danger',
];

if (empty($context))
{
	$context = 'plg_radicalmart_shipping.apiship.actions';
}

/** @var \Joomla\CMS\WebAsset\WebAssetManager $assets */
$assets = Factory::getApplication()->getDocument()->getWebAssetManager();
$assets->getRegistry()
	->addExtensionRegistryFile('plg_radicalmart_shipping_apiship');
$assets->useScript('plg_radicalmart_shipping_apiship.administrator.order.actions');
?>
<div>
	<?php foreach ($buttons as $action):
		$class = (isset($classes[$action])) ? $classes[$action] : $classes['default'];
		?>
		<button type="button" class="<?php echo $class; ?> mb-1"
				radicalmart-shipping-apiship-order-actions="<?php echo $action; ?>"
				data-order_id="<?php echo $order_id; ?>"
				data-context="<?php echo $context; ?>">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_API_ORDER_ACTIONS_' . $action); ?>
		</button>
	<?php endforeach; ?>
</div>