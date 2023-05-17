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

\defined('_JEXEC') or die;

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
	return false;
}

?>
<div radicalmart-shipping-apiship="order">
	<div class="uk-margin">
		<div class="uk-margin-small-bottom">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_SENDER_LABEL'); ?>
		</div>
		<div><?php echo $form->getInput('sender', 'shipping'); ?></div>
	</div>
	<div class="uk-margin">
		<div class="uk-margin-small-bottom">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_DELIVERY_TYPE_LABEL'); ?>
		</div>
		<div><?php echo $form->getInput('delivery_type', 'shipping'); ?></div>
	</div>
	<div class="uk-margin">
		<div class="uk-margin-small-bottom">
			<?php echo Text::_(($form->getValue('delivery_type', 'shipping', 1) == 1)
				? 'PLG_RADICALMART_SHIPPING_APISHIP_RECIPIENT_LABEL'
				: 'PLG_RADICALMART_SHIPPING_APISHIP_PVZ_LABEL'); ?>
		</div>
		<div>
			<?php
				echo $form->getInput('recipient', 'shipping');
				echo $form->getInput('pvz', 'shipping');
			?>
		</div>
	</div>
	<div class="uk-margin">
		<?php
		echo $form->renderField('base', 'shipping.price');
		echo $form->renderField('final', 'shipping.price');
		echo $form->renderField('hash', 'shipping.price');
		?>
		<div class="uk-flex uk-flex-middle">
			<div class="uk-margin-small-right uk-text-bold">
				<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_COST') . ': '; ?>
			</div>
			<div radicalmart-checkout-display="shipping.order.price.final_string"></div>
		</div>
	</div>
</div>
