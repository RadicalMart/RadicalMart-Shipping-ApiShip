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

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string $field_id   Field id.
 * @var   string $field_name Field name.
 * @var   string $provider   Provider key.
 * @var   int    $value      Field value.
 * @var   array  $tariffs    Autocomplete attribute for the field.
 * @var   string $currency   Currency code.
 */

$providerTitle        = Text::_('PLG_RADICALMART_SHIPPING_APISHIP_PROVIDER_' . $provider)

?>
<?php foreach ($tariffs as $tariff) :
	$tariff_fieldId = $field_id . '_' . $tariff->tariffId;
	$tariff_fieldName = $field_name . '[tariffs_select]';
	?>
	<div>
		<label for="<?php echo $tariff_fieldId; ?>">
			<input id="<?php echo $tariff_fieldId; ?>"
				   name="<?php echo $tariff_fieldName; ?>"
				   class="uk-radio" type="radio" value="<?php echo $tariff->tariffId; ?>"
				<?php if ($tariff->tariffId === $value) echo 'checked'; ?>
				   radicalmart-shipping-apiship-field-tariffs="input_tariff"
				   data-tariff_name="<?php echo $tariff->tariffName; ?>"
			>
			<?php echo $providerTitle . ' - ' . $tariff->tariffName
				. '  (~' . PriceHelper::toString($tariff->deliveryCost, $currency) . ')'; ?>
		</label>
	</div>
<?php endforeach; ?>