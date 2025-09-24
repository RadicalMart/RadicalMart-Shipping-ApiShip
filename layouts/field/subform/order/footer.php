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

use Joomla\CMS\Language\Text;

?>
<div class="d-flex">
	<div radicalmart-shipping-apiship-order="actions" style="display: none">
		<button type="button" class="btn btn-success"
				onclick="window.RadicalMartShippingApiShipAdministratorOrder().setValues(false);">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_ORDER_BUTTON_APPLY'); ?>
		</button>
	</div>
	<div>
		<button type="button" class="btn btn-danger" data-bs-dismiss="modal">
			<?php echo Text::_('JClOSE'); ?>
		</button>
	</div>
</div>