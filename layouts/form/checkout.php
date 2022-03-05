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

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

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

HTMLHelper::script('plg_radicalmart_shipping_apiship/order.min.js', array('version' => 'auto', 'relative' => true));

Factory::getDocument()->addScriptOptions('radicalmart_shipping_apiship_order', array(
	'shipping_id' => $shipping->id,
	'controller'  => Route::_('index.php?option=com_ajax&plugin=apiship&group=radicalmart_shipping&format=json', false),
	'formType'    => 'checkout',
));
?>
<div class="uk-grid-small" uk-grid="" radicalmart-shipping-apiship="order">
	<div radicalmart-shipping-apiship="error" class="uk-alert uk-alert-danger uk-width-1-1" style="display: none">
	</div>
	<div class="uk-width-1-1">
		<?php
		echo $form->getInput('recipient', 'shipping');
		echo $form->getInput('pvz', 'shipping');
		?>
	</div>
	<div class="uk-width-1-2@s">
		<?php echo $form->renderField('sender', 'shipping'); ?>
	</div>
	<div class="uk-width-1-2@s  uk-form-horizontal">
		<?php echo $form->renderField('delivery_type', 'shipping'); ?>
	</div>
	<div class="uk-width-1-1">
		<?php
		echo $form->renderField('base', 'shipping.price');
		echo $form->renderField('final', 'shipping.price');
		echo $form->renderField('hash', 'shipping.price');
		?>
		<div class="uk-flex uk-flex-middle">
			<div class="uk-margin-small-right uk-text-bold">
				<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_COST') . ': '; ?>
			</div>
			<div class="" radicalmart-checkout-display="shipping.order.price.final_string"></div>
		</div>
	</div>
</div>
