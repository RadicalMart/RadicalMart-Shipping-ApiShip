<?php
/*
 * @package     RadicalMart Shipping ApiShip Plugin
 * @subpackage  plg_radicalmart_shipping_apiship
 * @version     1.0.0
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2025 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string                   $autocomplete   Autocomplete attribute for the field.
 * @var   bool                     $autofocus      Is autofocus enabled?
 * @var   string                   $class          Classes for the input.
 * @var   string                   $description    Description of the field.
 * @var   bool                     $disabled       Is this field disabled?
 * @var   string                   $group          Group the field belongs to. <fields> section in form XML.
 * @var   bool                     $hidden         Is this field hidden in the form?
 * @var   string                   $hint           Placeholder for the field.
 * @var   string                   $id             DOM id of the field.
 * @var   string                   $label          Label of the field.
 * @var   string                   $labelclass     Classes to apply to the label.
 * @var   bool                     $multiple       Does this field support multiple values?
 * @var   string                   $name           Name of the input field.
 * @var   string                   $onchange       Onchange attribute for the field.
 * @var   string                   $onclick        Onclick attribute for the field.
 * @var   string                   $pattern        Pattern (Reg Ex) of value of the form field.
 * @var   bool                     $readonly       Is this field read only?
 * @var   bool                     $repeat         Allows extensions to duplicate elements.
 * @var   bool                     $required       Is this field required?
 * @var   int                      $size           Size attribute of the input.
 * @var   bool                     $spellcheck     Spellcheck state for the form field.
 * @var   string                   $validate       Validation rules to apply.
 * @var   array                    $value          Value attribute of the field.
 * @var   array                    $checkedOptions Options that will be set as checked.
 * @var   bool                     $hasValue       Has this field a value assigned?
 * @var   array                    $options        Options available for this field.
 *
 * Field specific variables
 * @var  int                       $shipping       Shipping method id.
 * @var  \Joomla\Registry\Registry $shippingParams Shipping method  params.
 * @var  array                     $files          Shipping points cache files.
 */

// Load assets
/** @var \Joomla\CMS\Document\Document $document */
$document = Factory::getApplication()->getDocument();
$assets = $document->getWebAssetManager();
$assets->getRegistry()
	->addExtensionRegistryFile('plg_radicalmart_shipping_apiship');
$assets->useScript('plg_radicalmart_shipping_apiship.fields.points.files');

?>

<div id="<?php echo $id; ?>" radicalmart-shipping-apiship-field-points-files="container"
	 data-shipping="<?php echo $shipping; ?>" class="position-relative">
	<div radicalmart-shipping-apiship-field-points-files="error" class="alert alert-danger"
		 style="display: none">
	</div>
	<div radicalmart-shipping-apiship-field-points-files="loading" style="display: none">
		<div class="position-absolute top-0 bottom-0 start-0 end-0 bg-light bg-opacity-75 d-flex justify-content-center align-items-center"
			 style="z-index: 9999">
			<div class="spinner-border text-info" role="status"></div>
		</div>
	</div>
	<div>
		<button type="button" class="btn btn-warning" radicalmart-shipping-apiship-field-points-files="reset">
			<span class="icon-bolt"></span>
			<?php echo Text::_('PLG_RADICALMART_SHIPPING_APISHIP_POINTS_FILES_RESET'); ?>
		</button>
	</div>
	<div class="mt-1" radicalmart-shipping-apiship-field-points-files="result">
		<?php if (!empty($files))
		{
			$count = count($files);
			$last  = end($files);
			$date  = HTMLHelper::date($last['time'], Text::_('DATE_FORMAT_LC5'));
			echo Text::sprintf('PLG_RADICALMART_SHIPPING_APISHIP_POINTS_FILES_RESET_RESULT',
				$count, $last['end'], $date);
		} ?>
	</div>
</div>