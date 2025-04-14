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
 * {point.title}
 * {point.display}
 * {point.lat}
 * {point.lng}
 *
 */
?>
<div class="uk-margin-bottom">
	<a radicalmart-shipping-apiship-field-points="select" class="uk-display-block">
		<div>
			<strong>{point.providerTitle}</strong>
		</div>
		<div>
			{point.name}
		</div>
		<div class="uk-text-meta">{point.address}</div>
	</a>
	<div class="uk-margin-small-top">
		<a radicalmart-shipping-apiship-field-points="select"
		   class="uk-button uk-button-primary uk-button-small">
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_POINT_SELECT_BUTTON'); ?>
		</a>
	</div>
	<hr>
</div>
