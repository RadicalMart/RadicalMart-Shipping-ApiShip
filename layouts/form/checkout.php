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

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  Form    $form     Form object.
 * @var  object  $item     Checkout object.
 * @var  object  $shipping Checkout shipping method object.
 * @var  boolean $new      Button target.
 *
 */

?>
<div class="uk-grid-small" uk-grid="" radicalmart-shipping-apiship="order" data-id="<?php echo $shipping->id; ?>"
	 data-form="checkout">
	<div class="uk-width-1-1">
		<?php echo $form->getInput('recipient', 'shipping');?>
	</div>
	<div class="uk-width-1-2@s">
		<?php echo $form->renderField('sender', 'shipping'); ?>
	</div>
	<div class="uk-width-1-2@s  uk-form-horizontal">
		<?php echo $form->renderField('delivery_type', 'shipping'); ?>
	</div>
	<div class="uk-width-1-2@s">
		<?php
		echo $form->renderField('base', 'shipping.price');
		echo $form->renderField('final', 'shipping.price');
		echo $form->renderField('hash', 'shipping.price');
		?>
		<div>
			<div class="uk-form-label">
				<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_COST'); ?>
			</div>
			<div class="uk-form-controls uk-form-controls-text"
				 radicalmart-checkout-display="shipping.order.price.final_string">
			</div>
		</div>
	</div>
</div>
