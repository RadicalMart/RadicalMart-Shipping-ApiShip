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

use Joomla\CMS\Form\Form;

extract($displayData);

/**
 * Layout variables
 * -----------------
 *
 * @var  Form   $form      Form object.
 * @var  object $item      Personal object.
 * @var  object $shipping  Checkout shipping method object.
 * @var  array  $fieldsets Method fieldsets.
 * @var  string $group     Method fields group.
 *
 */

if (empty($shipping))
{
	return false;
}
?>
<div id="personal_shipping_method_<?php echo $shipping->id; ?>">
	<?php echo $form->renderField('addresses', $group, null, ['hiddenLabel' => true]); ?>
	<?php echo $form->renderField('point', $group, null, ['hiddenLabel' => true]); ?>
</div>