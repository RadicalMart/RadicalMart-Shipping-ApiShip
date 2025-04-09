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

/**
 * Template variables
 * -----------------
 *
 * {point.providerTitle}
 * {point.address}
 * {point.phone}
 * {point.timetable}
 *
 */
?>
<div class="uk-margin-bottom">
	<div>
		<a radicalmart-shipping-apiship-field-points="select">
			<strong>{point.providerTitle} - {point.name}</strong>
		</a>
	</div>
	<div>{point.address}</div>
	<div>
		<small>{point.phone}</small>
	</div>
	<div>
		<small>{point.timetable}</small>
	</div>
	<div class="uk-margin-small-top">
		<a radicalmart-shipping-apiship-field-points="select"
		   class="uk-button uk-button-primary">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_POINT_SELECT_BUTTON'); ?>
		</a>
	</div>
	<hr>
</div>
